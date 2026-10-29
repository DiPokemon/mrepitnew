<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class School_Parents_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'parent',
            'plural'   => 'parents',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'name'      => 'Имя',
            'email'     => 'Email',
            'phone'     => 'Телефон',
            'timezone'  => 'Часовой пояс',
            'tg'        => 'Telegram',
            'children'  => 'Дети',
        ];
    }

    protected function column_name($item) {
        $url = admin_url('admin.php?page=school-parent-edit&user_id='.(int)$item->ID);
        $name = $item->display_name ?: $item->user_login;

        $actions = [
            'edit' => '<a href="'.esc_url($url).'">Редактировать</a>',
        ];

        return '<strong><a href="'.esc_url($url).'">'.esc_html($name).'</a></strong>' . $this->row_actions($actions);
    }

    protected function column_email($item) {
        return esc_html($item->user_email);
    }

    protected function column_phone($item) {
        return esc_html(get_user_meta($item->ID, 'parent_phone', true) ?: '—');
    }

    protected function column_timezone($item) {
        $tz = school_normalize_msk_offset(get_user_meta($item->ID, 'parent_timezone', true));
        $map = school_msk_offsets_options();
        return esc_html($map[$tz] ?? 'MSK+0');
    }

    protected function column_tg($item) {
        $chat = get_user_meta($item->ID, 'parent_telegram_chat_id', true);
        $opt  = get_user_meta($item->ID, 'parent_tg_opt_in', true);
        if ($chat) return 'Привязан' . ($opt === 'yes' ? ' (вкл.)' : ' (выкл.)');
        return '—';
    }

    protected function column_children($item) {
        if (!function_exists('carbon_get_user_meta')) return '—';
        $children_assoc = carbon_get_user_meta($item->ID, 'parent_children');
        $children_ids   = school_cf_assoc_to_user_ids($children_assoc);
        return 'Детей: ' . count($children_ids);
    }

    public function prepare_items() {
        $per_page = 20;
        $paged    = max(1, (int)($_GET['paged'] ?? 1));
        $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = method_exists($this, 'get_sortable_columns') ? $this->get_sortable_columns() : [];
        $this->_column_headers = [$columns, $hidden, $sortable];

        $args = [
            'number'   => $per_page,
            'offset'   => ($paged - 1) * $per_page,
            'role__in' => ['parent'],
            'orderby'  => 'registered',
            'order'    => 'DESC',
        ];

        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login','user_email','display_name'];
        }

        $q = new WP_User_Query($args);
        if ((int)$q->get_total() === 0) {
            $fallback_args = $args;
            $fallback_args['blog_id'] = 0;
            $q = new WP_User_Query($fallback_args);
        }

        $this->items = $q->get_results();

        $this->set_pagination_args([
            'total_items' => (int)$q->get_total(),
            'per_page'    => $per_page,
            'total_pages' => (int)ceil($q->get_total() / $per_page),
        ]);
    }
}
