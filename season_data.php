<?php
include_once 'db.php';

// ---------------------------------------------------------
// DATABASE MIGRATION CHECK (Runs automatically if needed)
// ---------------------------------------------------------
$migration_needed = false;
$check_sql = "SHOW COLUMNS FROM venue_details LIKE 'team_1_id'";
$check_res = $conn->query($check_sql);
if ($check_res && $check_res->num_rows == 0) {
    $migration_needed = true;
}

if ($migration_needed) {
    // 1. Alter Table
    $alter_sql = "ALTER TABLE venue_details 
                  ADD COLUMN team_1_id INT DEFAULT NULL,
                  ADD COLUMN team_2_id INT DEFAULT NULL,
                  ADD COLUMN team_3_id INT DEFAULT NULL,
                  ADD COLUMN team_4_id INT DEFAULT NULL,
                  ADD COLUMN round_date VARCHAR(50) DEFAULT NULL";
    $conn->query($alter_sql);

    // 2. Fetch Club ID Map
    $club_map = [];
    $c_res = $conn->query("SELECT id, name FROM clubs");
    if ($c_res) {
        while ($r = $c_res->fetch_assoc()) {
            $club_map[$r['name']] = $r['id'];
        }
    }

    // 3. Populate from original season_data.php structure
    $initial_seed_data = [
        [
            "round" => 1,
            "date" => "31/01/2026",
            "galas" => [
                ["host" => "Cwmbran", "teams" => ["Cwmbran", "Yeovil", "Dursley", "Monnow SC"]],
                ["host" => "Backwell", "teams" => ["Backwell", "Brockworth", "Bridgwater", "Forest of Dean"]],
                ["host" => "Corsham", "teams" => ["Corsham", "Swindon ASC", "Burnham-On-Sea", "Bristol North"]],
                ["host" => "Bath Dolphin", "teams" => ["Bath Dolphin", "Clevedon", "Wells", "Newport"]],
                ["host" => "COB (City of Bristol)", "teams" => ["COB (City of Bristol)", "Academy Swim Team", "Southwold SC", "Severnside Tritons"]]
            ]
        ],
        [
            "round" => 2,
            "date" => "14/02/2026",
            "galas" => [
                ["host" => "Yeovil", "teams" => ["Yeovil", "Bath Dolphin", "Bridgwater", "Bristol North"]],
                ["host" => "Brockworth", "teams" => ["Brockworth", "COB (City of Bristol)", "Burnham-On-Sea", "Newport"]],
                ["host" => "Swindon ASC", "teams" => ["Swindon ASC", "Cwmbran", "Wells", "Severnside Tritons"]],
                ["host" => "Clevedon", "teams" => ["Clevedon", "Backwell", "Southwold SC", "Monnow SC"]],
                ["host" => "Academy Swim Team", "teams" => ["Academy Swim Team", "Corsham", "Dursley", "Forest of Dean"]]
            ]
        ],
        [
            "round" => 3,
            "date" => "07/03/2026",
            "galas" => [
                ["host" => "Dursley", "teams" => ["Dursley", "COB (City of Bristol)", "Clevedon", "Bristol North"]],
                ["host" => "Bridgwater", "teams" => ["Bridgwater", "Cwmbran", "Academy Swim Team", "Newport"]],
                ["host" => "Burnham-On-Sea", "teams" => ["Burnham-On-Sea", "Backwell", "Yeovil", "Severnside Tritons"]],
                ["host" => "Wells", "teams" => ["Wells", "Corsham", "Brockworth", "Monnow SC"]],
                ["host" => "Southwold SC", "teams" => ["Southwold SC", "Bath Dolphin", "Swindon ASC", "Forest of Dean"]]
            ]
        ],
        [
            "round" => 4,
            "date" => "28/03/2026",
            "galas" => [
                ["host" => "Monnow SC", "teams" => ["Monnow SC", "Bath Dolphin", "Academy Swim Team", "Burnham-On-Sea"]],
                ["host" => "Forest of Dean", "teams" => ["Forest of Dean", "COB (City of Bristol)", "Yeovil", "Wells"]],
                ["host" => "Bristol North", "teams" => ["Bristol North", "Cwmbran", "Brockworth", "Southwold SC"]],
                ["host" => "Newport", "teams" => ["Newport", "Backwell", "Swindon ASC", "Dursley"]],
                ["host" => "Severnside Tritons", "teams" => ["Severnside Tritons", "Corsham", "Clevedon", "Bridgwater"]]
            ]
        ]
    ];

    $update_stmt = $conn->prepare("UPDATE venue_details SET team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, round_date=? WHERE club_id=? AND round_number=?");
    
    foreach ($initial_seed_data as $rnd) {
        $rnd_num = $rnd['round'];
        $rnd_date = $rnd['date'];
        foreach ($rnd['galas'] as $gala) {
            $host_id = $club_map[$gala['host']] ?? null;
            $t1 = $club_map[$gala['teams'][0]] ?? null;
            $t2 = $club_map[$gala['teams'][1]] ?? null;
            $t3 = $club_map[$gala['teams'][2]] ?? null;
            $t4 = $club_map[$gala['teams'][3]] ?? null;
            
            if ($host_id) {
                $update_stmt->bind_param("iiiisii", $t1, $t2, $t3, $t4, $rnd_date, $host_id, $rnd_num);
                $update_stmt->execute();
            }
        }
    }
}
// ---------------------------------------------------------

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
                  WHERE vd.round_number = $i
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
                
                $details_str = implode(". ", $details) . ".";
                
                // Construct logic for team inclusion
                $teams = [];
                if (!empty($row['team_1_id']) && isset($club_map[$row['team_1_id']])) $teams[] = $club_map[$row['team_1_id']];
                if (!empty($row['team_2_id']) && isset($club_map[$row['team_2_id']])) $teams[] = $club_map[$row['team_2_id']];
                if (!empty($row['team_3_id']) && isset($club_map[$row['team_3_id']])) $teams[] = $club_map[$row['team_3_id']];
                if (!empty($row['team_4_id']) && isset($club_map[$row['team_4_id']])) $teams[] = $club_map[$row['team_4_id']];
                
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