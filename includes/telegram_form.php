<?php
add_action('rest_api_init', function () {
    register_rest_route('its/v1', '/elementor-telegram', [
        'methods'             => 'POST',
        'callback'            => 'its_elementor_to_telegram',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Достаём form_fields[name] / form_fields[phone] из разных возможных форматов.
 */
function its_get_form_field(array $data, string $id): string {

    // То, что у тебя в HTML формы:
    // name="form_fields[name]" и name="form_fields[phone]"
    $flat_key = "form_fields[$id]";
    if (isset($data[$flat_key]) && !is_array($data[$flat_key])) {
        return (string) $data[$flat_key];
    }

    // Если WP распарсил в массив form_fields => [ name => ..., phone => ... ]
    if (isset($data['form_fields'][$id]) && !is_array($data['form_fields'][$id])) {
        return (string) $data['form_fields'][$id];
    }

    // Иногда прилетает вложенно как value
    if (isset($data['form_fields'][$id]['value'])) {
        return (string) $data['form_fields'][$id]['value'];
    }

    // На всякий случай: JSON-режимы
    if (isset($data['fields'][$id]['value'])) {
        return (string) $data['fields'][$id]['value'];
    }
    if (isset($data['record']['fields'][$id]['value'])) {
        return (string) $data['record']['fields'][$id]['value'];
    }

    return '';
}

function its_elementor_to_telegram(WP_REST_Request $request) {

    // 1) Защита секретом в URL: ?secret=...
    $expected_secret = function_exists('carbon_get_theme_option')
        ? (string) carbon_get_theme_option('tg_webhook_secret')
        : '';

    $secret = (string) $request->get_param('secret');
    if ($expected_secret && !hash_equals($expected_secret, $secret)) {
        return new WP_REST_Response(['ok' => false, 'error' => 'forbidden'], 403);
    }

    // 2) Payload (Elementor обычно шлёт form-data; WP_REST_Request это раскладывает в params)
    $data = $request->get_params();
    if (!is_array($data)) {
        $data = [];
    }

    // 3) Достаём поля
    $name  = trim(its_get_form_field($data, 'name'));
    $phone = trim(its_get_form_field($data, 'phone'));

    // 4) Ссылки
    $phone_digits = preg_replace('/\D+/', '', $phone);

    $wa_url = $phone_digits ? "https://wa.me/{$phone_digits}" : '';
    // Telegram: лучше всего deep-link в приложение
    $tg_url = $phone_digits ? "tg://resolve?phone={$phone_digits}" : '';

    // 5) Сообщение (HTML)
    $message  = "📩 Новая заявка:\n";
    $message .= "<b>Имя: </b> " . esc_html($name) . "\n";
    $message .= "<b>Телефон: </b> " . esc_html($phone) . "\n";

    $links = [];
    if ($wa_url) $links[] = '<a href="' . esc_url($wa_url) . '">WhatsApp</a>';
    if ($tg_url) $links[] = '<a href="' . esc_url($tg_url) . '">Telegram</a>';

    if (!empty($links)) {
        $message .= implode(' | ', $links);
    }

    // 6) Настройки Telegram из Carbon Fields
    if (!function_exists('carbon_get_theme_option')) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Carbon Fields not available'], 500);
    }

    $token  = trim((string) carbon_get_theme_option('tg_bot_token'));
    $chat_id = trim((string) carbon_get_theme_option('tg_chat_id'));

    if (!$token || !$chat_id) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Telegram settings missing'], 500);
    }

    // 7) Отправка в Telegram
    $tg_url_api = "https://api.telegram.org/bot{$token}/sendMessage";

    $resp = wp_remote_post($tg_url_api, [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'    => wp_json_encode([
            'chat_id' => $chat_id,
            'text'    => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    if (is_wp_error($resp)) {
        return new WP_REST_Response(['ok' => false, 'error' => $resp->get_error_message()], 500);
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    $decoded = json_decode($body, true);

    if ($code < 200 || $code >= 300 || (is_array($decoded) && isset($decoded['ok']) && !$decoded['ok'])) {
        $tg_desc = is_array($decoded) ? ($decoded['description'] ?? $body) : $body;
        return new WP_REST_Response([
            'ok' => false,
            'telegram_http_code' => $code,
            'telegram_description' => $tg_desc,
        ], 500);
    }

    return new WP_REST_Response(['ok' => true], 200);
}
