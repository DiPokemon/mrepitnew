<?php
add_action('rest_api_init', function () {
    register_rest_route('its/v1', '/elementor-telegram', [
        'methods'             => 'POST',
        'callback'            => 'its_elementor_to_telegram',
        'permission_callback' => '__return_true', // доступ снаружи, защитим секретом ниже
    ]);
});

function its_elementor_to_telegram(WP_REST_Request $request) {

    // 1) Простая защита секретом в URL: ?secret=...
    $expected_secret = function_exists('carbon_get_theme_option')
        ? (string) carbon_get_theme_option('tg_webhook_secret')
        : '';

    $secret = (string) $request->get_param('secret');
    if ($expected_secret && !hash_equals($expected_secret, $secret)) {
        return new WP_REST_Response(['ok' => false, 'error' => 'forbidden'], 403);
    }

    // 2) Достаём payload (Elementor может прислать JSON или form-data)
    $data = $request->get_json_params();
    if (empty($data)) {
        $data = $request->get_params();
    }

    // 3) Пытаемся найти поля формы (варианты структуры Elementor)
    $fields = [];
    if (isset($data['fields']) && is_array($data['fields'])) {
        $fields = $data['fields'];
    } elseif (isset($data['record']['fields']) && is_array($data['record']['fields'])) {
        $fields = $data['record']['fields'];
    }

    // 4) Формируем текст сообщения
    $form_name = $data['form_name'] ?? ($data['record']['form_name'] ?? 'Elementor form');

    $lines = [];
    $lines[] = "📩 Новая заявка: " . wp_strip_all_tags((string)$form_name);

    foreach ($fields as $key => $field) {
        $label = is_array($field) && !empty($field['title']) ? $field['title'] : $key;

        $value = is_array($field) && array_key_exists('value', $field) ? $field['value'] : $field;
        if (is_array($value)) {
            $value = implode(', ', array_map(static fn($v) => wp_strip_all_tags((string)$v), $value));
        } else {
            $value = wp_strip_all_tags((string)$value);
        }

        $lines[] = "• " . wp_strip_all_tags((string)$label) . ": " . $value;
    }

    $message = implode("\n", $lines);

    // 5) Берём токен и chat_id из Carbon Fields
    if (!function_exists('carbon_get_theme_option')) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Carbon Fields not available'], 500);
    }

    $token  = trim((string) carbon_get_theme_option('tg_bot_token'));
    $chat_id = trim((string) carbon_get_theme_option('tg_chat_id'));

    if (!$token || !$chat_id) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Telegram settings missing'], 500);
    }

    // 6) Отправка в Telegram
    $tg_url = "https://api.telegram.org/bot{$token}/sendMessage";

    $resp = wp_remote_post($tg_url, [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'    => wp_json_encode([
            'chat_id' => $chat_id,
            'text'    => $message,
            'disable_web_page_preview' => true,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    if (is_wp_error($resp)) {
        return new WP_REST_Response(['ok' => false, 'error' => $resp->get_error_message()], 500);
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);

    if ($code < 200 || $code >= 300) {
        return new WP_REST_Response([
            'ok' => false,
            'telegram_http_code' => $code,
            'telegram_response'  => $body,
        ], 500);
    }

    return new WP_REST_Response(['ok' => true], 200);
}
