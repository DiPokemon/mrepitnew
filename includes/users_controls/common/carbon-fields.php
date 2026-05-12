<?php
if (!defined('ABSPATH')) exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    $can_link_family = is_user_logged_in() && (school_is_admin() || current_user_can('school_link_family'));
    $tz_options = school_msk_offsets_options();

    Container::make('user_meta', 'Профиль учителя')
        ->where('user_role', '=', 'teacher')
        ->add_fields([
            Field::make('text', 'teacher_phone', 'Телефон'),
            Field::make('text', 'teacher_whatsapp', 'WhatsApp')->set_help_text('Номер в формате +7... или ссылка'),
            Field::make('text', 'teacher_telegram', 'Telegram')->set_help_text('Username (@...) или ссылка t.me/...'),
            Field::make('select', 'teacher_timezone', 'Часовой пояс (относительно МСК)')
                ->add_options($tz_options)
                ->set_default_value('0'),
            Field::make('text', 'teacher_telegram_chat_id', 'Telegram chat ID')
                ->set_help_text('Обычно заполняется автоматически через привязку бота.'),
            Field::make('checkbox', 'teacher_tg_opt_in', 'Получать уведомления в Telegram')->set_option_value('yes'),
        ]);

    Container::make('user_meta', 'Профиль ученика')
        ->where('user_role', '=', 'student')
        ->add_fields([
            Field::make('text', 'student_phone', 'Телефон (необязательно)'),
            Field::make('text', 'student_whatsapp', 'WhatsApp')->set_help_text('Номер в формате +7... или ссылка'),
            Field::make('text', 'student_telegram', 'Telegram')->set_help_text('Username (@...) или ссылка t.me/...'),
            Field::make('select', 'student_timezone', 'Часовой пояс (относительно МСК)')
                ->add_options($tz_options)
                ->set_default_value('0'),
            Field::make('text', 'student_telegram_chat_id', 'Telegram chat ID')
                ->set_help_text('Обычно заполняется автоматически через привязку бота.'),
            Field::make('checkbox', 'student_tg_opt_in', 'Получать уведомления в Telegram')->set_option_value('yes'),
            Field::make('date', 'student_birthdate', 'Дата рождения')->set_storage_format('Y-m-d'),
        ]);

    if ($can_link_family) {
        Container::make('user_meta', 'Связи ученика (для менеджера/админа)')
            ->where('user_role', '=', 'student')
            ->add_fields([
                Field::make('association', 'student_parents', 'Родители')
                    ->set_types([[ 'type' => 'user', 'subtype' => 'parent' ]])
                    ->set_help_text('Привязку может менять только менеджер или администратор.'),
            ]);
    }

    Container::make('user_meta', 'Профиль родителя')
        ->where('user_role', '=', 'parent')
        ->add_fields([
            Field::make('text', 'parent_phone', 'Телефон'),
            Field::make('text', 'parent_whatsapp', 'WhatsApp')->set_help_text('Номер в формате +7... или ссылка'),
            Field::make('text', 'parent_telegram', 'Telegram')->set_help_text('Username (@...) или ссылка t.me/...'),
            Field::make('select', 'parent_timezone', 'Часовой пояс (относительно МСК)')
                ->add_options($tz_options)
                ->set_default_value('0'),
            Field::make('text', 'parent_telegram_chat_id', 'Telegram chat ID')
                ->set_help_text('Обычно заполняется автоматически через привязку бота.'),
            Field::make('checkbox', 'parent_tg_opt_in', 'Получать уведомления в Telegram')->set_option_value('yes'),

            Field::make('multiselect', 'parent_reminder_offsets', 'Напоминания о занятиях')
                ->add_options([
                    '1440' => 'За 24 часа',
                    '60'   => 'За 60 минут',
                    '15'   => 'За 15 минут',
                ])
                ->set_default_value(['60', '15']),

            Field::make('checkbox', 'parent_notify_homework', 'Уведомлять о домашнем задании')
                ->set_option_value('yes'),
        ]);

    if ($can_link_family) {
        Container::make('user_meta', 'Связи родителя (для менеджера/админа)')
            ->where('user_role', '=', 'parent')
            ->add_fields([
                Field::make('association', 'parent_children', 'Дети (ученики)')
                    ->set_types([[ 'type' => 'user', 'subtype' => 'student' ]])
                    ->set_help_text('Привязку может менять только менеджер или администратор.'),
            ]);
    }
});
