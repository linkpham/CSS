#!/usr/bin/env python3
"""
Import CSI data from Zeus Core MySQL database into SQLite for the CSI Dashboard.

This script queries LIVE data from the zeus_core MySQL database for SPEAKWELL courses,
calculates health scores per student, and stores results in the CSI SQLite database.

The Excel file (Chăm sóc CSI.xlsx) is only used for the CSS staff mapping sheet
("Danh sách CSS"). All session data comes from the zeus_core database.

Usage:
  python3 scripts/import_csi.py                                # Current month
  python3 scripts/import_csi.py 2026-03-01 2026-03-31          # Custom date range
  python3 scripts/import_csi.py --css-excel /path/to/file.xlsx # With CSS mapping from Excel
"""

import sys
import os
import sqlite3
import argparse
from datetime import datetime, timedelta
from collections import defaultdict

# Try to import pymysql
try:
    import pymysql
except ImportError:
    print("Installing pymysql...")
    os.system(f"{sys.executable} -m pip install pymysql")
    import pymysql

# Try to import pandas for CSS Excel reading
try:
    import pandas as pd
    HAS_PANDAS = True
except ImportError:
    HAS_PANDAS = False

# Paths
DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'src', 'database', 'csi.sqlite')
DB_PATH = os.path.abspath(DB_PATH)

ENV_PATH = os.path.join(os.path.dirname(__file__), '..', 'src', '.env')
ENV_PATH = os.path.abspath(ENV_PATH)

CSS_EXCEL_PATH = '/Users/que/Downloads/zeus/Chăm sóc CSI.xlsx'

# SPEAKWELL subject IDs (must match DashboardService::SPEAKWELL_SUBJECT_IDS)
SPEAKWELL_SUBJECT_IDS = [
    533, 558, 560, 562, 580, 581, 564, 567, 568, 569,
    416, 415, 414, 413, 571, 572, 574, 575, 576, 389,
    390, 392, 405, 406, 407, 411, 412, 577, 586, 585,
    584, 582, 404, 403, 583, 471,
]

# Acceptance code → student behaviour
# Based on OrderLessonExtra::ACCEPTANCE_CODES (3×4 grid: teacher row × student column)
# Student No-show: codes where student = "No show" → column 1: codes 1, 4, 7, 10
# Student < 1/2:   codes where student = "Không đủ 1/2" → column 2: codes 2, 5, 8, 11
# Student Normal:  codes where student = "Bình thường" → column 3: codes 3, 6, 9, 12
STUDENT_NOSHOW_CODES = [1, 4, 7, 10]
STUDENT_HALF_CODES = [2, 5, 8, 11]
STUDENT_NORMAL_CODES = [3, 6, 9, 12]

# Acceptance code → teacher behaviour
# Teacher No-show:  codes 1, 2, 3
# Teacher < 1/2:    codes 4, 5, 6
# Teacher 1/2-2/3:  codes 7, 8, 9
# Teacher >= 2/3:   codes 10, 11, 12
TEACHER_NOSHOW_CODES = [1, 2, 3]

# Full success = Teacher >= 2/3 + Student Normal
SUCCESS_CODE = 12

# Cancelled session codes
STUDENT_LEAVE_CODES = [15, 16]   # HV xin nghỉ
TEACHER_LEAVE_CODES = [13, 14]   # GV xin nghỉ

# Billing: Chargeable codes (student is charged)
# Matches DashboardService::CHARGEABLE_CODES
CHARGEABLE_CODES = [4, 5, 6, 7, 8, 9, 10, 11, 12, 16, 17]


def read_env(env_path):
    """Read .env file and return dict of key-value pairs"""
    env = {}
    if not os.path.exists(env_path):
        return env
    with open(env_path, 'r') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, _, value = line.partition('=')
            value = value.strip().strip('"').strip("'")
            env[key.strip()] = value
    return env


