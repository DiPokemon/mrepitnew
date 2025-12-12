<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    Container::make('theme_options', 'Общие настройки')
        ->set_icon('dashicons-admin-generic')
        ->add_tab('Telegram', [
            Field::make('text', 'tg_chat_id', 'Чат ID')
                ->set_help_text('Например: -1001234567890')
                ->set_width(33),

            Field::make('text', 'tg_bot_token', 'Ключ бота')
                ->set_help_text('Токен вида: 123456:ABC-DEF1234...')
                ->set_width(33),

            Field::make('text', 'tg_webhook_secret', 'Webhook Secret')
                ->set_width(33),
        ]);
});
