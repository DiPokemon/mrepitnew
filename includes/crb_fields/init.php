<?php
use Carbon_Fields\Carbon_Fields;

// Подключаем autoload.php с использованием абсолютного пути
require_once __DIR__ . '/../../vendor/autoload.php';

add_action( 'after_setup_theme', function() {
    Carbon_Fields::boot();
});

require_once 'cpt.php';
require_once 'theme_options.php';
require_once 'review_fields.php';
require_once 'service_fields.php';