def get_mysql_connection(env):
    """Create MySQL connection from .env settings"""
    host = env.get('ZEUS_DB_HOST', '127.0.0.1')
    # When running outside Docker, host.docker.internal won't resolve → use 127.0.0.1
    if host == 'host.docker.internal':
        host = '127.0.0.1'
    return pymysql.connect(
        host=host,
        port=int(env.get('ZEUS_DB_PORT', 3306)),
        user=env.get('ZEUS_DB_USERNAME', 'forge'),
        password=env.get('ZEUS_DB_PASSWORD', ''),
        database=env.get('ZEUS_DB_DATABASE', 'zeus_core'),
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor,
        connect_timeout=30,
        read_timeout=120,
    )


def create_tables(conn):
    """Create SQLite tables (same schema as original import)"""
    c = conn.cursor()
    c.execute('DROP TABLE IF EXISTS csi_calculation')
    c.execute('DROP TABLE IF EXISTS csi_raw_data')
    c.execute('DROP TABLE IF EXISTS csi_ews')
    c.execute('DROP TABLE IF EXISTS csi_css_list')
    c.execute('DROP TABLE IF EXISTS csi_meta')

    c.execute('''CREATE TABLE csi_calculation (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id TEXT,
        student_name TEXT,
        phone TEXT,
        email TEXT,
        total_scheduled INTEGER DEFAULT 0,
        total_success INTEGER DEFAULT 0,
        student_noshow INTEGER DEFAULT 0,
        student_half INTEGER DEFAULT 0,
        student_leave INTEGER DEFAULT 0,
        health_score REAL DEFAULT 100,
        health_category TEXT,
        css_staff TEXT,
        teacher_noshow INTEGER DEFAULT 0,
        teacher_warning TEXT,
        success_rate REAL DEFAULT 0,
        weeks_studied REAL DEFAULT 0,
        avg_lessons_per_week REAL DEFAULT 0,
        total_cancel INTEGER DEFAULT 0,
        cancel_rate REAL DEFAULT 0,
        gap REAL DEFAULT 0
    )''')

    c.execute('''CREATE TABLE csi_raw_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id TEXT,
        student_name TEXT,
        phone TEXT,
        email TEXT,
        start_time TEXT,
        end_time TEXT,
        acceptance_code TEXT,
        session_status TEXT,
        billing TEXT,
        consecutive_half INTEGER DEFAULT 0,
        consecutive_noshow INTEGER DEFAULT 0,
        week_num INTEGER,
        month_num INTEGER,
        csi_score REAL DEFAULT 0,
        student_noshow_count INTEGER DEFAULT 0,
        student_half_count INTEGER DEFAULT 0,
        teacher_noshow_count INTEGER DEFAULT 0,
        success_count INTEGER DEFAULT 0
    )''')

    c.execute('''CREATE TABLE csi_ews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id TEXT,
        student_name TEXT,
        phone TEXT,
        email TEXT,
        total_missed INTEGER DEFAULT 0,
        css_staff TEXT
    )''')

    c.execute('''CREATE TABLE csi_css_list (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        record_id TEXT,
        student_id TEXT,
        phone TEXT,
        username TEXT,
        student_name TEXT,
        email TEXT,
        course TEXT,
        css_staff TEXT
    )''')

    c.execute('''CREATE TABLE csi_meta (
        key TEXT PRIMARY KEY,
        value TEXT
    )''')

    c.execute('CREATE INDEX idx_calc_student_id ON csi_calculation(student_id)')
    c.execute('CREATE INDEX idx_calc_health ON csi_calculation(health_category)')
    c.execute('CREATE INDEX idx_calc_css ON csi_calculation(css_staff)')
    c.execute('CREATE INDEX idx_calc_teacher_warning ON csi_calculation(teacher_warning)')
    c.execute('CREATE INDEX idx_raw_student_id ON csi_raw_data(student_id)')
    c.execute('CREATE INDEX idx_ews_student_id ON csi_ews(student_id)')
    conn.commit()


def normalize_css_name(name):
    """Normalize CSS staff name to consistent casing"""
    if not name or str(name).strip() in ('', 'nan', 'None'):
        return None
    mapping = {
        'anhptl': 'Anhptl',
        'giangpt4': 'GiangPT4',
        'hoannt3': 'Hoannt3',
        'huyenhk': 'Huyenhk',
        'nganpt2': 'NganPT2',
        'thaott8': 'Thaott8',
        'trangnt22': 'Trangnt22',
        'yenlt5': 'Yenlt5',
    }
    return mapping.get(str(name).strip().lower(), str(name).strip())


