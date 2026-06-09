<?php
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
include 'db.php';
include_once 'finals_sync.php';

header('Content-Type: application/json');

$active_season_year = $current_season_year ?? 2026;
$is_logged_in = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;
$current_club_id = (int)($_SESSION['club_id'] ?? 0);
$current_club_name = $_SESSION['club_name'] ?? 'Club User';

if (!$is_logged_in && !$is_super_admin) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resolve_digital_teamsheet_club($conn, $is_super_admin, $session_club_id, $session_club_name)
{
    $requested_club_id = (int)($_GET['club_id'] ?? $_POST['club_id'] ?? 0);
    $club_id = ($is_super_admin && $requested_club_id > 0) ? $requested_club_id : (int)$session_club_id;
    if ($club_id <= 0) {
        return [0, $session_club_name ?: 'Club User'];
    }

    $stmt = $conn->prepare("SELECT name FROM clubs WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [0, $session_club_name ?: 'Club User'];
    }

    $club_name = $is_super_admin ? 'Super Admin for ' . $row['name'] : ($session_club_name ?: $row['name']);
    return [$club_id, $club_name];
}

[$target_club_id, $target_club_name] = resolve_digital_teamsheet_club($conn, $is_super_admin, $current_club_id, $current_club_name);
if ($target_club_id <= 0) {
    echo json_encode(['error' => 'No club selected']);
    exit;
}

function json_input($key, $default = [])
{
    $raw = $_POST[$key] ?? null;
    if ($raw === null) {
        return $default;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function format_ms_time($ms)
{
    if ($ms === null || $ms === '') {
        return '';
    }
    $ms = (int)$ms;
    $minutes = intdiv($ms, 60000);
    $seconds = intdiv($ms % 60000, 1000);
    $hundredths = intdiv($ms % 1000, 10);
    if ($minutes > 0) {
        return sprintf('%d:%02d.%02d', $minutes, $seconds, $hundredths);
    }
    return sprintf('%d.%02d', $seconds, $hundredths);
}

function parse_teamunify_date($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    foreach (['d/m/Y', 'd/m/y', 'Y-m-d'] as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date instanceof DateTime) {
            return $date;
        }
    }
    return null;
}

function find_finals_date($conn, $season)
{
    $setting_key = 'finals_date_' . $season;
    $setting_stmt = $conn->prepare("SELECT setting_value FROM global_settings WHERE setting_key = ? LIMIT 1");
    $setting_stmt->bind_param("s", $setting_key);
    $setting_stmt->execute();
    $setting_row = $setting_stmt->get_result()->fetch_assoc();
    $setting_stmt->close();
    $date = parse_teamunify_date($setting_row['setting_value'] ?? '');
    if ($date) {
        return $date;
    }

    $stmt = $conn->prepare("SELECT round_date FROM venue_details WHERE season_year = ? AND (round_number = 99 OR gala_type IN ('a_final','b_final','c_final')) ORDER BY round_date DESC LIMIT 1");
    $stmt->bind_param("i", $season);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    return parse_teamunify_date($row['round_date'] ?? '');
}

function league_age_group_from_dob($dob, $finals_date)
{
    if (!$dob || !$finals_date) {
        return '';
    }
    $age = $dob->diff($finals_date)->y;
    if ($age <= 11) {
        return '11/U';
    }
    if ($age <= 13) {
        return '13/U';
    }
    if ($age <= 15) {
        return '15/U';
    }
    return 'Open';
}

function normalise_teamunify_name($name)
{
    $name = trim((string)$name);
    if (strpos($name, ',') !== false) {
        [$surname, $forenames] = array_map('trim', explode(',', $name, 2));
        $name = trim($forenames . ' ' . $surname);
    }
    return preg_replace('/\s+/', ' ', $name);
}

function normalise_teamunify_time($time)
{
    $time = strtoupper(trim((string)$time));
    return rtrim($time, 'S');
}

function safe_download_name($name)
{
    $name = trim((string)$name);
    $name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name, ' .') ?: 'teamsheet';
}

function safe_filename_token($value)
{
    $value = trim((string)$value);
    $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value);
    return trim($value, '_') ?: 'teamsheet';
}

function ensure_upload_dir($dir)
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        return false;
    }
    return is_dir($dir) && is_writable($dir);
}

function teamunify_event_field($event)
{
    $event = strtolower(trim((string)$event));
    $map = [
        '25 free' => 'pb_free_25',
        '25 back' => 'pb_back_25',
        '25 breast' => 'pb_breast_25',
        '25 fly' => 'pb_fly_25',
        '50 free' => 'pb_free_50',
        '50 back' => 'pb_back_50',
        '50 breast' => 'pb_breast_50',
        '50 fly' => 'pb_fly_50',
        '100 free' => 'pb_free_100',
        '100 back' => 'pb_back_100',
        '100 breast' => 'pb_breast_100',
        '100 fly' => 'pb_fly_100',
        '100 im' => 'pb_im',
    ];
    return $map[$event] ?? '';
}

