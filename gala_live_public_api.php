<?php
/**
 * Public read-only live gala scoresheet API.
 */
include 'db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$scoresheet_id = intval($_GET['scoresheet_id'] ?? 0);
if (!$scoresheet_id) {
    echo json_encode(['error' => 'Missing scoresheet_id']);
    exit;
}

$s = $conn->prepare("SELECT gs.id, gs.round_number, gs.gala_type, gs.host_club_id, gs.gala_date, gs.team_count, gs.status, gs.updated_at, gs.season_year, c.name AS host_club_name
    FROM gala_scoresheets gs
    JOIN clubs c ON gs.host_club_id = c.id
    WHERE gs.id = ? AND gs.live_public_enabled = 1 AND gs.status = 'in_progress'");
$s->bind_param("i", $scoresheet_id);
$s->execute();
$scoresheet = $s->get_result()->fetch_assoc();
$s->close();

if (!$scoresheet) {
    echo json_encode(['error' => 'Live results are not currently available for this gala.']);
    exit;
}

$teams = [];
$t = $conn->prepare("SELECT gt.club_id, gt.lane_number, gt.is_absent, c.name AS club_name, c.logo
    FROM gala_teams gt
    JOIN clubs c ON gt.club_id = c.id
    WHERE gt.scoresheet_id = ?
    ORDER BY gt.lane_number ASC, gt.id ASC");
$t->bind_param("i", $scoresheet_id);
$t->execute();
$t_res = $t->get_result();
while ($row = $t_res->fetch_assoc()) {
    $teams[] = [
        'club_id' => (int)$row['club_id'],
        'club_name' => $row['club_name'],
        'logo' => $row['logo'],
        'lane_number' => $row['lane_number'] ? (int)$row['lane_number'] : null,
        'is_absent' => (int)$row['is_absent'],
    ];
}
$t->close();

$season = (int)$scoresheet['season_year'];
if ($season === 9999) {
    $season = (int)($current_season_year ?? 2026);
}
$gala_type = $scoresheet['gala_type'];

$events = [];
$e = $conn->prepare("SELECT * FROM gala_events WHERE season_year = ? ORDER BY event_number ASC");
$e->bind_param("i", $season);
$e->execute();
$e_res = $e->get_result();
while ($row = $e_res->fetch_assoc()) {
    $event = [
        'id' => (int)$row['id'],
        'event_number' => (int)$row['event_number'],
        'event_name' => $row['event_name'],
        'distance' => $row['distance'],
        'age_group' => $row['age_group'],
        'gender' => $row['gender'],
        'event_type' => $row['event_type'],
        'cut_off_time_ms' => (int)$row['cut_off_time_ms'],
    ];
    if ($gala_type === 'a_final' && $row['a_final_event_name']) {
        $event['event_name'] = $row['a_final_event_name'];
        $event['distance'] = $row['a_final_distance'];
        $event['cut_off_time_ms'] = (int)$row['a_final_cut_off_time_ms'];
    }
    $events[] = $event;
}
$e->close();

$results = [];
$r = $conn->prepare("SELECT event_id, club_id, time_ms, is_dq, dq_reason
    FROM gala_results
    WHERE scoresheet_id = ?");
$r->bind_param("i", $scoresheet_id);
$r->execute();
$r_res = $r->get_result();
while ($row = $r_res->fetch_assoc()) {
    $results[] = [
        'event_id' => (int)$row['event_id'],
        'club_id' => (int)$row['club_id'],
        'time_ms' => $row['time_ms'] !== null ? (int)$row['time_ms'] : null,
        'is_dq' => (int)$row['is_dq'],
        'dq_reason' => $row['dq_reason'],
    ];
}
$r->close();

echo json_encode([
    'scoresheet' => [
        'id' => (int)$scoresheet['id'],
        'round_number' => (int)$scoresheet['round_number'],
        'gala_type' => $scoresheet['gala_type'],
        'host_club_id' => (int)$scoresheet['host_club_id'],
        'host_club_name' => $scoresheet['host_club_name'],
        'gala_date' => $scoresheet['gala_date'],
        'team_count' => (int)$scoresheet['team_count'],
        'status' => $scoresheet['status'],
        'updated_at' => $scoresheet['updated_at'],
    ],
    'teams' => $teams,
    'events' => $events,
    'results' => $results,
]);
