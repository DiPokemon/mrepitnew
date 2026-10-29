<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {

    if (!school_admin_can_manage_users()) return;

    // базовый cap для наших разделов
    $cap_list = 'school_manage_users';   // можно заменить на 'list_users'
    $cap_add  = 'create_users';
    $cap_edit = 'edit_users';

    // Главный раздел "Школа" + дашборд
    add_menu_page('Школа', 'Школа', $cap_list, 'school-dashboard', 'school_dashboard_page', 'dashicons-welcome-learn-more', 25);
    add_submenu_page('school-dashboard', 'Дашборд', 'Дашборд', $cap_list, 'school-dashboard', 'school_dashboard_page');

    // Пользователи
    add_submenu_page('school-dashboard', 'Учителя', 'Учителя', $cap_list, 'school-teachers', 'school_teachers_page_list');
    add_submenu_page('school-dashboard', 'Ученики', 'Ученики', $cap_list, 'school-students', 'school_students_page_list');
    add_submenu_page('school-dashboard', 'Родители', 'Родители', $cap_list, 'school-parents', 'school_parents_page_list');

    // Контент
    add_submenu_page('school-dashboard', 'Отзывы', 'Отзывы', 'edit_reviews', 'edit.php?post_type=review');
    add_submenu_page('school-dashboard', 'Услуги', 'Услуги', 'edit_services', 'edit.php?post_type=service');
    add_submenu_page('school-dashboard', 'Учителя (записи)', 'Учителя (записи)', 'edit_teacher_profiles', 'edit.php?post_type=teacher');

    // Скрытые страницы добавления
    add_submenu_page(null, 'Добавить учителя', '', $cap_add, 'school-teacher-add', 'school_teachers_page_add');
    add_submenu_page(null, 'Добавить ученика', '', $cap_add, 'school-student-add', 'school_students_page_add');
    add_submenu_page(null, 'Добавить родителя', '', $cap_add, 'school-parent-add', 'school_parents_page_add');

    // Скрытые страницы редактирования
    add_submenu_page(null, 'Редактировать родителя', '', $cap_edit, 'school-parent-edit', 'school_parents_page_edit');
    add_submenu_page(null, 'Редактировать ученика', '', $cap_edit, 'school-student-edit', 'school_students_page_edit');
    add_submenu_page(null, 'Редактировать учителя', '', $cap_edit, 'school-teacher-edit', 'school_teachers_page_edit');

}, 50);

function school_dashboard_page() {
    if (!school_admin_can_manage_users()) wp_die('Нет доступа');

    $counts = count_users();
    $roles  = $counts['avail_roles'] ?? [];

    $teachers_count = (int)($roles['teacher'] ?? 0);
    $students_count = (int)($roles['student'] ?? 0);
    $parents_count  = (int)($roles['parent'] ?? 0);

    $reviews_total  = school_count_posts_total('review');
    $services_total = school_count_posts_total('service');
    $teacher_profiles_total = school_count_posts_total('teacher');

    echo '<div class="wrap">';
    echo '<h1>Школа</h1>';

    echo '<div class="school-dashboard-grid">';
    echo school_dashboard_card('Учителя', $teachers_count, admin_url('admin.php?page=school-teachers'), 'Управление преподавателями');
    echo school_dashboard_card('Ученики', $students_count, admin_url('admin.php?page=school-students'), 'Список учеников и профили');
    echo school_dashboard_card('Родители', $parents_count, admin_url('admin.php?page=school-parents'), 'Родители и связи');
    echo school_dashboard_card('Отзывы', $reviews_total, admin_url('edit.php?post_type=review'), 'Отзывы на сайте');
    echo school_dashboard_card('Услуги', $services_total, admin_url('edit.php?post_type=service'), 'Услуги и цены');
    echo school_dashboard_card('Учителя (записи)', $teacher_profiles_total, admin_url('edit.php?post_type=teacher'), 'Профили преподавателей для сайта');
    echo '</div>';

    echo '</div>';

    echo '<style>
        .school-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .school-dashboard-card { padding: 16px; }
        .school-dashboard-card h3 { margin-top: 0; }
        .school-dashboard-card .count { font-size: 28px; font-weight: 600; }
        .school-dashboard-card .desc { color: #646970; }
    </style>';
}

function school_count_posts_total(string $post_type): int {
    $counts = wp_count_posts($post_type);
    if (!$counts) return 0;
    return array_sum((array) $counts);
}

function school_dashboard_card(string $title, int $count, string $url, string $desc): string {
    $out  = '<div class="card school-dashboard-card">';
    $out .= '<h3><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></h3>';
    $out .= '<div class="count">' . (int)$count . '</div>';
    $out .= '<div class="desc">' . esc_html($desc) . '</div>';
    $out .= '</div>';
    return $out;
}
