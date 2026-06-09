<?php
function cotswold_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    return !empty($_SERVER['HTTP_CF_VISITOR']) && strpos((string)$_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false;
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
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors {$frame_ancestors}; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.sheetjs.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https://docs.google.com https://sheets.googleapis.com; frame-src {$frame_src}; upgrade-insecure-requests");

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
}

cotswold_send_security_headers();