def fetch_raw_sessions(mysql_conn, date_from, date_to):
    """
    Fetch raw session data from zeus_core MySQL.
    
    Queries tbl_order_lessons + tbl_order_lessons_extras + tbl_orders + tbl_users
    for SPEAKWELL courses (ordles_tlang_id IN SPEAKWELL_SUBJECT_IDS) with:
    - Completed sessions (ordles_status = 3) to compute health metrics
    - Cancelled sessions (ordles_status = 4) for cancel rate
    - Paid orders only (order_status = 2, order_payment_status = 1)
    - Timestamps converted from UTC to Vietnam time (+07:00)
    """
    subject_ids = ','.join(str(x) for x in SPEAKWELL_SUBJECT_IDS)

    sql = f"""
    SELECT
        u.user_id AS student_id,
        CONCAT(COALESCE(u.user_first_name, ''), ' ', COALESCE(u.user_last_name, '')) AS student_name,
        COALESCE(
            NULLIF(CONCAT(c.country_dial_code, ' ', us.user_phone_number), ' '),
            ''
        ) AS phone,
        u.user_email AS email,
        CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') AS start_time,
        CONVERT_TZ(ol.ordles_lesson_endtime, '+00:00', '+07:00') AS end_time,
        COALESCE(ole.ole_acceptance_code, 0) AS acceptance_code,
        ol.ordles_status AS session_status,
        ol.ordles_id
    FROM tbl_order_lessons ol
    JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
    JOIN tbl_users u ON o.order_user_id = u.user_id
    LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
    LEFT JOIN tbl_countries c ON us.user_phone_code = c.country_id
    LEFT JOIN tbl_order_lessons_extras ole ON ol.ordles_id = ole.ole_ordles_id
    WHERE ol.ordles_tlang_id IN ({subject_ids})
      AND ol.ordles_status IN (3, 4)
      AND o.order_status = 2
      AND o.order_payment_status = 1
      AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') >= %s
      AND CONVERT_TZ(ol.ordles_lesson_starttime, '+00:00', '+07:00') < %s
    ORDER BY u.user_id, start_time
    """

    with mysql_conn.cursor() as cursor:
        cursor.execute(sql, (date_from, date_to))
        return cursor.fetchall()


def fetch_no_class_students(mysql_conn, date_from, date_to):
    """
    Fetch students who have active SPEAKWELL orders (with unscheduled/scheduled lessons)
    but NO completed or cancelled sessions in the period → "Chưa có lớp".
    """
    subject_ids = ','.join(str(x) for x in SPEAKWELL_SUBJECT_IDS)

    sql = f"""
    SELECT DISTINCT
        u.user_id AS student_id,
        CONCAT(COALESCE(u.user_first_name, ''), ' ', COALESCE(u.user_last_name, '')) AS student_name,
        COALESCE(
            NULLIF(CONCAT(c.country_dial_code, ' ', us.user_phone_number), ' '),
            ''
        ) AS phone,
        u.user_email AS email
    FROM tbl_order_lessons ol
    JOIN tbl_orders o ON ol.ordles_order_id = o.order_id
    JOIN tbl_users u ON o.order_user_id = u.user_id
    LEFT JOIN tbl_user_settings us ON u.user_id = us.user_id
    LEFT JOIN tbl_countries c ON us.user_phone_code = c.country_id
    WHERE ol.ordles_tlang_id IN ({subject_ids})
      AND ol.ordles_status IN (1, 2)
      AND o.order_status = 2
      AND o.order_payment_status = 1
      AND u.user_deleted IS NULL
      AND u.user_id NOT IN (
          SELECT DISTINCT o2.order_user_id
          FROM tbl_order_lessons ol2
          JOIN tbl_orders o2 ON ol2.ordles_order_id = o2.order_id
          WHERE ol2.ordles_tlang_id IN ({subject_ids})
            AND ol2.ordles_status IN (3, 4)
            AND o2.order_status = 2
            AND o2.order_payment_status = 1
            AND CONVERT_TZ(ol2.ordles_lesson_starttime, '+00:00', '+07:00') >= %s
            AND CONVERT_TZ(ol2.ordles_lesson_starttime, '+00:00', '+07:00') < %s
      )
    """

    with mysql_conn.cursor() as cursor:
        cursor.execute(sql, (date_from, date_to))
        return cursor.fetchall()


