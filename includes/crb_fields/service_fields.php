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
});
