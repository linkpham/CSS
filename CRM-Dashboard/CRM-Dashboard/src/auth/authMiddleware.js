const { getUserByToken } = require('./authService');

function extractToken(req) {
    const auth = req.headers.authorization || '';
    if (auth.startsWith('Bearer ')) return auth.slice(7).trim();
    return null;
}

async function requireAuth(req, res, next) {
    try {
        const token = extractToken(req);
        if (!token) {
            return res.status(401).json({ error: 'Missing bearer token' });
        }
        const user = await getUserByToken(token);
        if (!user) {
            return res.status(401).json({ error: 'Invalid or expired session' });
        }
        req.authToken = token;
        req.user = user;
        next();
    } catch (error) {
        next(error);
    }
}

function requireAnyRole(roles = []) {
    const allowed = new Set(roles || []);
    return (req, res, next) => {
        if (!req.user) return res.status(401).json({ error: 'Unauthorized' });
        if (!allowed.has(req.user.role)) {
            return res.status(403).json({ error: 'Forbidden for current role' });
        }
        next();
    };
}

module.exports = {
    extractToken,
    requireAuth,
    requireAnyRole,
};