def get_billing_text(acceptance_code, session_status):
    """Determine billing status text based on acceptance code"""
    if session_status == 4:  # Cancelled
        return 'Không tính phí'
    if acceptance_code in CHARGEABLE_CODES:
        return 'Tính phí'
    return 'Không tính phí'


def get_session_status_text(status):
    """Convert numeric session status to text label"""
    return {
        1: 'UNSCHEDULED',
        2: 'SCHEDULED',
        3: 'COMPLETED',
        4: 'CANCELLED',
    }.get(status, 'UNKNOWN')


def calculate_health_score(noshow, half):
    """
    Calculate health score (0-100).
    Rules: 100 - (noshow × 10) - (half × 5)
    """
    score = 100 - (noshow * 10) - (half * 5)
    return max(0, min(100, score))


def get_health_category(score, total_scheduled):
    """
    Get health category based on score.
    - 85-100: Xanh (Khỏe mạnh)
    - 60-84:  Vàng (Cảnh báo)
    - < 60:   Đỏ (Báo động)
    - 0 sessions: Chưa có lớp
    """
    if total_scheduled == 0:
        return 'Chưa có lớp'
    if score >= 85:
        return 'Xanh (Khỏe mạnh)'
    if score >= 60:
        return 'Vàng (Cảnh báo)'
    return 'Đỏ (Báo động)'


def get_teacher_warning(teacher_noshow):
    """
    Get teacher warning level based on number of teacher no-show sessions.
    """
    if teacher_noshow >= 4:
        return 'Khẩn cấp (GV nghỉ >= 4 buổi)'
    if teacher_noshow >= 2:
        return 'Nghiêm trọng (GV nghỉ >=2b)'
    if teacher_noshow == 1:
        return 'Có ảnh hưởng (GV nghỉ 1b)'
    return 'Bình thường'


def load_css_mapping(excel_path):
    """Load CSS staff mapping from Excel's 'Danh sách CSS' sheet"""
    if not HAS_PANDAS or not os.path.exists(excel_path):
        print(f'  CSS Excel not found or pandas not available: {excel_path}')
        return {}

    try:
        df = pd.read_excel(excel_path, sheet_name='Danh sách CSS', header=None, skiprows=1)
        cols = ['record_id', 'student_id', 'phone', 'username', 'student_name', 'email', 'course', 'col7', 'css_staff']
        df.columns = cols[:len(df.columns)]
        df = df.drop(columns=['col7'], errors='ignore')
        df = df.dropna(subset=['student_id'])
        df['student_id'] = df['student_id'].astype(int).astype(str)
        df['css_staff'] = df['css_staff'].apply(normalize_css_name)

        # Build mapping: student_id -> css_staff
        mapping = {}
        for _, row in df.iterrows():
            sid = str(row['student_id'])
            css = row.get('css_staff')
            if css:
                mapping[sid] = css
        print(f'  -> Loaded {len(mapping)} CSS staff mappings')
        return mapping
    except Exception as e:
        print(f'  Warning: Could not load CSS mapping: {e}')
        return {}


