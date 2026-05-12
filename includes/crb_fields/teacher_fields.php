<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    Container::make('post_meta', 'Документы преподавателя')
        ->where('post_type', '=', 'teacher')
        ->add_fields([
            Field::make('media_gallery', 'teacher_documents_gallery', 'Галерея документов')
                ->set_type(['image'])
                ->set_help_text('Загрузите сканы/фото документов преподавателя (будут храниться как ID вложений).'),
        ]);
});
