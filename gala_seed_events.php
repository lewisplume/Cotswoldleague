<?php
/**
 * Gala Results Portal — Event Seed Data
 * 
 * Seeds all 53 events for the 2027 season into gala_events.
 * This file is auto-included by db.php when the gala_events table is empty.
 * Cut-off times are stored in milliseconds.
 * 
 * 8 events have A Final overrides where 11/u distances change from 25m → 50m.
 */

// Ensure $conn is available (this file is included from db.php)
if (!isset($conn)) {
    die('gala_seed_events.php must be included from db.php');
}

$events = [
    // Events 1-10 (Block 1)
    [1,  'Girls 15/u 4x1 Ind. Medley',       '100m', '15/u',  'Girls', 'Individual', 77750, null, null, null],
    [2,  'Boys 15/u 4x1 Ind. Medley',        '100m', '15/u',  'Boys',  'Individual', 70390, null, null, null],
    [3,  'Girls Open 4x1 Ind. Medley',        '100m', 'Open',  'Girls', 'Individual', 75510, null, null, null],
    [4,  'Boys Open 4x1 Ind. Medley',         '100m', 'Open',  'Boys',  'Individual', 66340, null, null, null],
    [5,  'Girls 11/u 25m Freestyle',           '25m',  '11/u',  'Girls', 'Individual', 14780, 'Girls 11/u 50m Freestyle', '50m', 32290],
    [6,  'Boys 11/u 25m Freestyle',            '25m',  '11/u',  'Boys',  'Individual', 14810, 'Boys 11/u 50m Freestyle',  '50m', 31800],
    [7,  'Girls 13/u 50m Breaststroke',        '50m',  '13/u',  'Girls', 'Individual', 41700, null, null, null],
    [8,  'Boys 13/u 50m Breaststroke',         '50m',  '13/u',  'Boys',  'Individual', 41800, null, null, null],
    [9,  'Girls 15/u 50m Backstroke',          '50m',  '15/u',  'Girls', 'Individual', 33800, null, null, null],
    [10, 'Boys 15/u 50m Backstroke',           '50m',  '15/u',  'Boys',  'Individual', 33100, null, null, null],

    // Events 11-20 (Block 2)
    [11, 'Girls Open 100m Butterfly',          '100m', 'Open',  'Girls', 'Individual', 71700, null, null, null],
    [12, 'Boys Open 100m Butterfly',           '100m', 'Open',  'Boys',  'Individual', 61900, null, null, null],
    [13, 'Girls 11/u 4x1 Medley team',        '100m', '11/u',  'Girls', 'Relay',      69330, null, null, null],
    [14, 'Boys 11/u 4x1 Medley team',         '100m', '11/u',  'Boys',  'Relay',      68690, null, null, null],
    [15, 'Girls 13/u 4x1 Freestyle team',     '100m', '13/u',  'Girls', 'Relay',      57670, null, null, null],
    [16, 'Boys 13/u 4x1 Freestyle team',      '100m', '13/u',  'Boys',  'Relay',      55070, null, null, null],
    [17, 'Girls 15/u 50m Breaststroke',        '50m',  '15/u',  'Girls', 'Individual', 38500, null, null, null],
    [18, 'Boys 15/u 50m Breaststroke',         '50m',  '15/u',  'Boys',  'Individual', 37400, null, null, null],
    [19, 'Girls Open 100m Backstroke',         '100m', 'Open',  'Girls', 'Individual', 69500, null, null, null],
    [20, 'Boys Open 100m Backstroke',          '100m', 'Open',  'Boys',  'Individual', 63400, null, null, null],

    // Events 21-30 (Block 3)
    [21, 'Girls 11/u 25m Butterfly',           '25m',  '11/u',  'Girls', 'Individual', 16160, 'Girls 11/u 50m Butterfly',   '50m', 37000],
    [22, 'Boys 11/u 25m Butterfly',            '25m',  '11/u',  'Boys',  'Individual', 16390, 'Boys 11/u 50m Butterfly',    '50m', 38000],
    [23, 'Girls 13/u 50m Freestyle',           '50m',  '13/u',  'Girls', 'Individual', 30500, null, null, null],
    [24, 'Boys 13/u 50m Freestyle',            '50m',  '13/u',  'Boys',  'Individual', 30600, null, null, null],
    [25, 'Girls 15/u 4x2 Medley team',        '200m', '15/u',  'Girls', 'Relay',     132900, null, null, null],
    [26, 'Boys 15/u 4x2 Medley team',         '200m', '15/u',  'Boys',  'Relay',     122600, null, null, null],
    [27, 'Girls Open 4x2 Medley team',        '200m', 'Open',  'Girls', 'Relay',     131070, null, null, null],
    [28, 'Boys Open 4x2 Medley team',         '200m', 'Open',  'Boys',  'Relay',     119440, null, null, null],
    [29, 'Girls 11/u 25m Backstroke',          '25m',  '11/u',  'Girls', 'Individual', 18120, 'Girls 11/u 50m Backstroke',  '50m', 37000],
    [30, 'Boys 11/u 25m Backstroke',           '25m',  '11/u',  'Boys',  'Individual', 18220, 'Boys 11/u 50m Backstroke',   '50m', 37500],

    // Events 31-40 (Block 4)
    [31, 'Girls 13/u 50m Butterfly',           '50m',  '13/u',  'Girls', 'Individual', 35000, null, null, null],
    [32, 'Boys 13/u 50m Butterfly',            '50m',  '13/u',  'Boys',  'Individual', 35500, null, null, null],
    [33, 'Girls 15/u 50m Freestyle',           '50m',  '15/u',  'Girls', 'Individual', 29300, null, null, null],
    [34, 'Boys 15/u 50m Freestyle',            '50m',  '15/u',  'Boys',  'Individual', 28100, null, null, null],
    [35, 'Girls Open 100m Breaststroke',       '100m', 'Open',  'Girls', 'Individual', 82100, null, null, null],
    [36, 'Boys Open 100m Breaststoke',         '100m', 'Open',  'Boys',  'Individual', 73900, null, null, null],
    [37, 'Girls 11/u 4x1 Freestyle team',     '100m', '11/u',  'Girls', 'Relay',      59120, null, null, null],
    [38, 'Boys 11/u 4x1 Freestyle team',      '100m', '11/u',  'Boys',  'Relay',      59240, null, null, null],
    [39, 'Girls 13/u 4x1 Medley team',        '100m', '13/u',  'Girls', 'Relay',      64600, null, null, null],
    [40, 'Boys 13/u 4x1 Medley team',         '100m', '13/u',  'Boys',  'Relay',      62140, null, null, null],

    // Events 41-53 (Block 5 — Final stretch + Cannon)
    [41, 'Girls 15/u 50m Butterfly',           '50m',  '15/u',  'Girls', 'Individual', 32500, null, null, null],
    [42, 'Boys 15/u 50m Butterfly',            '50m',  '15/u',  'Boys',  'Individual', 31500, null, null, null],
    [43, 'Girls Open 100m Freestyle',          '100m', 'Open',  'Girls', 'Individual', 62100, null, null, null],
    [44, 'Boys Open 100m Freestyle',           '100m', 'Open',  'Boys',  'Individual', 55300, null, null, null],
    [45, 'Girls 11/u 25m Breaststroke',        '25m',  '11/u',  'Girls', 'Individual', 20270, 'Girls 11/u 50m Breaststroke', '50m', 43300],
    [46, 'Boys 11/u 25m Breaststroke',         '25m',  '11/u',  'Boys',  'Individual', 19270, 'Boys 11/u 50m Breaststroke',  '50m', 44600],
    [47, 'Girls 13/u 50m Backstroke',          '50m',  '13/u',  'Girls', 'Individual', 36600, null, null, null],
    [48, 'Boys 13/u 50m Backstroke',           '50m',  '13/u',  'Boys',  'Individual', 36000, null, null, null],
    [49, 'Girls 15/u 4x2 Freestyle team',     '200m', '15/u',  'Girls', 'Relay',     118800, null, null, null],
    [50, 'Boys 15/u 4x2 Freestyle team',      '200m', '15/u',  'Boys',  'Relay',     109200, null, null, null],
    [51, 'Girls Open 4x2 Freestyle team',     '200m', 'Open',  'Girls', 'Relay',     117210, null, null, null],
    [52, 'Boys Open 4x2 Freestyle team',      '200m', 'Open',  'Boys',  'Relay',     106980, null, null, null],
    [53, 'Cannon 8x1 Freestyle team',         '200m', 'Mixed', 'Mixed', 'Cannon',     59000, null, null, null],
];

$stmt = $conn->prepare("INSERT INTO gala_events 
    (event_number, event_name, distance, age_group, gender, event_type, cut_off_time_ms, 
     a_final_event_name, a_final_distance, a_final_cut_off_time_ms, season_year) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2027)");

foreach ($events as $e) {
    $stmt->bind_param(
        "isssssissi",
        $e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6],
        $e[7], $e[8], $e[9]
    );
    $stmt->execute();
}
$stmt->close();
