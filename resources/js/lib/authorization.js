import { usePage } from '@inertiajs/react';

export function useAuthorization() {
    const user = usePage().props.auth?.user;
    const permissions = user?.permissions || [];
    const isSuperAdmin = user?.roles?.some((role) => ['super_admin', 'super-admin'].includes(role.slug));

    return {
        user,
        can: (permission) => isSuperAdmin || permissions.includes(permission),
        hasRole: (role) => user?.roles?.some((item) => item.slug === role) || false,
    };
}
