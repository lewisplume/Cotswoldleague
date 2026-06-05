<?php
function cotswold_format_ms_time($ms)
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

function cotswold_document_event_label($event, $gala_type = 'round')
{
    if ($gala_type === 'a_final' && !empty($event['a_final_event_name'])) {
        return $event['a_final_event_name'];
    }

    return $event['event_name'];
}

function cotswold_document_event_cut_off($event, $gala_type = 'round')
{
    if ($gala_type === 'a_final' && !empty($event['a_final_cut_off_time_ms'])) {
        return cotswold_format_ms_time($event['a_final_cut_off_time_ms']);
    }

    return cotswold_format_ms_time($event['cut_off_time_ms']);
}

function cotswold_document_event_detail($event_name, $gender, $age_group)
{
    $detail = (string)$event_name;
    $prefix = trim((string)$gender . ' ' . (string)$age_group);

    if ($prefix !== '' && stripos($detail, $prefix) === 0) {
        $detail = trim(substr($detail, strlen($prefix)));
    }

    return $detail !== '' ? $detail : $event_name;
}

function cotswold_load_document_events($conn, $season_year)
{
    $events = [];
    $stmt = $conn->prepare("SELECT event_number, event_name, distance, age_group, gender, event_type, cut_off_time_ms, a_final_event_name, a_final_distance, a_final_cut_off_time_ms FROM gala_events WHERE season_year = ? ORDER BY event_number ASC");
    if (!$stmt) {
        return $events;
    }

    $season_year = (int)$season_year;
    $stmt->bind_param("i", $season_year);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $round_name = cotswold_document_event_label($row, 'round');
        $a_final_name = cotswold_document_event_label($row, 'a_final');
        $gender = $row['gender'];
        $age_group = $row['age_group'];

        $events[] = [
            'id' => (int)$row['event_number'],
            'event_number' => (int)$row['event_number'],
            'age' => $gender === 'Mixed' ? 'Mixed' : trim($gender . ' ' . $age_group),
            'round_desc' => $round_name,
            'a_final_desc' => $a_final_name,
            'round_detail' => cotswold_document_event_detail($round_name, $gender, $age_group),
            'a_final_detail' => cotswold_document_event_detail($a_final_name, $gender, $age_group),
            'round_limit' => cotswold_document_event_cut_off($row, 'round'),
            'a_final_limit' => cotswold_document_event_cut_off($row, 'a_final'),
        ];
    }

    $stmt->close();

    return $events;
}
