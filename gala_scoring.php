<?php

/**
 * Server-authoritative equivalent of GalaEngine.calculateEventScores().
 * Only raw time/DQ inputs are trusted; all derived fields are calculated here.
 */
function cotswold_calculate_event_scores(array $entries, int $cut_off_ms): array
{
    $classified = [];

    foreach ($entries as $entry) {
        $club_id = (int)($entry['club_id'] ?? 0);
        $time_ms = array_key_exists('time_ms', $entry) && $entry['time_ms'] !== null
            ? (int)$entry['time_ms']
            : null;
        $is_dq = !empty($entry['is_dq']);

        if ($is_dq) {
            $status = 'dq';
        } elseif ($time_ms === null) {
            $status = 'pending';
        } elseif ($time_ms < $cut_off_ms) {
            $status = 'too_fast';
        } else {
            $status = 'valid';
        }

        $classified[$club_id] = [
            'club_id' => $club_id,
            'time_ms' => $time_ms,
            'is_dq' => $is_dq ? 1 : 0,
            'points' => 0,
            'place' => null,
            'status' => $status,
        ];
    }

    $dq_count = count(array_filter($classified, static fn(array $entry): bool => $entry['status'] === 'dq'));
    $too_fast_count = count(array_filter($classified, static fn(array $entry): bool => $entry['status'] === 'too_fast'));
    $valid = array_values(array_filter($classified, static fn(array $entry): bool => $entry['status'] === 'valid'));

    foreach ($valid as $entry) {
        $same_or_slower = count(array_filter(
            $valid,
            static fn(array $other): bool => $other['time_ms'] >= $entry['time_ms']
        ));
        $classified[$entry['club_id']]['points'] = $dq_count + $too_fast_count + $same_or_slower;
    }

    usort($valid, static function (array $left, array $right): int {
        $time_comparison = $left['time_ms'] <=> $right['time_ms'];
        return $time_comparison !== 0 ? $time_comparison : ($left['club_id'] <=> $right['club_id']);
    });

    $previous_time = null;
    $previous_place = null;
    foreach ($valid as $index => $entry) {
        $place = ($index > 0 && $entry['time_ms'] === $previous_time)
            ? $previous_place
            : $index + 1;
        $classified[$entry['club_id']]['place'] = $place;
        $previous_time = $entry['time_ms'];
        $previous_place = $place;
    }

    return $classified;
}

/**
 * Recalculate derived results and authoritative totals for a scoresheet.
 * When event IDs are supplied, only those events are rescored before totals
 * are refreshed; submission/verification/publication call this without a
 * filter to recalculate the whole meet. The caller controls transactions.
 */
