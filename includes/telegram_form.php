<?php
add_action('rest_api_init', function () {
	register_rest_route('its/v1', '/elementor-telegram', [
		'methods'             => 'POST',
		'callback'            => 'its_elementor_to_telegram',
		'permission_callback' => '__return_true',
	]);
});

/**
 * Пытаемся распарсить payload максимально надёжно:
 * - JSON (Advanced Data)
 * - x-www-form-urlencoded / form-data (Simple)
 * - + подмешиваем $request->get_params()
 */
function its_parse_request_payload(WP_REST_Request $request): array {
	$raw = (string) $request->get_body();
	$data = [];

	// 1) JSON
	$decoded = json_decode($raw, true);
	if (is_array($decoded)) {
		$data = $decoded;
	} else {
		// 2) form-urlencoded (или часть form-data иногда так выглядит)
		$parsed = [];
		parse_str($raw, $parsed);
		if (is_array($parsed) && !empty($parsed)) {
			$data = $parsed;
		}
	}

	// 3) Подмешаем то, что WP уже распарсил
	$params = $request->get_params();
	if (is_array($params) && !empty($params)) {
		$data = array_replace_recursive($data, $params);
	}

	// 4) Иногда вложенные куски приходят JSON-строкой — попробуем декодировать
	$data = its_decode_json_strings_recursive($data);

	return is_array($data) ? $data : [];
}

function its_decode_json_strings_recursive($value) {
	if (is_array($value)) {
		foreach ($value as $k => $v) {
			$value[$k] = its_decode_json_strings_recursive($v);
		}
		return $value;
	}

	if (is_string($value)) {
		$trim = trim($value);
		if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
			$dec = json_decode($trim, true);
			if (is_array($dec)) {
				return its_decode_json_strings_recursive($dec);
			}
		}
	}

	return $value;
}

/**
 * Достаём значение поля по ID из разных структур Elementor.
 */
function its_extract_field_value(array $data, string $id): string {

	// A) Плоско: name=..., phone=...
	if (isset($data[$id]) && !is_array($data[$id])) {
		return (string) $data[$id];
	}

	// B) fields[name][value] или form_fields[name][value] в распарсенном виде
	if (isset($data['fields'][$id]['value'])) {
		return (string) $data['fields'][$id]['value'];
	}
	if (isset($data['form_fields'][$id]['value'])) {
		return (string) $data['form_fields'][$id]['value'];
	}
	if (isset($data['record']['fields'][$id]['value'])) {
		return (string) $data['record']['fields'][$id]['value'];
	}

	// C) Иногда прилетают “плоские” ключи с квадратными скобками
	$variants = [
		"fields[{$id}][value]",
		"form_fields[{$id}][value]",
		"record[fields][{$id}][value]",
		"record[fields][{$id}][value]",
		"fields[{$id}]",
		"form_fields[{$id}]",
	];
	foreach ($variants as $k) {
		if (isset($data[$k]) && !is_array($data[$k])) {
			return (string) $data[$k];
		}
		if (isset($data[$k]) && is_array($data[$k]) && isset($data[$k]['value'])) {
			return (string) $data[$k]['value'];
		}
	}

	// D) Рекурсивный поиск по дереву: где-то может быть ['id'=>'name','value'=>'...']
	$found = its_recursive_find_by_id($data, $id);
	if ($found !== '') {
		return $found;
	}

	return '';
}

function its_recursive_find_by_id($node, string $id): string {
	if (!is_array($node)) {
		return '';
	}

	// Если это структура поля
	if (isset($node['id']) && (string)$node['id'] === $id && isset($node['value']) && !is_array($node['value'])) {
		return (string) $node['value'];
	}

	foreach ($node as $k => $v) {
		// ключ совпал с id
		if (is_string($k) && $k === $id) {
			if (!is_array($v)) {
				return (string) $v;
			}
			if (is_array($v) && isset($v['value']) && !is_array($v['value'])) {
				return (string) $v['value'];
			}
		}

		$deep = its_recursive_find_by_id($v, $id);
		if ($deep !== '') {
			return $deep;
		}
	}

	return '';
}

function its_elementor_to_telegram(WP_REST_Request $request) {

	// 1) Защита секретом ?secret=...
	$expected_secret = function_exists('carbon_get_theme_option')
		? (string) carbon_get_theme_option('tg_webhook_secret')
		: '';

	$secret = (string) $request->get_param('secret');
	if ($expected_secret && !hash_equals($expected_secret, $secret)) {
		return new WP_REST_Response(['ok' => false, 'error' => 'forbidden'], 403);
	}

	// 2) Парсим payload
	$data = its_parse_request_payload($request);

	// Включаем отладку: добавь ?debug=1 к URL вебхука и посмотри ответ в Network
	if ((string)$request->get_param('debug') === '1') {
		return new WP_REST_Response([
			'ok' => true,
			'received_keys' => array_keys($data),
			'sample' => $data, // если слишком много — можно потом урезать
		], 200);
	}

	// 3) Достаём поля по ID
	$name  = trim(its_extract_field_value($data, 'name'));
	$phone = trim(its_extract_field_value($data, 'phone'));

	// 4) Формируем сообщение
	$name_safe  = esc_html($name);
	$phone_safe = esc_html($phone);

	$phone_digits = preg_replace('/\D+/', '', $phone);

	$wa_url = $phone_digits ? "https://wa.me/{$phone_digits}" : '';
	$tg_url = $phone_digits ? "tg://resolve?phone={$phone_digits}" : '';

	$message  = "📩 Новая заявка:\n";
	$message .= "<b>Имя: </b> {$name_safe}\n";
	$message .= "<b>Телефон: </b> {$phone_safe}\n";

	$links = [];
	if ($wa_url) $links[] = '<a href="' . esc_url($wa_url) . '">WhatsApp</a>';
	if ($tg_url) $links[] = '<a href="' . esc_url($tg_url) . '">Telegram</a>';

	if ($links) {
		$message .= implode(' | ', $links);
	}

	// 5) Настройки Telegram из Carbon Fields
	if (!function_exists('carbon_get_theme_option')) {
		return new WP_REST_Response(['ok' => false, 'error' => 'Carbon Fields not available'], 500);
	}

	$token   = trim((string) carbon_get_theme_option('tg_bot_token'));
	$chat_id = trim((string) carbon_get_theme_option('tg_chat_id'));

	if (!$token || !$chat_id) {
		return new WP_REST_Response(['ok' => false, 'error' => 'Telegram settings missing'], 500);
	}

	// 6) Отправка в Telegram
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
