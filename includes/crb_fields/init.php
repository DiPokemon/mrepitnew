<?php
use Carbon_Fields\Carbon_Fields;

// РџРѕРґРєР»СЋС‡Р°РµРј autoload.php СЃ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёРµРј Р°Р±СЃРѕР»СЋС‚РЅРѕРіРѕ РїСѓС‚Рё
require_once __DIR__ . '/../../vendor/autoload.php';

add_action( 'after_setup_theme', function() {
    Carbon_Fields::boot();
});

require_once 'cpt.php';
require_once 'theme_options.php';
require_once 'review_fields.php';
require_once 'service_fields.php';

require_once 'teacher_fields.php';
