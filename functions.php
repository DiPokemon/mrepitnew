<?php
require_once __DIR__ . '/includes/crb_fields/init.php';
require_once __DIR__ . '/includes/telegram_form.php';
add_action( 'wp_enqueue_scripts', 'my_child_theme_enqueue_styles', 20 );
function my_child_theme_enqueue_styles() {    
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css'  );
    wp_enqueue_style( 'child-style',  get_stylesheet_directory_uri() . '/assets/css/style.min.css', ['parent-style'], wp_get_theme()->get('Version') );
}

add_filter('upload_mimes', function ($mimes) {
    if (current_user_can('manage_options')) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes, $real_mime = '') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 5);

add_filter('wpcf7_autop_or_not', '__return_false');

add_action('wp_enqueue_scripts', function () {
    // если IMask уже где-то подключен — эту строку не добавляй
    wp_enqueue_script('imask', 'https://unpkg.com/imask@7.6.1/dist/imask.min.js', [], null, true);

    wp_enqueue_script('phone-mask', get_stylesheet_directory_uri() . '/assets/js/phone-mask.js', ['jquery','imask'], '1.0', true);
}, 20);


