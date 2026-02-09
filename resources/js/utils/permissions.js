export const resolveRole = (user) => {
    if (!user) return 'writer';
    if (user.role) return user.role;
    return user.is_superadmin ? 'superadmin' : 'tenant-admin';
};

export const isSuperadmin = (user) => resolveRole(user) === 'superadmin';

export const canManageTenants = (user) => isSuperadmin(user);

export const canManageThemes = (user) => isSuperadmin(user);

export const canManageUsers = (user) => {
    const role = resolveRole(user);
    return role === 'superadmin' || role === 'tenant-admin';
};

export const canManageContent = (user) => {
    const role = resolveRole(user);
    return ['superadmin', 'tenant-admin', 'editor', 'writer'].includes(role);
};

export const canReviewContent = (user) => {
    const role = resolveRole(user);
    return ['superadmin', 'tenant-admin', 'editor'].includes(role);
};

export const canPublishContent = (user) => {
    const role = resolveRole(user);
    return ['superadmin', 'tenant-admin'].includes(role);
};

export const canDeleteContent = (user) => {
    const role = resolveRole(user);
    return ['superadmin', 'tenant-admin', 'editor'].includes(role);
};

export const canManageCategories = (user) => {
    const role = resolveRole(user);
    return ['superadmin', 'tenant-admin', 'editor'].includes(role);
};
