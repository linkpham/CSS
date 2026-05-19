const crypto = require('crypto');
const db = require('../db/database');
const {
    ROLE,
    ROLE_LABELS,
    ROLE_RANK,
    isValidRole,
    canCreateRole,
    canAssignParentRole,
    canManageRole,
} = require('./roles');

function dbGet(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.get(sql, params, (err, row) => (err ? reject(err) : resolve(row)));
    });
}

function dbAll(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => (err ? reject(err) : resolve(rows)));
    });
}

function dbRun(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.run(sql, params, function onRun(err) {
            if (err) reject(err);
            else resolve({ lastID: this.lastID, changes: this.changes });
        });
    });
}

function nowIso() {
    return new Date().toISOString();
}

function normalizeEmail(email) {
    return String(email || '').trim().toLowerCase();
}

function normalizeSearch(text) {
    return String(text || '').trim().toLowerCase();
}

function randomToken() {
    return crypto.randomBytes(32).toString('hex');
}

function generateTemporaryPassword() {
    return `Tmp@${crypto.randomBytes(5).toString('hex')}`;
}

function hashPassword(password, salt = crypto.randomBytes(16).toString('hex')) {
    const derived = crypto.scryptSync(String(password), salt, 64).toString('hex');
    return `${salt}:${derived}`;
}

function verifyPassword(password, storedHash = '') {
    const [salt, hash] = String(storedHash || '').split(':');
    if (!salt || !hash) return false;
    const candidate = crypto.scryptSync(String(password), salt, 64).toString('hex');
    return crypto.timingSafeEqual(Buffer.from(hash, 'hex'), Buffer.from(candidate, 'hex'));
}

