<?php
if (!defined('ABSPATH')) exit;

use Carbon_Fields\Carbon_Fields;

require_once 'cpt.php';

$mrepit_carbon_autoload = __DIR__ . '/../../vendor/autoload.php';

if (!file_exists($mrepit_carbon_autoload)) {
    add_action('admin_notices', function () {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Carbon Fields dependency is missing. Run composer install or deploy the theme vendor directory.', 'mrepitnew');
        echo '</p></div>';
    });

    return;
}

require_once $mrepit_carbon_autoload;

add_action( 'after_setup_theme', function() {
    Carbon_Fields::boot();
});

require_once 'theme_options.php';
require_once 'review_fields.php';
require_once 'service_fields.php';
require_once 'teacher_fields.php';