function is_academy_swim_team_club($conn, $club_id)
{
    $stmt = $conn->prepare("SELECT name FROM clubs WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row && strcasecmp(trim((string)$row['name']), 'Academy Swim Team') === 0;
}

function fetch_ast_swimmer_export()
{
    if (!defined('COTSWOLD_AST_IMPORT_URL') || COTSWOLD_AST_IMPORT_URL === '' || !defined('COTSWOLD_AST_IMPORT_TOKEN') || COTSWOLD_AST_IMPORT_TOKEN === '') {
        return ['error' => 'AST import is not configured on the Cotswold League server.'];
    }

    $headers = [
        'Authorization: Bearer ' . COTSWOLD_AST_IMPORT_TOKEN,
        'X-Cotswold-Token: ' . COTSWOLD_AST_IMPORT_TOKEN,
        'Accept: application/json',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init(COTSWOLD_AST_IMPORT_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['error' => 'Could not contact the AST site: ' . $error];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
            ],
        ]);
        $body = @file_get_contents(COTSWOLD_AST_IMPORT_URL, false, $context);
        $status = 200;

        if ($body === false) {
            return ['error' => 'Could not contact the AST site.'];
        }

        $response_headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
    }

    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        return ['error' => 'The AST site returned an invalid response.'];
    }
    if (isset($response_headers[0]) && preg_match('/\s(\d{3})\s/', $response_headers[0], $matches)) {
        $status = (int)$matches[1];
    }
    if ($status < 200 || $status >= 300) {
        return ['error' => $data['error'] ?? 'The AST site rejected the import request.'];
    }
    if (($data['club'] ?? '') !== 'Academy Swim Team' || ($data['course'] ?? '') !== 'SCM' || !isset($data['swimmers']) || !is_array($data['swimmers'])) {
        return ['error' => 'The AST response did not contain the expected SCM swimmer export.'];
    }

    return $data;
}

function parse_teamunify_csv($path, $finals_date)
{
    $handle = fopen($path, 'r');
    if (!$handle) {
        return ['error' => 'Could not read uploaded CSV.'];
    }

    $header = fgetcsv($handle);
    if (!$header || count($header) < 3) {
        fclose($handle);
        return ['error' => 'The TeamUnify CSV header was not recognised.'];
    }

    $swimmers = [];
    $current_key = null;
    $ignored_events = [];
    $mapped_rows = 0;
    $event_rows = 0;

    $pb_fields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];

    while (($row = fgetcsv($handle)) !== false) {
        $rank = trim($row[0] ?? '');
        $event = trim($row[1] ?? '');
        $best_time = normalise_teamunify_time($row[2] ?? '');

        if ($rank !== '' && preg_match('/^(.+):\s*(\d{1,2}\/\d{1,2}\/\d{2,4})\s*\(([^)]+)\)/', $rank, $matches)) {
            $dob = parse_teamunify_date($matches[2]);
            $name = normalise_teamunify_name($matches[1]);
            if ($name === '') {
                $current_key = null;
                continue;
            }
            $current_key = strtolower($name);
            if (!isset($swimmers[$current_key])) {
                $swimmer = [
                    'id' => 0,
                    'swimmer_name' => $name,
                    'age_group' => league_age_group_from_dob($dob, $finals_date),
                    'availability' => [],
                    'import_meta' => [
                        'dob' => $dob ? $dob->format('d/m/Y') : '',
                        'teamunify_group' => trim($matches[3] ?? ''),
                    ],
                ];
                foreach ($pb_fields as $field) {
                    $swimmer[$field] = '';
                }
                $swimmers[$current_key] = $swimmer;
            }
            continue;
        }

        if ($current_key && preg_match('/^\d+$/', $rank) && $event !== '' && $best_time !== '') {
            $event_rows++;
            $field = teamunify_event_field($event);
            if ($field !== '') {
                $swimmers[$current_key][$field] = $best_time;
                $mapped_rows++;
            } else {
                $ignored_events[$event] = ($ignored_events[$event] ?? 0) + 1;
            }
        }
    }
    fclose($handle);

    ksort($ignored_events);
    return [
        'swimmers' => array_values($swimmers),
        'summary' => [
            'swimmer_count' => count($swimmers),
            'event_rows' => $event_rows,
            'mapped_rows' => $mapped_rows,
            'ignored_events' => $ignored_events,
            'finals_date' => $finals_date ? $finals_date->format('d/m/Y') : '',
            'age_groups_calculated' => $finals_date ? true : false,
        ],
    ];
}

