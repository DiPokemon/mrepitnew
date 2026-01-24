<?php
// inc/cpt.php

add_action('init', function () {

    // ===== УСЛУГИ =====
    register_post_type('service', [
        'labels' => [
            'name'               => 'Услуги',
            'singular_name'      => 'Услуга',
            'add_new'            => 'Добавить',
            'add_new_item'       => 'Добавить услугу',
            'edit_item'          => 'Редактировать услугу',
            'new_item'           => 'Новая услуга',
            'view_item'          => 'Просмотр услуги',
            'search_items'       => 'Найти услугу',
            'not_found'          => 'Услуги не найдены',
            'not_found_in_trash' => 'В корзине услуг нет',
            'menu_name'          => 'Услуги',
        ],
        'public'             => true,
        'has_archive'        => false,
        'show_in_menu'       => true,   // отдельным пунктом слева
        'show_in_rest'       => true,   // полезно для редакторов/Elementor
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-hammer',
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite'            => ['slug' => 'services'],
    ]);

    // ===== ОТЗЫВЫ =====
    register_post_type('review', [
        'labels' => [
            'name'               => 'Отзывы',
            'singular_name'      => 'Отзыв',
            'add_new'            => 'Добавить',
            'add_new_item'       => 'Добавить отзыв',
            'edit_item'          => 'Редактировать отзыв',
            'new_item'           => 'Новый отзыв',
            'view_item'          => 'Просмотр отзыва',
            'search_items'       => 'Найти отзыв',
            'not_found'          => 'Отзывы не найдены',
            'not_found_in_trash' => 'В корзине отзывов нет',
            'menu_name'          => 'Отзывы',
        ],
        'public'             => true,
        'has_archive'        => false,
        'show_in_menu'       => true,   // отдельным пунктом слева
        'show_in_rest'       => true,
        'menu_position'      => 21,
        'menu_icon'          => 'dashicons-testimonial',
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite'            => ['slug' => 'reviews'],
    ]);

    // ===== УЧИТЕЛЯ =====
    register_post_type('teacher', [
        'labels' => [
            'name'               => 'Преподаватели',
            'singular_name'      => 'Преподаватель',
            'add_new'            => 'Добавить',
            'add_new_item'       => 'Добавить преподавателя',
            'edit_item'          => 'Редактировать преподавателя',
            'new_item'           => 'Новый преподаватель',
            'view_item'          => 'Просмотр преподавателя',
            'search_items'       => 'Найти преподавателя',
            'not_found'          => 'Преподаватели не найдены',
            'not_found_in_trash' => 'В корзине преподавателей нет',
            'menu_name'          => 'Преподаватели',
        ],
        'public'             => true,
        'has_archive'        => false,
        'show_in_menu'       => true,   // отдельным пунктом слева
        'show_in_rest'       => true,
        'menu_position'      => 22,
        'menu_icon'          => 'dashicons-welcome-learn-more',
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite'            => ['slug' => 'teachers'],
    ]);
});
