const { getZeusMysqlConfig } = require('./dbConnectionService');

const EASY_SPEAK_IDS = [403, 404, 471, 582, 583, 584, 585, 586];
const CSI_DASHBOARD_CACHE_TTL_MS = Number(process.env.CSI_DASHBOARD_CACHE_TTL_MS || 15000);
const csiDashboardCache = new Map();

function todayDateString() {
    return new Date().toISOString().slice(0, 10);
}

function normalizeStudentIdList(values) {
    if (!Array.isArray(values)) return null;
    const normalized = [...new Set(values
        .map(value => Number(value))
        .filter(Number.isFinite)
        .filter(value => value > 0))];
    return normalized;
}

function normalizeCssScopeList(values) {
    if (!Array.isArray(values)) return [];
    return [...new Set(values
        .map(value => String(value || '').trim())
        .filter(Boolean))];
}

function normalizeCsiFilters(filters = {}) {
    return {
        search: String(filters.search || '').trim(),
        css_staff: String(filters.css_staff || filters.css || '').trim(),
        css_scopes: normalizeCssScopeList(filters.css_scopes || filters.cssScopes || []),
        student_ids: normalizeStudentIdList(filters.student_ids || filters.studentIds),
        date_from: String(filters.date_from || filters.fromDate || '').trim() || '2025-11-04',
        date_to: String(filters.date_to || filters.toDate || '').trim() || todayDateString(),
        health_category: String(filters.health_category || '').trim(),
        teacher_warning: String(filters.teacher_warning || '').trim(),
        program: String(filters.program || '').trim(),
    };
}

function buildCsiWhereClause(filters) {
    const clauses = [];
    const params = [];

    if (filters.css_staff) {
        clauses.push('css_staff = ?');
        params.push(filters.css_staff);
    }

    if (filters.css_scopes?.length) {
        clauses.push(`css_staff IN (${filters.css_scopes.map(() => '?').join(', ')})`);
        params.push(...filters.css_scopes);
    }

    if (filters.student_ids) {
        if (!filters.student_ids.length) {
            clauses.push('1 = 0');
        } else {
            clauses.push(`student_id IN (${filters.student_ids.map(() => '?').join(', ')})`);
            params.push(...filters.student_ids);
        }
    }

    if (filters.search) {
        clauses.push(`(
            CAST(student_id AS CHAR) LIKE ?
            OR student_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )`);
        const keyword = `%${filters.search}%`;
        params.push(keyword, keyword, keyword, keyword);
    }

    if (filters.health_category) {
        clauses.push('health_category = ?');
        params.push(filters.health_category);
    }

    if (filters.teacher_warning) {
        clauses.push('teacher_warning = ?');
        params.push(filters.teacher_warning);
    }

    if (filters.program === 'SPEAKWELL') {
        clauses.push('has_speakwell = 1');
    } else if (filters.program === 'EASYSPEAK') {
        clauses.push('has_easyspeak = 1');
    }

    return {
        whereSql: clauses.length ? `WHERE ${clauses.join(' AND ')}` : '',
        params,
    };
}