function get_round_options($conn, $club_id, $season)
{
    $sql = "SELECT vd.id, vd.round_number, vd.gala_type, vd.club_id AS host_club_id, vd.venue_name,
                   c_host.name AS host_name,
                   c1.name AS team1_name, c2.name AS team2_name, c3.name AS team3_name, c4.name AS team4_name,
                   c5.name AS team5_name, c6.name AS team6_name, c7.name AS team7_name, c8.name AS team8_name
            FROM venue_details vd
            LEFT JOIN clubs c_host ON vd.club_id = c_host.id
            LEFT JOIN clubs c1 ON vd.team_1_id = c1.id
            LEFT JOIN clubs c2 ON vd.team_2_id = c2.id
            LEFT JOIN clubs c3 ON vd.team_3_id = c3.id
            LEFT JOIN clubs c4 ON vd.team_4_id = c4.id
            LEFT JOIN clubs c5 ON vd.team_5_id = c5.id
            LEFT JOIN clubs c6 ON vd.team_6_id = c6.id
            LEFT JOIN clubs c7 ON vd.team_7_id = c7.id
            LEFT JOIN clubs c8 ON vd.team_8_id = c8.id
            WHERE vd.season_year = ?
              AND (
                   (
                       (vd.round_number = 99 OR vd.gala_type IN ('a_final','b_final','c_final'))
                       AND (vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ?
                            OR vd.team_5_id = ? OR vd.team_6_id = ? OR vd.team_7_id = ? OR vd.team_8_id = ?)
                   )
                   OR (
                       vd.round_number != 99
                       AND (vd.club_id = ? OR vd.team_1_id = ? OR vd.team_2_id = ? OR vd.team_3_id = ? OR vd.team_4_id = ?
                            OR vd.team_5_id = ? OR vd.team_6_id = ? OR vd.team_7_id = ? OR vd.team_8_id = ?)
                   )
              )
            ORDER BY vd.round_number ASC, c_host.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiiiiiiiiiiiiiii",
        $season,
        $club_id, $club_id, $club_id, $club_id, $club_id, $club_id, $club_id, $club_id,
        $club_id, $club_id, $club_id, $club_id, $club_id, $club_id, $club_id, $club_id, $club_id
    );
    $stmt->execute();
    $res = $stmt->get_result();
    $rounds = [];
    while ($row = $res->fetch_assoc()) {
        $teams = array_values(array_filter([
            $row['host_name'],
            $row['team1_name'],
            $row['team2_name'],
            $row['team3_name'],
            $row['team4_name'],
            $row['team5_name'],
            $row['team6_name'],
            $row['team7_name'],
            $row['team8_name'],
        ]));
        $gala_type = $row['gala_type'] ?: 'round';
        $round_key = $gala_type === 'round' ? 'round_' . (int)$row['round_number'] : $gala_type;
        $label = 'Round ' . (int)$row['round_number'] . ' - ' . $row['host_name'];
        if ($gala_type !== 'round') {
            $label = ucwords(str_replace('_', ' ', $gala_type));
            if (!empty($row['venue_name']) && stripos($row['venue_name'], 'Venue TBC') === false) {
                $label .= ' - ' . $row['venue_name'];
            }
        }
        $rounds[] = [
            'venue_detail_id' => (int)$row['id'],
            'round_number' => (int)$row['round_number'],
            'round_key' => $round_key,
            'gala_type' => $gala_type,
            'host_name' => $row['host_name'],
            'teams' => array_values(array_unique($teams)),
            'label' => $label,
        ];
    }
    $stmt->close();
    return $rounds;
}

function finals_are_configured($conn, $season)
{
    $setting_key = 'finals_date_' . $season;
    $stmt = $conn->prepare("SELECT 1 FROM global_settings WHERE setting_key = ? AND setting_value != '' LIMIT 1");
    $stmt->bind_param("s", $setting_key);
    $stmt->execute();
    $has_setting = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($has_setting) {
        return true;
    }

    $stmt = $conn->prepare("SELECT 1 FROM venue_details WHERE season_year = ? AND (round_number = 99 OR gala_type IN ('a_final','b_final','c_final')) LIMIT 1");
    $stmt->bind_param("i", $season);
    $stmt->execute();
    $has_rows = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $has_rows;
}

function can_view_teamsheet($conn, $viewer_club_id, $teamsheet)
{
    if ((int)$teamsheet['club_id'] === (int)$viewer_club_id) {
        return true;
    }
    if ($teamsheet['status'] !== 'submitted') {
        return false;
    }
    if (empty($teamsheet['venue_detail_id'])) {
        return false;
    }
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
    $venue_id = (int)$teamsheet['venue_detail_id'];
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

function load_teamsheet_payload($conn, $teamsheet_id, $viewer_club_id)
{
    $stmt = $conn->prepare("SELECT ts.*, c.name AS club_name FROM club_teamsheets ts JOIN clubs c ON ts.club_id = c.id WHERE ts.id = ?");
    $stmt->bind_param("i", $teamsheet_id);
    $stmt->execute();
    $teamsheet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$teamsheet || !can_view_teamsheet($conn, $viewer_club_id, $teamsheet)) {
        return null;
    }

    $entries = [];
    $e_stmt = $conn->prepare("SELECT e.*, ge.event_number, ge.event_name, ge.event_type, ge.distance, ge.cut_off_time_ms,
                                     ge.a_final_event_name, ge.a_final_distance, ge.a_final_cut_off_time_ms
                              FROM club_teamsheet_entries e
                              JOIN gala_events ge ON e.event_id = ge.id
                              WHERE e.teamsheet_id = ?
                              ORDER BY ge.event_number ASC");
    $e_stmt->bind_param("i", $teamsheet_id);
    $e_stmt->execute();
    $res = $e_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $is_a_final = $teamsheet['gala_type'] === 'a_final' && $row['a_final_event_name'];
        $entries[] = [
            'event_id' => (int)$row['event_id'],
            'event_number' => (int)$row['event_number'],
            'event_name' => $is_a_final ? $row['a_final_event_name'] : $row['event_name'],
            'event_type' => $row['event_type'],
            'distance' => $is_a_final ? $row['a_final_distance'] : $row['distance'],
            'cut_off' => format_ms_time($is_a_final ? $row['a_final_cut_off_time_ms'] : $row['cut_off_time_ms']),
            'selected_swimmers' => json_decode($row['selected_swimmers_json'] ?: '[]', true) ?: [],
            'pb_snapshot' => $row['pb_snapshot'],
            'notes' => $row['notes'],
        ];
    }
    $e_stmt->close();

    $audit = [];
    $a_stmt = $conn->prepare("SELECT changed_by, reason, change_summary, created_at FROM club_teamsheet_audit WHERE teamsheet_id = ? ORDER BY created_at DESC LIMIT 12");
    $a_stmt->bind_param("i", $teamsheet_id);
    $a_stmt->execute();
    $a_res = $a_stmt->get_result();
    while ($row = $a_res->fetch_assoc()) {
        $audit[] = $row;
    }
    $a_stmt->close();

    return [
        'teamsheet' => [
            'id' => (int)$teamsheet['id'],
            'club_id' => (int)$teamsheet['club_id'],
            'club_name' => $teamsheet['club_name'],
            'season_year' => (int)$teamsheet['season_year'],
            'round_key' => $teamsheet['round_key'],
            'gala_type' => $teamsheet['gala_type'],
            'venue_detail_id' => $teamsheet['venue_detail_id'] ? (int)$teamsheet['venue_detail_id'] : null,
            'submission_type' => $teamsheet['submission_type'] ?: 'builder',
            'upload_original_name' => $teamsheet['upload_original_name'],
            'upload_mime_type' => $teamsheet['upload_mime_type'],
            'upload_file_size' => $teamsheet['upload_file_size'] ? (int)$teamsheet['upload_file_size'] : null,
            'upload_url' => !empty($teamsheet['upload_file_path']) ? 'digital_teamsheet_file.php?id=' . (int)$teamsheet['id'] : '',
            'status' => $teamsheet['status'],
            'submitted_at' => $teamsheet['submitted_at'],
            'updated_at' => $teamsheet['updated_at'],
            'last_reason' => $teamsheet['last_reason'],
            'can_edit' => (int)$teamsheet['club_id'] === (int)$viewer_club_id,
        ],
        'entries' => $entries,
        'audit' => $audit,
    ];
}

