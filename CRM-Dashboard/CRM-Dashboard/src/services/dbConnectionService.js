const fs = require('fs');
const path = require('path');

function parseDeployDefaults() {
    const deployScriptPath = path.resolve(__dirname, '../../../../zeus/core/DEPLOY-SERVER.sh');
    const defaults = {
        host: '',
        port: 3306,
        database: '',
        username: '',
        source: 'process.env',
        passwordConfigured: Boolean(process.env.ZEUS_DB_PASSWORD || process.env.DB_PASSWORD),
    };

    if (!fs.existsSync(deployScriptPath)) return defaults;

    const script = fs.readFileSync(deployScriptPath, 'utf8');
    const extract = (name) => {
        const match = script.match(new RegExp(`${name}_VAL="\\$\\{${name}:-([^}]*)\\}"`));
        return match ? String(match[1]).trim() : '';
    };

    return {
        host: extract('ZEUS_DB_HOST') || defaults.host,
        port: Number(extract('ZEUS_DB_PORT') || defaults.port),
        database: extract('ZEUS_DB_DATABASE') || defaults.database,
        username: extract('ZEUS_DB_USERNAME') || defaults.username,
        source: 'zeus/core/DEPLOY-SERVER.sh',
        passwordConfigured: defaults.passwordConfigured,
    };
}

function getZeusDbConnectionDefaults() {
    const deployDefaults = parseDeployDefaults();
    return {
        host: process.env.ZEUS_DB_HOST || process.env.DB_HOST || deployDefaults.host,
        port: Number(process.env.ZEUS_DB_PORT || process.env.DB_PORT || deployDefaults.port || 3306),
        database: process.env.ZEUS_DB_DATABASE || process.env.DB_DATABASE || deployDefaults.database,
        username: process.env.ZEUS_DB_USERNAME || process.env.DB_USERNAME || deployDefaults.username,
        source: (process.env.ZEUS_DB_HOST || process.env.DB_HOST) ? 'process.env' : deployDefaults.source,
        passwordConfigured: Boolean(process.env.ZEUS_DB_PASSWORD || process.env.DB_PASSWORD),
    };
}

function getZeusMysqlConfig(overrides = {}) {
    const defaults = getZeusDbConnectionDefaults();
    return {
        host: overrides.host || defaults.host,
        port: Number(overrides.port || defaults.port || 3306),
        database: overrides.database || defaults.database,
        user: overrides.username || defaults.username,
        password: overrides.password || process.env.ZEUS_DB_PASSWORD || process.env.DB_PASSWORD || '',
        charset: 'utf8mb4',
    };
}

async function testZeusDbConnection(payload = {}) {
    let mysql;
    try {
        mysql = require('mysql2/promise');
    } catch (error) {
        const err = new Error('mysql2 chưa được cài đặt trong app.');
        err.code = 'MYSQL2_MISSING';
        err.status = 500;
        throw err;
    }

    const config = getZeusMysqlConfig(payload);
    if (!config.host || !config.database || !config.user) {
        const err = new Error('Thiếu host/database/username để kiểm thử kết nối Zeus DB.');
        err.code = 'DB_CONFIG_INCOMPLETE';
        err.status = 400;
        throw err;
    }

    const startedAt = Date.now();
    const connection = await mysql.createConnection(config);
    try {
        const [rows] = await connection.query('SELECT CURRENT_USER() AS currentUser, VERSION() AS serverVersion, NOW() AS serverTime');
        return {
            ok: true,
            host: config.host,
            port: config.port,
            database: config.database,
            username: config.user,
            currentUser: rows?.[0]?.currentUser || config.user,
            serverVersion: rows?.[0]?.serverVersion || '',
            serverTime: rows?.[0]?.serverTime || '',
            durationMs: Date.now() - startedAt,
            source: getZeusDbConnectionDefaults().source,
        };
    } finally {
        await connection.end();
    }
}

module.exports = {
    getZeusDbConnectionDefaults,
    getZeusMysqlConfig,
    testZeusDbConnection,
};
