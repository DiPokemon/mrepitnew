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

    // 0) Плоские ключи вида form_fields[name][value]
    $flat_variants = [
        "form_fields[$id]",
        "form_fields[$id][value]",
        "form_fields[$id][raw_value]",
        "fields[$id][value]",
        "fields[$id][raw_value]",
    ];

    foreach ($flat_variants as $k) {
        if (isset($data[$k]) && !is_array($data[$k])) {
            return (string) $data[$k];
        }
    }

    // 1) Нормальный массив form_fields => [ name => ..., phone => ... ]
    if (isset($data['form_fields'][$id]) && !is_array($data['form_fields'][$id])) {
        return (string) $data['form_fields'][$id];
    }

    // 2) form_fields[name][value] / raw_value
    if (isset($data['form_fields'][$id]['value'])) {
        return (string) $data['form_fields'][$id]['value'];
    }
    if (isset($data['form_fields'][$id]['raw_value'])) {
        return (string) $data['form_fields'][$id]['raw_value'];
    }

    // 3) fields[name][value] (как в JSON от Elementor)
    if (isset($data['fields'][$id]['value'])) {
        return (string) $data['fields'][$id]['value'];
    }
    if (isset($data['fields'][$id]['raw_value'])) {
        return (string) $data['fields'][$id]['raw_value'];
    }

    // 4) fields может быть списком: fields: [ {id: 'name', value: '...'}, ... ]
    if (isset($data['fields']) && is_array($data['fields']) && array_is_list($data['fields'])) {
        foreach ($data['fields'] as $f) {
            if (!is_array($f)) continue;
            $fid = (string)($f['id'] ?? $f['name'] ?? '');
            if ($fid === $id) {
                if (isset($f['value'])) return (string)$f['value'];
                if (isset($f['raw_value'])) return (string)$f['raw_value'];
            }
        }
    }

    // 5) Иногда прилетает record.fields
    if (isset($data['record']['fields'][$id]['value'])) {
        return (string) $data['record']['fields'][$id]['value'];
    }
    if (isset($data['record']['fields'][$id]['raw_value'])) {
        return (string) $data['record']['fields'][$id]['raw_value'];
    }

    return '';
}


/**
 * Нормально достаём payload независимо от Content-Type
 */
function its_get_request_payload(WP_REST_Request $request): array {
    // 1) JSON body
    $json = $request->get_json_params();
    if (is_array($json) && !empty($json)) {
        return $json;
    }

    // 2) x-www-form-urlencoded / multipart/form-data (body params)
    $body = $request->get_body_params();
    if (is_array($body) && !empty($body)) {
        return $body;
    }

    // 3) fallback: всё, что WP смог собрать
    $params = $request->get_params();
    return is_array($params) ? $params : [];
}

function its_maybe_log_telegram_webhook_debug(bool $debug, array $data): void {
    if (!$debug || !defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
        return;
    }

    error_log('ITS TG WEBHOOK DEBUG: ' . wp_json_encode(its_get_telegram_webhook_debug_summary($data), JSON_UNESCAPED_UNICODE));
}

function its_get_telegram_webhook_debug_summary(array $data): array {
    $summary = [
        'top_level_keys' => array_slice(array_keys($data), 0, 30),
        'field_keys'     => [],
    ];

    if (isset($data['form_fields']) && is_array($data['form_fields'])) {
        $summary['field_keys'] = array_slice(array_keys($data['form_fields']), 0, 30);
    } elseif (isset($data['fields']) && is_array($data['fields'])) {
        $summary['field_keys'] = array_slice(array_keys($data['fields']), 0, 30);
    }

    return $summary;
}

function its_get_telegram_webhook_client_key(): string {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    return 'its_tg_webhook_' . hash('sha256', $ip);
}

function its_check_telegram_webhook_rate_limit(): bool {
    $limit = (int) apply_filters('its_telegram_webhook_rate_limit_count', 10);
    $window = (int) apply_filters('its_telegram_webhook_rate_limit_window', 5 * MINUTE_IN_SECONDS);

    if ($limit < 1 || $window < 1) {
        return true;
    }

    $key = its_get_telegram_webhook_client_key();
    $count = (int) get_transient($key);

    if ($count >= $limit) {
        return false;
    }

    set_transient($key, $count + 1, $window);
    return true;
}

