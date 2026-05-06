<?php

function cotswold_get_placeholder_club_id($conn) {
    $res = $conn->query("SELECT id FROM clubs ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return (int)$res->fetch_assoc()['id'];
    }
    return 1;
}

function cotswold_final_gala_label($gala_type) {
    $labels = [
        'a_final' => 'A Final',
        'b_final' => 'B Final',
        'c_final' => 'C Final',
    ];
    return $labels[$gala_type] ?? strtoupper(str_replace('_', ' ', $gala_type));
}

function cotswold_get_finals_date_for_season($conn, $season) {
    $setting_key = 'finals_date_' . $season;
    $stmt = $conn->prepare("SELECT setting_value FROM global_settings WHERE setting_key=? LIMIT 1");
    $stmt->bind_param("s", $setting_key);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        return $row['setting_value'] ?? '';
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT round_date FROM venue_details WHERE season_year=? AND (round_number=99 OR gala_type IN ('a_final','b_final','c_final')) AND round_date IS NOT NULL AND round_date != '' ORDER BY round_date DESC LIMIT 1");
    $stmt->bind_param("i", $season);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        return $row['round_date'] ?? '';
    }
    $stmt->close();
    return '';
}

function cotswold_ensure_finals_venue_rows($conn, $season, $round_date = '') {
    $placeholder_club = cotswold_get_placeholder_club_id($conn);
    $final_types = ['a_final', 'b_final', 'c_final'];

    foreach ($final_types as $gala_type) {
        $stmt = $conn->prepare("SELECT id FROM venue_details WHERE season_year=? AND round_number=99 AND gala_type=? LIMIT 1");
        $stmt->bind_param("is", $season, $gala_type);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            if ($round_date !== '') {
                $existing_id = (int)$existing['id'];
                $update = $conn->prepare("UPDATE venue_details SET round_date=? WHERE id=?");
                $update->bind_param("si", $round_date, $existing_id);
                $update->execute();
                $update->close();
            }
            continue;
        }

        $venue_name = cotswold_final_gala_label($gala_type) . ' - Venue TBC';
        $date_value = $round_date !== '' ? $round_date : null;
        $insert = $conn->prepare("INSERT INTO venue_details (club_id, venue_name, round_number, gala_type, season_year, round_date) VALUES (?, ?, 99, ?, ?, ?)");
        $insert->bind_param("issis", $placeholder_club, $venue_name, $gala_type, $season, $date_value);
        $insert->execute();
        $insert->close();
    }
}

function cotswold_sync_finals_from_standings($conn, $season) {
    $finals_date = cotswold_get_finals_date_for_season($conn, $season);
    cotswold_ensure_finals_venue_rows($conn, $season, $finals_date);

    $rank_sql = "SELECT c.id,
                        (COALESCE(r.round_1,0) + COALESCE(r.round_2,0) + COALESCE(r.round_3,0) + COALESCE(r.round_4,0)) AS total
                 FROM clubs c
                 LEFT JOIN results r ON r.club_id = c.id AND r.season_year = ?
                 ORDER BY total DESC, c.name ASC";
    $stmt = $conn->prepare($rank_sql);
    $stmt->bind_param("i", $season);
    $stmt->execute();
    $res = $stmt->get_result();

    $ranked_teams = [];
    while ($row = $res->fetch_assoc()) {
        $ranked_teams[] = (int)$row['id'];
    }
    $stmt->close();

    if (count($ranked_teams) < 20) {
        return [
            'synced' => false,
            'team_count' => count($ranked_teams),
            'message' => 'Finals slots exist, but at least 20 clubs are required to assign A/B/C finals.',
        ];
    }

    $finals = [
        'a_final' => array_slice($ranked_teams, 0, 8),
        'b_final' => array_slice($ranked_teams, 8, 6),
        'c_final' => array_slice($ranked_teams, 14, 6),
    ];

    foreach ($finals as $gala_type => $teams) {
        $t = array_pad($teams, 8, null);
        $find = $conn->prepare("SELECT id FROM venue_details WHERE season_year=? AND round_number=99 AND gala_type=? LIMIT 1");
        $find->bind_param("is", $season, $gala_type);
        $find->execute();
        $existing = $find->get_result()->fetch_assoc();
        $find->close();

        if (!$existing) {
            cotswold_ensure_finals_venue_rows($conn, $season, $finals_date);
            $find = $conn->prepare("SELECT id FROM venue_details WHERE season_year=? AND round_number=99 AND gala_type=? LIMIT 1");
            $find->bind_param("is", $season, $gala_type);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            $find->close();
        }

        if ($existing) {
            $existing_id = (int)$existing['id'];
            $update = $conn->prepare("UPDATE venue_details SET team_1_id=?, team_2_id=?, team_3_id=?, team_4_id=?, team_5_id=?, team_6_id=?, team_7_id=?, team_8_id=? WHERE id=?");
            $update->bind_param("iiiiiiiii", $t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $existing_id);
            $update->execute();
            $update->close();
        }
    }

    return [
        'synced' => true,
        'team_count' => count($ranked_teams),
        'message' => 'Finals assignments synced from the current standings.',
    ];
}

?>
