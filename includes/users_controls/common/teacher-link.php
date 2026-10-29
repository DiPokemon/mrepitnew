<?php
if (!defined('ABSPATH')) exit;

const SCHOOL_TEACHER_USER_META = 'school_teacher_post_id';
const SCHOOL_TEACHER_POST_META = 'school_teacher_user_id';

function school_get_teacher_post_id_for_user(int $user_id): int {
    return (int) get_user_meta($user_id, SCHOOL_TEACHER_USER_META, true);
}

function school_get_teacher_user_id_for_post(int $post_id): int {
    return (int) get_post_meta($post_id, SCHOOL_TEACHER_POST_META, true);
}

function school_unlink_teacher_user_by_user(int $user_id): void {
    $user_id = absint($user_id);
    if (!$user_id) return;

    $post_id = (int) get_user_meta($user_id, SCHOOL_TEACHER_USER_META, true);
    if ($post_id) {
        $linked_user = (int) get_post_meta($post_id, SCHOOL_TEACHER_POST_META, true);
        if ($linked_user === $user_id) {
            delete_post_meta($post_id, SCHOOL_TEACHER_POST_META);
        }
    }

    delete_user_meta($user_id, SCHOOL_TEACHER_USER_META);
}

function school_unlink_teacher_user_by_post(int $post_id): void {
    $post_id = absint($post_id);
    if (!$post_id) return;

    $user_id = (int) get_post_meta($post_id, SCHOOL_TEACHER_POST_META, true);
    if ($user_id) {
        $linked_post = (int) get_user_meta($user_id, SCHOOL_TEACHER_USER_META, true);
        if ($linked_post === $post_id) {
            delete_user_meta($user_id, SCHOOL_TEACHER_USER_META);
        }
    }

    delete_post_meta($post_id, SCHOOL_TEACHER_POST_META);
}

function school_link_teacher_user_post(int $user_id, int $post_id): void {
    $user_id = absint($user_id);
    $post_id = absint($post_id);
    if (!$user_id || !$post_id) return;

    $prev_post = (int) get_user_meta($user_id, SCHOOL_TEACHER_USER_META, true);
    if ($prev_post && $prev_post !== $post_id) {
        school_unlink_teacher_user_by_post($prev_post);
    }

    $prev_user = (int) get_post_meta($post_id, SCHOOL_TEACHER_POST_META, true);
    if ($prev_user && $prev_user !== $user_id) {
        school_unlink_teacher_user_by_user($prev_user);
    }

    update_user_meta($user_id, SCHOOL_TEACHER_USER_META, $post_id);
    update_post_meta($post_id, SCHOOL_TEACHER_POST_META, $user_id);
}

add_action('add_meta_boxes_teacher', function () {
    add_meta_box(
        'school_teacher_user_link',
        'Связанный пользователь',
        'school_teacher_user_metabox',
        'teacher',
        'side'
    );
});

function school_teacher_user_metabox($post): void {
    $linked_user_id = school_get_teacher_user_id_for_post($post->ID);
    $users = get_users([
        'role'    => 'teacher',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'number'  => 9999,
    ]);

    wp_nonce_field('school_teacher_user_link', 'school_teacher_user_link_nonce');

    echo '<select name="school_teacher_user_id" class="widefat">';
    echo '<option value="">— Не связан —</option>';
    foreach ($users as $u) {
        $label = $u->display_name ?: $u->user_login;
        if (!empty($u->user_email)) {
            $label .= ' (' . $u->user_email . ')';
        }

        $busy_post = school_get_teacher_post_id_for_user($u->ID);
        if ($busy_post && $busy_post !== $post->ID) {
            $label .= ' — связан';
        }

        $selected = selected($linked_user_id, $u->ID, false);
        echo '<option value="' . (int)$u->ID . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';

    if ($linked_user_id) {
        $url = admin_url('admin.php?page=school-teacher-edit&user_id=' . (int)$linked_user_id);
        echo '<p><a href="' . esc_url($url) . '">Открыть пользователя</a></p>';
    }

    echo '<p class="description">Связывает запись преподавателя с пользователем.</p>';
}

add_action('save_post_teacher', function ($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['school_teacher_user_link_nonce'])) return;
    if (!wp_verify_nonce($_POST['school_teacher_user_link_nonce'], 'school_teacher_user_link')) return;
    if (!isset($_POST['school_teacher_user_id'])) return;

    $user_id = absint($_POST['school_teacher_user_id']);
    if ($user_id) {
        $u = get_user_by('id', $user_id);
        if (!$u || !in_array('teacher', (array)$u->roles, true)) {
            $user_id = 0;
        }
    }

    if ($user_id) {
        school_link_teacher_user_post($user_id, $post_id);
    } else {
        school_unlink_teacher_user_by_post($post_id);
    }
}, 10, 3);

add_action('deleted_user', function ($user_id) {
    school_unlink_teacher_user_by_user((int)$user_id);
});

add_action('before_delete_post', function ($post_id) {
    if (get_post_type($post_id) !== 'teacher') return;
    school_unlink_teacher_user_by_post((int)$post_id);
});
