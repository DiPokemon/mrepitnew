<?php
if (!defined('ABSPATH')) exit;

function school_teachers_page_list() {
    if (!school_admin_can_manage_users()) wp_die('Нет доступа');

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Учителя</h1> ';
    echo '<a class="page-title-action" href="'.esc_url(admin_url('admin.php?page=school-teacher-add')).'">Добавить нового</a>';
    echo '<hr class="wp-header-end">';

    $table = new School_Teachers_List_Table();
    $table->prepare_items();

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="school-teachers">';
    $table->search_box('Поиск', 'school-teachers-search');
    $table->display();
    echo '</form>';

    echo '</div>';
}

function school_teachers_page_add() {
    school_teachers_render_form('add');
}

function school_teachers_page_edit() {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    school_teachers_render_form('edit', $user_id);
}

function school_teachers_render_form(string $mode, int $user_id = 0) {
    if (!school_admin_can_manage_users()) wp_die('Нет доступа');

    $is_edit = ($mode === 'edit');
    $u = null;

    if ($is_edit) {
        $u = get_user_by('id', $user_id);
        if (!$u || !school_user_has_role($u, 'teacher')) wp_die('Пользователь не найден');
    }

    $title  = $is_edit ? 'Редактировать учителя' : 'Добавить учителя';
    $action = $is_edit ? 'school_teacher_update' : 'school_teacher_create';

    $display_name = $u ? $u->display_name : '';
    $email        = $u ? $u->user_email : '';
    $login        = $u ? $u->user_login : '';

    $phone = $u ? get_user_meta($user_id, 'teacher_phone', true) : '';
    $whatsapp = $u ? get_user_meta($user_id, 'teacher_whatsapp', true) : '';
    $telegram = $u ? get_user_meta($user_id, 'teacher_telegram', true) : '';
    $tz    = $u ? school_normalize_msk_offset(get_user_meta($user_id, 'teacher_timezone', true)) : '0';
    $chat  = $u ? get_user_meta($user_id, 'teacher_telegram_chat_id', true) : '';
    $tg_opt= $u ? (get_user_meta($user_id, 'teacher_tg_opt_in', true) ?: '') : 'yes';
    $linked_post_id = $u ? school_get_teacher_post_id_for_user($user_id) : 0;

    $teacher_posts = get_posts([
        'post_type'   => 'teacher',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);

    echo '<div class="wrap">';
    echo '<h1>'.esc_html($title).'</h1>';

    if (!empty($_GET['updated'])) echo '<div class="notice notice-success"><p>Сохранено.</p></div>';
    if (!empty($_GET['created'])) echo '<div class="notice notice-success"><p>Создано.</p></div>';
    if (!empty($_GET['error']))   echo '<div class="notice notice-error"><p>'.esc_html($_GET['error']).'</p></div>';

    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field($action);
    echo '<input type="hidden" name="action" value="'.esc_attr($action).'">';
    if ($is_edit) echo '<input type="hidden" name="user_id" value="'.(int)$user_id.'">';

    echo '<table class="form-table"><tbody>';

    echo '<tr><th><label>Имя (отображаемое)</label></th><td><input class="regular-text" name="display_name" value="'.esc_attr($display_name).'"></td></tr>';

    if (!$is_edit) {
        echo '<tr><th><label>Логин</label></th><td><input class="regular-text" name="user_login" required></td></tr>';
    } else {
        echo '<tr><th><label>Логин</label></th><td><code>'.esc_html($login).'</code></td></tr>';
    }

    echo '<tr><th><label>Email</label></th><td><input type="email" class="regular-text" name="user_email" value="'.esc_attr($email).'" required></td></tr>';

    echo '<tr><th><label>Пароль</label></th><td>
    <input type="password" class="regular-text" id="school_user_pass" name="user_pass" placeholder="'.($is_edit ? 'Оставьте пустым, чтобы не менять' : '').'">
    <button type="button" class="button" id="school_gen_pass">Сгенерировать</button>
    <p class="description">Пароль можно сгенерировать автоматически.</p>
    </td></tr>';

    echo '<tr><th><label>Телефон</label></th><td><input class="regular-text" name="teacher_phone" data-phone-mask="ru" placeholder="+7 (___) ___-__-__" value="'.esc_attr($phone).'"></td></tr>';
    echo '<tr><th><label>WhatsApp</label></th><td><input class="regular-text" name="teacher_whatsapp" placeholder="+7..., wa.me/... или ссылка" value="'.esc_attr($whatsapp).'"></td></tr>';
    echo '<tr><th><label>Telegram</label></th><td><input class="regular-text" name="teacher_telegram" placeholder="@username или https://t.me/..." value="'.esc_attr($telegram).'"></td></tr>';

    $tz_list = school_msk_offsets_options();
    echo '<tr><th><label>Часовой пояс</label></th><td>';
    echo '<select name="teacher_timezone" class="regular-text">';
    foreach ($tz_list as $zone_value => $zone_label) {
        echo '<option value="'.esc_attr($zone_value).'" '.selected($tz,$zone_value,false).'>'.esc_html($zone_label).'</option>';
    }
    echo '</select>';
    echo '<p class="description">Относительно московского времени (MSK).</p>';
    echo '</td></tr>';

    echo '<tr><th><label>Профиль на сайте</label></th><td>';
    echo '<select name="teacher_profile_post_id" class="regular-text">';
    echo '<option value="">— Не связан —</option>';
    foreach ($teacher_posts as $p) {
        $label = $p->post_title ?: ('#' . $p->ID);
        $busy_user = school_get_teacher_user_id_for_post($p->ID);
        if ($busy_user && $busy_user !== (int)$user_id) {
            $busy_name = get_userdata($busy_user);
            $label .= ' — связан';
            if ($busy_name) {
                $label .= ' (' . ($busy_name->display_name ?: $busy_name->user_login) . ')';
            }
        }
        $sel = selected($linked_post_id, $p->ID, false);
        echo '<option value="'.(int)$p->ID.'" '.$sel.'>'.esc_html($label).'</option>';
    }
    echo '</select>';

    if ($linked_post_id) {
        $edit_url = get_edit_post_link($linked_post_id, '');
        if ($edit_url) {
            echo '<p><a class="button button-secondary" href="'.esc_url($edit_url).'">Открыть профиль</a></p>';
        }
    } elseif ($is_edit) {
        $create_url = wp_nonce_url(
            admin_url('admin-post.php?action=school_teacher_create_profile&user_id='.(int)$user_id),
            'school_teacher_create_profile_'.$user_id
        );
        echo '<p><a class="button" href="'.esc_url($create_url).'">Создать профиль преподавателя</a></p>';
    }

    echo '<p class="description">Связь между пользователем и записью преподавателя для сайта.</p>';
    echo '</td></tr>';

    echo '<tr><th><label>Telegram chat ID</label></th><td><input class="regular-text" name="teacher_telegram_chat_id" value="'.esc_attr($chat).'">
          <p class="description">Обычно заполняется автоматически через привязку бота.</p></td></tr>';

    echo '<tr><th><label>Уведомления Telegram</label></th><td><label><input type="checkbox" name="teacher_tg_opt_in" value="yes" '.checked($tg_opt,'yes',false).'> Включены</label></td></tr>';

    echo '</tbody></table>';

    submit_button($is_edit ? 'Сохранить' : 'Создать');

    echo '</form>';
    echo '</div>';

    echo '<script>
(function(){
  const btn = document.getElementById("school_gen_pass");
  const input = document.getElementById("school_user_pass");
  if(!btn || !input) return;

  function gen(len){
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%";
    let out = "";
    for(let i=0;i<len;i++) out += chars[Math.floor(Math.random()*chars.length)];
    return out;
  }

  btn.addEventListener("click", function(){
    const p = gen(12);
    input.type = "text";
    input.value = p;
    input.focus();
    input.select();
  });

  function maskRuPhone(value){
    const d = (value || "").replace(/\D+/g, "");
    let src = d;
    if (src.length === 11 && src[0] === "8") src = "7" + src.slice(1);
    if (src.length === 10) src = "7" + src;
    if (src.length && src[0] !== "7") src = "7" + src;
    src = src.slice(0, 11);

    let out = "+7";
    if (src.length > 1) out += " (" + src.slice(1, 4);
    if (src.length >= 4) out += ")";
    if (src.length > 4) out += " " + src.slice(4, 7);
    if (src.length > 7) out += "-" + src.slice(7, 9);
    if (src.length > 9) out += "-" + src.slice(9, 11);
    return out;
  }

  document.querySelectorAll("input[data-phone-mask=\"ru\"]").forEach(function(el){
    el.addEventListener("input", function(){ el.value = maskRuPhone(el.value); });
    el.addEventListener("blur", function(){ el.value = maskRuPhone(el.value); });
    if (el.value) el.value = maskRuPhone(el.value);
  });
})();
</script>';
}
