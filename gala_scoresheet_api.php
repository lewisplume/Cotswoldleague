<?php
/**
 * Gala Scoresheet API
 * Handles all AJAX operations for the scoresheet interface.
 */
require_once __DIR__ . '/security_headers.php';
cotswold_secure_session_start();
include 'db.php';
require_once __DIR__ . '/gala_access.php';
require_once __DIR__ . '/gala_scoring.php';
require_once __DIR__ . '/audit_helpers.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cotswold_require_csrf(true);
    cotswold_audit_authenticated_request($conn, $is_super_admin ? 'Super Admin' : (string)($_SESSION['club_name'] ?? 'Club User'), 'Scoresheet', $action);
}

function cotswold_is_final_venue_row($row) {
    return ((int)($row['round_number'] ?? 0) === 99) || in_array($row['gala_type'] ?? 'round', ['a_final', 'b_final', 'c_final'], true);
}

function cotswold_load_scoresheet_access_row($conn, $scoresheet_id) {
    $stmt = $conn->prepare("SELECT gs.id, gs.status, COALESCE(vd.round_number, gs.round_number) AS round_number,
               COALESCE(vd.gala_type, gs.gala_type) AS gala_type,
               gs.host_club_id,
               vd.club_id AS venue_host_club_id,
               vd.final_scoresheet_club_id
        FROM gala_scoresheets gs
        LEFT JOIN venue_details vd ON gs.venue_detail_id = vd.id
        WHERE gs.id = ?");
    $stmt->bind_param("i", $scoresheet_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id) {
    if (!$scoresheet_id) {
        echo json_encode(['error' => 'Missing scoresheet_id']);
        exit;
    }
    $row = cotswold_load_scoresheet_access_row($conn, $scoresheet_id);
    if (!$row) {
        echo json_encode(['error' => 'Scoresheet not found']);
        exit;
    }
    if (!cotswold_user_can_access_scoresheet_venue($row, $is_super_admin, $current_club_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'This scoresheet has not been assigned to your club.']);
        exit;
    }
    if (!in_array($row['status'], ['draft', 'in_progress', 'submitted'], true)) {
        http_response_code(409);
        echo json_encode(['error' => 'This scoresheet is verified or published and is no longer editable.']);
        exit;
    }
}

function cotswold_normalize_raw_result(array $input): array {
    $time_value = $input['time_ms'] ?? null;
    if ($time_value === '' || $time_value === null) {
        $time_ms = null;
    } elseif (filter_var($time_value, FILTER_VALIDATE_INT) === false) {
        throw new InvalidArgumentException('Invalid result time.');
    } else {
        $time_ms = (int)$time_value;
        if ($time_ms < 0 || $time_ms > 86400000) {
            throw new InvalidArgumentException('Result time is outside the accepted range.');
        }
    }

    $is_dq = !empty($input['is_dq']) ? 1 : 0;
    $dq_reason = $is_dq ? trim((string)($input['dq_reason'] ?? '')) : '';
    if (strlen($dq_reason) > 255) {
        throw new InvalidArgumentException('DQ reason must be 255 characters or fewer.');
    }

    return [
        'event_id' => (int)($input['event_id'] ?? 0),
        'club_id' => (int)($input['club_id'] ?? 0),
        'time_ms' => $time_ms,
        'is_dq' => $is_dq,
        'dq_reason' => $dq_reason !== '' ? $dq_reason : null,
    ];
}

function cotswold_save_raw_results($conn, int $scoresheet_id, array $inputs, int $active_season_year): int {
    if (count($inputs) > 1000) {
        throw new InvalidArgumentException('Too many results in one request.');
    }

    $membership = $conn->prepare("SELECT gs.status
        FROM gala_results gr
        JOIN gala_scoresheets gs ON gs.id = gr.scoresheet_id
        JOIN gala_teams gt ON gt.scoresheet_id = gr.scoresheet_id AND gt.club_id = gr.club_id
        JOIN gala_events ge ON ge.id = gr.event_id
        WHERE gr.scoresheet_id = ? AND gr.event_id = ? AND gr.club_id = ?
          AND gt.is_absent = 0
          AND (gs.season_year = 9999 OR ge.season_year = gs.season_year)");
    $update = $conn->prepare("UPDATE gala_results
        SET time_ms = ?, is_dq = ?, dq_reason = ?, is_verified = 0,
            source_type = 'live', source_scoresheet_id = NULL, imported_by = NULL, imported_at = NULL
        WHERE scoresheet_id = ? AND event_id = ? AND club_id = ?");

    $saved = 0;
    foreach ($inputs as $input) {
        $result = cotswold_normalize_raw_result($input);
        if ($result['event_id'] <= 0 || $result['club_id'] <= 0) {
            throw new InvalidArgumentException('Missing result event or club.');
        }

        $membership->bind_param('iii', $scoresheet_id, $result['event_id'], $result['club_id']);
        $membership->execute();
        $row = $membership->get_result()->fetch_assoc();
        if (!$row) {
            throw new InvalidArgumentException('Result does not belong to this scoresheet.');
        }
        if (!in_array($row['status'], ['draft', 'in_progress', 'submitted'], true)) {
            throw new InvalidArgumentException('This scoresheet is no longer editable.');
        }

        $update->bind_param(
            'iisiii',
            $result['time_ms'],
            $result['is_dq'],
            $result['dq_reason'],
            $scoresheet_id,
            $result['event_id'],
            $result['club_id']
        );
        $update->execute();
        $saved++;
    }

    $membership->close();
    $update->close();
    $affected_event_ids = array_values(array_unique(array_map(
        static fn(array $input): int => (int)($input['event_id'] ?? 0),
        $inputs
    )));
    cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year, $affected_event_ids);
    return $saved;
}

function cotswold_precreate_results_for_club($conn, $scoresheet_id, $club_id) {
    $stmt = $conn->prepare("SELECT IF(gs.season_year = 9999, ?, gs.season_year) AS event_season
        FROM gala_scoresheets gs
        WHERE gs.id = ?");
    global $active_season_year;
    $stmt->bind_param("ii", $active_season_year, $scoresheet_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return;
    }

    $event_season = (int)$row['event_season'];
    $event_stmt = $conn->prepare("SELECT id FROM gala_events WHERE season_year = ? ORDER BY event_number ASC");
    $event_stmt->bind_param("i", $event_season);
    $event_stmt->execute();
    $events = $event_stmt->get_result();
    $insert = $conn->prepare("INSERT IGNORE INTO gala_results (scoresheet_id, event_id, club_id) VALUES (?, ?, ?)");
    while ($event = $events->fetch_assoc()) {
        $event_id = (int)$event['id'];
        $insert->bind_param("iii", $scoresheet_id, $event_id, $club_id);
        $insert->execute();
    }
    $insert->close();
    $event_stmt->close();
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

    if ($venue_detail_id <= 0 && !$is_super_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Club scoresheets must be created from an assigned venue.']);
        exit;
    }

    if ($venue_detail_id > 0) {
        $v_stmt = $conn->prepare("SELECT round_number, gala_type, club_id, final_scoresheet_club_id, season_year, team_1_id, team_2_id, team_3_id, team_4_id, team_5_id, team_6_id, team_7_id, team_8_id FROM venue_details WHERE id = ?");
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

        if (!cotswold_user_can_access_scoresheet_venue($venue_row, $is_super_admin, $current_club_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'This scoresheet has not been assigned to your club.']);
            exit;
        }

        $round_number = (int)$venue_row['round_number'];
        $gala_type = $venue_row['gala_type'] ?: $gala_type;
        $host_club_id = (int)$venue_row['club_id'];
        if (cotswold_is_final_venue_row($venue_row) && !empty($venue_row['final_scoresheet_club_id'])) {
            $host_club_id = (int)$venue_row['final_scoresheet_club_id'];
        }
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
    $check = $conn->prepare("SELECT id FROM gala_scoresheets WHERE venue_detail_id = ? AND season_year = ? ORDER BY updated_at DESC, id DESC LIMIT 1");
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
    if (!$is_super_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'The testing sandbox is available to league administrators only.']);
        exit;
    }
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
    $s = $conn->prepare("SELECT gs.*, c.name AS host_club_name,
               vd.round_number AS venue_round_number,
               vd.gala_type AS venue_gala_type,
               vd.club_id AS venue_host_club_id,
               vd.final_scoresheet_club_id
        FROM gala_scoresheets gs
        JOIN clubs c ON gs.host_club_id = c.id
        LEFT JOIN venue_details vd ON gs.venue_detail_id = vd.id
        WHERE gs.id = ?");
    $s->bind_param("i", $scoresheet_id);
    $s->execute();
    $scoresheet = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$scoresheet) {
        echo json_encode(['error' => 'Scoresheet not found']);
        exit;
    }

    $scoresheet_access_row = [
        'round_number' => $scoresheet['venue_round_number'] ?? $scoresheet['round_number'],
        'gala_type' => $scoresheet['venue_gala_type'] ?? $scoresheet['gala_type'],
        'host_club_id' => $scoresheet['host_club_id'] ?? null,
        'venue_host_club_id' => $scoresheet['venue_host_club_id'] ?? null,
        'final_scoresheet_club_id' => $scoresheet['final_scoresheet_club_id'] ?? null,
    ];
    if (!cotswold_user_can_access_scoresheet_venue($scoresheet_access_row, $is_super_admin, $current_club_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'This scoresheet has not been assigned to your club.']);
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
            'live_public_enabled' => (int)($scoresheet['live_public_enabled'] ?? 0),
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
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

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
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    $check = $conn->prepare("SELECT id FROM gala_teams WHERE scoresheet_id = ? AND club_id = ?");
    $check->bind_param("ii", $scoresheet_id, $new_club_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        echo json_encode(['error' => 'Team is already in this gala']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE gala_teams SET club_id = ?, is_absent = 0 WHERE scoresheet_id = ? AND club_id = ?");
    $stmt->bind_param("iii", $new_club_id, $scoresheet_id, $old_club_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows < 1) {
            $stmt->close();
            echo json_encode(['error' => 'Original team was not found in this gala']);
            exit;
        }
        $conn->query("UPDATE gala_results SET club_id = $new_club_id, time_ms = NULL, is_dq = 0, dq_reason = NULL, is_verified = 0, points = 0, place = NULL, status = 'pending' WHERE scoresheet_id = $scoresheet_id AND club_id = $old_club_id");
        cotswold_precreate_results_for_club($conn, $scoresheet_id, $new_club_id);
        $conn->query("UPDATE gala_scoresheets SET status = 'in_progress', updated_at = NOW() WHERE id = $scoresheet_id AND status = 'draft'");
        cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year);
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
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    $stmt = $conn->prepare("UPDATE gala_teams SET is_absent = 1, lane_number = NULL WHERE scoresheet_id = ? AND club_id = ?");
    $stmt->bind_param("ii", $scoresheet_id, $club_id);
    if ($stmt->execute()) {
        $clear = $conn->prepare("UPDATE gala_results SET time_ms = NULL, is_dq = 0, dq_reason = NULL, is_verified = 0, points = 0, place = NULL, status = 'pending' WHERE scoresheet_id = ? AND club_id = ?");
        $clear->bind_param("ii", $scoresheet_id, $club_id);
        $clear->execute();
        $clear->close();
        cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year);
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
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    // Check if team already exists
    $check = $conn->query("SELECT id FROM gala_teams WHERE scoresheet_id = $scoresheet_id AND club_id = $new_club_id");
    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'Team is already in this gala']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO gala_teams (scoresheet_id, club_id, is_absent, lane_number) VALUES (?, ?, 0, NULL)");
    $stmt->bind_param("ii", $scoresheet_id, $new_club_id);
    if ($stmt->execute()) {
        cotswold_precreate_results_for_club($conn, $scoresheet_id, $new_club_id);
        $conn->query("UPDATE gala_scoresheets SET team_count = team_count + 1, status = 'in_progress', updated_at = NOW() WHERE id = $scoresheet_id");
        cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year);
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
    if (!$scoresheet_id) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    $conn->begin_transaction();
    try {
        cotswold_save_raw_results($conn, $scoresheet_id, [$_POST], $active_season_year);
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (InvalidArgumentException $e) {
        $conn->rollback();
        http_response_code(422);
        echo json_encode(['error' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'The result could not be saved.']);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Scoresheet result save failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'The result could not be saved.']);
    }
    exit;
}

