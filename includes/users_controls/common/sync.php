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

function school_sync_parent_student_links($user_id) {
    if (!function_exists('carbon_get_user_meta') || !function_exists('carbon_set_user_meta')) return;

    $user = get_user_by('id', $user_id);
    if (!$user) return;

    if (in_array('parent', (array) $user->roles, true)) {
        if (school_sync_lock('parent_sync_' . $user_id)) return;
        school_sync_lock('parent_sync_' . $user_id, true);

        $children_assoc = carbon_get_user_meta($user_id, 'parent_children');
        $children_ids   = school_cf_assoc_to_user_ids($children_assoc);

        foreach ($children_ids as $student_id) {
            $student = get_user_by('id', $student_id);
            if (!$student || !in_array('student', (array) $student->roles, true)) continue;

            $parents_assoc = carbon_get_user_meta($student_id, 'student_parents');
            $parents_ids   = school_cf_assoc_to_user_ids($parents_assoc);

            if (!in_array((int)$user_id, $parents_ids, true)) {
                $parents_ids[] = (int)$user_id;

                $new_assoc = array_map(fn($id) => [
                    'type' => 'user',
                    'subtype' => 'parent',
                    'id' => (int)$id
                ], $parents_ids);

                carbon_set_user_meta($student_id, 'student_parents', $new_assoc);
            }
        }

        school_sync_lock('parent_sync_' . $user_id, false);
    }

    if (in_array('student', (array) $user->roles, true)) {
        if (school_sync_lock('student_sync_' . $user_id)) return;
        school_sync_lock('student_sync_' . $user_id, true);

        $parents_assoc = carbon_get_user_meta($user_id, 'student_parents');
        $parents_ids   = school_cf_assoc_to_user_ids($parents_assoc);

        foreach ($parents_ids as $parent_id) {
            $parent = get_user_by('id', $parent_id);
            if (!$parent || !in_array('parent', (array) $parent->roles, true)) continue;

            $children_assoc = carbon_get_user_meta($parent_id, 'parent_children');
            $children_ids   = school_cf_assoc_to_user_ids($children_assoc);

            if (!in_array((int)$user_id, $children_ids, true)) {
                $children_ids[] = (int)$user_id;

                $new_assoc = array_map(fn($id) => [
                    'type' => 'user',
                    'subtype' => 'student',
                    'id' => (int)$id
                ], $children_ids);

                carbon_set_user_meta($parent_id, 'parent_children', $new_assoc);
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
