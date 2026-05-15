<?php
if (!defined('ABSPATH')) exit;

add_action('after_switch_theme', function () {
    add_role('teacher', 'РџСЂРµРїРѕРґР°РІР°С‚РµР»СЊ', ['read' => true]);
    add_role('student', 'РЈС‡РµРЅРёРє', ['read' => true]);
    add_role('parent',  'Р РѕРґРёС‚РµР»СЊ', ['read' => true]);

    add_role('manager', 'РњРµРЅРµРґР¶РµСЂ', [
        'read'           => true,
        'list_users'     => true,
        'create_users'   => true,
        'edit_users'     => true,
        'promote_users'  => true,
        'delete_users'   => false,

        'school_manage_users' => true,
        'school_link_family'  => true,
    ]);
});

// Safety net: ensure custom roles exist even if code was added after theme activation.
add_action('init', function () {
    if (!get_role('teacher')) {
        add_role('teacher', 'Преподаватель', ['read' => true]);
    }
    if (!get_role('student')) {
        add_role('student', 'Ученик', ['read' => true]);
    }
    if (!get_role('parent')) {
        add_role('parent', 'Родитель', ['read' => true]);
    }
}, 1);
add_action('init', function () {
    $base_caps = [
        'list_users',
        'create_users',
        'edit_users',
        'promote_users',
        'school_manage_users',
        'school_link_family',
    ];

    $review_caps = [
        'edit_review', 'read_review', 'delete_review',
        'edit_reviews', 'edit_others_reviews', 'edit_published_reviews', 'edit_private_reviews',
        'publish_reviews', 'read_private_reviews',
        'delete_reviews', 'delete_private_reviews', 'delete_published_reviews', 'delete_others_reviews',
    ];

    $service_caps = [
        'edit_service', 'read_service', 'delete_service',
        'edit_services', 'edit_others_services', 'edit_published_services', 'edit_private_services',
        'publish_services', 'read_private_services',
        'delete_services', 'delete_private_services', 'delete_published_services', 'delete_others_services',
    ];

    $teacher_profile_caps = [
        'edit_teacher_profile', 'read_teacher_profile', 'delete_teacher_profile',
        'edit_teacher_profiles', 'edit_others_teacher_profiles', 'edit_published_teacher_profiles', 'edit_private_teacher_profiles',
        'publish_teacher_profiles', 'read_private_teacher_profiles',
        'delete_teacher_profiles', 'delete_private_teacher_profiles', 'delete_published_teacher_profiles', 'delete_others_teacher_profiles',
    ];

    $roles = [
        'administrator',
        'manager',
    ];

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if (!$role && $role_name === 'manager') {
            $role = add_role('manager', 'РњРµРЅРµРґР¶РµСЂ', ['read' => true]);
        }
        if (!$role) continue;

        foreach (array_merge($base_caps, $review_caps, $service_caps, $teacher_profile_caps) as $cap) {
            if (!$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }
});

/** РћРіСЂР°РЅРёС‡РёРІР°РµРј СЂРѕР»Рё РІ РІС‹РїР°РґР°СЋС‰РµРј СЃРїРёСЃРєРµ СЂРѕР»РµР№ */
add_filter('editable_roles', function ($roles) {
    if (!current_user_can('school_manage_users') || school_is_admin()) {
        return $roles;
    }

    $allowed = ['teacher', 'student', 'parent'];

    foreach ($roles as $role_key => $role_data) {
        if (!in_array($role_key, $allowed, true)) {
            unset($roles[$role_key]);
        }
    }

    return $roles;
});

/** РњРµРЅРµРґР¶РµСЂ РЅРµ РјРѕР¶РµС‚ СЂРµРґР°РєС‚РёСЂРѕРІР°С‚СЊ Р°РґРјРёРЅРѕРІ/РјРµРЅРµРґР¶РµСЂРѕРІ Рё РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ РІРЅРµ teacher/student/parent */
add_filter('map_meta_cap', function ($caps, $cap, $user_id, $args) {

    if (!in_array($cap, ['edit_user', 'remove_user', 'delete_user', 'promote_user'], true)) {
        return $caps;
    }

    $current_user = get_user_by('id', $user_id);
    if (!$current_user) return $caps;

    if (user_can($current_user, 'administrator')) {
        return $caps;
    }

    if (!user_can($current_user, 'school_manage_users')) {
        return $caps;
    }

    $target_user_id = isset($args[0]) ? (int)$args[0] : 0;
    if (!$target_user_id) return ['do_not_allow'];

    $target = get_user_by('id', $target_user_id);
    if (!$target) return ['do_not_allow'];

    if (in_array('administrator', (array)$target->roles, true) || in_array('manager', (array)$target->roles, true)) {
        return ['do_not_allow'];
    }

    $allowed = ['teacher', 'student', 'parent'];
    $ok = false;
    foreach ((array)$target->roles as $r) {
        if (in_array($r, $allowed, true)) { $ok = true; break; }
    }
    if (!$ok) return ['do_not_allow'];

    return $caps;
}, 10, 4);
