<?php
if (!defined('ABSPATH')) exit;

function school_parents_page_list() {
    if (!school_admin_can_manage_users()) wp_die('Нет доступа');

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Родители</h1> ';
    echo '<a class="page-title-action" href="'.esc_url(admin_url('admin.php?page=school-parent-add')).'">Добавить нового</a>';
    echo '<hr class="wp-header-end">';

    $table = new School_Parents_List_Table();
    $table->prepare_items();

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="school-parents">';
    $table->search_box('Поиск', 'school-parents-search');
    $table->display();
    echo '</form>';

    echo '</div>';
}

function school_parents_page_add() {
    school_parents_render_form('add');
}

function school_parents_page_edit() {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    school_parents_render_form('edit', $user_id);
}

function school_parents_render_form(string $mode, int $user_id = 0) {
    if (!school_admin_can_manage_users()) wp_die('Нет доступа');

    $is_edit = ($mode === 'edit');
    $u = null;

    if ($is_edit) {
        $u = get_user_by('id', $user_id);
        if (!$u || !school_user_has_role($u, 'parent')) wp_die('Пользователь не найден');
    }

    $title = $is_edit ? 'Редактировать родителя' : 'Добавить родителя';
    $action = $is_edit ? 'school_parent_update' : 'school_parent_create';

    $display_name = $u ? $u->display_name : '';
    $email = $u ? $u->user_email : '';
    $login = $u ? $u->user_login : '';

    $phone = $u ? get_user_meta($user_id, 'parent_phone', true) : '';
    $tz    = $u ? (get_user_meta($user_id, 'parent_timezone', true) ?: 'Europe/Tallinn') : 'Europe/Tallinn';
    $tg_opt= $u ? get_user_meta($user_id, 'parent_tg_opt_in', true) : 'yes';

    $can_link = school_admin_can_link_family();

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

    echo '<tr><th><label>Телефон</label></th><td><input class="regular-text" name="parent_phone" value="'.esc_attr($phone).'"></td></tr>';
    $tz_list = timezone_identifiers_list();
    echo '<tr><th><label>Часовой пояс</label></th><td>';
    echo '<select name="parent_timezone" class="regular-text">';
    foreach ($tz_list as $zone) {
    echo '<option value="'.esc_attr($zone).'" '.selected($tz,$zone,false).'>'.esc_html($zone).'</option>';
    }
    echo '</select>';
    echo '</td></tr>';


    echo '<tr><th><label>Telegram уведомления</label></th><td><label><input type="checkbox" name="parent_tg_opt_in" value="yes" '.checked($tg_opt,'yes',false).'> Включены</label></td></tr>';

    // Привязка детей (только менеджер/админ)
    if ($can_link && function_exists('carbon_get_user_meta')) {
        $selected = $is_edit ? school_cf_assoc_to_user_ids(carbon_get_user_meta($user_id, 'parent_children')) : [];
        $students = get_users(['role'=>'student','orderby'=>'display_name','order'=>'ASC','number'=>9999]);

        echo '<tr><th><label>Дети (ученики)</label></th><td>';
        echo '<select name="parent_children[]" multiple size="8" style="min-width:340px;">';
        foreach ($students as $s) {
            $sel = in_array((int)$s->ID, $selected, true) ? 'selected' : '';
            echo '<option value="'.(int)$s->ID.'" '.$sel.'>'.esc_html($s->display_name.' ('.$s->user_email.')').'</option>';
        }
        echo '</select>';
        echo '<p class="description">Привязку может менять только менеджер или администратор.</p>';
        echo '</td></tr>';
    }

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
})();
</script>';

}
