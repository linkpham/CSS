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
const {
    ROLE,
    bootstrapHeadUser,
    login,
    logout,
    getUserByToken,
    listUsers,
    createUser,
    updateUser,
    changeOwnPassword,
    resetUserPassword,
    listAuditLogs,
    getAllowedCssScopes,
} = require('./auth/authService');
const { requireAuth, requireAnyRole } = require('./auth/authMiddleware');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*', methods: ['GET', 'POST', 'PATCH'] },
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

function serializeStudentRow(item) {
    return {
        id: item.student.id,
        name: item.student.name,
        email: item.student.email,
        phone: item.student.phone,
        css: item.student.css,
        cutOffDate: item.period.date,
        month: item.period.month,
        quarter: item.period.quarter,
        year: item.period.year,
        week: item.period.week,
        scoreTarget: item.health.scoreTarget,
        scoreBase: item.health.scoreBase,
        variance: item.health.variance,
        targetCategory: item.health.targetCategory,
        baseCategory: item.health.baseCategory,
        managementScore: item.health.managementScore,
        learningPace: item.health.learningPace,
        movementGroup: item.movement.group,
        movementNormalized: item.movement.normalized,
        teacherDisruptionRate: item.operation.teacherDisruptionRate,
        unfinishedRate: item.operation.unfinishedRate,
        activationSpeed: item.operation.activationSpeed,
        teacherDisruptionCumulative: item.operation.teacherDisruptionCumulative,
        renewalStatus: item.renewal.status,
        renewalRevenue: item.renewal.revenue,
        renewalProduct: item.renewal.product,
        remainingSessions: item.renewal.remainingSessions,
        lifecycleStatus: item.renewal.lifecycleStatus,
    };
}

function applyStudentSearch(data, search = '') {
    const keyword = String(search || '').trim().toLowerCase();
    if (!keyword) return data;
    return data.filter(item => {
        const text = [
            item.student.id,
            item.student.name,
            item.student.email,
            item.student.phone,
            item.student.css,
            item.health.targetCategory,
            item.health.baseCategory,
            item.movement.group,
            item.movement.normalized,
            item.renewal.status,
            item.renewal.product,
            item.renewal.lifecycleStatus,
        ].join(' ').toLowerCase();
        return text.includes(keyword);
    });
}

function paginate(items, page, pageSize) {
    const total = items.length;
    const safePageSize = Math.min(Math.max(Number(pageSize) || 25, 1), 10000);
    const totalPages = Math.max(Math.ceil(total / safePageSize), 1);
    const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
    const start = (safePage - 1) * safePageSize;
    return {
        page: safePage,
        pageSize: safePageSize,
        total,
        totalPages,
        rows: items.slice(start, start + safePageSize),
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

async function applyRoleScope(allData, user) {
    if (!user) return [];
    if (user.role === ROLE.HEAD) return allData;

    const allowedCssScopes = await getAllowedCssScopes(user);
    if (!allowedCssScopes || !allowedCssScopes.length) return [];
    const allowed = new Set(allowedCssScopes.map(value => String(value).trim().toLowerCase()));
    return allData.filter(row => allowed.has(String(row.student.css || '').trim().toLowerCase()));
}

async function getScopedDashboardSource(filters = {}, user, options = {}) {
    const { allData, lastSyncedAt } = await getCachedDashboardData();
    const scopedData = await applyRoleScope(allData, user);
    const filteredData = applyFilters(scopedData, filters);
    const searchedData = applyStudentSearch(filteredData, options.search);
    return { allData, scopedData, filteredData, searchedData, lastSyncedAt };
}

async function getDashboardPayload(filters = {}, user) {
    const { allData, scopedData, filteredData, lastSyncedAt } = await getScopedDashboardSource(filters, user);
    const metrics = calculateComprehensiveMetrics(filteredData);
    return {
        ...metrics,
        viewer: user,
        filters,
        filterOptions: getFilterOptions(scopedData),
        rowCount: filteredData.length,
        totalCachedRows: allData.length,
        scopedRows: scopedData.length,
        lastSyncedAt,
        generatedAt: new Date().toISOString(),
    };
}

io.use(async (socket, next) => {
    try {
        const token = socket.handshake.auth?.token || socket.handshake.query?.token;
        const user = await getUserByToken(token);
        if (!user) {
            return next(new Error('Unauthorized socket session'));
        }
        socket.user = user;
        socket.authToken = token;
        next();
    } catch (error) {
        next(error);
    }
});

app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'CRM-Dashboard', time: new Date().toISOString() });
});