if ($action === 'import_ast_swimmers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $season = (int)($_POST['season'] ?? $active_season_year);
    if (!is_academy_swim_team_club($conn, $target_club_id)) {
        echo json_encode(['error' => 'AST import is only available in the Academy Swim Team portal.']);
        exit;
    }

    $export = fetch_ast_swimmer_export();
    if (isset($export['error'])) {
        echo json_encode(['error' => $export['error']]);
        exit;
    }

    $finals_date = find_finals_date($conn, $season);
    $existing = [];
    $existing_stmt = $conn->prepare("SELECT LOWER(swimmer_name) AS swimmer_key FROM club_swimmers WHERE club_id = ? AND season_year = ?");
    $existing_stmt->bind_param("ii", $target_club_id, $season);
    $existing_stmt->execute();
    $existing_res = $existing_stmt->get_result();
    while ($row = $existing_res->fetch_assoc()) {
        $existing[$row['swimmer_key']] = true;
    }
    $existing_stmt->close();

    $sql = "INSERT INTO club_swimmers
        (club_id, season_year, swimmer_name, gender, date_of_birth, ast_source_member_id, age_group,
         pb_free_25, pb_back_25, pb_breast_25, pb_fly_25,
         pb_free_50, pb_back_50, pb_breast_50, pb_fly_50, pb_im,
         pb_free_100, pb_back_100, pb_breast_100, pb_fly_100, availability_json, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '{}', 1)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            swimmer_name = VALUES(swimmer_name),
            gender = VALUES(gender),
            date_of_birth = VALUES(date_of_birth),
            ast_source_member_id = VALUES(ast_source_member_id),
            age_group = VALUES(age_group),
            pb_free_25 = VALUES(pb_free_25), pb_back_25 = VALUES(pb_back_25), pb_breast_25 = VALUES(pb_breast_25), pb_fly_25 = VALUES(pb_fly_25),
            pb_free_50 = VALUES(pb_free_50), pb_back_50 = VALUES(pb_back_50), pb_breast_50 = VALUES(pb_breast_50), pb_fly_50 = VALUES(pb_fly_50),
            pb_im = VALUES(pb_im),
            pb_free_100 = VALUES(pb_free_100), pb_back_100 = VALUES(pb_back_100), pb_breast_100 = VALUES(pb_breast_100), pb_fly_100 = VALUES(pb_fly_100),
            is_active = 1";
    $stmt = $conn->prepare($sql);

    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $saved = [];
    $pb_fields = ['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'];

    foreach ($export['swimmers'] as $swimmer) {
        $name = trim((string)($swimmer['full_name'] ?? ''));
        if ($name === '') {
            $skipped++;
            continue;
        }

        $gender = trim((string)($swimmer['gender'] ?? ''));
        $dob_value = trim((string)($swimmer['date_of_birth'] ?? ''));
        $dob = parse_teamunify_date($dob_value);
        $dob_sql = $dob ? $dob->format('Y-m-d') : null;
        $source_id = (int)($swimmer['source_member_id'] ?? 0);
        $age_group = league_age_group_from_dob($dob, $finals_date);
        $pbs = is_array($swimmer['pbs'] ?? null) ? $swimmer['pbs'] : [];
        $vals = [];
        foreach ($pb_fields as $field) {
            $vals[$field] = trim((string)($pbs[$field] ?? ''));
        }

        $key = strtolower($name);
        if (isset($existing[$key])) {
            $updated++;
        } else {
            $imported++;
            $existing[$key] = true;
        }

        $stmt->bind_param(
            "iisssissssssssssssss",
            $target_club_id,
            $season,
            $name,
            $gender,
            $dob_sql,
            $source_id,
            $age_group,
            $vals['pb_free_25'],
            $vals['pb_back_25'],
            $vals['pb_breast_25'],
            $vals['pb_fly_25'],
            $vals['pb_free_50'],
            $vals['pb_back_50'],
            $vals['pb_breast_50'],
            $vals['pb_fly_50'],
            $vals['pb_im'],
            $vals['pb_free_100'],
            $vals['pb_back_100'],
            $vals['pb_breast_100'],
            $vals['pb_fly_100']
        );
        $stmt->execute();
        $saved[] = [
            'id' => (int)$conn->insert_id,
            'swimmer_name' => $name,
            'age_group' => $age_group,
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'swimmer_count' => count($export['swimmers']),
        'finals_date' => $finals_date ? $finals_date->format('d/m/Y') : '',
        'swimmers' => $saved,
    ]);
    exit;
}

