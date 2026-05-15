// src/scripts/sync.js
const { getSheetData } = require('../services/gsheetService');
const db = require('../db/database');

const SPREADSHEET_ID = process.env.SPREADSHEET_ID || '1t46BJhDlgB8BYRlx29x5GeB-Gd3A4Jolzm87OZhYdAQ';
const SHEET_NAME = process.env.SHEET_NAME || 'Data_Model';
const SYNC_INTERVAL_MS = Number(process.env.SYNC_INTERVAL_MS || 5 * 60 * 1000);

function run(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.run(sql, params, function onRun(err) {
            if (err) reject(err);
            else resolve(this);
        });
    });
}

async function replaceDashboardData(data) {
    const syncedAt = new Date().toISOString();
    await run('BEGIN TRANSACTION');
    try {
        await run('DELETE FROM dashboard_data');
        const sql = `INSERT INTO dashboard_data (
            cut_off_date, period_month, period_quarter, period_year, period_week,
            student_id, student_name, email, phone, css,
            score_target, score_base, variance, target_category, base_category, movement_group,
            teacher_disruption_rate, unfinished_rate, activation_speed,
            renewal_status, renewal_revenue, renewal_product, remaining_sessions, lifecycle_status,
            management_health_score, learning_pace, teacher_disruption_cumulative, synced_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

        for (const item of data) {
            await run(sql, [
                item.period?.date || '',
                item.period?.month || '',
                item.period?.quarter || '',
                item.period?.year || '',
                item.period?.week || '',
                item.student.id,
                item.student.name,
                item.student.email,
                item.student.phone,
                item.student.css,
                item.health.scoreTarget,
                item.health.scoreBase,
                item.health.variance,
                item.health.targetCategory,
                item.health.baseCategory,
                item.movement.group,
                item.operation.teacherDisruptionRate,
                item.operation.unfinishedRate,
                item.operation.activationSpeed,
                item.renewal.status,
                item.renewal.revenue,
                item.renewal.product,
                item.renewal.remainingSessions,
                item.renewal.lifecycleStatus,
                item.health.managementScore,
                item.health.learningPace,
                item.operation.teacherDisruptionCumulative,
                syncedAt,
            ]);
        }
        await run('COMMIT');
        return { rows: data.length, syncedAt };
    } catch (error) {
        await run('ROLLBACK').catch(() => {});
        throw error;
    }
}

async function syncData() {
    console.log(`[sync] Starting Google Sheet sync: ${SHEET_NAME}`);
    const data = await getSheetData(SPREADSHEET_ID, SHEET_NAME);
    const result = await replaceDashboardData(data);
    console.log(`[sync] Completed: ${result.rows} rows at ${result.syncedAt}`);
    return result;
}

function startSyncJob() {
    syncData().catch(error => console.error('[sync] Failed:', error));
    const timer = setInterval(() => {
        syncData().catch(error => console.error('[sync] Failed:', error));
    }, SYNC_INTERVAL_MS);
    return timer;
}

if (require.main === module) {
    syncData()
        .then(() => process.exit(0))
        .catch(error => {
            console.error('[sync] Failed:', error);
            process.exit(1);
        });
}

module.exports = { syncData, startSyncJob };
