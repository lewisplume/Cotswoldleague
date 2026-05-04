<?php
/**
 * Gala Admin API
 * Handles all AJAX operations for the Super Admin Gala Management dashboard.
 */
session_start();
include 'db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$is_super_admin = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'] === true;

if (!$is_super_admin) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// =====================================================
// GET: List all scoresheets for a given round
// =====================================================
if ($action === 'list_scoresheets' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $round = intval($_GET['round'] ?? 0);
    $season = intval($_GET['season'] ?? 2027);

    // Get all venues for this round
    $sql = "SELECT vd.id as venue_detail_id, c.name as host_club_name, c.logo as host_club_logo,
                   vd.team_1_id, t1.name as t1_name,
                   vd.team_2_id, t2.name as t2_name,
                   vd.team_3_id, t3.name as t3_name,
                   vd.team_4_id, t4.name as t4_name,
                   gs.id as scoresheet_id, gs.status, gs.recorder_name, gs.updated_at, gs.submitted_at
            FROM venue_details vd
            JOIN clubs c ON vd.club_id = c.id
            LEFT JOIN clubs t1 ON vd.team_1_id = t1.id
            LEFT JOIN clubs t2 ON vd.team_2_id = t2.id
            LEFT JOIN clubs t3 ON vd.team_3_id = t3.id
            LEFT JOIN clubs t4 ON vd.team_4_id = t4.id
            LEFT JOIN gala_scoresheets gs ON vd.id = gs.venue_detail_id AND gs.season_year = ?
            WHERE vd.round_number = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $season, $round);
    $stmt->execute();
    $res = $stmt->get_result();

    $venues = [];
    while ($row = $res->fetch_assoc()) {
        $venues[] = [
            'venue_detail_id' => (int)$row['venue_detail_id'],
            'host_club_name' => $row['host_club_name'],
            'host_club_logo' => $row['host_club_logo'],
            'team_1_id' => $row['team_1_id'] ? (int)$row['team_1_id'] : null,
            'team_1_name' => $row['t1_name'],
            'team_2_id' => $row['team_2_id'] ? (int)$row['team_2_id'] : null,
            'team_2_name' => $row['t2_name'],
            'team_3_id' => $row['team_3_id'] ? (int)$row['team_3_id'] : null,
            'team_3_name' => $row['t3_name'],
            'team_4_id' => $row['team_4_id'] ? (int)$row['team_4_id'] : null,
            'team_4_name' => $row['t4_name'],
            'scoresheet_id' => $row['scoresheet_id'] ? (int)$row['scoresheet_id'] : null,
            'status' => $row['status'] ?? 'not_started',
            'recorder_name' => $row['recorder_name'],
            'updated_at' => $row['updated_at'],
            'submitted_at' => $row['submitted_at']
        ];
    }
    $stmt->close();

    echo json_encode(['venues' => $venues]);
    exit;
}