// =====================================================
// POST: Batch save results (for offline sync)
// =====================================================
if ($action === 'save_batch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $results = json_decode($_POST['results'] ?? '[]', true);

    if (!$scoresheet_id || !is_array($results) || empty($results)) {
        echo json_encode(['error' => 'Missing data']);
        exit;
    }
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    $conn->begin_transaction();
    try {
        $saved = cotswold_save_raw_results($conn, $scoresheet_id, $results, $active_season_year);
        $conn->commit();
        echo json_encode(['success' => true, 'saved' => $saved]);
    } catch (InvalidArgumentException $e) {
        $conn->rollback();
        http_response_code(422);
        echo json_encode(['error' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'The results could not be saved.']);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Scoresheet batch save failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'The results could not be saved.']);
    }
    exit;
}

// =====================================================
// POST: Toggle public live results
// =====================================================
if ($action === 'set_live_public' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $enabled = intval($_POST['enabled'] ?? 0) ? 1 : 0;

    if (!$scoresheet_id) {
        echo json_encode(['error' => 'Missing scoresheet_id']);
        exit;
    }
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    if ($enabled) {
        cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year);
        $stmt = $conn->prepare("UPDATE gala_scoresheets SET live_public_enabled = 1, live_public_started_at = COALESCE(live_public_started_at, NOW()), updated_at = NOW() WHERE id = ? AND status = 'in_progress'");
    } else {
        $stmt = $conn->prepare("UPDATE gala_scoresheets SET live_public_enabled = 0, updated_at = NOW() WHERE id = ?");
    }
    $stmt->bind_param("i", $scoresheet_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($enabled && $affected === 0) {
        echo json_encode(['error' => 'This scoresheet cannot be published live.']);
        exit;
    }

    echo json_encode(['success' => true, 'live_public_enabled' => $enabled]);
    exit;
}