if ($action === 'load') {
    $season = (int)($_GET['season'] ?? $active_season_year);
    $club_id = $target_club_id;

    $swimmers = [];
    $stmt = $conn->prepare("SELECT * FROM club_swimmers WHERE club_id = ? AND season_year = ? AND is_active = 1 ORDER BY swimmer_name ASC");
    $stmt->bind_param("ii", $club_id, $season);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['availability'] = json_decode($row['availability_json'] ?: '{}', true) ?: [];
        unset($row['availability_json']);
        $swimmers[] = $row;
    }
    $stmt->close();

    $finals_sync = null;
    if (finals_are_configured($conn, $season)) {
        $finals_sync = cotswold_sync_finals_from_standings($conn, $season);
    }

    $rounds = get_round_options($conn, $club_id, $season);

    $events = [];
    $e_stmt = $conn->prepare("SELECT * FROM gala_events WHERE season_year = ? ORDER BY event_number ASC");
    $e_stmt->bind_param("i", $season);
    $e_stmt->execute();
    $e_res = $e_stmt->get_result();
    while ($row = $e_res->fetch_assoc()) {
        $events[] = [
            'id' => (int)$row['id'],
            'event_number' => (int)$row['event_number'],
            'event_name' => $row['event_name'],
            'distance' => $row['distance'],
            'age_group' => $row['age_group'],
            'gender' => $row['gender'],
            'event_type' => $row['event_type'],
            'cut_off' => format_ms_time($row['cut_off_time_ms']),
            'a_final_event_name' => $row['a_final_event_name'],
            'a_final_distance' => $row['a_final_distance'],
            'a_final_cut_off' => format_ms_time($row['a_final_cut_off_time_ms']),
        ];
    }
    $e_stmt->close();

    $teamsheets = [];
    $ts_stmt = $conn->prepare("SELECT id, round_key, venue_detail_id, status, submitted_at, updated_at, submission_type, upload_original_name FROM club_teamsheets WHERE club_id = ? AND season_year = ?");
    $ts_stmt->bind_param("ii", $club_id, $season);
    $ts_stmt->execute();
    $ts_res = $ts_stmt->get_result();
    while ($row = $ts_res->fetch_assoc()) {
        $teamsheets[$row['round_key']] = [
            'id' => (int)$row['id'],
            'round_key' => $row['round_key'],
            'venue_detail_id' => $row['venue_detail_id'] ? (int)$row['venue_detail_id'] : null,
            'status' => $row['status'],
            'submitted_at' => $row['submitted_at'],
            'updated_at' => $row['updated_at'],
            'submission_type' => $row['submission_type'] ?: 'builder',
            'upload_original_name' => $row['upload_original_name'],
        ];
    }
    $ts_stmt->close();

    $shared = [];
    if (!empty($rounds)) {
        $venue_ids = array_values(array_unique(array_filter(array_map(fn($r) => (int)$r['venue_detail_id'], $rounds))));
        if (!empty($venue_ids)) {
            $in = implode(',', array_map('intval', $venue_ids));
            $res = $conn->query("SELECT ts.id, ts.club_id, ts.round_key, ts.venue_detail_id, ts.status, ts.submitted_at, ts.updated_at,
                    ts.submission_type, ts.upload_original_name, ts.upload_file_size, c.name AS club_name
                FROM club_teamsheets ts JOIN clubs c ON ts.club_id = c.id
                WHERE ts.season_year = $season AND ts.venue_detail_id IN ($in) AND ts.status = 'submitted'
                ORDER BY ts.round_key ASC, c.name ASC");
            while ($row = $res->fetch_assoc()) {
                $shared[] = [
                    'id' => (int)$row['id'],
                    'club_id' => (int)$row['club_id'],
                    'club_name' => $row['club_name'],
                    'round_key' => $row['round_key'],
                    'venue_detail_id' => (int)$row['venue_detail_id'],
                    'status' => $row['status'],
                    'submitted_at' => $row['submitted_at'],
                    'updated_at' => $row['updated_at'],
                    'submission_type' => $row['submission_type'] ?: 'builder',
                    'upload_original_name' => $row['upload_original_name'],
                    'upload_file_size' => $row['upload_file_size'] ? (int)$row['upload_file_size'] : null,
                    'upload_url' => ($row['submission_type'] ?? '') === 'upload' ? 'digital_teamsheet_file.php?id=' . (int)$row['id'] : '',
                    'is_mine' => (int)$row['club_id'] === (int)$club_id,
                ];
            }
        }
    }

    echo json_encode([
        'season' => $season,
        'club_id' => $club_id,
        'swimmers' => $swimmers,
        'rounds' => $rounds,
        'events' => $events,
        'teamsheets' => $teamsheets,
        'shared' => $shared,
        'finals_sync' => $finals_sync,
    ]);
    exit;
}

if ($action === 'copy_swimmers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_year = (int)($_POST['source_year'] ?? 0);
    $target_year = (int)($_POST['target_year'] ?? $active_season_year);
    if (!$source_year || !$target_year) {
        echo json_encode(['error' => 'Missing source or target season']);
        exit;
    }

    $sql = "INSERT IGNORE INTO club_swimmers
        (club_id, season_year, swimmer_name, gender, date_of_birth, ast_source_member_id, age_group, pb_free_25, pb_back_25, pb_breast_25, pb_fly_25,
         pb_free_50, pb_back_50, pb_breast_50, pb_fly_50, pb_im, pb_free_100, pb_back_100, pb_breast_100, pb_fly_100, availability_json)
        SELECT club_id, ?, swimmer_name, gender, date_of_birth, ast_source_member_id, age_group, pb_free_25, pb_back_25, pb_breast_25, pb_fly_25,
               pb_free_50, pb_back_50, pb_breast_50, pb_fly_50, pb_im, pb_free_100, pb_back_100, pb_breast_100, pb_fly_100, '{}'
        FROM club_swimmers
        WHERE club_id = ? AND season_year = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $target_year, $target_club_id, $source_year);
    $stmt->execute();
    $copied = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['success' => true, 'copied' => $copied]);
    exit;
}

