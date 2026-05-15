// src/app.js
const express = require('express');
const http = require('http');
const path = require('path');
const { Server } = require('socket.io');
const db = require('./db/database');
const {
    classifyHealthMovement,
    applyFilters,
    calculateComprehensiveMetrics,
    getFilterOptions,
} = require('./services/analyticsService');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*', methods: ['GET', 'POST'] },
});

function dbAll(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

const DATA_CACHE_TTL_MS = Number(process.env.DATA_CACHE_TTL_MS || 15000);
let cachedData = null;
let cachedFilterOptions = null;
let cachedLastSyncedAt = null;
let cachedAt = 0;

function mapDbRow(row) {
    return {
        period: {
            date: row.cut_off_date || '',
            month: row.period_month || '',
            quarter: row.period_quarter || '',
            year: row.period_year || '',
            week: row.period_week || '',
        },
        student: {
            id: row.student_id,
            name: row.student_name,
            email: row.email,
            phone: row.phone,
            css: row.css,
        },
        health: {
            scoreTarget: Number(row.score_target) || 0,
            scoreBase: Number(row.score_base) || 0,
            variance: Number(row.variance) || 0,
            targetCategory: row.target_category || '',
            baseCategory: row.base_category || '',
            managementScore: Number(row.management_health_score) || 0,
            learningPace: Number(row.learning_pace) || 0,
        },
        movement: {
            group: row.movement_group || '',
        },
        operation: {
            teacherDisruptionRate: Number(row.teacher_disruption_rate) || 0,
            unfinishedRate: Number(row.unfinished_rate) || 0,
            activationSpeed: row.activation_speed || '',
            teacherDisruptionCumulative: Number(row.teacher_disruption_cumulative) || 0,
        },
        renewal: {
            status: row.renewal_status || '',
            revenue: Number(row.renewal_revenue) || 0,
            product: row.renewal_product || '',
            remainingSessions: Number(row.remaining_sessions) || 0,
            lifecycleStatus: row.lifecycle_status || '',
        },
        syncedAt: row.synced_at,
    };
}

async function getCachedDashboardData() {
    const now = Date.now();
    if (cachedData && now - cachedAt < DATA_CACHE_TTL_MS) {
        return { allData: cachedData, filterOptions: cachedFilterOptions, lastSyncedAt: cachedLastSyncedAt };
    }

    const rows = await dbAll('SELECT * FROM dashboard_data');
    const allData = classifyHealthMovement(rows.map(mapDbRow));
    cachedData = allData;
    cachedFilterOptions = getFilterOptions(allData);
    cachedLastSyncedAt = rows[0]?.synced_at || null;
    cachedAt = now;
    return { allData, filterOptions: cachedFilterOptions, lastSyncedAt: cachedLastSyncedAt };
}

async function getDashboardPayload(filters = {}) {
    const { allData, filterOptions, lastSyncedAt } = await getCachedDashboardData();
    const filteredData = applyFilters(allData, filters);
    const metrics = calculateComprehensiveMetrics(filteredData);
    return {
        ...metrics,
        filters,
        filterOptions,
        rowCount: filteredData.length,
        totalCachedRows: allData.length,
        lastSyncedAt,
        generatedAt: new Date().toISOString(),
    };
}

app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'CRM-Dashboard', time: new Date().toISOString() });
});

app.get('/api/dashboard', async (req, res) => {
    try {
        const payload = await getDashboardPayload(req.query || {});
        res.json(payload);
    } catch (error) {
        console.error('[api] dashboard failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.post('/api/sync', (_req, res) => {
    res.status(202).json({
        message: 'Sync is intentionally separated from web process. Run: node src/scripts/sync.js',
    });
});

io.on('connection', (socket) => {
    console.log('[socket] Client connected');
    let currentFilters = {};

    const emitDashboard = async () => {
        try {
            const payload = await getDashboardPayload(currentFilters);
            socket.emit('dataUpdate', payload);
        } catch (error) {
            console.error('[socket] dataUpdate failed:', error);
            socket.emit('dashboardError', { error: error.message });
        }
    };

    socket.on('setFilters', (filters = {}) => {
        currentFilters = filters;
        emitDashboard();
    });

    emitDashboard();
    const interval = setInterval(emitDashboard, 30000);

    socket.on('disconnect', () => {
        clearInterval(interval);
        console.log('[socket] Client disconnected');
    });
});

const PORT = Number(process.env.PORT || 3000);
const HOST = process.env.HOST || '0.0.0.0';
server.listen(PORT, HOST, () => {
    console.log(`Server running on http://127.0.0.1:${PORT}`);
    console.log(`Server bound to ${HOST}:${PORT}`);
    console.log('Auto Google sync is disabled in web process. Run `node src/scripts/sync.js` manually or call POST /api/sync.');
});