function buildCsiBaseSql(finalSelect) {
    return `
        WITH joined AS (
            SELECT
                ol.ordles_id AS lesson_id,
                ol.ordles_beneficiary_id AS student_id,
                ol.ordles_tlang_id AS tlang_id,
                ol.ordles_lesson_starttime AS lesson_time,
                COALESCE((
                    SELECT ole.ole_acceptance_code
                    FROM tbl_order_lessons_extras ole
                    WHERE ole.ole_ordles_id = ol.ordles_id
                    ORDER BY ole.ole_id DESC
                    LIMIT 1
                ), NULL) AS acceptance_code,
                CONCAT(COALESCE(u.user_last_name, ''), ' ', COALESCE(u.user_first_name, '')) AS student_name,
                COALESCE(u.user_email, '') AS email,
                COALESCE(us.user_phone_number, '') AS phone,
                COALESCE(a.admin_username, '') AS css_staff
            FROM tbl_order_lessons ol
            JOIN tbl_users u ON u.user_id = ol.ordles_beneficiary_id
            LEFT JOIN tbl_user_settings us ON us.user_id = u.user_id
            LEFT JOIN tbl_user_extras ue ON ue.usrextra_user_id = u.user_id
            LEFT JOIN tbl_admin a ON a.admin_id = ue.usrextra_css_id
            WHERE ol.ordles_status = 3
              AND DATE(ol.ordles_lesson_starttime) >= ?
              AND DATE(ol.ordles_lesson_starttime) <= ?
              AND FIND_IN_SET(ol.ordles_tlang_id, (
                    SELECT REPLACE(conf_val, ' ', '')
                    FROM tbl_configurations
                    WHERE conf_name = 'CONF_SPEAKWELL_SUBJECT_IDS'
                    LIMIT 1
              ))
        ),
        first_3_ranked AS (
            SELECT
                student_id,
                lesson_time,
                acceptance_code,
                ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY lesson_time ASC) AS rn
            FROM joined
            WHERE tlang_id <> 533
        ),
        first_3_pivot AS (
            SELECT
                student_id,
                MAX(CASE WHEN rn = 1 THEN lesson_time END) AS lesson_1_date,
                MAX(CASE WHEN rn = 1 THEN acceptance_code END) AS lesson_1_code,
                MAX(CASE WHEN rn = 2 THEN lesson_time END) AS lesson_2_date,
                MAX(CASE WHEN rn = 2 THEN acceptance_code END) AS lesson_2_code,
                MAX(CASE WHEN rn = 3 THEN lesson_time END) AS lesson_3_date,
                MAX(CASE WHEN rn = 3 THEN acceptance_code END) AS lesson_3_code,
                SUM(CASE WHEN rn <= 3 AND acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) AS first_3_success,
                SUM(CASE WHEN rn <= 3 THEN 1 ELSE 0 END) AS first_3_total
            FROM first_3_ranked
            WHERE rn <= 3
            GROUP BY student_id
        ),
        leave_per_student AS (
            SELECT
                CAST(JSON_UNQUOTE(JSON_EXTRACT(lrs.tlrs_session_info, '$.learners[0].id')) AS UNSIGNED) AS learner_id,
                COUNT(*) AS leave_sessions
            FROM tbl_teacher_leave_requests lr
            JOIN tbl_teacher_leave_request_sessions lrs ON lr.tlr_id = lrs.tlrs_leave_request_id
            WHERE lr.tlr_status IN (2, 3)
              AND DATE(lrs.tlrs_session_date) >= ?
              AND DATE(lrs.tlrs_session_date) <= ?
            GROUP BY learner_id
            HAVING learner_id IS NOT NULL AND learner_id > 0
        ),
        csi_data AS (
            SELECT
                j.student_id,
                MAX(j.student_name) AS student_name,
                MAX(j.email) AS email,
                MAX(j.phone) AS phone,
                MAX(j.css_staff) AS css_staff,
                COUNT(*) AS total_scheduled,
                SUM(CASE WHEN j.acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) AS total_success,
                SUM(CASE WHEN j.acceptance_code IN (0, 4, 7, 10) OR j.acceptance_code IS NULL THEN 1 ELSE 0 END) AS student_noshow,
                SUM(CASE WHEN j.acceptance_code IN (2, 5, 8, 11) THEN 1 ELSE 0 END) AS student_half,
                SUM(CASE WHEN j.acceptance_code IN (0, 1, 2, 3) OR j.acceptance_code IS NULL THEN 1 ELSE 0 END) AS teacher_noshow,
                COALESCE(MAX(lps.leave_sessions), 0) AS leave_sessions,
                SUM(CASE WHEN j.acceptance_code = 12 THEN 1 ELSE 0 END) AS total_success_12,
                ROUND(SUM(CASE WHEN j.acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) AS health_score,
                ROUND(SUM(CASE WHEN j.acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) / COUNT(*), 4) AS success_rate,
                CASE
                    WHEN TIMESTAMPDIFF(WEEK, MIN(j.lesson_time), MAX(j.lesson_time)) <= 0 THEN ROUND(SUM(CASE WHEN j.acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END), 2)
                    ELSE ROUND(SUM(CASE WHEN j.acceptance_code IN (3, 6, 9, 12) THEN 1 ELSE 0 END) / (TIMESTAMPDIFF(WEEK, MIN(j.lesson_time), MAX(j.lesson_time)) + 1), 2)
                END AS avg_per_week,
                MAX(CASE WHEN j.tlang_id IN (${EASY_SPEAK_IDS.join(',')}) THEN 1 ELSE 0 END) AS has_easyspeak,
                MAX(CASE WHEN j.tlang_id NOT IN (${EASY_SPEAK_IDS.join(',')}) THEN 1 ELSE 0 END) AS has_speakwell
            FROM joined j
            LEFT JOIN leave_per_student lps ON lps.learner_id = j.student_id
            GROUP BY j.student_id
        ),
        csi_full AS (
            SELECT
                c.*,
                f.lesson_1_date,
                f.lesson_1_code,
                f.lesson_2_date,
                f.lesson_2_code,
                f.lesson_3_date,
                f.lesson_3_code,
                f.first_3_success,
                f.first_3_total,
                CASE WHEN COALESCE(f.first_3_total, 0) > 0 THEN ROUND(COALESCE(f.first_3_success, 0) * 100.0 / f.first_3_total, 1) ELSE NULL END AS first_3_success_rate,
                (COALESCE(c.teacher_noshow, 0) + COALESCE(c.leave_sessions, 0)) AS teacher_disruption_events,
                ROUND(c.total_success_12 * 100.0 / c.total_scheduled, 1) AS ontrack_score,
                CASE
                    WHEN c.health_score >= 85 THEN 'Xanh (Khỏe mạnh)'
                    WHEN c.health_score >= 60 THEN 'Vàng (Cảnh báo)'
                    ELSE 'Đỏ (Báo động)'
                END AS health_category,
                CASE
                    WHEN c.teacher_noshow >= 4 THEN 'Khẩn cấp (GV nghỉ >= 4 buổi)'
                    WHEN c.teacher_noshow >= 2 THEN 'Nghiêm trọng (GV nghỉ >=2b)'
                    WHEN c.teacher_noshow = 1 THEN 'Có ảnh hưởng (GV nghỉ 1b)'
                    ELSE 'Bình thường'
                END AS teacher_warning,
                CASE
                    WHEN c.has_easyspeak = 1 AND c.has_speakwell = 1 THEN 'EASYSPEAK, SPEAKWELL'
                    WHEN c.has_easyspeak = 1 THEN 'EASYSPEAK'
                    ELSE 'SPEAKWELL'
                END AS course_names
            FROM csi_data c
            LEFT JOIN first_3_pivot f ON f.student_id = c.student_id
        )
        ${finalSelect}
    `;
}

