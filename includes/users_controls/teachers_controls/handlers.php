<?php
if (!defined('ABSPATH')) exit;

add_action('admin_post_school_teacher_create', function () {
    if (!school_admin_can_manage_users()) wp_die('РќРµС‚ РґРѕСЃС‚СѓРїР°');
    check_admin_referer('school_teacher_create');

    $user_login   = sanitize_user(wp_unslash($_POST['user_login'] ?? ''));
    $user_email   = sanitize_email(wp_unslash($_POST['user_email'] ?? ''));
    $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));
    $user_pass    = (string)($_POST['user_pass'] ?? '');

    if (!$user_login || !$user_email) {
        wp_redirect(add_query_arg(['page'=>'school-teacher-add','error'=>rawurlencode('Р—Р°РїРѕР»РЅРёС‚Рµ Р»РѕРіРёРЅ Рё email')], admin_url('admin.php')));
        exit;
    }

    $userdata = [
        'user_login'   => $user_login,
        'user_email'   => $user_email,
        'display_name' => $display_name ?: $user_login,
        'role'         => 'teacher',
    ];
    if (!empty($user_pass)) $userdata['user_pass'] = $user_pass;

    $new_id = wp_insert_user($userdata);
    if (is_wp_error($new_id)) {
        wp_redirect(add_query_arg(['page'=>'school-teacher-add','error'=>rawurlencode($new_id->get_error_message())], admin_url('admin.php')));
        exit;
    }
    school_assign_role_on_current_blog((int)$new_id, 'teacher');

    update_user_meta($new_id, 'teacher_phone', school_normalize_ru_phone(sanitize_text_field(wp_unslash($_POST['teacher_phone'] ?? ''))));
    update_user_meta($new_id, 'teacher_whatsapp', sanitize_text_field(wp_unslash($_POST['teacher_whatsapp'] ?? '')));
    update_user_meta($new_id, 'teacher_telegram', sanitize_text_field(wp_unslash($_POST['teacher_telegram'] ?? '')));
    update_user_meta($new_id, 'teacher_timezone', school_normalize_msk_offset(wp_unslash($_POST['teacher_timezone'] ?? '0')));
    update_user_meta($new_id, 'teacher_telegram_chat_id', sanitize_text_field(wp_unslash($_POST['teacher_telegram_chat_id'] ?? '')));
    update_user_meta($new_id, 'teacher_tg_opt_in', isset($_POST['teacher_tg_opt_in']) ? 'yes' : '');

    $profile_post_id = isset($_POST['teacher_profile_post_id']) ? absint($_POST['teacher_profile_post_id']) : 0;
    if ($profile_post_id && get_post_type($profile_post_id) === 'teacher') {
        school_link_teacher_user_post($new_id, $profile_post_id);
    } else {
        school_unlink_teacher_user_by_user($new_id);
    }

    wp_redirect(add_query_arg(['page'=>'school-teacher-edit','user_id'=>$new_id,'created'=>1], admin_url('admin.php')));
    exit;
});

add_action('admin_post_school_teacher_update', function () {
    if (!school_admin_can_manage_users()) wp_die('РќРµС‚ РґРѕСЃС‚СѓРїР°');
    check_admin_referer('school_teacher_update');

    $user_id = (int)($_POST['user_id'] ?? 0);
    $u = get_user_by('id', $user_id);
    if (!$u || !school_user_has_role($u, 'teacher')) wp_die('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ');

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
        wp_redirect(add_query_arg(['page'=>'school-teacher-edit','user_id'=>$user_id,'error'=>rawurlencode($res->get_error_message())], admin_url('admin.php')));
        exit;
    }
    school_assign_role_on_current_blog($user_id, 'teacher');

    update_user_meta($user_id, 'teacher_phone', school_normalize_ru_phone(sanitize_text_field(wp_unslash($_POST['teacher_phone'] ?? ''))));
    update_user_meta($user_id, 'teacher_whatsapp', sanitize_text_field(wp_unslash($_POST['teacher_whatsapp'] ?? '')));
    update_user_meta($user_id, 'teacher_telegram', sanitize_text_field(wp_unslash($_POST['teacher_telegram'] ?? '')));
    update_user_meta($user_id, 'teacher_timezone', school_normalize_msk_offset(wp_unslash($_POST['teacher_timezone'] ?? '0')));
    update_user_meta($user_id, 'teacher_telegram_chat_id', sanitize_text_field(wp_unslash($_POST['teacher_telegram_chat_id'] ?? '')));
    update_user_meta($user_id, 'teacher_tg_opt_in', isset($_POST['teacher_tg_opt_in']) ? 'yes' : '');

    $profile_post_id = isset($_POST['teacher_profile_post_id']) ? absint($_POST['teacher_profile_post_id']) : 0;
    if ($profile_post_id && get_post_type($profile_post_id) === 'teacher') {
        school_link_teacher_user_post($user_id, $profile_post_id);
    } else {
        school_unlink_teacher_user_by_user($user_id);
    }

    wp_redirect(add_query_arg(['page'=>'school-teacher-edit','user_id'=>$user_id,'updated'=>1], admin_url('admin.php')));
    exit;
});

add_action('admin_post_school_teacher_create_profile', function () {
    if (!school_admin_can_manage_users()) wp_die('РќРµС‚ РґРѕСЃС‚СѓРїР°');

    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    check_admin_referer('school_teacher_create_profile_' . $user_id);

    $u = get_user_by('id', $user_id);
    if (!$u || !school_user_has_role($u, 'teacher')) wp_die('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ');

    $title = $u->display_name ?: $u->user_login;
    $post_id = wp_insert_post([
        'post_type'   => 'teacher',
        'post_title'  => $title,
        'post_status' => 'draft',
    ], true);

    if (is_wp_error($post_id)) {
        wp_redirect(add_query_arg([
            'page' => 'school-teacher-edit',
            'user_id' => $user_id,
            'error' => rawurlencode($post_id->get_error_message()),
        ], admin_url('admin.php')));
        exit;
    }

    school_link_teacher_user_post($user_id, (int)$post_id);

    $edit_url = admin_url('post.php?post=' . (int)$post_id . '&action=edit');
    wp_redirect($edit_url);
    exit;
});
