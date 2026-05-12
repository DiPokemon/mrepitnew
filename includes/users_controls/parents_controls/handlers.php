<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_school_parent_create', function () {
    if (!school_admin_can_manage_users()) wp_die('РќРµС‚ РґРѕСЃС‚СѓРїР°');
    check_admin_referer('school_parent_create');

    $user_login   = sanitize_user(wp_unslash($_POST['user_login'] ?? ''));
    $user_email   = sanitize_email(wp_unslash($_POST['user_email'] ?? ''));
    $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $user_pass    = (string)($_POST['user_pass'] ?? '');

    if (!$user_login || !$user_email) {
        wp_redirect(add_query_arg(['page'=>'school-parent-add','error'=>rawurlencode('Р—Р°РїРѕР»РЅРёС‚Рµ Р»РѕРіРёРЅ Рё email')], admin_url('admin.php')));
        exit;
    }

    $userdata = [
        'user_login'   => $user_login,
        'user_email'   => $user_email,
        'display_name' => $display_name ?: $user_login,
        'role'         => 'parent',
    ];
    if (!empty($user_pass)) $userdata['user_pass'] = $user_pass;

    $new_id = wp_insert_user($userdata);
    if (is_wp_error($new_id)) {
        wp_redirect(add_query_arg(['page'=>'school-parent-add','error'=>rawurlencode($new_id->get_error_message())], admin_url('admin.php')));
        exit;
    }
    school_assign_role_on_current_blog((int)$new_id, 'parent');

    update_user_meta($new_id, 'parent_phone', school_normalize_ru_phone(sanitize_text_field(wp_unslash($_POST['parent_phone'] ?? ''))));
    update_user_meta($new_id, 'parent_whatsapp', sanitize_text_field(wp_unslash($_POST['parent_whatsapp'] ?? '')));
    update_user_meta($new_id, 'parent_telegram', sanitize_text_field(wp_unslash($_POST['parent_telegram'] ?? '')));
    update_user_meta($new_id, 'parent_timezone', school_normalize_msk_offset(wp_unslash($_POST['parent_timezone'] ?? '0')));
    update_user_meta($new_id, 'parent_tg_opt_in', isset($_POST['parent_tg_opt_in']) ? 'yes' : '');

    // РџСЂРёРІСЏР·РєР° РґРµС‚РµР№ (РµСЃР»Рё РјРµРЅРµРґР¶РµСЂ/Р°РґРјРёРЅ)
    if (school_admin_can_link_family() && function_exists('carbon_set_user_meta')) {
        $children = isset($_POST['parent_children']) ? array_map('intval', (array)$_POST['parent_children']) : [];
        $assoc = array_map(fn($id) => ['type'=>'user','subtype'=>'student','id'=>(int)$id], $children);
        carbon_set_user_meta($new_id, 'parent_children', $assoc);

        // СЃРёРЅС…СЂРѕРЅРёР·Р°С†РёСЏ РІ student_parents
        school_sync_parent_student_links($new_id);
    }

    wp_redirect(add_query_arg(['page'=>'school-parent-edit','user_id'=>$new_id,'created'=>1], admin_url('admin.php')));
    exit;
});

add_action('admin_post_school_parent_update', function () {
    if (!school_admin_can_manage_users()) wp_die('РќРµС‚ РґРѕСЃС‚СѓРїР°');
    check_admin_referer('school_parent_update');

    $user_id = (int)($_POST['user_id'] ?? 0);
    $u = get_user_by('id', $user_id);
    if (!$u || !school_user_has_role($u, 'parent')) wp_die('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ');

    $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $user_email   = sanitize_email(wp_unslash($_POST['user_email'] ?? ''));
    $user_pass    = (string)($_POST['user_pass'] ?? '');

    $userdata = [
        'ID'           => $user_id,
        'display_name' => $display_name ?: $u->display_name,
        'user_email'   => $user_email ?: $u->user_email,
    ];
    if (!empty($user_pass)) $userdata['user_pass'] = $user_pass;

    $res = wp_update_user($userdata);
    if (is_wp_error($res)) {
        wp_redirect(add_query_arg(['page'=>'school-parent-edit','user_id'=>$user_id,'error'=>rawurlencode($res->get_error_message())], admin_url('admin.php')));
        exit;
    }
    school_assign_role_on_current_blog($user_id, 'parent');

    update_user_meta($user_id, 'parent_phone', school_normalize_ru_phone(sanitize_text_field(wp_unslash($_POST['parent_phone'] ?? ''))));
    update_user_meta($user_id, 'parent_whatsapp', sanitize_text_field(wp_unslash($_POST['parent_whatsapp'] ?? '')));
    update_user_meta($user_id, 'parent_telegram', sanitize_text_field(wp_unslash($_POST['parent_telegram'] ?? '')));
    update_user_meta($user_id, 'parent_timezone', school_normalize_msk_offset(wp_unslash($_POST['parent_timezone'] ?? '0')));
    update_user_meta($user_id, 'parent_tg_opt_in', isset($_POST['parent_tg_opt_in']) ? 'yes' : '');

    if (school_admin_can_link_family() && function_exists('carbon_set_user_meta')) {
        $children = isset($_POST['parent_children']) ? array_map('intval', (array)$_POST['parent_children']) : [];
        $assoc = array_map(fn($id) => ['type'=>'user','subtype'=>'student','id'=>(int)$id], $children);
        carbon_set_user_meta($user_id, 'parent_children', $assoc);

        school_sync_parent_student_links($user_id);
    }

    wp_redirect(add_query_arg(['page'=>'school-parent-edit','user_id'=>$user_id,'updated'=>1], admin_url('admin.php')));
    exit;
});