def import_css_list_from_excel(sqlite_conn, excel_path):
    """Import CSS list from Excel for reference table"""
    if not HAS_PANDAS or not os.path.exists(excel_path):
        return 0

    try:
        df = pd.read_excel(excel_path, sheet_name='Danh sách CSS', header=None, skiprows=1)
        cols = ['record_id', 'student_id', 'phone', 'username', 'student_name', 'email', 'course', 'col7', 'css_staff']
        df.columns = cols[:len(df.columns)]
        df = df.drop(columns=['col7'], errors='ignore')
        df = df.dropna(subset=['student_id'])
        df['student_id'] = pd.to_numeric(df['student_id'], errors='coerce').fillna(0).astype(int).astype(str)
        if 'record_id' in df.columns:
            df['record_id'] = pd.to_numeric(df['record_id'], errors='coerce').fillna(0).astype(int).astype(str)
        else:
            df['record_id'] = ''
        df['css_staff'] = df['css_staff'].apply(normalize_css_name)

        df.to_sql('csi_css_list', sqlite_conn, if_exists='append', index=False)
        return len(df)
    except Exception as e:
        print(f'  Warning: Could not import CSS list: {e}')
        return 0


def process_sessions(sessions, css_mapping):
    """
    Process raw session data from MySQL into:
    - csi_calculation records (per-student aggregated metrics)
    - csi_raw_data records (per-session detail)
    - csi_ews records (Early Warning System - consecutive missed sessions)
    """
    # Group sessions by student
    students = defaultdict(lambda: {
        'student_id': '',
        'student_name': '',
        'phone': '',
        'email': '',
        'sessions': [],
    })

    for session in sessions:
        sid = str(session['student_id'])
        students[sid]['student_id'] = sid
        students[sid]['student_name'] = (session['student_name'] or '').strip()
        students[sid]['phone'] = session['phone'] or ''
        students[sid]['email'] = session['email'] or ''
        students[sid]['sessions'].append(session)

    calculations = []
    raw_data_records = []
    ews_records = []

    for sid, student in students.items():
        all_sessions = student['sessions']
        completed = [s for s in all_sessions if s['session_status'] == 3]
        cancelled = [s for s in all_sessions if s['session_status'] == 4]

        # --- Count metrics from COMPLETED sessions (status=3) ---
        total_scheduled = len(completed)
        total_success = sum(1 for s in completed if s['acceptance_code'] == SUCCESS_CODE)
        student_noshow = sum(1 for s in completed if s['acceptance_code'] in STUDENT_NOSHOW_CODES)
        student_half = sum(1 for s in completed if s['acceptance_code'] in STUDENT_HALF_CODES)
        teacher_noshow = sum(1 for s in completed if s['acceptance_code'] in TEACHER_NOSHOW_CODES)

        # --- Cancelled sessions ---
        total_cancel = len(cancelled)
        student_leave = sum(1 for s in cancelled if s['acceptance_code'] in STUDENT_LEAVE_CODES)

        # --- Health score & category ---
        health_score = calculate_health_score(student_noshow, student_half)
        health_category = get_health_category(health_score, total_scheduled)
        teacher_warning = get_teacher_warning(teacher_noshow)

        # --- Rates ---
        success_rate = total_success / total_scheduled if total_scheduled > 0 else 0
        total_all = total_scheduled + total_cancel
        cancel_rate = total_cancel / total_all if total_all > 0 else 0

        # --- Weeks studied ---
        weeks = set()
        for s in completed:
            dt = s['start_time']
            if dt:
                if isinstance(dt, str):
                    dt = datetime.strptime(dt, '%Y-%m-%d %H:%M:%S')
                weeks.add(dt.isocalendar()[1])
        weeks_studied = len(weeks) if weeks else 0
        avg_lessons_per_week = total_scheduled / weeks_studied if weeks_studied > 0 else 0

        # --- Gap (sessions where student normal but not full success) ---
        gap = total_scheduled - total_success - student_noshow - student_half

        # --- CSS staff mapping ---
        css_staff = css_mapping.get(sid)

        calculations.append({
            'student_id': sid,
            'student_name': student['student_name'],
            'phone': student['phone'],
            'email': student['email'],
            'total_scheduled': total_scheduled,
            'total_success': total_success,
            'student_noshow': student_noshow,
            'student_half': student_half,
            'student_leave': student_leave,
            'health_score': health_score,
            'health_category': health_category,
            'css_staff': css_staff,
            'teacher_noshow': teacher_noshow,
            'teacher_warning': teacher_warning,
            'success_rate': success_rate,
            'weeks_studied': weeks_studied,
            'avg_lessons_per_week': avg_lessons_per_week,
            'total_cancel': total_cancel,
            'cancel_rate': cancel_rate,
            'gap': gap,
        })

        # --- Build raw data records (per-session) ---
        running_noshow = 0
        running_half = 0
        running_student_noshow = 0
        running_student_half = 0
        running_teacher_noshow = 0
        running_success = 0

        for s in sorted(all_sessions, key=lambda x: str(x['start_time'] or '')):
            code = s['acceptance_code'] or 0
            status = s['session_status']

            if status == 3:  # Completed
                if code in STUDENT_NOSHOW_CODES:
                    running_noshow += 1
                    running_student_noshow += 1
                else:
                    running_noshow = 0

                if code in STUDENT_HALF_CODES:
                    running_half += 1
                    running_student_half += 1
                else:
                    running_half = 0

                if code in TEACHER_NOSHOW_CODES:
                    running_teacher_noshow += 1

                if code == SUCCESS_CODE:
                    running_success += 1

            start_time = s['start_time']
            if isinstance(start_time, datetime):
                start_time = start_time.strftime('%Y-%m-%d %H:%M:%S')
            end_time = s['end_time']
            if isinstance(end_time, datetime):
                end_time = end_time.strftime('%Y-%m-%d %H:%M:%S')

            dt = s['start_time']
            if isinstance(dt, str):
                try:
                    dt = datetime.strptime(dt, '%Y-%m-%d %H:%M:%S')
                except ValueError:
                    dt = None
            week_num = dt.isocalendar()[1] if dt else 0
            month_num = dt.month if dt else 0

            current_score = calculate_health_score(running_student_noshow, running_student_half)

            raw_data_records.append({
                'student_id': sid,
                'student_name': student['student_name'],
                'phone': student['phone'],
                'email': student['email'],
                'start_time': str(start_time) if start_time else None,
                'end_time': str(end_time) if end_time else None,
                'acceptance_code': str(code),
                'session_status': get_session_status_text(status),
                'billing': get_billing_text(code, status),
                'consecutive_half': running_half if code in STUDENT_HALF_CODES else 0,
                'consecutive_noshow': running_noshow if code in STUDENT_NOSHOW_CODES else 0,
                'week_num': week_num,
                'month_num': month_num,
                'csi_score': current_score,
                'student_noshow_count': running_student_noshow,
                'student_half_count': running_student_half,
                'teacher_noshow_count': running_teacher_noshow,
                'success_count': running_success,
            })

        # --- EWS: consecutive missed sessions from most recent ---
        completed_sorted = sorted(completed, key=lambda x: str(x['start_time'] or ''), reverse=True)
        consecutive_missed = 0
        for s in completed_sorted:
            code = s['acceptance_code'] or 0
            if code in STUDENT_NOSHOW_CODES or code in STUDENT_HALF_CODES:
                consecutive_missed += 1
            else:
                break

        if consecutive_missed >= 2:
            ews_records.append({
                'student_id': sid,
                'student_name': student['student_name'],
                'phone': student['phone'],
                'email': student['email'],
                'total_missed': consecutive_missed,
                'css_staff': css_staff,
            })

    return calculations, raw_data_records, ews_records