// =====================================================
// POST: Submit scoresheet
// =====================================================
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    cotswold_require_scoresheet_access($conn, $scoresheet_id, $is_super_admin, $current_club_id);

    $conn->begin_transaction();
    try {
        cotswold_recalculate_scoresheet($conn, $scoresheet_id, $active_season_year);
        $stmt = $conn->prepare("UPDATE gala_scoresheets SET status = 'submitted', submitted_at = NOW(), live_public_enabled = 0 WHERE id = ? AND status IN ('draft', 'in_progress', 'submitted')");
        $stmt->bind_param('i', $scoresheet_id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new InvalidArgumentException('This scoresheet is no longer available for submission.');
        }
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Scoresheet submitted for verification']);
    } catch (InvalidArgumentException $e) {
        $conn->rollback();
        http_response_code(422);
        echo json_encode(['error' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'The scoresheet could not be submitted.']);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Scoresheet submission failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'The scoresheet could not be submitted.']);
    }
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

    $v_check = $conn->prepare("SELECT id, round_number, gala_type, club_id, final_scoresheet_club_id FROM venue_details WHERE id = ? AND season_year = ?");
    $v_check->bind_param("ii", $venue_detail_id, $season);
    $v_check->execute();
    $v_check_res = $v_check->get_result();
    $venue_access_row = $v_check_res->fetch_assoc();
    if (!$venue_access_row) {
        echo json_encode(['error' => 'Venue not found for this season']);
        exit;
    }
    $v_check->close();

    if (!cotswold_user_can_access_scoresheet_venue($venue_access_row, $is_super_admin, $current_club_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'This scoresheet has not been assigned to your club.']);
        exit;
    }

    // Check if scoresheet exists
    $s = $conn->prepare("SELECT id, status FROM gala_scoresheets WHERE venue_detail_id = ? AND season_year = ? ORDER BY updated_at DESC, id DESC LIMIT 1");
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
