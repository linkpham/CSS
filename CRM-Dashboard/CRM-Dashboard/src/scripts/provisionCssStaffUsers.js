const { ROLE, dbAll, dbGet, createUser, updateUser } = require('../auth/authService');

const APPLY = process.argv.includes('--apply');
const actorLoginArg = process.argv.find(arg => arg.startsWith('--actor='));
const preferredActorLogin = (actorLoginArg ? actorLoginArg.split('=').slice(1).join('=') : '') || process.env.PROVISION_ACTOR_LOGIN || 'linhpg@hocmai.vn';

function normalizeLogin(value) {
    return String(value || '').trim().toLowerCase();
}

function buildPassword(cssName) {
    return `${String(cssName || '').trim()}@123`;
}

async function getProvisionActor() {
    if (preferredActorLogin) {
        const preferred = await dbGet(
            `SELECT id, full_name, email, role, status
             FROM users
             WHERE LOWER(email) = ? AND status = 'active'
             LIMIT 1`,
            [normalizeLogin(preferredActorLogin)],
        );
        if (preferred) {
            return { id: preferred.id, role: preferred.role, email: preferred.email, fullName: preferred.full_name };
        }
    }

    const fallback = await dbGet(
        `SELECT id, full_name, email, role, status
         FROM users
         WHERE role = ? AND status = 'active'
         ORDER BY id DESC
         LIMIT 1`,
        [ROLE.HEAD],
    );

    if (!fallback) {
        throw new Error('Không tìm thấy Head active để cấp phát user STAFF.');
    }

    return { id: fallback.id, role: fallback.role, email: fallback.email, fullName: fallback.full_name };
}

async function getDistinctCssNames() {
    const rows = await dbAll(
        `SELECT DISTINCT TRIM(css) AS css
         FROM dashboard_data
         WHERE TRIM(COALESCE(css, '')) <> ''
         ORDER BY css COLLATE NOCASE ASC`,
    );
    return rows.map(row => String(row.css || '').trim()).filter(Boolean);
}

async function getExistingUsers() {
    const rows = await dbAll(
        `SELECT id, full_name, email, role, status, css_scope, reports_to_user_id
         FROM users
         ORDER BY id ASC`,
    );
    return rows;
}

function buildPlan(cssName, existingUser) {
    const username = normalizeLogin(cssName);
    const password = buildPassword(cssName);
    const common = {
        cssName,
        username,
        password,
        role: ROLE.STAFF,
        cssScope: cssName,
    };

    if (!existingUser) {
        return {
            ...common,
            action: 'create',
            note: 'Tạo mới STAFF dưới Head hiện tại.',
        };
    }

    if (existingUser.role !== ROLE.STAFF) {
        return {
            ...common,
            action: 'conflict',
            note: `Đã tồn tại user cùng username với role ${existingUser.role}, bỏ qua để tránh ghi đè.`,
            existingUser,
        };
    }

    return {
        ...common,
        action: 'update',
        note: 'Căn chỉnh lại fullName/status/cssScope và reset password theo quy tắc.',
        existingUser,
    };
}

async function applyPlan(actor, plan) {
    if (plan.action === 'create') {
        const user = await createUser(actor, {
            fullName: plan.cssName,
            email: plan.username,
            password: plan.password,
            role: ROLE.STAFF,
            status: 'active',
            cssScope: plan.cssScope,
        });
        return { type: 'created', user };
    }

    if (plan.action === 'update') {
        const user = await updateUser(actor, plan.existingUser.id, {
            fullName: plan.cssName,
            role: ROLE.STAFF,
            status: 'active',
            cssScope: plan.cssScope,
            password: plan.password,
        });
        return { type: 'updated', user };
    }

    return { type: 'skipped', user: plan.existingUser || null };
}

async function main() {
    const actor = await getProvisionActor();
    const cssNames = await getDistinctCssNames();
    const existingUsers = await getExistingUsers();
    const existingByLogin = new Map(existingUsers.map(user => [normalizeLogin(user.email), user]));
    const plans = cssNames.map(cssName => buildPlan(cssName, existingByLogin.get(normalizeLogin(cssName))));

    const summary = {
        mode: APPLY ? 'apply' : 'dry-run',
        actor,
        cssCount: cssNames.length,
        createCount: plans.filter(item => item.action === 'create').length,
        updateCount: plans.filter(item => item.action === 'update').length,
        conflictCount: plans.filter(item => item.action === 'conflict').length,
    };

    console.log(JSON.stringify({ summary, plans }, null, 2));

    if (!APPLY) return;

    const results = [];
    for (const plan of plans) {
        if (plan.action === 'conflict') {
            results.push({ username: plan.username, result: 'skipped-conflict', note: plan.note });
            continue;
        }
        const result = await applyPlan(actor, plan);
        results.push({
            username: plan.username,
            result: result.type,
            userId: result.user?.id || null,
            role: result.user?.role || null,
            cssScope: result.user?.cssScope || plan.cssScope,
        });
    }

    console.log(JSON.stringify({ applied: true, actor, results }, null, 2));
}

main().catch(error => {
    console.error('[provision-css-staff-users] Failed:', error.message);
    process.exit(1);
});