def save_to_sqlite(sqlite_conn, calculations, raw_data_records, ews_records, no_class_students, css_mapping):
    """Save all processed data to SQLite database"""
    c = sqlite_conn.cursor()

    # Save calculations (students with sessions)
    for calc in calculations:
        c.execute('''INSERT INTO csi_calculation
            (student_id, student_name, phone, email, total_scheduled, total_success,
             student_noshow, student_half, student_leave, health_score, health_category,
             css_staff, teacher_noshow, teacher_warning, success_rate, weeks_studied,
             avg_lessons_per_week, total_cancel, cancel_rate, gap)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)''',
            (calc['student_id'], calc['student_name'], calc['phone'], calc['email'],
             calc['total_scheduled'], calc['total_success'], calc['student_noshow'],
             calc['student_half'], calc['student_leave'], calc['health_score'],
             calc['health_category'], calc['css_staff'], calc['teacher_noshow'],
             calc['teacher_warning'], calc['success_rate'], calc['weeks_studied'],
             calc['avg_lessons_per_week'], calc['total_cancel'], calc['cancel_rate'],
             calc['gap']))

    # Save "Chưa có lớp" students (active orders but no sessions in period)
    for student in no_class_students:
        sid = str(student['student_id'])
        css_staff = css_mapping.get(sid)
        c.execute('''INSERT INTO csi_calculation
            (student_id, student_name, phone, email, total_scheduled, total_success,
             student_noshow, student_half, student_leave, health_score, health_category,
             css_staff, teacher_noshow, teacher_warning, success_rate, weeks_studied,
             avg_lessons_per_week, total_cancel, cancel_rate, gap)
            VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 'Chưa có lớp', ?, 0, 'Bình thường', 0, 0, 0, 0, 0, 0)''',
            (sid, (student['student_name'] or '').strip(), student['phone'] or '',
             student['email'] or '', css_staff))

    # Save raw data records
    for raw in raw_data_records:
        c.execute('''INSERT INTO csi_raw_data
            (student_id, student_name, phone, email, start_time, end_time,
             acceptance_code, session_status, billing, consecutive_half,
             consecutive_noshow, week_num, month_num, csi_score,
             student_noshow_count, student_half_count, teacher_noshow_count, success_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)''',
            (raw['student_id'], raw['student_name'], raw['phone'], raw['email'],
             raw['start_time'], raw['end_time'], raw['acceptance_code'],
             raw['session_status'], raw['billing'], raw['consecutive_half'],
             raw['consecutive_noshow'], raw['week_num'], raw['month_num'],
             raw['csi_score'], raw['student_noshow_count'], raw['student_half_count'],
             raw['teacher_noshow_count'], raw['success_count']))

    # Save EWS records
    for ews in ews_records:
        c.execute('''INSERT INTO csi_ews
            (student_id, student_name, phone, email, total_missed, css_staff)
            VALUES (?, ?, ?, ?, ?, ?)''',
            (ews['student_id'], ews['student_name'], ews['phone'], ews['email'],
             ews['total_missed'], ews['css_staff']))

    sqlite_conn.commit()


