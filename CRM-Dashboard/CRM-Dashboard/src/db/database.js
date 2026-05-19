// src/db/database.js
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');
const os = require('os');

// Không đặt SQLite DB trên /mnt/f vì WSL/NTFS có thể bị lock/hang IO.
// Dùng filesystem Linux local để dashboard ổn định.
const dbDir = process.env.CRM_DB_DIR || path.join(os.homedir(), '.crm-dashboard');
fs.mkdirSync(dbDir, { recursive: true });
const dbPath = process.env.CRM_DB_PATH || path.join(dbDir, 'crm.db');
const db = new sqlite3.Database(dbPath);

function addColumnIfMissing(table, column, definition) {
    db.run(`ALTER TABLE ${table} ADD COLUMN ${column} ${definition}`, err => {
        if (err && !String(err.message || '').includes('duplicate column name')) {
            console.error(`[db] Cannot add column ${column}:`, err.message);
        }
    });
}

db.serialize(() => {
    db.run(`CREATE TABLE IF NOT EXISTS dashboard_data (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cut_off_date TEXT,
        period_month TEXT,
        period_quarter TEXT,
        period_year TEXT,
        period_week TEXT,
        student_id TEXT,
        student_name TEXT,
        email TEXT,
        phone TEXT,
        css TEXT,
        score_target REAL,
        score_base REAL,
        variance REAL,
        target_category TEXT,
        base_category TEXT,
        movement_group TEXT,
        teacher_disruption_rate REAL,
        unfinished_rate REAL,
        activation_speed TEXT,
        renewal_status TEXT,
        renewal_revenue REAL,
        renewal_product TEXT,
        remaining_sessions REAL,
        lifecycle_status TEXT,
        management_health_score REAL,
        learning_pace REAL,
        teacher_disruption_cumulative REAL,
        synced_at TEXT
    )`);

    db.run(`CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'active',
        css_scope TEXT DEFAULT '',
        reports_to_user_id INTEGER,
        assigned_by_user_id INTEGER,
        last_login_at TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        FOREIGN KEY (reports_to_user_id) REFERENCES users(id),
        FOREIGN KEY (assigned_by_user_id) REFERENCES users(id)
    )`);

    db.run(`CREATE TABLE IF NOT EXISTS auth_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL,
        expires_at TEXT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )`);

    db.run(`CREATE TABLE IF NOT EXISTS user_audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        actor_user_id INTEGER,
        target_user_id INTEGER,
        action TEXT NOT NULL,
        details TEXT,
        created_at TEXT NOT NULL,
        FOREIGN KEY (actor_user_id) REFERENCES users(id),
        FOREIGN KEY (target_user_id) REFERENCES users(id)
    )`);

    // Migration cho DB đã tạo trước khi có bộ lọc thời gian.
    addColumnIfMissing('dashboard_data', 'cut_off_date', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_month', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_quarter', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_year', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_week', 'TEXT');

    // Migration cho module user management.
    addColumnIfMissing('users', 'css_scope', "TEXT DEFAULT ''");
    addColumnIfMissing('users', 'reports_to_user_id', 'INTEGER');
    addColumnIfMissing('users', 'assigned_by_user_id', 'INTEGER');
    addColumnIfMissing('users', 'last_login_at', 'TEXT');
    addColumnIfMissing('users', 'status', "TEXT NOT NULL DEFAULT 'active'");
    addColumnIfMissing('users', 'created_at', 'TEXT');
    addColumnIfMissing('users', 'updated_at', 'TEXT');
    addColumnIfMissing('users', 'must_change_password', 'INTEGER NOT NULL DEFAULT 0');

    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_css ON dashboard_data(css)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_target_category ON dashboard_data(target_category)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_group ON dashboard_data(movement_group)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_renewal_status ON dashboard_data(renewal_status)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_month ON dashboard_data(period_month)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_quarter ON dashboard_data(period_quarter)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_year ON dashboard_data(period_year)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_cutoff_date ON dashboard_data(cut_off_date)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_users_reports_to ON users(reports_to_user_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_users_css_scope ON users(css_scope)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_auth_sessions_token ON auth_sessions(token)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_auth_sessions_user_id ON auth_sessions(user_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_user_audit_logs_actor ON user_audit_logs(actor_user_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_user_audit_logs_target ON user_audit_logs(target_user_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_user_audit_logs_action ON user_audit_logs(action)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_user_audit_logs_created_at ON user_audit_logs(created_at)`);
});

module.exports = db;