function its_elementor_to_telegram(WP_REST_Request $request) {

    $debug = (string) $request->get_param('debug') === '1';

    // 1) Защита секретом в URL: ?secret=...
    $expected_secret = function_exists('carbon_get_theme_option')
        ? (string) carbon_get_theme_option('tg_webhook_secret')
        : '';

    $secret = (string) $request->get_param('secret');

    if ($expected_secret === '') {
        return new WP_REST_Response(['ok' => false, 'error' => 'webhook_secret_missing'], 500);
    }

    if (!hash_equals($expected_secret, $secret)) {
        return new WP_REST_Response(['ok' => false, 'error' => 'forbidden'], 403);
    }

    if (!its_check_telegram_webhook_rate_limit()) {
        return new WP_REST_Response(['ok' => false, 'error' => 'rate_limited'], 429);
    }

    // 2) Payload
    $data = its_get_request_payload($request);
    its_maybe_log_telegram_webhook_debug($debug, $data);


    // (Опционально) Honeypot антиспам:
    // Добавь в Elementor скрытое поле, например id="website"
    // и если оно заполнено — выходим.
    $honeypot = trim(its_get_form_field($data, 'website'));
    if ($honeypot !== '') {
        // Возвращаем 200, чтобы боты не “учились” на ошибках
        return new WP_REST_Response(['ok' => true, 'skipped' => 'honeypot'], 200);
    }

    // 3) Поля
    $name  = trim(its_get_form_field($data, 'name'));
    $phone = trim(its_get_form_field($data, 'phone'));

    // Мягкие ограничения, чтобы не ломать Telegram
    $name  = mb_substr($name, 0, 120);
    $phone = mb_substr($phone, 0, 60);

    // 4) Ссылки
    $phone_digits = preg_replace('/\D+/', '', $phone);

    $wa_url  = $phone_digits ? "https://wa.me/{$phone_digits}" : '';
    $tel_url = $phone_digits ? "tel:+{$phone_digits}" : '';

    // tg:// ссылки работают не везде (в Telegram сообщении они кликабельны, но могут открываться по-разному)
    $tg_deeplink = $phone_digits ? "tg://resolve?phone={$phone_digits}" : '';

    // 5) Сообщение (HTML)
    $message  = "📩 Новая заявка:\n";
    $message .= "<b>Имя:</b> " . esc_html($name ?: '—') . "\n";
    $message .= "<b>Телефон:</b> " . esc_html($phone ?: '—') . "\n";

    $links = [];
    if ($wa_url)     $links[] = '<a href="' . esc_url($wa_url) . '">WhatsApp</a>';
    if ($tel_url)    $links[] = '<a href="' . esc_url($tel_url) . '">Позвонить</a>';
    if ($tg_deeplink) $links[] = '<a href="' . esc_url($tg_deeplink) . '">Telegram</a>';

    if ($links) {
        $message .= "\n" . implode(' | ', $links);
    }

    // 6) Настройки Telegram из Carbon Fields
    if (!function_exists('carbon_get_theme_option')) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Carbon Fields not available'], 500);
    }

    $token   = trim((string) carbon_get_theme_option('tg_bot_token'));
    $chat_id = trim((string) carbon_get_theme_option('tg_chat_id'));

    if (!$token || !$chat_id) {
        return new WP_REST_Response(['ok' => false, 'error' => 'Telegram settings missing'], 500);
    }

    // 7) Отправка в Telegram
    $tg_url_api = "https://api.telegram.org/bot{$token}/sendMessage";

    $payload = [
        'chat_id'                  => $chat_id,
        'text'                     => $message,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ];

    $resp = wp_remote_post($tg_url_api, [
        'timeout' => 15,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'    => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    if (is_wp_error($resp)) {
        $out = ['ok' => false, 'error' => $resp->get_error_message()];
        if ($debug) $out['debug'] = its_get_telegram_webhook_debug_summary($data);
        return new WP_REST_Response($out, 500);
    }

    $code    = wp_remote_retrieve_response_code($resp);
    $body    = wp_remote_retrieve_body($resp);
    $decoded = json_decode($body, true);

    $tg_ok = ($code >= 200 && $code < 300 && is_array($decoded) && !empty($decoded['ok']));

    if (!$tg_ok) {
        $tg_desc = is_array($decoded) ? ($decoded['description'] ?? $body) : $body;

        $out = [
            'ok' => false,
            'telegram_http_code' => $code,
            'telegram_description' => $tg_desc,
        ];
        if ($debug) {
            $out['debug'] = array_merge(
                its_get_telegram_webhook_debug_summary($data),
                [
                    'telegram_http_code' => $code,
                    'telegram_response_ok' => !empty($decoded['ok']),
                ]
            );
        }
        return new WP_REST_Response($out, 500);
    }

    // 8) Debug-ответ (если надо посмотреть что прилетело)
    if ($debug) {
        return new WP_REST_Response([
            'ok' => true,
            'debug' => array_merge(
                its_get_telegram_webhook_debug_summary($data),
                [
                    'telegram_http_code' => $code,
                    'telegram_response_ok' => !empty($decoded['ok']),
                ]
            ),
        ], 200);
    }

    return new WP_REST_Response(['ok' => true], 200);
}