function escapeCsvValue(value) {
    const text = String(value ?? '');
    if (/[",\n]/.test(text)) {
        return `"${text.replace(/"/g, '""')}"`;
    }
    return text;
}

function splitScopes(value) {
    return String(value || '')
        .split(/[;,\n]/)
        .map(item => item.trim())
        .filter(Boolean);
}

function userOrderCase(column = 'u.role') {
    return `CASE ${column}
        WHEN '${ROLE.HEAD}' THEN 4
        WHEN '${ROLE.CSS_MANAGER}' THEN 3
        WHEN '${ROLE.CSS_TEAM_LEADER}' THEN 2
        WHEN '${ROLE.STAFF}' THEN 1
        ELSE 0
    END`;
}

function sanitizeUser(row) {
    if (!row) return null;
    return {
        id: row.id,
        fullName: row.full_name,
        email: row.email,
        role: row.role,
        roleLabel: ROLE_LABELS[row.role] || row.role,
        status: row.status,
        cssScope: row.css_scope || '',
        reportsToUserId: row.reports_to_user_id || null,
        reportsToName: row.reports_to_name || null,
        assignedByUserId: row.assigned_by_user_id || null,
        assignedByName: row.assigned_by_name || null,
        createdAt: row.created_at,
        updatedAt: row.updated_at,
        lastLoginAt: row.last_login_at || null,
        mustChangePassword: Boolean(row.must_change_password),
    };
}

function sanitizeAuditLog(row) {
    let details = null;
    try {
        details = row.details ? JSON.parse(row.details) : null;
    } catch (_) {
        details = row.details || null;
    }
    return {
        id: row.id,
        action: row.action,
        createdAt: row.created_at,
        actorUserId: row.actor_user_id || null,
        actorName: row.actor_name || null,
        actorEmail: row.actor_email || null,
        targetUserId: row.target_user_id || null,
        targetName: row.target_name || null,
        targetEmail: row.target_email || null,
        details,
    };
}

async function getUserById(userId) {
    const row = await dbGet('SELECT * FROM users WHERE id = ?', [userId]);
    return row || null;
}

async function getJoinedUserById(userId) {
    const row = await dbGet(
        `SELECT u.*, parent.full_name AS reports_to_name, assigner.full_name AS assigned_by_name
         FROM users u
         LEFT JOIN users parent ON parent.id = u.reports_to_user_id
         LEFT JOIN users assigner ON assigner.id = u.assigned_by_user_id
         WHERE u.id = ?`,
        [userId],
    );
    return row || null;
}

async function getUserByEmail(email) {
    const row = await dbGet('SELECT * FROM users WHERE email = ?', [normalizeEmail(email)]);
    return row || null;
}

async function getDescendantIds(rootUserId) {
    const rows = await dbAll(
        `WITH RECURSIVE managed(id) AS (
            SELECT id FROM users WHERE reports_to_user_id = ?
            UNION ALL
            SELECT u.id FROM users u
            JOIN managed m ON u.reports_to_user_id = m.id
        )
        SELECT id FROM managed`,
        [rootUserId],
    );
    return rows.map(row => row.id);
}

async function getVisibleUserIds(actor) {
    if (actor.role === ROLE.HEAD) {
        const rows = await dbAll('SELECT id FROM users');
        return rows.map(row => row.id);
    }
    const descendantIds = await getDescendantIds(actor.id);
    return [actor.id, ...descendantIds];
}

async function getVisibleUsers(actor, filters = {}) {
    const ids = await getVisibleUserIds(actor);
    if (!ids.length) return [];

    const clauses = [];
    const params = [];
    const placeholders = ids.map(() => '?').join(',');
    clauses.push(`u.id IN (${placeholders})`);
    params.push(...ids);

    if (filters.role) {
        clauses.push('u.role = ?');
        params.push(String(filters.role).trim());
    }
    if (filters.status) {
        clauses.push('u.status = ?');
        params.push(String(filters.status).trim());
    }
    if (filters.search) {
        const search = `%${normalizeSearch(filters.search)}%`;
        clauses.push(`(
            LOWER(u.full_name) LIKE ? OR LOWER(u.email) LIKE ? OR LOWER(COALESCE(u.css_scope, '')) LIKE ? OR LOWER(COALESCE(parent.full_name, '')) LIKE ?
        )`);
        params.push(search, search, search, search);
    }

    const rows = await dbAll(
        `SELECT u.*, parent.full_name AS reports_to_name, assigner.full_name AS assigned_by_name
         FROM users u
         LEFT JOIN users parent ON parent.id = u.reports_to_user_id
         LEFT JOIN users assigner ON assigner.id = u.assigned_by_user_id
         WHERE ${clauses.join(' AND ')}
         ORDER BY ${userOrderCase('u.role')} DESC, u.full_name ASC`,
        params,
    );
    return rows.map(sanitizeUser);
}

async function getAllowedCssScopes(actor) {
    if (actor.role === ROLE.HEAD) return null;
    const ids = await getVisibleUserIds(actor);
    if (!ids.length) return [];
    const placeholders = ids.map(() => '?').join(',');
    const rows = await dbAll(
        `SELECT css_scope FROM users WHERE id IN (${placeholders}) AND status = 'active'`,
        ids,
    );
    return [...new Set(rows.flatMap(row => splitScopes(row.css_scope)).map(item => item.trim()).filter(Boolean))];
}

async function logAudit(actorUserId, targetUserId, action, details = null) {
    await dbRun(
        `INSERT INTO user_audit_logs (actor_user_id, target_user_id, action, details, created_at) VALUES (?, ?, ?, ?, ?)`,
        [actorUserId || null, targetUserId || null, action, details ? JSON.stringify(details) : null, nowIso()],
    );
}

async function assertActorCanTouchUser(actor, targetUserId) {
    const target = await getUserById(targetUserId);
    if (!target) {
        const error = new Error('User not found');
        error.status = 404;
        throw error;
    }

    if (actor.role !== ROLE.HEAD) {
        const visibleIds = await getVisibleUserIds(actor);
        if (!visibleIds.includes(target.id)) {
            const error = new Error('Target user is outside your management scope');
            error.status = 403;
            throw error;
        }
        if (actor.id === target.id) {
            const error = new Error('You cannot manage your own account from this endpoint');
            error.status = 400;
            throw error;
        }
    }

    if (!canManageRole(actor.role, target.role)) {
        const error = new Error('Your role cannot manage this target role');
        error.status = 403;
        throw error;
    }

    return target;
}

async function validateParentAssignment(actor, role, reportsToUserId) {
    if (role === ROLE.HEAD) {
        if (reportsToUserId) {
            const error = new Error('Head user cannot report to another user');
            error.status = 400;
            throw error;
        }
        return null;
    }

    if (!reportsToUserId) {
        return actor;
    }

    const parent = await getUserById(reportsToUserId);
    if (!parent) {
        const error = new Error('reportsToUserId does not exist');
        error.status = 400;
        throw error;
    }

    if (!canAssignParentRole(parent.role, role)) {
        const error = new Error('Parent role must be higher than child role');
        error.status = 400;
        throw error;
    }

    if (actor.role !== ROLE.HEAD) {
        const visibleIds = await getVisibleUserIds(actor);
        if (!visibleIds.includes(parent.id)) {
            const error = new Error('Parent user is outside your management scope');
            error.status = 403;
            throw error;
        }
        if (actor.role === ROLE.CSS_TEAM_LEADER && parent.id !== actor.id) {
            const error = new Error('CSS Team Leader can only assign direct Staff under themselves');
            error.status = 403;
            throw error;
        }
    }

    return parent;
}

async function bootstrapHeadUser() {
    const row = await dbGet('SELECT COUNT(*) AS count FROM users');
    if ((row?.count || 0) > 0) return null;

    const email = normalizeEmail(process.env.BOOTSTRAP_HEAD_EMAIL || 'head@crm.local');
    const password = process.env.BOOTSTRAP_HEAD_PASSWORD || 'Head@123456';
    const createdAt = nowIso();
    const result = await dbRun(
        `INSERT INTO users (
            full_name, email, password_hash, role, status, css_scope,
            reports_to_user_id, assigned_by_user_id, last_login_at,
            created_at, updated_at, must_change_password
        ) VALUES (?, ?, ?, ?, 'active', '', NULL, NULL, NULL, ?, ?, 1)`,
        ['System Head', email, hashPassword(password), ROLE.HEAD, createdAt, createdAt],
    );
    await logAudit(null, result.lastID, 'BOOTSTRAP_HEAD_CREATED', { email });
    return { id: result.lastID, email, password };
}

async function createSession(user) {
    const token = randomToken();
    const createdAt = nowIso();
    const expiresAt = new Date(Date.now() + 1000 * 60 * 60 * 24 * 30).toISOString();
    await dbRun(
        `INSERT INTO auth_sessions (user_id, token, created_at, expires_at) VALUES (?, ?, ?, ?)`,
        [user.id, token, createdAt, expiresAt],
    );
    await dbRun('UPDATE users SET last_login_at = ?, updated_at = ? WHERE id = ?', [createdAt, createdAt, user.id]);
    return { token, expiresAt };
}

async function login(email, password) {
    const user = await getUserByEmail(email);
    if (!user || user.status !== 'active' || !verifyPassword(password, user.password_hash)) {
        const error = new Error('Invalid email or password');
        error.status = 401;
        throw error;
    }
    const session = await createSession(user);
    const joinedUser = await getJoinedUserById(user.id);
    return {
        token: session.token,
        expiresAt: session.expiresAt,
        user: sanitizeUser(joinedUser || user),
    };
}

async function getUserByToken(token) {
    if (!token) return null;
    const row = await dbGet(
        `SELECT u.*, s.expires_at, parent.full_name AS reports_to_name, assigner.full_name AS assigned_by_name
         FROM auth_sessions s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN users parent ON parent.id = u.reports_to_user_id
         LEFT JOIN users assigner ON assigner.id = u.assigned_by_user_id
         WHERE s.token = ? AND u.status = 'active' AND s.expires_at > ?`,
        [token, nowIso()],
    );
    return row ? sanitizeUser(row) : null;
}

async function logout(token) {
    if (!token) return;
    await dbRun('DELETE FROM auth_sessions WHERE token = ?', [token]);
}

async function listUsers(actor, filters = {}) {
    return getVisibleUsers(actor, filters);
}

async function createUser(actor, payload = {}) {
    const fullName = String(payload.fullName || '').trim();
    const email = normalizeEmail(payload.email);
    const password = String(payload.password || '');
    const role = String(payload.role || '').trim();
    const status = String(payload.status || 'active').trim() || 'active';
    const cssScope = String(payload.cssScope || '').trim();
    const reportsToUserId = payload.reportsToUserId ? Number(payload.reportsToUserId) : null;

    if (!fullName) {
        const error = new Error('fullName is required');
        error.status = 400;
        throw error;
    }
    if (!email) {
        const error = new Error('email is required');
        error.status = 400;
        throw error;
    }
    if (password.length < 8) {
        const error = new Error('password must be at least 8 characters');
        error.status = 400;
        throw error;
    }
    if (!isValidRole(role)) {
        const error = new Error('Invalid role');
        error.status = 400;
        throw error;
    }
    if (!canCreateRole(actor.role, role)) {
        const error = new Error(`Role ${ROLE_LABELS[actor.role]} cannot create ${ROLE_LABELS[role]}`);
        error.status = 403;
        throw error;
    }

    const existing = await getUserByEmail(email);
    if (existing) {
        const error = new Error('Email already exists');
        error.status = 409;
        throw error;
    }

    await validateParentAssignment(actor, role, reportsToUserId);

    const createdAt = nowIso();
    const assignedReportsToUserId = reportsToUserId || (actor.role === ROLE.HEAD && role === ROLE.HEAD ? null : actor.id);
    const result = await dbRun(
        `INSERT INTO users (
            full_name, email, password_hash, role, status, css_scope,
            reports_to_user_id, assigned_by_user_id, created_at, updated_at, must_change_password
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`,
        [
            fullName,
            email,
            hashPassword(password),
            role,
            status === 'inactive' ? 'inactive' : 'active',
            cssScope,
            assignedReportsToUserId,
            actor.id,
            createdAt,
            createdAt,
        ],
    );

    const createdUser = await getJoinedUserById(result.lastID);
    await logAudit(actor.id, result.lastID, 'USER_CREATED', {
        fullName,
        email,
        role,
        status: status === 'inactive' ? 'inactive' : 'active',
        cssScope,
        reportsToUserId: assignedReportsToUserId,
    });

    return sanitizeUser(createdUser);
}

async function updateUser(actor, targetUserId, payload = {}) {
    const target = await assertActorCanTouchUser(actor, Number(targetUserId));

    const nextFullName = payload.fullName !== undefined ? String(payload.fullName || '').trim() : target.full_name;
    const nextRole = payload.role !== undefined ? String(payload.role || '').trim() : target.role;
    const nextStatus = payload.status !== undefined ? String(payload.status || '').trim() : target.status;
    const nextCssScope = payload.cssScope !== undefined ? String(payload.cssScope || '').trim() : (target.css_scope || '');
    const nextReportsToUserId = payload.reportsToUserId !== undefined
        ? (payload.reportsToUserId ? Number(payload.reportsToUserId) : null)
        : target.reports_to_user_id;
    const nextPassword = payload.password !== undefined ? String(payload.password || '') : null;

    if (!nextFullName) {
        const error = new Error('fullName cannot be empty');
        error.status = 400;
        throw error;
    }
    if (!isValidRole(nextRole)) {
        const error = new Error('Invalid role');
        error.status = 400;
        throw error;
    }
    if (!canManageRole(actor.role, nextRole)) {
        const error = new Error('Your role cannot assign this role');
        error.status = 403;
        throw error;
    }
    if (ROLE_RANK[nextRole] >= ROLE_RANK[actor.role] && actor.role !== ROLE.HEAD) {
        const error = new Error('You cannot promote a user to equal or higher than your role');
        error.status = 403;
        throw error;
    }

    await validateParentAssignment(actor, nextRole, nextReportsToUserId);

    const nextStatusNormalized = nextStatus === 'inactive' ? 'inactive' : 'active';
    const updateSql = [];
    const params = [];
    updateSql.push('full_name = ?'); params.push(nextFullName);
    updateSql.push('role = ?'); params.push(nextRole);
    updateSql.push('status = ?'); params.push(nextStatusNormalized);
    updateSql.push('css_scope = ?'); params.push(nextCssScope);
    updateSql.push('reports_to_user_id = ?'); params.push(nextReportsToUserId);
    if (nextPassword !== null) {
        if (nextPassword.length < 8) {
            const error = new Error('password must be at least 8 characters');
            error.status = 400;
            throw error;
        }
        updateSql.push('password_hash = ?'); params.push(hashPassword(nextPassword));
        updateSql.push('must_change_password = 0');
    }
    updateSql.push('updated_at = ?'); params.push(nowIso());
    params.push(target.id);

    await dbRun(`UPDATE users SET ${updateSql.join(', ')} WHERE id = ?`, params);

    const details = {
        before: {
            fullName: target.full_name,
            role: target.role,
            status: target.status,
            cssScope: target.css_scope || '',
            reportsToUserId: target.reports_to_user_id || null,
        },
        after: {
            fullName: nextFullName,
            role: nextRole,
            status: nextStatusNormalized,
            cssScope: nextCssScope,
            reportsToUserId: nextReportsToUserId,
            passwordChanged: nextPassword !== null,
        },
    };

    if (nextPassword !== null) {
        await dbRun('DELETE FROM auth_sessions WHERE user_id = ?', [target.id]);
    }
    await logAudit(actor.id, target.id, 'USER_UPDATED', details);

    return sanitizeUser(await getJoinedUserById(target.id));
}

async function changeOwnPassword(actor, currentPassword, newPassword) {
    const user = await getUserById(actor.id);
    if (!user) {
        const error = new Error('User not found');
        error.status = 404;
        throw error;
    }
    if (!verifyPassword(currentPassword, user.password_hash)) {
        const error = new Error('Current password is incorrect');
        error.status = 400;
        throw error;
    }
    if (String(newPassword || '').length < 8) {
        const error = new Error('New password must be at least 8 characters');
        error.status = 400;
        throw error;
    }
    const updatedAt = nowIso();
    await dbRun(
        'UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE id = ?',
        [hashPassword(newPassword), updatedAt, actor.id],
    );
    await logAudit(actor.id, actor.id, 'SELF_PASSWORD_CHANGED', { updatedAt });
    return sanitizeUser(await getJoinedUserById(actor.id));
}

async function resetUserPassword(actor, targetUserId, newPassword) {
    const target = await assertActorCanTouchUser(actor, Number(targetUserId));
    const temporaryPassword = String(newPassword || '').trim() || generateTemporaryPassword();
    if (temporaryPassword.length < 8) {
        const error = new Error('Reset password must be at least 8 characters');
        error.status = 400;
        throw error;
    }
    const updatedAt = nowIso();
    await dbRun(
        'UPDATE users SET password_hash = ?, must_change_password = 1, updated_at = ? WHERE id = ?',
        [hashPassword(temporaryPassword), updatedAt, target.id],
    );
    await dbRun('DELETE FROM auth_sessions WHERE user_id = ?', [target.id]);
    await logAudit(actor.id, target.id, 'USER_PASSWORD_RESET', { updatedAt, mustChangePassword: true });
    return {
        temporaryPassword,
        user: sanitizeUser(await getJoinedUserById(target.id)),
    };
}

async function listAuditLogs(actor, filters = {}) {
    const params = [];
    const clauses = [];

    if (actor.role !== ROLE.HEAD) {
        const visibleIds = await getVisibleUserIds(actor);
        if (!visibleIds.length) return [];
        const placeholders = visibleIds.map(() => '?').join(',');
        clauses.push(`(
            logs.actor_user_id IN (${placeholders}) OR
            logs.target_user_id IN (${placeholders})
        )`);
        params.push(...visibleIds, ...visibleIds);
    }

    if (filters.action) {
        clauses.push('logs.action = ?');
        params.push(String(filters.action).trim());
    }
    if (filters.search) {
        const search = `%${normalizeSearch(filters.search)}%`;
        clauses.push(`(
            LOWER(COALESCE(actor.full_name, '')) LIKE ? OR
            LOWER(COALESCE(actor.email, '')) LIKE ? OR
            LOWER(COALESCE(target.full_name, '')) LIKE ? OR
            LOWER(COALESCE(target.email, '')) LIKE ? OR
            LOWER(COALESCE(logs.action, '')) LIKE ?
        )`);
        params.push(search, search, search, search, search);
    }

    const limit = Math.min(Math.max(Number(filters.limit) || 50, 1), 500);
    params.push(limit);

    const whereClause = clauses.length ? `WHERE ${clauses.join(' AND ')}` : '';
    const rows = await dbAll(
        `SELECT logs.*, actor.full_name AS actor_name, actor.email AS actor_email,
                target.full_name AS target_name, target.email AS target_email
         FROM user_audit_logs logs
         LEFT JOIN users actor ON actor.id = logs.actor_user_id
         LEFT JOIN users target ON target.id = logs.target_user_id
         ${whereClause}
         ORDER BY logs.created_at DESC
         LIMIT ?`,
        params,
    );
    return rows.map(sanitizeAuditLog);
}

module.exports = {
    ROLE,
    ROLE_LABELS,
    escapeCsvValue,
    dbGet,
    dbAll,
    dbRun,
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
};