function cotswold_recalculate_scoresheet(
    mysqli $conn,
    int $scoresheet_id,
    int $active_season_year,
    ?array $only_event_ids = null
): array
{
    $sheet_stmt = $conn->prepare('SELECT id, gala_type, season_year FROM gala_scoresheets WHERE id = ?');
    $sheet_stmt->bind_param('i', $scoresheet_id);
    $sheet_stmt->execute();
    $sheet = $sheet_stmt->get_result()->fetch_assoc();
    $sheet_stmt->close();

    if (!$sheet) {
        throw new RuntimeException('Scoresheet not found.');
    }

    $event_season = (int)$sheet['season_year'] === 9999
        ? $active_season_year
        : (int)$sheet['season_year'];

    $teams = [];
    $team_stmt = $conn->prepare('SELECT club_id, is_absent FROM gala_teams WHERE scoresheet_id = ? ORDER BY id ASC');
    $team_stmt->bind_param('i', $scoresheet_id);
    $team_stmt->execute();
    $team_result = $team_stmt->get_result();
    while ($team = $team_result->fetch_assoc()) {
        $teams[(int)$team['club_id']] = [
            'club_id' => (int)$team['club_id'],
            'is_absent' => (int)$team['is_absent'],
        ];
    }
    $team_stmt->close();

    $events = [];
    $event_stmt = $conn->prepare('SELECT id, cut_off_time_ms, a_final_event_name, a_final_cut_off_time_ms FROM gala_events WHERE season_year = ? ORDER BY event_number ASC');
    $event_stmt->bind_param('i', $event_season);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
    while ($event = $event_result->fetch_assoc()) {
        $cut_off_ms = (int)$event['cut_off_time_ms'];
        if ($sheet['gala_type'] === 'a_final' && !empty($event['a_final_event_name']) && $event['a_final_cut_off_time_ms'] !== null) {
            $cut_off_ms = (int)$event['a_final_cut_off_time_ms'];
        }
        $events[(int)$event['id']] = $cut_off_ms;
    }
    $event_stmt->close();

    if ($only_event_ids !== null) {
        $allowed_event_ids = array_fill_keys(array_map('intval', $only_event_ids), true);
        $events = array_intersect_key($events, $allowed_event_ids);
    }

    if ($teams && $events) {
        $ensure = $conn->prepare('INSERT IGNORE INTO gala_results (scoresheet_id, event_id, club_id) VALUES (?, ?, ?)');
        foreach (array_keys($events) as $event_id) {
            foreach (array_keys($teams) as $club_id) {
                $ensure->bind_param('iii', $scoresheet_id, $event_id, $club_id);
                $ensure->execute();
            }
        }
        $ensure->close();
    }

    $raw_results = [];
    $result_stmt = $conn->prepare('SELECT event_id, club_id, time_ms, is_dq FROM gala_results WHERE scoresheet_id = ?');
    $result_stmt->bind_param('i', $scoresheet_id);
    $result_stmt->execute();
    $result_set = $result_stmt->get_result();
    while ($result = $result_set->fetch_assoc()) {
        $raw_results[(int)$result['event_id']][(int)$result['club_id']] = [
            'club_id' => (int)$result['club_id'],
            'time_ms' => $result['time_ms'] !== null ? (int)$result['time_ms'] : null,
            'is_dq' => (int)$result['is_dq'],
        ];
    }
    $result_stmt->close();

    $update = $conn->prepare('UPDATE gala_results SET points = ?, place = ?, status = ? WHERE scoresheet_id = ? AND event_id = ? AND club_id = ?');

    foreach ($events as $event_id => $cut_off_ms) {
        $entries = [];
        foreach ($teams as $club_id => $team) {
            if ($team['is_absent']) {
                continue;
            }
            $entries[] = $raw_results[$event_id][$club_id] ?? [
                'club_id' => $club_id,
                'time_ms' => null,
                'is_dq' => 0,
            ];
        }

        $scored = cotswold_calculate_event_scores($entries, $cut_off_ms);
        foreach ($teams as $club_id => $team) {
            $score = $team['is_absent']
                ? ['points' => 0, 'place' => null, 'status' => 'pending']
                : $scored[$club_id];
            $points = (int)$score['points'];
            $place = $score['place'] !== null ? (int)$score['place'] : null;
            $status = $score['status'];
            $update->bind_param('iisiii', $points, $place, $status, $scoresheet_id, $event_id, $club_id);
            $update->execute();
        }
    }
    $update->close();

    $totals = [];
    $totals_stmt = $conn->prepare("SELECT gt.club_id,
               COALESCE(SUM(CASE WHEN gt.is_absent = 0 AND ge.id IS NOT NULL THEN gr.points ELSE 0 END), 0) AS total_points
        FROM gala_teams gt
        JOIN gala_scoresheets gs ON gs.id = gt.scoresheet_id
        LEFT JOIN gala_results gr ON gr.scoresheet_id = gt.scoresheet_id AND gr.club_id = gt.club_id
        LEFT JOIN gala_events ge ON ge.id = gr.event_id AND (gs.season_year = 9999 OR ge.season_year = gs.season_year)
        WHERE gt.scoresheet_id = ?
        GROUP BY gt.club_id
        ORDER BY gt.club_id ASC");
    $totals_stmt->bind_param('i', $scoresheet_id);
    $totals_stmt->execute();
    $totals_result = $totals_stmt->get_result();
    while ($total = $totals_result->fetch_assoc()) {
        $totals[(int)$total['club_id']] = (int)$total['total_points'];
    }
    $totals_stmt->close();

    $total_points_json = json_encode($totals, JSON_THROW_ON_ERROR);
    $total_stmt = $conn->prepare('UPDATE gala_scoresheets SET total_points_json = ?, updated_at = NOW() WHERE id = ?');
    $total_stmt->bind_param('si', $total_points_json, $scoresheet_id);
    $total_stmt->execute();
    $total_stmt->close();

    return ['total_points' => $totals, 'total_points_json' => $total_points_json];
}
