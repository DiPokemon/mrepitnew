<?php
if (!defined('ABSPATH')) exit;

function school_is_admin(): bool {
    return current_user_can('manage_options') || is_super_admin();
}

function school_admin_can_manage_users(): bool {
    return school_is_admin() || current_user_can('school_manage_users');
}

function school_admin_can_link_family(): bool {
    return school_is_admin() || current_user_can('school_link_family');
}

function school_allowed_roles_map(): array {
    return [
        'parent'  => 'Родители',
        'student' => 'Ученики',
        'teacher' => 'Учителя',
    ];
}

function school_role_label(string $role): string {
    $map = school_allowed_roles_map();
    return $map[$role] ?? $role;
}

function school_user_has_role(WP_User $u, string $role): bool {
    return in_array($role, (array)$u->roles, true);
}
