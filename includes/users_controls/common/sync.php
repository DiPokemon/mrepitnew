<?php
if (!defined('ABSPATH')) exit;

function school_cf_assoc_to_user_ids($assoc_value): array {
    if (empty($assoc_value) || !is_array($assoc_value)) return [];
    $ids = [];
    foreach ($assoc_value as $row) {
        if (is_array($row) && isset($row['id'])) {
            $ids[] = (int) $row['id'];
        } elseif (is_numeric($row)) {
            $ids[] = (int) $row;
        }
    }
    return array_values(array_unique(array_filter($ids)));
}

function school_user_ids_to_cf_assoc(array $user_ids, string $subtype): array {
    $assoc = [];

    foreach (array_values(array_unique(array_map('absint', $user_ids))) as $user_id) {
        if (!$user_id) {
            continue;
        }

        $assoc[] = [
            'type' => 'user',
            'subtype' => $subtype,
            'id' => $user_id,
        ];
    }

    return $assoc;
}

function school_get_user_ids_by_role(string $role): array {
    $users = get_users([
        'role' => $role,
        'fields' => 'ID',
        'number' => 9999,
    ]);

    return array_values(array_unique(array_map('absint', (array) $users)));
}

function school_sync_lock($key, $set = null) {
    static $locks = [];
    if ($set === null) return !empty($locks[$key]);
    $locks[$key] = (bool) $set;
}

add_action('profile_update', function ($user_id) {
    school_sync_parent_student_links($user_id);
}, 20, 1);

add_action('user_register', function ($user_id) {
    school_sync_parent_student_links($user_id);
}, 20, 1);

function school_remove_parent_from_unlinked_students(int $parent_id, array $linked_student_ids): void {
    $linked_student_ids = array_values(array_unique(array_map('absint', $linked_student_ids)));

    foreach (school_get_user_ids_by_role('student') as $student_id) {
        if (in_array($student_id, $linked_student_ids, true)) {
            continue;
        }

        $parents_assoc = carbon_get_user_meta($student_id, 'student_parents');
        $parents_ids = school_cf_assoc_to_user_ids($parents_assoc);

        if (!in_array($parent_id, $parents_ids, true)) {
            continue;
        }

        $parents_ids = array_values(array_diff($parents_ids, [$parent_id]));
        carbon_set_user_meta($student_id, 'student_parents', school_user_ids_to_cf_assoc($parents_ids, 'parent'));
    }
}

function school_remove_student_from_unlinked_parents(int $student_id, array $linked_parent_ids): void {
    $linked_parent_ids = array_values(array_unique(array_map('absint', $linked_parent_ids)));

    foreach (school_get_user_ids_by_role('parent') as $parent_id) {
        if (in_array($parent_id, $linked_parent_ids, true)) {
            continue;
        }

        $children_assoc = carbon_get_user_meta($parent_id, 'parent_children');
        $children_ids = school_cf_assoc_to_user_ids($children_assoc);

        if (!in_array($student_id, $children_ids, true)) {
            continue;
        }

        $children_ids = array_values(array_diff($children_ids, [$student_id]));
        carbon_set_user_meta($parent_id, 'parent_children', school_user_ids_to_cf_assoc($children_ids, 'student'));
    }
}

function school_sync_parent_student_links($user_id) {
    if (!function_exists('carbon_get_user_meta') || !function_exists('carbon_set_user_meta')) return;

    $user = get_user_by('id', $user_id);
    if (!$user) return;

    if (in_array('parent', (array) $user->roles, true)) {
        if (school_sync_lock('parent_sync_' . $user_id)) return;
        school_sync_lock('parent_sync_' . $user_id, true);

        $children_assoc = carbon_get_user_meta($user_id, 'parent_children');
        $children_ids   = school_cf_assoc_to_user_ids($children_assoc);
        school_remove_parent_from_unlinked_students((int)$user_id, $children_ids);

        foreach ($children_ids as $student_id) {
            $student = get_user_by('id', $student_id);
            if (!$student || !in_array('student', (array) $student->roles, true)) continue;

            $parents_assoc = carbon_get_user_meta($student_id, 'student_parents');
            $parents_ids   = school_cf_assoc_to_user_ids($parents_assoc);

            if (!in_array((int)$user_id, $parents_ids, true)) {
                $parents_ids[] = (int)$user_id;
                carbon_set_user_meta($student_id, 'student_parents', school_user_ids_to_cf_assoc($parents_ids, 'parent'));
            }
        }

        school_sync_lock('parent_sync_' . $user_id, false);
    }

    if (in_array('student', (array) $user->roles, true)) {
        if (school_sync_lock('student_sync_' . $user_id)) return;
        school_sync_lock('student_sync_' . $user_id, true);

        $parents_assoc = carbon_get_user_meta($user_id, 'student_parents');
        $parents_ids   = school_cf_assoc_to_user_ids($parents_assoc);
        school_remove_student_from_unlinked_parents((int)$user_id, $parents_ids);

        foreach ($parents_ids as $parent_id) {
            $parent = get_user_by('id', $parent_id);
            if (!$parent || !in_array('parent', (array) $parent->roles, true)) continue;

            $children_assoc = carbon_get_user_meta($parent_id, 'parent_children');
            $children_ids   = school_cf_assoc_to_user_ids($children_assoc);

            if (!in_array((int)$user_id, $children_ids, true)) {
                $children_ids[] = (int)$user_id;
                carbon_set_user_meta($parent_id, 'parent_children', school_user_ids_to_cf_assoc($children_ids, 'student'));
            }
        }

        school_sync_lock('student_sync_' . $user_id, false);
    }
}

function school_parent_has_child($parent_id, $student_id): bool {
    if (!function_exists('carbon_get_user_meta')) return false;
    $assoc = carbon_get_user_meta($parent_id, 'parent_children');
    $ids = school_cf_assoc_to_user_ids($assoc);
    return in_array((int)$student_id, $ids, true);
}
