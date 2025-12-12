<?php
add_action('rest_api_init', function () {
	register_rest_route('its/v1', '/elementor-telegram', [
		'methods'             => 'POST',
		'callback'            => 'its_elementor_to_telegram',
		'permission_callback' => '__return_true', // доступ снаружи, защитим секретом ниже
	]);
});

function its_get_elementor_value(array $data, string $key): string {

	// Вариант 1: плоско: { "name": "...", "phone": "..." }
	if (isset($data[$key]) && !is_array($data[$key])) {
		return (string) $data[$key];
	}

	// Вариант 2: { fields: { name: {value: ...}, phone: {value: ...} } }
	if (isset($data['fields'][$key]['value'])) {
		return (string) $data['fields'][$key]['value'];
	}

	// Вариант 3: { record: { fields: { name: {value: ...} } } }
	if (isset($data['record']['fields'][$key]['value'])) {
		return (string) $data['record']['fields'][$key]['value'];
	}

	return '';
}

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
	if (!is_array($data)) {
		$data = [];
	}

	// 3) Забираем нужные поля (у тебя ID = name и phone)
	$name  = trim(its_get_elementor_value($data, 'name'));
	$phone = trim(its_get_elementor_value($data, 'phone'));

	// Если Elementor прислал плоско, но с другими ключами (на всякий случай)
	if ($name === '' && isset($data['name'])) {
		$name = trim((string) $data['name']);
	}
	if ($phone === '' && isset($data['phone'])) {
		$phone = trim((string) $data['phone']);
	}

	// 4) Формируем сообщение в нужном формате (HTML)
	$name_safe  = esc_html($name);
	$phone_safe = esc_html($phone);

	$phone_digits = preg_replace('/\D+/', '', $phone);

	// WhatsApp (надёжно)
	$wa_url = $phone_digits ? "https://wa.me/{$phone_digits}" : '';

	// Telegram: универсальной web-ссылки "по номеру" нет; deep-link для приложения
	$tg_url_app = $phone_digits ? "tg://resolve?phone={$phone_digits}" : '';

	$message  = "📩 Новая заявка:\n";
	$message .= "<b>Имя: </b> {$name_safe}\n";
	$message .= "<b>Телефон: </b> {$phone_safe}\n";

	$links = [];
	if ($wa_url)     { $links[] = '<a href="' . esc_url($wa_url) . '">WhatsApp</a>'; }
	if ($tg_url_app) { $links[] = '<a href="' . esc_url($tg_url_app) . '">Telegram</a>'; }

	if (!empty($links)) {
		$message .= implode(' | ', $links);
	}

	// 5) Берём токен и chat_id из Carbon Fields
	if (!function_exists('carbon_get_theme_option')) {
		return new WP_REST_Response(['ok' => false, 'error' => 'Carbon Fields not available'], 500);
	}

	$token   = trim((string) carbon_get_theme_option('tg_bot_token'));
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
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true,
		], JSON_UNESCAPED_UNICODE),
	]);

	if (is_wp_error($resp)) {
		return new WP_REST_Response(['ok' => false, 'error' => $resp->get_error_message()], 500);
	}

	$code = wp_remote_retrieve_response_code($resp);
	$body = wp_remote_retrieve_body($resp);

	// Более понятная ошибка от Telegram
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
