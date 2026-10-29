$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$file = Join-Path $root 'includes\telegram_form.php'
$source = Get-Content -Raw -Encoding UTF8 $file

if ($source -match "error_log\s*\(\s*'ITS TG WEBHOOK PAYLOAD:") {
    Write-Error 'Telegram webhook must not log raw Elementor payloads because they may contain personal data.'
}

if ($source -notmatch 'its_maybe_log_telegram_webhook_debug') {
    Write-Error 'Telegram webhook should route debug logging through its_maybe_log_telegram_webhook_debug().'
}

if ($source -match 'if\s*\(\s*\$expected_secret\s*&&\s*!hash_equals') {
    Write-Error 'Telegram webhook must reject requests when the webhook secret is not configured.'
}

if ($source -match '''data''\s*=>\s*\$data') {
    Write-Error 'Telegram webhook debug responses must not expose raw request payloads.'
}

if ($source -notmatch 'its_check_telegram_webhook_rate_limit') {
    Write-Error 'Telegram webhook should check a rate limit before sending messages.'
}

if ($source -notmatch 'get_transient' -or $source -notmatch 'set_transient') {
    Write-Error 'Telegram webhook rate limiting should use WordPress transients.'
}

Write-Output 'Telegram webhook security static checks passed.'