// =====================================================
// POST: Verify a scoresheet
// =====================================================
if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE gala_scoresheets SET status = 'verified', verified_by = 'Super Admin', verified_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $scoresheet_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Reject a scoresheet
// =====================================================
if ($action === 'reject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE gala_scoresheets SET status = 'in_progress', verified_by = NULL, verified_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $scoresheet_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Publish a round (updates main results table)
// =====================================================
if ($action === 'publish_round' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $round = intval($_POST['round'] ?? 0);
    $season = intval($_POST['season'] ?? 2027);

    // 1. Check if all scoresheets for this round are verified
    $check_sql = "SELECT gs.id, gs.status, gs.total_points_json 
                  FROM venue_details vd 
                  LEFT JOIN gala_scoresheets gs ON vd.id = gs.venue_detail_id AND gs.season_year = ?
                  WHERE vd.round_number = ?";
    $c_stmt = $conn->prepare($check_sql);
    $c_stmt->bind_param("ii", $season, $round);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();

    $all_verified = true;
    $scoresheets = [];
    $total_venues = $c_res->num_rows;
    $completed_venues = 0;

    while ($row = $c_res->fetch_assoc()) {
        if (!$row['id'] || $row['status'] !== 'verified') {
            $all_verified = false;
            break;
        }
        $scoresheets[] = $row;
        $completed_venues++;
    }
    $c_stmt->close();

    if ($total_venues === 0) {
        echo json_encode(['error' => 'No venues found for this round.']);
        exit;
    }

    if (!$all_verified || $completed_venues < $total_venues) {
        echo json_encode(['error' => 'Cannot publish. All venues must be verified first.']);
        exit;
    }

    // 2. Aggregate points from all verified scoresheets
    $points_map = []; // [club_id => total_points]
    foreach ($scoresheets as $sheet) {
        if (!empty($sheet['total_points_json'])) {
            $pts = json_decode($sheet['total_points_json'], true);
            if (is_array($pts)) {
                foreach ($pts as $club_id => $score) {
                    $points_map[(int)$club_id] = (int)$score;
                }
            }
        }
    }

    // 3. Update the main `results` table
    $round_col = "round_" . $round; // round_1, round_2, etc.
    
    // Ensure column is safe
    if (!in_array($round_col, ['round_1', 'round_2', 'round_3', 'round_4'])) {
        echo json_encode(['error' => 'Invalid round column.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        foreach ($points_map as $club_id => $pts) {
            // Check if club exists in results
            $chk = $conn->query("SELECT id FROM results WHERE club_id = $club_id AND season_year = $season");
            if ($chk->num_rows > 0) {
                $conn->query("UPDATE results SET $round_col = $pts WHERE club_id = $club_id AND season_year = $season");
            } else {
                $conn->query("INSERT INTO results (club_id, season_year, $round_col) VALUES ($club_id, $season, $pts)");
            }
        }

        // Update totals
        $conn->query("UPDATE results SET total = COALESCE(round_1,0) + COALESCE(round_2,0) + COALESCE(round_3,0) + COALESCE(round_4,0) WHERE season_year = $season");

        // Mark scoresheets as published
        $ids = array_column($scoresheets, 'id');
        $id_str = implode(',', $ids);
        $conn->query("UPDATE gala_scoresheets SET status = 'published' WHERE id IN ($id_str)");

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Round $round published successfully."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Database error during publish: ' . $e->getMessage()]);
    }
    exit;
}

// =====================================================
// POST: Mark team absent
// =====================================================
if ($action === 'mark_absent' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_id = intval($_POST['scoresheet_id'] ?? 0);
    $club_id = intval($_POST['club_id'] ?? 0);
    $is_absent = intval($_POST['is_absent'] ?? 1);

    $stmt = $conn->prepare("UPDATE gala_teams SET is_absent = ?, lane_number = NULL WHERE scoresheet_id = ? AND club_id = ?");
    $stmt->bind_param("iii", $is_absent, $scoresheet_id, $club_id);
    if ($stmt->execute()) {
        // If marking absent, also clear any existing results for this team in this scoresheet
        if ($is_absent == 1) {
            $conn->query("UPDATE gala_results SET time_ms = NULL, is_dq = 0, status = 'pending', source_type = 'live' WHERE scoresheet_id = $scoresheet_id AND club_id = $club_id");
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update attendance status.']);
    }
    $stmt->close();
    exit;
}

// =====================================================
// POST: Cross-gala import results
// =====================================================
if ($action === 'import_results' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_scoresheet_id = intval($_POST['target_scoresheet_id'] ?? 0);
    $source_scoresheet_id = intval($_POST['source_scoresheet_id'] ?? 0);
    $club_id = intval($_POST['club_id'] ?? 0);

    if (!$target_scoresheet_id || !$source_scoresheet_id || !$club_id) {
        echo json_encode(['error' => 'Missing required IDs for import']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Mark team as present but imported in target scoresheet
        $conn->query("UPDATE gala_teams SET is_absent = 0 WHERE scoresheet_id = $target_scoresheet_id AND club_id = $club_id");

        // 2. Get source results
        $src_res = $conn->query("SELECT event_id, time_ms, is_dq, dq_reason FROM gala_results WHERE scoresheet_id = $source_scoresheet_id AND club_id = $club_id");
        
        // 3. Insert/Update into target
        $upd_stmt = $conn->prepare("UPDATE gala_results SET 
            time_ms = ?, is_dq = ?, dq_reason = ?, 
            source_type = 'imported', source_scoresheet_id = ?, imported_by = 'Super Admin', imported_at = NOW(),
            is_verified = 1
            WHERE scoresheet_id = ? AND event_id = ? AND club_id = ?");

        $imported_count = 0;
        while ($row = $src_res->fetch_assoc()) {
            $upd_stmt->bind_param("iisiiii", 
                $row['time_ms'], $row['is_dq'], $row['dq_reason'], 
                $source_scoresheet_id, $target_scoresheet_id, $row['event_id'], $club_id
            );
            $upd_stmt->execute();
            $imported_count++;
        }
        $upd_stmt->close();

        // 4. Force status back to in_progress if it was submitted/verified so scoring recalculates
        $conn->query("UPDATE gala_scoresheets SET status = 'in_progress', verified_by = NULL WHERE id = $target_scoresheet_id");

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Imported $imported_count event results."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
    }
    exit;
}

// =====================================================
// POST: Virtual Swap Teams
// =====================================================
if ($action === 'swap_teams' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoresheet_a = intval($_POST['scoresheet_a'] ?? 0);
    $team_a = intval($_POST['team_a'] ?? 0);
    $scoresheet_b = intval($_POST['scoresheet_b'] ?? 0);
    $team_b = intval($_POST['team_b'] ?? 0);

    if (!$scoresheet_a || !$team_a || !$scoresheet_b || !$team_b) {
        echo json_encode(['error' => 'Missing data for swap']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Find venue details IDs for these scoresheets
        $v1_res = $conn->query("SELECT venue_detail_id FROM gala_scoresheets WHERE id = $scoresheet_a")->fetch_assoc();
        $v2_res = $conn->query("SELECT venue_detail_id FROM gala_scoresheets WHERE id = $scoresheet_b")->fetch_assoc();
        $venue_1_id = $v1_res['venue_detail_id'];
        $venue_2_id = $v2_res['venue_detail_id'];

        // 1. Swap in gala_teams
        $conn->query("UPDATE gala_teams SET scoresheet_id = 999999 WHERE scoresheet_id = $scoresheet_a AND club_id = $team_a");
        $conn->query("UPDATE gala_teams SET scoresheet_id = $scoresheet_a WHERE scoresheet_id = $scoresheet_b AND club_id = $team_b");
        $conn->query("UPDATE gala_teams SET scoresheet_id = $scoresheet_b WHERE scoresheet_id = 999999 AND club_id = $team_a");

        // 2. Swap in gala_results
        $conn->query("UPDATE gala_results SET scoresheet_id = 999999 WHERE scoresheet_id = $scoresheet_a AND club_id = $team_a");
        $conn->query("UPDATE gala_results SET scoresheet_id = $scoresheet_a WHERE scoresheet_id = $scoresheet_b AND club_id = $team_b");
        $conn->query("UPDATE gala_results SET scoresheet_id = $scoresheet_b WHERE scoresheet_id = 999999 AND club_id = $team_a");

        // 3. Swap in venue_details
        $v1_data = $conn->query("SELECT team_1_id, team_2_id, team_3_id, team_4_id FROM venue_details WHERE id = $venue_1_id")->fetch_assoc();
        $v2_data = $conn->query("SELECT team_1_id, team_2_id, team_3_id, team_4_id FROM venue_details WHERE id = $venue_2_id")->fetch_assoc();
        
        $col1 = null;
        foreach(['team_1_id', 'team_2_id', 'team_3_id', 'team_4_id'] as $col) {
            if ($v1_data[$col] == $team_a) $col1 = $col;
        }
        $col2 = null;
        foreach(['team_1_id', 'team_2_id', 'team_3_id', 'team_4_id'] as $col) {
            if ($v2_data[$col] == $team_b) $col2 = $col;
        }

        if ($col1 && $col2) {
            $conn->query("UPDATE venue_details SET $col1 = $team_b WHERE id = $venue_1_id");
            $conn->query("UPDATE venue_details SET $col2 = $team_a WHERE id = $venue_2_id");
        }

        // 4. Force status to in_progress to recalculate
        $conn->query("UPDATE gala_scoresheets SET status = 'in_progress', verified_by = NULL WHERE id IN ($scoresheet_a, $scoresheet_b)");

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Swap failed: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action: ' . $action]);
