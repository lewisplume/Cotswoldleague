<?php
session_start();
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

function dts_format_ms_time($ms)
{
    if ($ms === null || $ms === '') {
        return '';
    }
    $ms = (int)$ms;
    $minutes = intdiv($ms, 60000);
    $seconds = intdiv($ms % 60000, 1000);
    $hundredths = intdiv($ms % 1000, 10);
    if ($minutes > 0) {
        return sprintf('%02d:%02d.%02d', $minutes, $seconds, $hundredths);
    }
    return sprintf('00:%02d.%02d', $seconds, $hundredths);
}

$stmt = $conn->prepare("SELECT ts.*, c.name AS club_name FROM club_teamsheets ts JOIN clubs c ON ts.club_id = c.id WHERE ts.id = ?");
$stmt->bind_param("i", $teamsheet_id);
$stmt->execute();
$teamsheet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teamsheet) {
    http_response_code(404);
    die('Teamsheet not found');
}

$can_view = (int)$teamsheet['club_id'] === $current_club_id || $is_super_admin;
if (!$can_view && $teamsheet['status'] === 'submitted' && !empty($teamsheet['venue_detail_id'])) {
    $venue_id = (int)$teamsheet['venue_detail_id'];
    $check = $conn->prepare("SELECT id FROM venue_details
        WHERE id = ? AND (club_id = ? OR team_1_id = ? OR team_2_id = ? OR team_3_id = ? OR team_4_id = ?
        OR team_5_id = ? OR team_6_id = ? OR team_7_id = ? OR team_8_id = ?)");
    $check->bind_param("iiiiiiiiii", $venue_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id, $current_club_id);
    $check->execute();
    $can_view = $check->get_result()->num_rows > 0;
    $check->close();
}

if (!$can_view) {
    http_response_code(403);
    die('Teamsheet is not shared with your club');
}

$filename = preg_replace('/[^A-Za-z0-9_-]+/', '_', $teamsheet['club_name'] . '_' . $teamsheet['round_key'] . '_teamsheet') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['No', 'Event', 'Swimmers Name', 'Cut Off Time', 'P.B Time', 'Notes For Host Team']);

$sql = "SELECT e.*, ge.event_number, ge.event_name, ge.event_type, ge.cut_off_time_ms,
               ge.a_final_event_name, ge.a_final_cut_off_time_ms
        FROM club_teamsheet_entries e
        JOIN gala_events ge ON e.event_id = ge.id
        WHERE e.teamsheet_id = ?
        ORDER BY ge.event_number ASC";
$entry_stmt = $conn->prepare($sql);
$entry_stmt->bind_param("i", $teamsheet_id);
$entry_stmt->execute();
$res = $entry_stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $selected = json_decode($row['selected_swimmers_json'] ?: '[]', true) ?: [];
    $is_a_final = $teamsheet['gala_type'] === 'a_final' && $row['a_final_event_name'];
    $event_name = $is_a_final ? $row['a_final_event_name'] : $row['event_name'];
    $cut_off = dts_format_ms_time($is_a_final ? $row['a_final_cut_off_time_ms'] : $row['cut_off_time_ms']);
    if ($row['event_type'] === 'Cannon') {
        $cut_off = 'No Limit';
    }
    fputcsv($out, [
        $row['event_number'],
        $event_name,
        implode(', ', $selected),
        $cut_off,
        $row['pb_snapshot'],
        $row['notes'],
    ]);
}
$entry_stmt->close();
fclose($out);
