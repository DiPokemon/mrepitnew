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

function school_filter_user_ids_by_role(array $user_ids, string $role): array {
    $filtered = [];

    foreach (array_unique(array_map('absint', $user_ids)) as $user_id) {
        if (!$user_id) {
            continue;
        }

        $user = get_user_by('id', $user_id);
        if ($user && school_user_has_role($user, $role)) {
            $filtered[] = $user_id;
        }
    }

    return $filtered;
}

function school_assign_role_on_current_blog(int $user_id, string $role): void {
    $user_id = absint($user_id);
    if (!$user_id || $role === '') return;

    if (is_multisite()) {
        add_user_to_blog(get_current_blog_id(), $user_id, $role);
        return;
    }

    $u = new WP_User($user_id);
    if ($u->exists()) {
        $u->set_role($role);
    }
}

function school_msk_offsets_options(): array {
    $options = [];
    for ($i = -2; $i <= 9; $i++) {
        $key = (string)$i;
        if ($i === 0) {
            $options[$key] = 'MSK+0';
        } elseif ($i > 0) {
            $options[$key] = 'MSK+' . $i;
        } else {
            $options[$key] = 'MSK' . $i;
        }
    }
    return $options;
}

function school_normalize_msk_offset($raw): string {
    $n = (int)$raw;
    if ($n < -2) $n = -2;
    if ($n > 9) $n = 9;
    return (string)$n;
}

function school_normalize_ru_phone(string $raw): string {
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === null) $digits = '';

    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    } elseif (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }

    if (strlen($digits) === 11 && $digits[0] === '7') {
        return '+' . $digits;
    }

    return $raw !== '' ? trim($raw) : '';
}