async function queryCsi(finalSelect, filters = {}) {
    let mysql;
    try {
        mysql = require('mysql2/promise');
    } catch (error) {
        throw new Error('mysql2 is required for CSI data access.');
    }

    const normalizedFilters = normalizeCsiFilters(filters);
    const { whereSql, params } = buildCsiWhereClause(normalizedFilters);
    const sql = buildCsiBaseSql(finalSelect(whereSql));
    const connection = await mysql.createConnection(getZeusMysqlConfig());
    try {
        const [rows] = await connection.query(sql, [
            normalizedFilters.date_from,
            normalizedFilters.date_to,
            normalizedFilters.date_from,
            normalizedFilters.date_to,
            ...params,
        ]);
        return rows;
    } finally {
        await connection.end();
    }
}

async function getCsiStudents(filters = {}, options = {}) {
    const sortBy = String(options.sortBy || 'student_id').trim();
    const sortDir = String(options.sortDir || 'asc').trim().toUpperCase() === 'DESC' ? 'DESC' : 'ASC';
    const allowedSortColumns = new Set([
        'student_id', 'student_name', 'health_score', 'total_scheduled', 'total_success',
        'student_noshow', 'student_half', 'success_rate', 'teacher_noshow', 'lesson_1_date',
        'lesson_2_date', 'lesson_3_date', 'first_3_success_rate', 'avg_per_week', 'css_staff',
        'ontrack_score', 'health_category', 'teacher_warning',
    ]);
    const orderBy = allowedSortColumns.has(sortBy) ? sortBy : 'student_id';

    return queryCsi(whereSql => `
        SELECT
            student_id,
            student_name,
            email,
            phone,
            css_staff,
            total_scheduled,
            total_success,
            student_noshow,
            student_half,
            teacher_noshow,
            total_success_12,
            health_score,
            success_rate,
            avg_per_week,
            has_easyspeak,
            has_speakwell,
            lesson_1_date,
            lesson_1_code,
            lesson_2_date,
            lesson_2_code,
            lesson_3_date,
            lesson_3_code,
            first_3_success,
            first_3_total,
            first_3_success_rate,
            ontrack_score,
            health_category,
            teacher_warning,
            course_names
        FROM csi_full
        ${whereSql}
        ORDER BY ${orderBy} ${sortDir}, student_id ASC
    `, filters);
}