def main():
    parser = argparse.ArgumentParser(description='Import CSI data from Zeus Core MySQL database')
    parser.add_argument('date_from', nargs='?',
                        help='Start date (YYYY-MM-DD). Default: first day of current month')
    parser.add_argument('date_to', nargs='?',
                        help='End date (YYYY-MM-DD, inclusive). Default: last day of current month')
    parser.add_argument('--css-excel', default=CSS_EXCEL_PATH,
                        help='Path to Excel file for CSS staff mapping (Danh sách CSS sheet)')
    parser.add_argument('--env', default=ENV_PATH,
                        help='Path to .env file with MySQL credentials')
    args = parser.parse_args()

    # Determine date range
    now = datetime.now()
    if args.date_from:
        date_from = args.date_from
    else:
        date_from = now.replace(day=1).strftime('%Y-%m-%d')

    if args.date_to:
        # Make end date exclusive (add 1 day)
        dt = datetime.strptime(args.date_to, '%Y-%m-%d')
        date_to_exclusive = (dt + timedelta(days=1)).strftime('%Y-%m-%d')
        display_to = args.date_to
    else:
        # End of current month + 1 day
        if now.month == 12:
            next_month = now.replace(year=now.year + 1, month=1, day=1)
        else:
            next_month = now.replace(month=now.month + 1, day=1)
        date_to_exclusive = next_month.strftime('%Y-%m-%d')
        display_to = (next_month - timedelta(days=1)).strftime('%Y-%m-%d')

    print(f'═══════════════════════════════════════════════════')
    print(f'  CSI Import from Zeus Core MySQL')
    print(f'═══════════════════════════════════════════════════')
    print(f'  Date range : {date_from} → {display_to}')
    print(f'  SQLite     : {DB_PATH}')
    print(f'  CSS Excel  : {args.css_excel}')
    print(f'  .env       : {args.env}')
    print()

    # Read .env
    env = read_env(args.env)
    if not env.get('ZEUS_DB_HOST'):
        print(f'ERROR: ZEUS_DB_HOST not found in {args.env}')
        print('Please configure the Zeus Core database connection in src/.env')
        sys.exit(1)

    db_host = env['ZEUS_DB_HOST']
    db_port = env.get('ZEUS_DB_PORT', '3306')
    db_name = env.get('ZEUS_DB_DATABASE', 'zeus_core')
    print(f'Connecting to MySQL: {db_host}:{db_port}/{db_name}')

    try:
        mysql_conn = get_mysql_connection(env)
        print('  -> Connected!')
    except Exception as e:
        print(f'ERROR: Could not connect to MySQL: {e}')
        sys.exit(1)

    # Load CSS staff mapping from Excel
    print('\nLoading CSS staff mapping from Excel...')
    css_mapping = load_css_mapping(args.css_excel)

    # Fetch raw sessions from MySQL
    print(f'\nFetching sessions from zeus_core ({date_from} → {display_to})...')
    sessions = fetch_raw_sessions(mysql_conn, date_from, date_to_exclusive)
    print(f'  -> Fetched {len(sessions)} sessions')

    # Fetch "no class" students
    print('\nFetching students with no sessions in period (Chưa có lớp)...')
    no_class_students = fetch_no_class_students(mysql_conn, date_from, date_to_exclusive)
    print(f'  -> Found {len(no_class_students)} students')

    mysql_conn.close()

    # Process data
    print('\nProcessing CSI calculations...')
    calculations, raw_data_records, ews_records = process_sessions(sessions, css_mapping)
    print(f'  -> {len(calculations)} students with sessions')
    print(f'  -> {len(raw_data_records)} raw data records')
    print(f'  -> {len(ews_records)} EWS alerts (≥2 consecutive missed)')

    # Save to SQLite
    print('\nSaving to SQLite...')
    os.makedirs(os.path.dirname(DB_PATH), exist_ok=True)
    sqlite_conn = sqlite3.connect(DB_PATH)
    create_tables(sqlite_conn)
    save_to_sqlite(sqlite_conn, calculations, raw_data_records, ews_records, no_class_students, css_mapping)

    # Import CSS list from Excel (for reference)
    print('\nImporting CSS list from Excel (reference)...')
    css_rows = import_css_list_from_excel(sqlite_conn, args.css_excel)
    print(f'  -> {css_rows} CSS records')

    # Save metadata
    c = sqlite_conn.cursor()
    imported_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('imported_at', ?)", (imported_at,))
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('source', ?)", ('zeus_core MySQL',))
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('date_from', ?)", (date_from,))
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('date_to', ?)", (display_to,))
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('calc_rows', ?)",
              (str(len(calculations) + len(no_class_students)),))
    c.execute("INSERT OR REPLACE INTO csi_meta (key, value) VALUES ('raw_rows', ?)", (str(len(raw_data_records)),))
    sqlite_conn.commit()
    sqlite_conn.close()

    # Summary
    total_calc = len(calculations) + len(no_class_students)
    green = sum(1 for c in calculations if c['health_category'] == 'Xanh (Khỏe mạnh)')
    yellow = sum(1 for c in calculations if c['health_category'] == 'Vàng (Cảnh báo)')
    red = sum(1 for c in calculations if c['health_category'] == 'Đỏ (Báo động)')

    print()
    print(f'═══════════════════════════════════════════════════')
    print(f'  Done! Database: {DB_PATH}')
    print(f'═══════════════════════════════════════════════════')
    print(f'  Calculation : {total_calc} rows')
    print(f'    With sessions : {len(calculations)}')
    print(f'      🟢 Xanh    : {green}')
    print(f'      🟡 Vàng    : {yellow}')
    print(f'      🔴 Đỏ      : {red}')
    print(f'    Chưa có lớp   : {len(no_class_students)}')
    print(f'  Raw data    : {len(raw_data_records)} rows')
    print(f'  EWS         : {len(ews_records)} rows')
    print(f'  CSS list    : {css_rows} rows')
    print()


if __name__ == '__main__':
    main()
