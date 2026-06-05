<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
include 'db.php';

$is_logged_in = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;
$current_club_id = (int)($_SESSION['club_id'] ?? 0);

if (!$is_logged_in && !$is_super_admin) {
    http_response_code(403);
    die('Unauthorized');
}

$teamsheet_id = (int)($_GET['id'] ?? 0);
if (!$teamsheet_id) {
    http_response_code(400);
    die('Missing teamsheet id');
}

function dts_file_can_view($conn, $viewer_club_id, $is_super_admin, $teamsheet)
{
    if ($is_super_admin || (int)$teamsheet['club_id'] === (int)$viewer_club_id) {
        return true;
    }
    if ($teamsheet['status'] !== 'submitted' || empty($teamsheet['venue_detail_id'])) {
        return false;
    }

    $venue_id = (int)$teamsheet['venue_detail_id'];
    $stmt = $conn->prepare("SELECT id FROM venue_details
        WHERE id = ? AND (
            (
                (round_number = 99 OR gala_type IN ('a_final','b_final','c_final'))
                AND (team_1_id = ? OR team_2_id = ? OR team_3_id = ? OR team_4_id = ?
                     OR team_5_id = ? OR team_6_id = ? OR team_7_id = ? OR team_8_id = ?)
            )
            OR (
                round_number != 99
                AND (club_id = ? OR team_1_id = ? OR team_2_id = ? OR team_3_id = ? OR team_4_id = ?
                     OR team_5_id = ? OR team_6_id = ? OR team_7_id = ? OR team_8_id = ?)
            )
        )");
    $stmt->bind_param(
        "iiiiiiiiiiiiiiiiii",
        $venue_id,
        $viewer_club_id, $viewer_club_id, $viewer_club_id, $viewer_club_id,
        $viewer_club_id, $viewer_club_id, $viewer_club_id, $viewer_club_id,
        $viewer_club_id, $viewer_club_id, $viewer_club_id, $viewer_club_id, $viewer_club_id,
        $viewer_club_id, $viewer_club_id, $viewer_club_id, $viewer_club_id
    );
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

$stmt = $conn->prepare("SELECT * FROM club_teamsheets WHERE id = ? AND submission_type = 'upload' LIMIT 1");
$stmt->bind_param("i", $teamsheet_id);
$stmt->execute();
$teamsheet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teamsheet || !dts_file_can_view($conn, $current_club_id, $is_super_admin, $teamsheet)) {
    http_response_code(404);
    die('Teamsheet file not found or not shared with your club');
}

$path = $teamsheet['upload_file_path'] ?? '';
$upload_root = realpath(__DIR__ . '/uploads/teamsheets');
$real_path = $path !== '' ? realpath(__DIR__ . '/' . ltrim($path, '/')) : false;
if ($upload_root === false || $real_path === false || strpos($real_path, $upload_root . DIRECTORY_SEPARATOR) !== 0 || !is_file($real_path)) {
    http_response_code(404);
    die('Teamsheet file is missing');
}

$download_name = $teamsheet['upload_original_name'] ?: basename($path);
$download_name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $download_name);
$mime = preg_replace('/[^A-Za-z0-9.+\/-]+/', '', $teamsheet['upload_mime_type'] ?: 'application/octet-stream');
$size = (int)($teamsheet['upload_file_size'] ?: filesize($real_path));
$encoded_name = rawurlencode($download_name);

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Content-Disposition: attachment; filename="' . addcslashes($download_name, "\\\"") . '"; filename*=UTF-8\'\'' . $encoded_name);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($real_path);
