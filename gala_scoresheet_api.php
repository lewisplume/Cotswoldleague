<?php
/**
 * Gala Scoresheet API
 * Handles all AJAX operations for the scoresheet interface.
 */
session_start();
include 'db.php';

header('Content-Type: application/json');

$active_season_year = $current_season_year ?? 2026;

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;
$is_club_logged_in = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$current_club_id = $_SESSION['club_id'] ?? 0;

if (!$is_super_admin && !$is_club_logged_in) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// =====================================================
// GET: Load events for a gala type
// =====================================================
if ($action === 'events' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $season = intval($_GET['season'] ?? $active_season_year);
    $type = $_GET['type'] ?? 'round'; // round, a_final, b_final, c_final

    $sql = "SELECT * FROM gala_events WHERE season_year = ? ORDER BY event_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $season);
    $stmt->execute();
    $res = $stmt->get_result();

    $events = [];
    while ($row = $res->fetch_assoc()) {
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

        // Apply A Final overrides
        if ($type === 'a_final' && $row['a_final_event_name']) {
            $event['event_name'] = $row['a_final_event_name'];
            $event['distance'] = $row['a_final_distance'];
            $event['cut_off_time_ms'] = (int)$row['a_final_cut_off_time_ms'];
        }

        $events[] = $event;
    }
    $stmt->close();
    echo json_encode(['events' => $events]);
    exit;
}

