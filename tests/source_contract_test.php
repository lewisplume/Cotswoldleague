<?php

function contract_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$read = static fn(string $file): string => (string)file_get_contents($root . '/' . $file);

$clubs = $read('clubs.php');
contract_assert(!str_contains($clubs, "include 'geocode_init.php'"), 'Public club requests must not run geocoding.');

$security = $read('security_headers.php');
contract_assert(str_contains($security, 'cotswold_require_csrf'), 'Central CSRF enforcement helper must exist.');
contract_assert(str_contains($security, 'cotswold_is_trusted_proxy_request'), 'Forwarding headers must be gated by trusted proxy configuration.');

foreach (['gala_scoresheet_api.php', 'gala_admin_api.php', 'digital_teamsheet_api.php'] as $endpoint) {
    contract_assert(str_contains($read($endpoint), 'cotswold_require_csrf(true)'), "{$endpoint} must enforce CSRF on mutations.");
}

$admin = $read('league_admin.php');
contract_assert(str_contains($admin, 'cotswold_store_logo_upload'), 'Administrator logo uploads must use the verified image pipeline.');
contract_assert(!str_contains($admin, 'move_uploaded_file'), 'Raw uploads must not be moved into the image directory.');

contract_assert(!str_contains($read('spectators.php'), 'Everything you need to know for the 2026'), 'Spectator copy must use the active season.');
contract_assert(!str_contains($read('index.php'), 'February 13, 2027'), 'Homepage countdown must not use a hardcoded season date.');
contract_assert(!str_contains($read('table.php'), '🥇 Monnow SC'), 'Finals results must come from published season data.');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    $path = $file->getPathname();
    if (!$file->isFile() || $file->getExtension() !== 'php' || str_contains($path, '/assets/vendor/')) {
        continue;
    }
    $source = (string)file_get_contents($path);
    contract_assert(!preg_match('#https://(?:cdn\.tailwindcss\.com|unpkg\.com/lucide@latest|cdn\.sheetjs\.com/xlsx-latest)#', $source), basename($path) . ' uses an unpinned executable dependency.');
}

fwrite(STDOUT, "Source contract tests passed.\n");
