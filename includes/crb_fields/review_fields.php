<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    Container::make('post_meta', 'Данные отзыва')
        ->where('post_type', '=', 'review')
        ->add_fields([

            Field::make('text', 'review_name', 'Имя')
                ->set_required(true)
                ->set_width(25),

            Field::make('text', 'review_age', 'Возраст')
                ->set_attribute('type', 'number')
                ->set_help_text('Только число')
                ->set_width(25),

            Field::make('text', 'review_class', 'Класс')
                ->set_help_text('Например: 8А, 10Б, 11')
                ->set_width(25),

            Field::make('image', 'review_screenshot', 'Скриншот')
                ->set_value_type('id') // будет храниться ID вложения
                ->set_help_text('Загрузите скрин (PNG/JPG/WebP).')
                ->set_width(25),
        ]);
});
