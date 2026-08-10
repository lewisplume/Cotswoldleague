<?php

function cotswold_request_id(): string
{
    static $requestId = null;
    if ($requestId === null) {
        $incoming = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        $requestId = preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $incoming)
            ? $incoming
            : bin2hex(random_bytes(8));
        if (!headers_sent()) {
            header('X-Request-ID: ' . $requestId);
        }
    }
    return $requestId;
}

function cotswold_audit_event($conn, string $actor, string $action, array $details = []): void
{
    $safeDetails = [
        'request_id' => cotswold_request_id(),
        'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
        'path' => basename((string)($_SERVER['SCRIPT_NAME'] ?? '')),
    ];

    foreach ($details as $key => $value) {
        if (preg_match('/pass|pin|secret|token|cookie/i', (string)$key)) {
            continue;
        }
        if (is_scalar($value) || $value === null) {
            $safeDetails[(string)$key] = is_string($value)
                ? (function_exists('mb_substr') ? mb_substr($value, 0, 250) : substr($value, 0, 250))
                : $value;
        }
    }

    $encoded = json_encode($safeDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        $encoded = '{"request_id":"' . cotswold_request_id() . '"}';
    }

    try {
        $stmt = $conn->prepare('INSERT INTO audit_log (club_name, action, change_details) VALUES (?, ?, ?)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sss', $actor, $action, $encoded);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Audit event write failed [' . cotswold_request_id() . ']: ' . $e->getMessage());
    }
}

function cotswold_audit_authenticated_request($conn, string $actor, string $area, string $action): void
{
    cotswold_audit_event($conn, $actor, $area . ' request', [
        'action' => $action,
        'target_id' => $_POST['scoresheet_id'] ?? $_POST['teamsheet_id'] ?? $_POST['id'] ?? null,
        'season' => $_POST['season'] ?? $_POST['season_year'] ?? null,
        'http_status' => http_response_code(),
    ]);
}
