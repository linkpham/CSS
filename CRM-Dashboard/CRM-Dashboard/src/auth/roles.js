const ROLE = {
    HEAD: 'HEAD',
    CSS_MANAGER: 'CSS_MANAGER',
    CSS_TEAM_LEADER: 'CSS_TEAM_LEADER',
    STAFF: 'STAFF',
};

const ROLE_LABELS = {
    [ROLE.HEAD]: 'Head',
    [ROLE.CSS_MANAGER]: 'CSS Manager',
    [ROLE.CSS_TEAM_LEADER]: 'CSS Team Leader',
    [ROLE.STAFF]: 'Staff',
};

const ROLE_RANK = {
    [ROLE.HEAD]: 4,
    [ROLE.CSS_MANAGER]: 3,
    [ROLE.CSS_TEAM_LEADER]: 2,
    [ROLE.STAFF]: 1,
};

function isValidRole(role) {
    return Object.values(ROLE).includes(role);
}

function canCreateRole(actorRole, targetRole) {
    if (!isValidRole(actorRole) || !isValidRole(targetRole)) return false;
    if (actorRole === ROLE.HEAD) return true;
    if (actorRole === ROLE.CSS_MANAGER) return [ROLE.CSS_TEAM_LEADER, ROLE.STAFF].includes(targetRole);
    if (actorRole === ROLE.CSS_TEAM_LEADER) return targetRole === ROLE.STAFF;
    return false;
}

function canAssignParentRole(parentRole, childRole) {
    return ROLE_RANK[parentRole] > ROLE_RANK[childRole];
}

function canManageRole(actorRole, targetRole) {
    if (!isValidRole(actorRole) || !isValidRole(targetRole)) return false;
    if (actorRole === ROLE.HEAD) return true;
    return ROLE_RANK[actorRole] > ROLE_RANK[targetRole];
}

function isLeadershipRole(role) {
    return [ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER].includes(role);
}

module.exports = {
    ROLE,
    ROLE_LABELS,
    ROLE_RANK,
    isValidRole,
    canCreateRole,
    canAssignParentRole,
    canManageRole,
    isLeadershipRole,
};