// =====================================================
// POST: Create a new scoresheet for a venue
// =====================================================
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue_detail_id = intval($_POST['venue_detail_id'] ?? 0);
    $round_number = intval($_POST['round_number'] ?? 0);
    $gala_type = $_POST['gala_type'] ?? 'round';
    $host_club_id = intval($_POST['host_club_id'] ?? 0);
    $gala_date = $_POST['gala_date'] ?? null;
    $team_count = intval($_POST['team_count'] ?? 4);
    $season_year = intval($_POST['season_year'] ?? $active_season_year);
    $venue_row = null;

    if ($venue_detail_id > 0) {
        $v_stmt = $conn->prepare("SELECT round_number, gala_type, club_id, season_year, team_1_id, team_2_id, team_3_id, team_4_id, team_5_id, team_6_id, team_7_id, team_8_id FROM venue_details WHERE id = ?");
        $v_stmt->bind_param("i", $venue_detail_id);
        $v_stmt->execute();
        $v_res = $v_stmt->get_result();
        $venue_row = $v_res->fetch_assoc();
        $v_stmt->close();

        if (!$venue_row) {
            echo json_encode(['error' => 'Venue not found']);
            exit;
        }

        if ((int)$venue_row['season_year'] !== $season_year) {
            echo json_encode(['error' => 'Venue does not belong to the selected season']);
            exit;
        }

        $round_number = (int)$venue_row['round_number'];
        $gala_type = $venue_row['gala_type'] ?: $gala_type;
        $host_club_id = (int)$venue_row['club_id'];
        $team_count = 0;
        for ($i = 1; $i <= 8; $i++) {
            if (!empty($venue_row["team_{$i}_id"])) {
                $team_count++;
            }
        }
        $team_count = $team_count ?: intval($_POST['team_count'] ?? 4);
    }

    if (!$host_club_id || !$round_number) {
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Check for existing scoresheet
    $check = $conn->prepare("SELECT id FROM gala_scoresheets WHERE venue_detail_id = ? AND season_year = ?");
    $check->bind_param("ii", $venue_detail_id, $season_year);
    $check->execute();
    $existing = $check->get_result();
    if ($existing->num_rows > 0) {
        $row = $existing->fetch_assoc();
        echo json_encode(['scoresheet_id' => (int)$row['id'], 'message' => 'Scoresheet already exists']);
        exit;
    }
    $check->close();

    $recorder_club_id = $is_club_logged_in ? $current_club_id : null;

    $stmt = $conn->prepare("INSERT INTO gala_scoresheets 
        (venue_detail_id, round_number, gala_type, host_club_id, gala_date, team_count, recorder_club_id, season_year) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisiii", $venue_detail_id, $round_number, $gala_type, $host_club_id, $gala_date, $team_count, $recorder_club_id, $season_year);
    $stmt->execute();
    $scoresheet_id = $conn->insert_id;
    $stmt->close();

    // Auto-populate teams from venue_details draw
    if ($venue_row) {
            $team_stmt = $conn->prepare("INSERT INTO gala_teams (scoresheet_id, club_id) VALUES (?, ?)");
            for ($i = 1; $i <= 8; $i++) {
                $tid = $venue_row["team_{$i}_id"];
                if ($tid) {
                    $team_stmt->bind_param("ii", $scoresheet_id, $tid);
                    $team_stmt->execute();
                }
            }
            $team_stmt->close();
    }

    // Pre-create result rows for all events × all teams
    $event_ids = [];
    $e_res = $conn->query("SELECT id FROM gala_events WHERE season_year = $season_year ORDER BY event_number ASC");
    while ($e = $e_res->fetch_assoc()) {
        $event_ids[] = (int)$e['id'];
    }

    $team_ids = [];
    $t_res = $conn->query("SELECT club_id FROM gala_teams WHERE scoresheet_id = $scoresheet_id");
    while ($t = $t_res->fetch_assoc()) {
        $team_ids[] = (int)$t['club_id'];
    }

    if (!empty($event_ids) && !empty($team_ids)) {
        $r_stmt = $conn->prepare("INSERT IGNORE INTO gala_results (scoresheet_id, event_id, club_id) VALUES (?, ?, ?)");
        foreach ($event_ids as $eid) {
            foreach ($team_ids as $cid) {
                $r_stmt->bind_param("iii", $scoresheet_id, $eid, $cid);
                $r_stmt->execute();
            }
        }
        $r_stmt->close();
    }

    echo json_encode(['scoresheet_id' => $scoresheet_id, 'message' => 'Scoresheet created', 'teams' => count($team_ids), 'events' => count($event_ids)]);
    exit;
}

// =====================================================
// POST: Create a SANDBOX scoresheet (Isolated Testing)
// =====================================================
if ($action === 'create_sandbox' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host_club_id = intval($_POST['host_club_id'] ?? 0);
    $team_ids_input = $_POST['team_ids'] ?? []; // Array of club IDs
    $season_year = 9999; // Special year for sandbox

    if (!$host_club_id) {
        echo json_encode(['error' => 'Missing host club']);
        exit;
    }

    // Create the scoresheet
    $stmt = $conn->prepare("INSERT INTO gala_scoresheets (round_number, gala_type, host_club_id, gala_date, team_count, status, season_year) VALUES (0, 'round', ?, CURDATE(), ?, 'draft', ?)");
    $team_count = count($team_ids_input);
    $stmt->bind_param("iii", $host_club_id, $team_count, $season_year);
    $stmt->execute();
    $scoresheet_id = $conn->insert_id;
    $stmt->close();

    // Add teams
    $team_stmt = $conn->prepare("INSERT INTO gala_teams (scoresheet_id, club_id) VALUES (?, ?)");
    foreach ($team_ids_input as $tid) {
        $tid = intval($tid);
        if ($tid > 0) {
            $team_stmt->bind_param("ii", $scoresheet_id, $tid);
            $team_stmt->execute();
        }
    }
    $team_stmt->close();

    // Pre-create result rows
    $event_ids = [];
    $e_res = $conn->query("SELECT id FROM gala_events WHERE season_year = $active_season_year ORDER BY event_number ASC"); // Use active season events as template
    while ($e = $e_res->fetch_assoc()) {
        $event_ids[] = (int)$e['id'];
    }

    if (!empty($event_ids)) {
        $r_stmt = $conn->prepare("INSERT IGNORE INTO gala_results (scoresheet_id, event_id, club_id) VALUES (?, ?, ?)");
        foreach ($event_ids as $eid) {
            foreach ($team_ids_input as $cid) {
                $cid = intval($cid);
                $r_stmt->bind_param("iii", $scoresheet_id, $eid, $cid);
                $r_stmt->execute();
            }
        }
        $r_stmt->close();
    }

    echo json_encode(['scoresheet_id' => $scoresheet_id, 'message' => 'Sandbox created']);
    exit;
}

// =====================================================
// GET: Load a scoresheet with all data
// =====================================================
if ($action === 'load' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $scoresheet_id = intval($_GET['scoresheet_id'] ?? 0);
    if (!$scoresheet_id) {
        echo json_encode(['error' => 'Missing scoresheet_id']);
        exit;
    }

    // Scoresheet header
    $s = $conn->prepare("SELECT gs.*, c.name AS host_club_name 
        FROM gala_scoresheets gs JOIN clubs c ON gs.host_club_id = c.id WHERE gs.id = ?");
    $s->bind_param("i", $scoresheet_id);
    $s->execute();
    $scoresheet = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$scoresheet) {
        echo json_encode(['error' => 'Scoresheet not found']);
        exit;
    }

    // Teams
    $teams = [];
    $t = $conn->query("SELECT gt.*, c.name AS club_name, c.logo 
        FROM gala_teams gt JOIN clubs c ON gt.club_id = c.id 
        WHERE gt.scoresheet_id = $scoresheet_id ORDER BY gt.lane_number ASC, gt.id ASC");
    while ($row = $t->fetch_assoc()) {
        $teams[] = [
            'id' => (int)$row['id'],
            'club_id' => (int)$row['club_id'],
            'club_name' => $row['club_name'],
            'logo' => $row['logo'],
            'lane_number' => $row['lane_number'] ? (int)$row['lane_number'] : null,
            'is_absent' => (int)$row['is_absent'],
        ];
    }

    // Events
    $gala_type = $scoresheet['gala_type'];
    $season = (int)$scoresheet['season_year'];
    if ($season === 9999) {
        $season = $active_season_year; // Use the active season template for Sandbox
    }
    $events = [];
    $e = $conn->query("SELECT * FROM gala_events WHERE season_year = $season ORDER BY event_number ASC");
    while ($row = $e->fetch_assoc()) {
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

    // Results
    $results = [];
    $r = $conn->query("SELECT gr.*, ge.event_number 
        FROM gala_results gr JOIN gala_events ge ON gr.event_id = ge.id 
        WHERE gr.scoresheet_id = $scoresheet_id ORDER BY ge.event_number ASC, gr.club_id ASC");
    while ($row = $r->fetch_assoc()) {
        $results[] = [
            'event_id' => (int)$row['event_id'],
            'event_number' => (int)$row['event_number'],
            'club_id' => (int)$row['club_id'],
            'time_ms' => $row['time_ms'] !== null ? (int)$row['time_ms'] : null,
            'is_dq' => (int)$row['is_dq'],
            'dq_reason' => $row['dq_reason'],
            'is_verified' => (int)$row['is_verified'],
            'points' => (int)$row['points'],
            'place' => $row['place'] !== null ? (int)$row['place'] : null,
            'status' => $row['status'],
            'source_type' => $row['source_type'],
        ];
    }

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
            'recorder_name' => $scoresheet['recorder_name'],
        ],
        'teams' => $teams,
        'events' => $events,
        'results' => $results,
    ]);
    exit;
}

// =====================================================
// POST: Save lane assignments
// =====================================================
if ($action === 'save_lanes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $lanes = json_decode($_POST['lanes'] ?? '[]', true); // [{club_id: X, lane_number: Y}, ...]
    $recorder_name = $_POST['recorder_name'] ?? null;

    if (!$scoresheet_id || empty($lanes)) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE gala_teams SET lane_number = ? WHERE scoresheet_id = ? AND club_id = ?");
    foreach ($lanes as $lane) {
        $ln = intval($lane['lane_number']);
        $cid = intval($lane['club_id']);
        $stmt->bind_param("iii", $ln, $scoresheet_id, $cid);
        $stmt->execute();
    }
    $stmt->close();

    // Update recorder name and status
    if ($recorder_name) {
        $conn->query("UPDATE gala_scoresheets SET recorder_name = '" . $conn->real_escape_string($recorder_name) . "', status = 'in_progress' WHERE id = $scoresheet_id AND status = 'draft'");
    } else {
        $conn->query("UPDATE gala_scoresheets SET status = 'in_progress' WHERE id = $scoresheet_id AND status = 'draft'");
    }

    echo json_encode(['success' => true]);
    exit;
}

// =====================================================
// POST: Substitute team
// =====================================================
if ($action === 'substitute_team' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $old_club_id = intval($_POST['old_club_id'] ?? 0);
    $new_club_id = intval($_POST['new_club_id'] ?? 0);

    if (!$scoresheet_id || !$old_club_id || !$new_club_id) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    // Replace the old absent team with the new team in gala_teams
    $stmt = $conn->prepare("UPDATE gala_teams SET club_id = ?, is_absent = 0 WHERE scoresheet_id = ? AND club_id = ?");
    $stmt->bind_param("iii", $new_club_id, $scoresheet_id, $old_club_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Mark team absent
// =====================================================
if ($action === 'mark_absent' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $club_id = intval($_POST['club_id'] ?? 0);

    if (!$scoresheet_id || !$club_id) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE gala_teams SET is_absent = 1, lane_number = NULL WHERE scoresheet_id = ? AND club_id = ?");
    $stmt->bind_param("ii", $scoresheet_id, $club_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Add extra team
// =====================================================
if ($action === 'add_team' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $new_club_id = intval($_POST['new_club_id'] ?? 0);

    if (!$scoresheet_id || !$new_club_id) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    // Check if team already exists
    $check = $conn->query("SELECT id FROM gala_teams WHERE scoresheet_id = $scoresheet_id AND club_id = $new_club_id");
    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'Team is already in this gala']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO gala_teams (scoresheet_id, club_id, is_absent, lane_number) VALUES (?, ?, 0, NULL)");
    $stmt->bind_param("ii", $scoresheet_id, $new_club_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Save a single result
// =====================================================
if ($action === 'save_result' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $event_id = intval($_POST['event_id'] ?? 0);
    $club_id = intval($_POST['club_id'] ?? 0);
    $time_ms = isset($_POST['time_ms']) && $_POST['time_ms'] !== '' ? intval($_POST['time_ms']) : null;
    $is_dq = intval($_POST['is_dq'] ?? 0);
    $dq_reason = $_POST['dq_reason'] ?? null;
    $is_verified = intval($_POST['is_verified'] ?? 0);
    $points = intval($_POST['points'] ?? 0);
    $place = isset($_POST['place']) && $_POST['place'] !== '' ? intval($_POST['place']) : null;
    $status = $_POST['status'] ?? 'pending';

    $stmt = $conn->prepare("UPDATE gala_results SET 
        time_ms = ?, is_dq = ?, dq_reason = ?, is_verified = ?, points = ?, place = ?, status = ?
        WHERE scoresheet_id = ? AND event_id = ? AND club_id = ?");
    $stmt->bind_param("iisiiisiii", $time_ms, $is_dq, $dq_reason, $is_verified, $points, $place, $status, $scoresheet_id, $event_id, $club_id);
    $stmt->execute();
    $stmt->close();

    // Update scoresheet timestamp
    $conn->query("UPDATE gala_scoresheets SET updated_at = NOW() WHERE id = $scoresheet_id");

    echo json_encode(['success' => true]);
    exit;
}

// =====================================================
// POST: Batch save results (for offline sync)
// =====================================================
if ($action === 'save_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $results = json_decode($_POST['results'] ?? '[]', true);

    if (!$scoresheet_id || empty($results)) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE gala_results SET 
        time_ms = ?, is_dq = ?, dq_reason = ?, is_verified = ?, points = ?, place = ?, status = ?
        WHERE scoresheet_id = ? AND event_id = ? AND club_id = ?");

    $saved = 0;
    foreach ($results as $r) {
        $time_ms = $r['time_ms'] ?? null;
        $is_dq = intval($r['is_dq'] ?? 0);
        $dq_reason = $r['dq_reason'] ?? null;
        $is_verified = intval($r['is_verified'] ?? 0);
        $points = intval($r['points'] ?? 0);
        $place = $r['place'] ?? null;
        $status = $r['status'] ?? 'pending';
        $event_id = intval($r['event_id']);
        $club_id = intval($r['club_id']);

        $stmt->bind_param("iisiiisiii", $time_ms, $is_dq, $dq_reason, $is_verified, $points, $place, $status, $scoresheet_id, $event_id, $club_id);
        $stmt->execute();
        $saved++;
    }
    $stmt->close();

    $conn->query("UPDATE gala_scoresheets SET updated_at = NOW() WHERE id = $scoresheet_id");

    echo json_encode(['success' => true, 'saved' => $saved]);
    exit;
}

// =====================================================
// POST: Submit scoresheet
// =====================================================
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $total_points_json = $_POST['total_points_json'] ?? null;

    $stmt = $conn->prepare("UPDATE gala_scoresheets SET status = 'submitted', submitted_at = NOW(), total_points_json = ? WHERE id = ? AND status IN ('draft', 'in_progress')");
    $stmt->bind_param("si", $total_points_json, $scoresheet_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Scoresheet submitted for verification']);
    exit;
}

// =====================================================
// GET: Find scoresheet for a host club's venue
// =====================================================
if ($action === 'find_by_venue' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $venue_detail_id = intval($_GET['venue_detail_id'] ?? 0);
    $club_id = intval($_GET['club_id'] ?? 0);
    $round = intval($_GET['round'] ?? 0);
    $season = intval($_GET['season'] ?? $active_season_year);

    // If venue_detail_id provided directly, use it; otherwise resolve from club_id + round
    if (!$venue_detail_id && $club_id && $round) {
        $v = $conn->prepare("SELECT id FROM venue_details WHERE club_id = ? AND round_number = ? AND season_year = ?");
        $v->bind_param("iii", $club_id, $round, $season);
        $v->execute();
        $vr = $v->get_result();

        if ($vr->num_rows === 0) {
            echo json_encode(['error' => 'No venue found for this club/round']);
            exit;
        }
        $venue = $vr->fetch_assoc();
        $venue_detail_id = (int)$venue['id'];
        $v->close();
    }

    if (!$venue_detail_id) {
        echo json_encode(['error' => 'Missing venue_detail_id or club_id/round']);
        exit;
    }

    $v_check = $conn->prepare("SELECT id FROM venue_details WHERE id = ? AND season_year = ?");
    $v_check->bind_param("ii", $venue_detail_id, $season);
    $v_check->execute();
    $v_check_res = $v_check->get_result();
    if ($v_check_res->num_rows === 0) {
        echo json_encode(['error' => 'Venue not found for this season']);
        exit;
    }
    $v_check->close();

    // Check if scoresheet exists
    $s = $conn->prepare("SELECT id, status FROM gala_scoresheets WHERE venue_detail_id = ? AND season_year = ?");
    $s->bind_param("ii", $venue_detail_id, $season);
    $s->execute();
    $sr = $s->get_result();

    if ($sr->num_rows > 0) {
        $row = $sr->fetch_assoc();
        echo json_encode(['scoresheet_id' => (int)$row['id'], 'status' => $row['status'], 'venue_detail_id' => $venue_detail_id]);
    } else {
        echo json_encode(['scoresheet_id' => null, 'venue_detail_id' => $venue_detail_id, 'message' => 'No scoresheet yet — create one']);
    }
    $s->close();
    exit;
}

echo json_encode(['error' => 'Unknown action: ' . $action]);