async function getCsiSummary(filters = {}) {
    const rows = await queryCsi(whereSql => `
        SELECT
            COUNT(*) AS total_students,
            SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) AS green,
            SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) AS yellow,
            SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) AS red,
            ROUND(AVG(health_score), 1) AS avg_score,
            ROUND(AVG(success_rate) * 100, 1) AS success_rate,
            ROUND(AVG(avg_per_week), 2) AS avg_lessons_per_week,
            SUM(CASE WHEN ontrack_score >= 90 THEN 1 ELSE 0 END) AS ontrack_count,
            ROUND(SUM(CASE WHEN ontrack_score >= 90 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1) AS ontrack_rate
        FROM csi_full
        ${whereSql}
    `, filters);
    return rows[0] || {};
}

async function getCsiHealthDistribution(filters = {}) {
    const rows = await queryCsi(whereSql => `
        SELECT health_category, COUNT(*) AS count
        FROM csi_full
        ${whereSql}
        GROUP BY health_category
        ORDER BY count DESC
    `, filters);
    return rows;
}

async function getCsiScoreDistribution(filters = {}) {
    const rows = await queryCsi(whereSql => `
        SELECT
            CASE
                WHEN health_score BETWEEN 0 AND 20 THEN '0-20'
                WHEN health_score > 20 AND health_score <= 40 THEN '21-40'
                WHEN health_score > 40 AND health_score <= 60 THEN '41-60'
                WHEN health_score > 60 AND health_score <= 80 THEN '61-80'
                ELSE '81-100'
            END AS label,
            COUNT(*) AS count,
            CASE
                WHEN health_score BETWEEN 0 AND 20 THEN 1
                WHEN health_score > 20 AND health_score <= 40 THEN 2
                WHEN health_score > 40 AND health_score <= 60 THEN 3
                WHEN health_score > 60 AND health_score <= 80 THEN 4
                ELSE 5
            END AS sort_order
        FROM csi_full
        ${whereSql}
        GROUP BY label, sort_order
        ORDER BY sort_order ASC
    `, filters);
    return rows;
}

async function getCsiCssPerformance(filters = {}) {
    const rows = await queryCsi(whereSql => `
        SELECT
            css_staff,
            COUNT(*) AS total,
            SUM(CASE WHEN health_category = 'Xanh (Khỏe mạnh)' THEN 1 ELSE 0 END) AS green,
            SUM(CASE WHEN health_category = 'Vàng (Cảnh báo)' THEN 1 ELSE 0 END) AS yellow,
            SUM(CASE WHEN health_category = 'Đỏ (Báo động)' THEN 1 ELSE 0 END) AS red,
            ROUND(AVG(health_score), 1) AS avg_score,
            ROUND(AVG(success_rate) * 100, 1) AS avg_success_rate
        FROM csi_full
        ${whereSql ? `${whereSql} AND css_staff IS NOT NULL AND css_staff != ''` : `WHERE css_staff IS NOT NULL AND css_staff != ''`}
        GROUP BY css_staff
        ORDER BY avg_score ASC, css_staff ASC
    `, filters);
    return rows;
}

async function getCsiTeacherWarning(filters = {}) {
    const rows = await queryCsi(whereSql => `
        SELECT teacher_warning, COUNT(*) AS count
        FROM csi_full
        ${whereSql}
        GROUP BY teacher_warning
        ORDER BY count DESC
    `, filters);
    return rows;
}

async function getCsiHealthDashboard(filters = {}) {
    const normalizedFilters = normalizeCsiFilters(filters);
    const cacheKey = JSON.stringify(normalizedFilters);
    const cached = csiDashboardCache.get(cacheKey);
    const now = Date.now();
    if (cached && now - cached.at < CSI_DASHBOARD_CACHE_TTL_MS) {
        return cached.payload;
    }

    const [summary, healthDistribution, scoreDistribution, cssPerformance, teacherWarning] = await Promise.all([
        getCsiSummary(normalizedFilters),
        getCsiHealthDistribution(normalizedFilters),
        getCsiScoreDistribution(normalizedFilters),
        getCsiCssPerformance(normalizedFilters),
        getCsiTeacherWarning(normalizedFilters),
    ]);

    const payload = {
        summary,
        healthDistribution,
        scoreDistribution,
        cssPerformance,
        teacherWarning,
        filters: normalizedFilters,
        refreshedAt: new Date().toISOString(),
    };
    csiDashboardCache.set(cacheKey, { at: now, payload });
    return payload;
}

module.exports = {
    normalizeCsiFilters,
    getCsiStudents,
    getCsiSummary,
    getCsiHealthDistribution,
    getCsiScoreDistribution,
    getCsiCssPerformance,
    getCsiTeacherWarning,
    getCsiHealthDashboard,
};
