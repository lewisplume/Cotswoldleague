<?php
include_once 'db.php';

// This read helper intentionally performs no migrations or fallback seeding.
// Schema changes and season imports belong to explicit operational scripts.

$season_draw = [];

// Helper to get club name by ID
$club_map = [];
$c_res = $conn->query("SELECT id, name FROM clubs");
if ($c_res) {
    while ($r = $c_res->fetch_assoc()) {
        $club_map[$r['id']] = $r['name'];
    }
}

// Ensure the db columns exist (after optional migration)
$can_build_draw = false;
$check_cols = $conn->query("SHOW COLUMNS FROM venue_details LIKE 'team_1_id'");
if ($check_cols && $check_cols->num_rows > 0) {
    $can_build_draw = true;
}

if ($can_build_draw) {
    // Fetch venues data grouped by round
    for ($i = 1; $i <= 4; $i++) {
        $round_date = "";
        $galas = [];
        
        $v_sql = "SELECT vd.*, c.name AS host_club_name 
                  FROM venue_details vd 
                  JOIN clubs c ON vd.club_id = c.id 
                  WHERE vd.round_number = $i AND vd.season_year = $current_season_year
                  ORDER BY c.name ASC";
        $v_res = $conn->query($v_sql);
        
        if ($v_res && $v_res->num_rows > 0) {
            while ($row = $v_res->fetch_assoc()) {
                if (empty($round_date) && !empty($row['round_date'])) {
                    $round_date = $row['round_date'];
                }
                
                // Build details string for fallback compatibility with old static arrays
                // Example format: "Halo Pontypool Active Living Centre (NP4 8AT). Doors 5:30PM. Cash Only. Free Parking."
                $details = [];
                if (!empty($row['venue_name'])) $details[] = $row['venue_name'];
                if (!empty($row['address'])) $details[] = $row['address'];
                if (!empty($row['start_time'])) $details[] = "Doors {$row['start_time']}";
                if (!empty($row['warmup_time'])) $details[] = "W/U {$row['warmup_time']}";
                if (!empty($row['payment_info'])) $details[] = $row['payment_info'];
                if (!empty($row['parking_info'])) $details[] = $row['parking_info'];
                if (!empty($row['other_info'])) $details[] = $row['other_info'];
                
                $details_str = implode(". ", $details) . ".";
                
                // Construct logic for team inclusion
                $teams = [];
                if (!empty($row['team_1_id']) && isset($club_map[$row['team_1_id']])) $teams[] = $club_map[$row['team_1_id']];
                if (!empty($row['team_2_id']) && isset($club_map[$row['team_2_id']])) $teams[] = $club_map[$row['team_2_id']];
                if (!empty($row['team_3_id']) && isset($club_map[$row['team_3_id']])) $teams[] = $club_map[$row['team_3_id']];
                if (!empty($row['team_4_id']) && isset($club_map[$row['team_4_id']])) $teams[] = $club_map[$row['team_4_id']];
                if (!empty($row['team_5_id']) && isset($club_map[$row['team_5_id']])) $teams[] = $club_map[$row['team_5_id']];
                if (!empty($row['team_6_id']) && isset($club_map[$row['team_6_id']])) $teams[] = $club_map[$row['team_6_id']];
                if (!empty($row['team_7_id']) && isset($club_map[$row['team_7_id']])) $teams[] = $club_map[$row['team_7_id']];
                if (!empty($row['team_8_id']) && isset($club_map[$row['team_8_id']])) $teams[] = $club_map[$row['team_8_id']];
                
                // If teams array is empty for some reason, at least list the host so the UI doesn't crash completely.
                if (empty($teams)) {
                     $teams[] = $row['host_club_name'];
                }

                $galas[] = [
                    "host" => $row['host_club_name'],
                    "details" => $details_str,
                    "teams" => $teams
                ];
            }
        }
        
        if (!empty($galas)) {
            $season_draw[] = [
                 "round" => $i,
                 "date" => $round_date ?: "TBA",
                 "galas" => $galas
            ];
        }
    }
}
?>
