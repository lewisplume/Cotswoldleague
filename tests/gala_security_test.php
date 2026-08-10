<?php

require_once __DIR__ . '/../gala_access.php';
require_once __DIR__ . '/../gala_scoring.php';

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assert_true(bool $condition, string $message): void
{
    assert_same(true, $condition, $message);
}

function assert_false(bool $condition, string $message): void
{
    assert_same(false, $condition, $message);
}

// Preliminary rounds: only the venue host records the gala.
$round = ['round_number' => 1, 'gala_type' => 'round', 'club_id' => 10];
assert_true(cotswold_user_can_access_scoresheet_venue($round, false, 10), 'Preliminary host should have access.');
assert_false(cotswold_user_can_access_scoresheet_venue($round, false, 11), 'A participating non-host club must not have access.');
assert_true(cotswold_user_can_access_scoresheet_venue($round, true, 0), 'A league administrator should retain access.');
$linked_round = ['round_number' => 1, 'gala_type' => 'round', 'host_club_id' => 99, 'venue_host_club_id' => 10];
assert_true(cotswold_user_can_access_scoresheet_venue($linked_round, false, 10), 'The current venue host should own a linked scoresheet.');
assert_false(cotswold_user_can_access_scoresheet_venue($linked_round, false, 99), 'A stale scoresheet host must not override the current venue assignment.');

// Finals: the explicit scoresheet assignment overrides the venue host.
$final = [
    'round_number' => 99,
    'gala_type' => 'a_final',
    'club_id' => 10,
    'final_scoresheet_club_id' => 12,
];
assert_true(cotswold_user_can_access_scoresheet_venue($final, false, 12), 'Assigned finals recorder should have access.');
assert_false(cotswold_user_can_access_scoresheet_venue($final, false, 10), 'Finals venue host should not gain access without the recorder assignment.');
$unassigned_final = ['round_number' => 99, 'gala_type' => 'b_final', 'club_id' => 10];
assert_false(cotswold_user_can_access_scoresheet_venue($unassigned_final, false, 10), 'An unassigned final must fail closed.');

// Venue-less legacy/sandbox rows use their stored host.
$standalone = ['round_number' => 0, 'gala_type' => 'round', 'host_club_id' => 20];
assert_true(cotswold_user_can_access_scoresheet_venue($standalone, false, 20), 'Stored host should own a venue-less scoresheet.');
assert_false(cotswold_user_can_access_scoresheet_venue($standalone, false, 21), 'Other clubs must not own a venue-less scoresheet.');

// The PHP scoring result must match the established browser/Excel algorithm.
$scores = cotswold_calculate_event_scores([
    ['club_id' => 1, 'time_ms' => null, 'is_dq' => 1],
    ['club_id' => 2, 'time_ms' => 59000, 'is_dq' => 0],
    ['club_id' => 3, 'time_ms' => 62000, 'is_dq' => 0],
    ['club_id' => 4, 'time_ms' => 65000, 'is_dq' => 0],
], 60000);
assert_same(['points' => 0, 'place' => null, 'status' => 'dq'], array_intersect_key($scores[1], array_flip(['points', 'place', 'status'])), 'DQ classification should score zero.');
assert_same(['points' => 0, 'place' => null, 'status' => 'too_fast'], array_intersect_key($scores[2], array_flip(['points', 'place', 'status'])), 'Too-fast classification should score zero.');
assert_same(['points' => 4, 'place' => 1, 'status' => 'valid'], array_intersect_key($scores[3], array_flip(['points', 'place', 'status'])), 'Fastest valid time should receive the top score.');
assert_same(['points' => 3, 'place' => 2, 'status' => 'valid'], array_intersect_key($scores[4], array_flip(['points', 'place', 'status'])), 'Second valid time should receive the next score.');

$dead_heat = cotswold_calculate_event_scores([
    ['club_id' => 3, 'time_ms' => 62000, 'is_dq' => 0],
    ['club_id' => 4, 'time_ms' => 62000, 'is_dq' => 0],
], 60000);
assert_same(1, $dead_heat[3]['place'], 'First dead-heat swimmer should place first.');
assert_same(1, $dead_heat[4]['place'], 'Equal times should share a place.');
assert_same(2, $dead_heat[3]['points'], 'Dead-heat points should mirror the established algorithm.');
assert_same(2, $dead_heat[4]['points'], 'Dead-heat points should be equal.');

// Lightweight source contracts guard the critical trust boundaries without a DB.
$api = file_get_contents(__DIR__ . '/../gala_scoresheet_api.php');
$swimmer_api = file_get_contents(__DIR__ . '/../digital_teamsheet_api.php');
$admin = file_get_contents(__DIR__ . '/../league_admin.php');
$team_portal = file_get_contents(__DIR__ . '/../teamportal.php');
$clubs_page = file_get_contents(__DIR__ . '/../clubs.php');
$htaccess = file_get_contents(__DIR__ . '/../.htaccess');
assert_true(strpos($api, "require_once __DIR__ . '/gala_scoring.php'") !== false, 'Scoresheet API must load server scoring.');
assert_false(strpos($api, 'points = VALUES(points)') !== false, 'Scoresheet API must not persist client-derived points.');
assert_false(strpos($api, "\$_POST['points']") !== false, 'Scoresheet API must not read client-derived points.');
assert_false(strpos($api, "\$total_points_json = \$_POST['total_points_json']") !== false, 'Submission must not trust client totals.');
assert_true(strpos($api, '$venue_detail_id <= 0 && !$is_super_admin') !== false, 'Club scoresheets must be created from an assigned venue.');
assert_true(strpos($api, "['draft', 'in_progress', 'submitted']") !== false, 'Verified and published scoresheets must be locked against edits.');
assert_true(strpos($swimmer_api, 'WHERE id = ? AND club_id = ? AND season_year = ?') !== false, 'Swimmer IDs must be checked against club ownership.');
assert_true(strpos($admin, 'bind_param("sssssddi"') !== false, 'Club update binding signature must match its eight values.');
assert_true(strpos($admin, 'bind_param("sssssissii"') !== false, 'Event update binding signature must preserve string and integer fields.');
assert_true(strpos($admin, 'bind_param("isssssissii"') !== false, 'Event insert binding signature must preserve string and integer fields.');
assert_true(strpos($admin, 'session_regenerate_id(true)') !== false, 'Administrator login must rotate the session ID.');
assert_true(strpos($team_portal, 'session_regenerate_id(true)') !== false, 'Club login must rotate the session ID.');
assert_true(strpos($clubs_page, 'JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT') !== false, 'Club map data must be safely encoded for JavaScript.');
assert_true(strpos($clubs_page, "in_array(\$website_scheme, ['http', 'https'], true)") !== false, 'Club website links must use an allowed scheme.');
assert_true(strpos($htaccess, 'db_backups|_legacy|scratch|scripts|Documents') !== false, 'Server-only directories must be denied.');

echo "PASS: gala authorization, server scoring, and critical source contracts\n";
