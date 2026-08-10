<?php

require_once __DIR__ . '/../security_headers.php';

function security_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'league.test';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ORIGIN'] = 'https://league.test';
unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['HTTP_CF_VISITOR']);

security_assert(!cotswold_is_https_request(), 'Untrusted forwarding headers must not imply HTTPS.');
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
security_assert(!cotswold_is_https_request(), 'An untrusted client cannot spoof X-Forwarded-Proto.');
$_SERVER['HTTPS'] = 'on';
security_assert(cotswold_is_https_request(), 'Direct HTTPS must be detected.');

cotswold_secure_session_start();
$token = cotswold_ensure_csrf_token(true);
$_POST['_csrf_token'] = $token;
security_assert(cotswold_csrf_is_valid(), 'A matching same-origin CSRF token must pass.');

$_POST['_csrf_token'] = 'wrong';
security_assert(!cotswold_csrf_is_valid(), 'A mismatched CSRF token must fail.');

$_POST['_csrf_token'] = $token;
$_SERVER['HTTP_ORIGIN'] = 'https://attacker.example';
security_assert(!cotswold_csrf_is_valid(), 'A cross-origin request must fail even with a token.');

fwrite(STDOUT, "Security helper tests passed.\n");
