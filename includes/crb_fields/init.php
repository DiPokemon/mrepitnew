<?php
use Carbon_Fields\Carbon_Fields;

// Подключаем autoload.php с использованием абсолютного пути
require_once __DIR__ . '/../../vendor/autoload.php';

add_action( 'after_setup_theme', function() {
    Carbon_Fields::boot();
});