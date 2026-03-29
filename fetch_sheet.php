<?php
session_start();
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('No Sheet ID provided');
}
$sheetId = preg_replace('/[^a-zA-Z0-9-_]/', '', $_GET['id']);
$exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=xlsx";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $exportUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$headers = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
    if (stripos($header, 'content-disposition:') === 0) {
        $headers['Content-Disposition'] = trim($header);
    }
    return strlen($header);
});

$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$data) {
    http_response_code(500);
    die('Failed to download sheet from Google');
}

if (isset($headers['Content-Disposition'])) {
    header($headers['Content-Disposition']);
    header('Access-Control-Expose-Headers: Content-Disposition');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
echo $data;