app.post('/api/auth/login', async (req, res) => {
    try {
        const { email, password } = req.body || {};
        const result = await login(email, password);
        res.json(result);
    } catch (error) {
        console.error('[api] login failed:', error.message);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/auth/logout', requireAuth, async (req, res) => {
    try {
        await logout(req.authToken);
        res.json({ ok: true });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/auth/me', requireAuth, async (req, res) => {
    res.json({ user: req.user });
});

app.post('/api/auth/change-password', requireAuth, async (req, res) => {
    try {
        const { currentPassword, newPassword } = req.body || {};
        const user = await changeOwnPassword(req.user, currentPassword, newPassword);
        res.json({ ok: true, user });
    } catch (error) {
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/users', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const users = await listUsers(req.user, req.query || {});
        res.json({ users });
    } catch (error) {
        console.error('[api] listUsers failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/users/audit', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const logs = await listAuditLogs(req.user, req.query || {});
        res.json({ logs });
    } catch (error) {
        console.error('[api] listAuditLogs failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/users', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const user = await createUser(req.user, req.body || {});
        res.status(201).json({ user });
    } catch (error) {
        console.error('[api] createUser failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.patch('/api/users/:id', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const user = await updateUser(req.user, req.params.id, req.body || {});
        res.json({ user });
    } catch (error) {
        console.error('[api] updateUser failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/users/:id/reset-password', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const result = await resetUserPassword(req.user, req.params.id, req.body?.newPassword);
        res.json({ ok: true, ...result });
    } catch (error) {
        console.error('[api] resetUserPassword failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/dashboard', requireAuth, async (req, res) => {
    try {
        const payload = await getDashboardPayload(req.query || {}, req.user);
        res.json(payload);
    } catch (error) {
        console.error('[api] dashboard failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/students', requireAuth, async (req, res) => {
    try {
        const { searchedData, scopedData, lastSyncedAt } = await getScopedDashboardSource(req.query || {}, req.user, {
            search: req.query.search,
        });
        const downloadAll = String(req.query.download || '') === 'all';
        const paging = downloadAll ? { rows: searchedData, total: searchedData.length, page: 1, pageSize: searchedData.length || 1, totalPages: 1 } : paginate(searchedData, req.query.page, req.query.pageSize);
        res.json({
            students: paging.rows.map(serializeStudentRow),
            total: paging.total,
            page: paging.page,
            pageSize: paging.pageSize,
            totalPages: paging.totalPages,
            scopedRows: scopedData.length,
            lastSyncedAt,
        });
    } catch (error) {
        console.error('[api] students failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.post('/api/sync', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER]), (_req, res) => {
    res.status(202).json({
        message: 'Sync is intentionally separated from web process. Run: node src/scripts/sync.js',
    });
});

io.on('connection', (socket) => {
    console.log(`[socket] Client connected: ${socket.user.email}`);
    let currentFilters = {};

    const emitDashboard = async () => {
        try {
            const payload = await getDashboardPayload(currentFilters, socket.user);
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
        console.log(`[socket] Client disconnected: ${socket.user.email}`);
    });
});

const PORT = Number(process.env.PORT || 3000);
const HOST = process.env.HOST || '0.0.0.0';

async function startServer() {
    try {
        const bootstrapped = await bootstrapHeadUser();
        if (bootstrapped) {
            console.log('[auth] Bootstrapped default Head account');
            console.log(`[auth] Email: ${bootstrapped.email}`);
            console.log(`[auth] Temporary password: ${bootstrapped.password}`);
        }
        server.listen(PORT, HOST, () => {
            console.log(`Server running on http://127.0.0.1:${PORT}`);
            console.log(`Server bound to ${HOST}:${PORT}`);
            console.log('Auto Google sync is disabled in web process. Run `node src/scripts/sync.js` manually or call POST /api/sync.');
        });
    } catch (error) {
        console.error('[startup] Failed to start server:', error);
        process.exit(1);
    }
}

startServer();
