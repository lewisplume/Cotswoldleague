<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../geocode_init.php';

try {
    $summary = cotswold_geocode_missing_clubs($conn);
    fwrite(STDOUT, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit($summary['failed'] === [] ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, 'Geocoding failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
