<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    Container::make('post_meta', 'Данные услуги')
        ->where('post_type', '=', 'service')
        ->add_fields([           

            Field::make('text', 'service_price', 'Стоимость')
                ->set_attribute('type', 'number')
                ->set_help_text('Только число')           

            
        ]);

    Container::make('post_meta', 'Преимущества для анонса')
        ->where('post_type', '=', 'service')
        ->add_fields([
            Field::make( 'rich_text', 'service_anounce', 'Текст анонса' )
                ->set_help_text('Будет отображаться на главной странице в блоке услуг')
        ]);

    Container::make('post_meta', 'Промо настройки')
        ->where('post_type', '=', 'service')
        ->add_fields({
            Field::make( 'checkbox', 'crown_display', 'Показать корону' ),
            Field::make( 'checkbox', 'promo_display', 'Показать "Выгодно!"' )
        });
});
