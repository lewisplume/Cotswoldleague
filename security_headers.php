<?php
function cotswold_trusted_proxy_ips(): array
{
    $configured = getenv('COTSWOLD_TRUSTED_PROXY_IPS');
    if ($configured === false || trim($configured) === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $configured))));
}

function cotswold_is_trusted_proxy_request(): bool
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $remote !== '' && in_array($remote, cotswold_trusted_proxy_ips(), true);
}

function cotswold_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!cotswold_is_trusted_proxy_request()) {
        return false;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwarded = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        if ($forwarded === 'https') {
            return true;
        }
    }

    return !empty($_SERVER['HTTP_CF_VISITOR'])
        && strpos((string)$_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false;
}

function cotswold_content_security_policy(string $frame_ancestors, string $frame_src): string
{
    return "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors {$frame_ancestors}; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://connect.facebook.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://docs.google.com https://sheets.googleapis.com https://connect.facebook.net https://www.facebook.com https://graph.facebook.com; frame-src {$frame_src} https://www.facebook.com https://web.facebook.com; upgrade-insecure-requests";
}

function cotswold_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    $allow_same_origin_frame_ancestors = defined('COTSWOLD_ALLOW_SAME_ORIGIN_FRAME_ANCESTORS') && COTSWOLD_ALLOW_SAME_ORIGIN_FRAME_ANCESTORS;
    $allow_same_origin_frame_src = defined('COTSWOLD_ALLOW_SAME_ORIGIN_FRAME_SRC') && COTSWOLD_ALLOW_SAME_ORIGIN_FRAME_SRC;
    $frame_ancestors = $allow_same_origin_frame_ancestors ? "'self'" : "'none'";
    $frame_src = $allow_same_origin_frame_src ? "'self'" : "'none'";

    header('X-Frame-Options: ' . ($allow_same_origin_frame_ancestors ? 'SAMEORIGIN' : 'DENY'));
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Content-Security-Policy: ' . cotswold_content_security_policy($frame_ancestors, $frame_src));

    if (cotswold_is_https_request()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

function cotswold_secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', cotswold_is_https_request() ? '1' : '0');

    session_start();
    cotswold_ensure_csrf_token();
}

function cotswold_ensure_csrf_token(bool $rotate = false): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('A session must be active before creating a CSRF token.');
    }

    if ($rotate || empty($_SESSION['cotswold_csrf_token'])) {
        $_SESSION['cotswold_csrf_token'] = bin2hex(random_bytes(32));
    }

    $token = (string)$_SESSION['cotswold_csrf_token'];
    if (!headers_sent()) {
        setcookie('cotswold_csrf', $token, [
            'expires' => 0,
            'path' => '/',
            'secure' => cotswold_is_https_request(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    return $token;
}

function cotswold_request_csrf_token(): string
{
    $header = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($header !== '') {
        return $header;
    }
    return (string)($_POST['_csrf_token'] ?? '');
}

function cotswold_request_origin_is_same_site(): bool
{
    $source = (string)($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');
    if ($source === '') {
        return true;
    }

    $sourceHost = strtolower((string)parse_url($source, PHP_URL_HOST));
    $requestHost = strtolower(trim(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0], '[]'));
    return $sourceHost !== '' && $requestHost !== '' && hash_equals($requestHost, $sourceHost);
}

function cotswold_csrf_is_valid(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE || !cotswold_request_origin_is_same_site()) {
        return false;
    }

    $expected = (string)($_SESSION['cotswold_csrf_token'] ?? '');
    $provided = cotswold_request_csrf_token();
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}

function cotswold_require_csrf(bool $json = false): void
{
    if (cotswold_csrf_is_valid()) {
        return;
    }

    http_response_code(403);
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Your security token has expired. Refresh the page and try again.']);
    } else {
        echo 'Your security token has expired. Please go back, refresh the page and try again.';
    }
    exit;
}

function cotswold_require_same_site_request(bool $json = false): void
{
    if (cotswold_request_origin_is_same_site()) {
        return;
    }

    http_response_code(403);
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cross-site request rejected.']);
    } else {
        echo 'Cross-site request rejected.';
    }
    exit;
}

cotswold_send_security_headers();
