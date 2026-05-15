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

    // Migration cho DB đã tạo trước khi có bộ lọc thời gian.
    addColumnIfMissing('dashboard_data', 'cut_off_date', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_month', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_quarter', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_year', 'TEXT');
    addColumnIfMissing('dashboard_data', 'period_week', 'TEXT');

    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_css ON dashboard_data(css)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_target_category ON dashboard_data(target_category)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_group ON dashboard_data(movement_group)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_renewal_status ON dashboard_data(renewal_status)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_month ON dashboard_data(period_month)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_quarter ON dashboard_data(period_quarter)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_period_year ON dashboard_data(period_year)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_dashboard_cutoff_date ON dashboard_data(cut_off_date)`);
});

module.exports = db;