if ($action === 'preview_teamunify_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $season = (int)($_POST['season'] ?? $active_season_year);
    if (empty($_FILES['teamunify_csv']) || ($_FILES['teamunify_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Please choose a TeamUnify CSV export to import.']);
        exit;
    }

    $file = $_FILES['teamunify_csv'];
    $name = strtolower($file['name'] ?? '');
    if (substr($name, -4) !== '.csv') {
        echo json_encode(['error' => 'The TeamUnify import must be a CSV file.']);
        exit;
    }

    $finals_date = find_finals_date($conn, $season);
    $parsed = parse_teamunify_csv($file['tmp_name'], $finals_date);
    if (!empty($parsed['error'])) {
        echo json_encode(['error' => $parsed['error']]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'swimmers' => $parsed['swimmers'],
        'summary' => $parsed['summary'],
    ]);
    exit;
}

if ($action === 'save_swimmers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $season = (int)($_POST['season'] ?? $active_season_year);
    $swimmers = json_input('swimmers');
    $seen_ids = [];

    $stmt = $conn->prepare("INSERT INTO club_swimmers
        (id, club_id, season_year, swimmer_name, age_group, pb_free_25, pb_back_25, pb_breast_25, pb_fly_25,
         pb_free_50, pb_back_50, pb_breast_50, pb_fly_50, pb_im, pb_free_100, pb_back_100, pb_breast_100, pb_fly_100, availability_json, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            swimmer_name = VALUES(swimmer_name), age_group = VALUES(age_group),
            pb_free_25 = VALUES(pb_free_25), pb_back_25 = VALUES(pb_back_25), pb_breast_25 = VALUES(pb_breast_25), pb_fly_25 = VALUES(pb_fly_25),
            pb_free_50 = VALUES(pb_free_50), pb_back_50 = VALUES(pb_back_50), pb_breast_50 = VALUES(pb_breast_50), pb_fly_50 = VALUES(pb_fly_50),
            pb_im = VALUES(pb_im), pb_free_100 = VALUES(pb_free_100), pb_back_100 = VALUES(pb_back_100), pb_breast_100 = VALUES(pb_breast_100), pb_fly_100 = VALUES(pb_fly_100),
            availability_json = VALUES(availability_json), is_active = 1");

    $saved_swimmers = [];
    foreach ($swimmers as $swimmer) {
        $name = trim($swimmer['swimmer_name'] ?? '');
        if ($name === '') {
            continue;
        }
        $id = (int)($swimmer['id'] ?? 0);
        $age = trim($swimmer['age_group'] ?? '');
        $availability = json_encode($swimmer['availability'] ?? []);
        $vals = [];
        foreach (['pb_free_25','pb_back_25','pb_breast_25','pb_fly_25','pb_free_50','pb_back_50','pb_breast_50','pb_fly_50','pb_im','pb_free_100','pb_back_100','pb_breast_100','pb_fly_100'] as $field) {
            $vals[$field] = trim($swimmer[$field] ?? '');
        }
        $stmt->bind_param(
            "iiissssssssssssssss",
            $id,
            $target_club_id,
            $season,
            $name,
            $age,
            $vals['pb_free_25'],
            $vals['pb_back_25'],
            $vals['pb_breast_25'],
            $vals['pb_fly_25'],
            $vals['pb_free_50'],
            $vals['pb_back_50'],
            $vals['pb_breast_50'],
            $vals['pb_fly_50'],
            $vals['pb_im'],
            $vals['pb_free_100'],
            $vals['pb_back_100'],
            $vals['pb_breast_100'],
            $vals['pb_fly_100'],
            $availability
        );
        $stmt->execute();
        $saved_id = $id ?: $conn->insert_id;
        $seen_ids[] = $saved_id;
        $saved_swimmers[] = [
            'id' => $saved_id,
            'swimmer_name' => $name
        ];
    }
    $stmt->close();

    if (!empty($seen_ids)) {
        $id_list = implode(',', array_map('intval', array_filter($seen_ids)));
        if ($id_list !== '') {
            $conn->query("UPDATE club_swimmers SET is_active = 0 WHERE club_id = $target_club_id AND season_year = $season AND id NOT IN ($id_list)");
        }
    } else {
        $conn->query("UPDATE club_swimmers SET is_active = 0 WHERE club_id = $target_club_id AND season_year = $season");
    }

    echo json_encode(['success' => true, 'swimmers' => $saved_swimmers]);
    exit;
}

if ($action === 'save_teamsheet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $season = (int)($_POST['season'] ?? $active_season_year);
    $round_key = $_POST['round_key'] ?? '';
    $gala_type = $_POST['gala_type'] ?? 'round';
    $venue_detail_id = (int)($_POST['venue_detail_id'] ?? 0);
    $entries = json_input('entries');
    $reason = trim($_POST['reason'] ?? '');

    if ($round_key === '' || empty($entries)) {
        echo json_encode(['error' => 'Missing teamsheet data']);
        exit;
    }

    $existing = null;
    $check = $conn->prepare("SELECT * FROM club_teamsheets WHERE club_id = ? AND season_year = ? AND round_key = ?");
    $check->bind_param("iis", $target_club_id, $season, $round_key);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing && $existing['status'] === 'submitted' && $reason === '') {
        echo json_encode(['error' => 'A reason is required when editing a submitted teamsheet.']);
        exit;
    }

    if (!$existing) {
        $stmt = $conn->prepare("INSERT INTO club_teamsheets (club_id, season_year, round_key, gala_type, venue_detail_id, submission_type) VALUES (?, ?, ?, ?, ?, 'builder')");
        $stmt->bind_param("iissi", $target_club_id, $season, $round_key, $gala_type, $venue_detail_id);
        $stmt->execute();
        $teamsheet_id = $conn->insert_id;
        $stmt->close();
    } else {
        $teamsheet_id = (int)$existing['id'];
        $stmt = $conn->prepare("UPDATE club_teamsheets SET gala_type = ?, venue_detail_id = ?, submission_type = 'builder', last_reason = NULLIF(?, '') WHERE id = ?");
        $stmt->bind_param("sisi", $gala_type, $venue_detail_id, $reason, $teamsheet_id);
        $stmt->execute();
        $stmt->close();
    }

    $old_snapshot = [];
    if ($existing && $existing['status'] === 'submitted') {
        $old_payload = load_teamsheet_payload($conn, $teamsheet_id, $target_club_id);
        $old_snapshot = $old_payload['entries'] ?? [];
    }

    $entry_stmt = $conn->prepare("INSERT INTO club_teamsheet_entries (teamsheet_id, event_id, selected_swimmers_json, pb_snapshot, notes)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE selected_swimmers_json = VALUES(selected_swimmers_json), pb_snapshot = VALUES(pb_snapshot), notes = VALUES(notes)");
    foreach ($entries as $entry) {
        $event_id = (int)($entry['event_id'] ?? 0);
        if (!$event_id) {
            continue;
        }
        $selected = json_encode(array_values($entry['selected_swimmers'] ?? []));
        $pb = trim($entry['pb_snapshot'] ?? '');
        $notes = trim($entry['notes'] ?? '');
        $entry_stmt->bind_param("iisss", $teamsheet_id, $event_id, $selected, $pb, $notes);
        $entry_stmt->execute();
    }
    $entry_stmt->close();

    if ($existing && $existing['status'] === 'submitted') {
        $before_by_event = [];
        foreach ($old_snapshot as $old_entry) {
            $before_by_event[(int)$old_entry['event_id']] = [
                'event_number' => $old_entry['event_number'] ?? '',
                'selected' => implode(', ', $old_entry['selected_swimmers'] ?? []),
                'notes' => $old_entry['notes'] ?? '',
            ];
        }
        $changed_events = [];
        foreach ($entries as $new_entry) {
            $event_id = (int)($new_entry['event_id'] ?? 0);
            $before = $before_by_event[$event_id] ?? null;
            $new_selected = implode(', ', $new_entry['selected_swimmers'] ?? []);
            $new_notes = $new_entry['notes'] ?? '';
            if (!$before || $before['selected'] !== $new_selected || $before['notes'] !== $new_notes) {
                $changed_events[] = $before['event_number'] ?? '#' . $event_id;
            }
        }
        $summary = 'Post-submission edit';
        if (!empty($changed_events)) {
            $summary .= ': events ' . implode(', ', array_slice($changed_events, 0, 12));
            if (count($changed_events) > 12) {
                $summary .= ' +' . (count($changed_events) - 12) . ' more';
            }
        }
        $snapshot = json_encode(['before' => $old_snapshot, 'after' => $entries]);
        $audit = $conn->prepare("INSERT INTO club_teamsheet_audit (teamsheet_id, club_id, changed_by, reason, change_summary, snapshot_json) VALUES (?, ?, ?, ?, ?, ?)");
        $audit->bind_param("iissss", $teamsheet_id, $target_club_id, $target_club_name, $reason, $summary, $snapshot);
        $audit->execute();
        $audit->close();
    }

    echo json_encode(['success' => true, 'teamsheet_id' => $teamsheet_id]);
    exit;
}

if ($action === 'upload_teamsheet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $season = (int)($_POST['season'] ?? $active_season_year);
    $round_key = $_POST['round_key'] ?? '';
    $gala_type = $_POST['gala_type'] ?? 'round';
    $venue_detail_id = (int)($_POST['venue_detail_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($round_key === '' || $venue_detail_id <= 0) {
        echo json_encode(['error' => 'Missing round details for the uploaded teamsheet.']);
        exit;
    }
    if (empty($_FILES['teamsheet_file']) || ($_FILES['teamsheet_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Please choose a teamsheet document to upload.']);
        exit;
    }

    $file = $_FILES['teamsheet_file'];
    if (($file['size'] ?? 0) <= 0) {
        echo json_encode(['error' => 'The uploaded file was empty.']);
        exit;
    }
    if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
        echo json_encode(['error' => 'Uploaded teamsheets must be 10MB or smaller.']);
        exit;
    }

    $original_name = safe_download_name($file['name'] ?? 'teamsheet');
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'rtf', 'odt'];
    if (!in_array($ext, $allowed_exts, true)) {
        echo json_encode(['error' => 'Please upload a PDF, Word, Excel, CSV, RTF, or ODT teamsheet.']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    $allowed_mimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
        'application/rtf',
        'text/rtf',
        'application/vnd.oasis.opendocument.text',
        'application/zip',
        'application/octet-stream',
    ];
    if (!in_array($mime, $allowed_mimes, true)) {
        echo json_encode(['error' => 'That file type was not recognised as a supported teamsheet document.']);
        exit;
    }

    $existing = null;
    $check = $conn->prepare("SELECT * FROM club_teamsheets WHERE club_id = ? AND season_year = ? AND round_key = ?");
    $check->bind_param("iis", $target_club_id, $season, $round_key);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing && $existing['status'] === 'submitted' && $reason === '') {
        echo json_encode(['error' => 'A reason is required when replacing a submitted teamsheet.']);
        exit;
    }

    $upload_dir = 'uploads/teamsheets/';
    if (!ensure_upload_dir($upload_dir)) {
        echo json_encode(['error' => 'Could not prepare the teamsheet upload folder.']);
        exit;
    }

    $token = bin2hex(random_bytes(6));
    $stored_name = implode('_', [
        'club' . (int)$target_club_id,
        safe_filename_token((string)$season),
        safe_filename_token($round_key),
        $token
    ]) . '.' . $ext;
    $stored_path = $upload_dir . $stored_name;
    if (!move_uploaded_file($file['tmp_name'], $stored_path)) {
        echo json_encode(['error' => 'Could not save the uploaded teamsheet.']);
        exit;
    }

    if (!$existing) {
        $stmt = $conn->prepare("INSERT INTO club_teamsheets
            (club_id, season_year, round_key, gala_type, venue_detail_id, submission_type, upload_file_path, upload_original_name, upload_mime_type, upload_file_size, status, submitted_at, submitted_by)
            VALUES (?, ?, ?, ?, ?, 'upload', ?, ?, ?, ?, 'submitted', NOW(), ?)");
        $size = (int)$file['size'];
        $stmt->bind_param("iississsis", $target_club_id, $season, $round_key, $gala_type, $venue_detail_id, $stored_path, $original_name, $mime, $size, $target_club_name);
        $stmt->execute();
        $teamsheet_id = $conn->insert_id;
        $stmt->close();
    } else {
        $teamsheet_id = (int)$existing['id'];
        $old_path = $existing['upload_file_path'] ?? '';
        $stmt = $conn->prepare("UPDATE club_teamsheets
            SET gala_type = ?, venue_detail_id = ?, submission_type = 'upload', upload_file_path = ?, upload_original_name = ?,
                upload_mime_type = ?, upload_file_size = ?, status = 'submitted', submitted_at = COALESCE(submitted_at, NOW()),
                submitted_by = ?, last_reason = NULLIF(?, '')
            WHERE id = ? AND club_id = ?");
        $size = (int)$file['size'];
        $stmt->bind_param("sisssissii", $gala_type, $venue_detail_id, $stored_path, $original_name, $mime, $size, $target_club_name, $reason, $teamsheet_id, $target_club_id);
        $stmt->execute();
        $stmt->close();
        if ($old_path && $old_path !== $stored_path && is_file($old_path)) {
            unlink($old_path);
        }
    }

    $summary = $existing && $existing['status'] === 'submitted' ? 'Uploaded replacement teamsheet document' : 'Uploaded teamsheet document';
    $snapshot = json_encode([
        'submission_type' => 'upload',
        'file_name' => $original_name,
        'mime_type' => $mime,
        'file_size' => (int)$file['size'],
        'round_key' => $round_key,
        'venue_detail_id' => $venue_detail_id,
    ]);
    $audit = $conn->prepare("INSERT INTO club_teamsheet_audit (teamsheet_id, club_id, changed_by, reason, change_summary, snapshot_json) VALUES (?, ?, ?, ?, ?, ?)");
    $audit->bind_param("iissss", $teamsheet_id, $target_club_id, $target_club_name, $reason, $summary, $snapshot);
    $audit->execute();
    $audit->close();

    echo json_encode(['success' => true, 'teamsheet_id' => $teamsheet_id]);
    exit;
}

if ($action === 'submit_teamsheet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamsheet_id = (int)($_POST['teamsheet_id'] ?? 0);
    if (!$teamsheet_id) {
        echo json_encode(['error' => 'Missing teamsheet id']);
        exit;
    }
    $owner = $conn->prepare("SELECT id FROM club_teamsheets WHERE id = ? AND club_id = ? LIMIT 1");
    $owner->bind_param("ii", $teamsheet_id, $target_club_id);
    $owner->execute();
    $can_submit = $owner->get_result()->num_rows > 0;
    $owner->close();
    if (!$can_submit) {
        echo json_encode(['error' => 'Teamsheet not found for this club']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE club_teamsheets SET status = 'submitted', submitted_at = COALESCE(submitted_at, NOW()), submitted_by = ? WHERE id = ? AND club_id = ?");
    $stmt->bind_param("sii", $target_club_name, $teamsheet_id, $target_club_id);
    $stmt->execute();
    $stmt->close();

    $snapshot = load_teamsheet_payload($conn, $teamsheet_id, $target_club_id);
    $summary = 'Teamsheet submitted to league';
    $snap_json = json_encode($snapshot);
    $audit = $conn->prepare("INSERT INTO club_teamsheet_audit (teamsheet_id, club_id, changed_by, reason, change_summary, snapshot_json) VALUES (?, ?, ?, '', ?, ?)");
    $audit->bind_param("iisss", $teamsheet_id, $target_club_id, $target_club_name, $summary, $snap_json);
    $audit->execute();
    $audit->close();

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'teamsheet') {
    $teamsheet_id = (int)($_GET['id'] ?? 0);
    $payload = $teamsheet_id ? load_teamsheet_payload($conn, $teamsheet_id, $target_club_id) : null;
    if (!$payload) {
        echo json_encode(['error' => 'Teamsheet not found or not shared yet']);
        exit;
    }
    echo json_encode($payload);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
