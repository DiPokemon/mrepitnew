<?php
if (!defined('ABSPATH')) exit;

/** Запрет изменения связей для всех кроме администратора/менеджера */
add_filter('pre_update_user_meta', function ($new_value, $object_id, $meta_key, $prev_value) {

    $protected_keys = ['parent_children', 'student_parents'];

    if (!in_array($meta_key, $protected_keys, true)) {
        return $new_value;
    }

    if (school_is_admin() || current_user_can('school_link_family')) {
        return $new_value;
    }

    return $prev_value;
}, 10, 4);
