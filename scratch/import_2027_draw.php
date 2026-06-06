<?php
/**
 * Import the 2027 Cotswold League draw from the provided fixture workbook.
 *
 * Usage:
 *   php scratch/import_2027_draw.php --dry-run
 *   php scratch/import_2027_draw.php --apply
 *
 * The script is intentionally conservative: it validates all club names first
 * and will not create new clubs with guessed venue/contact details.
 */

require_once __DIR__ . '/../db.php';

$apply = in_array('--apply', $argv, true);
$dryRun = in_array('--dry-run', $argv, true) || !$apply;
$seasonYear = 2027;

$nameAliases = [
    'AST' => 'Academy Swim Team',
    'Burnham' => 'Burnham-On-Sea',
    'COB' => 'COB (City of Bristol)',
    'Forest Of Dean' => 'Forest of Dean',
    'Southwold' => 'Southwold SC',
    'Swindon' => 'Swindon ASC',
    'Severnside' => 'Severnside Tritons',
    'Monnow' => 'Monnow SC',
    'Chippenham' => 'Chippenham ASC',
    'Cheddar' => 'Cheddar Kingfishers',
];

$rounds = [
    [
        'round' => 1,
        'date' => '13/02/2027',
        'galas' => [
            ['host' => 'Burnham', 'teams' => ['Burnham', 'Backwell', 'Clevedon', 'Bristol North']],
            ['host' => 'Swindon', 'teams' => ['Swindon', 'AST', 'Dursley', 'Brockworth']],
            ['host' => 'Forest Of Dean', 'teams' => ['Forest Of Dean', 'Bridgwater', 'Severnside', 'Monnow']],
            ['host' => 'Bath Dolphin', 'teams' => ['Bath Dolphin', 'COB', 'Cwmbran', 'Chippenham']],
            ['host' => 'Southwold', 'teams' => ['Southwold', 'Yeovil', 'Corsham', 'Cheddar']],
        ],
    ],
    [
        'round' => 2,
        'date' => '06/03/2027',
        'galas' => [
            ['host' => 'Backwell', 'teams' => ['Backwell', 'Bath Dolphin', 'Dursley', 'Monnow']],
            ['host' => 'AST', 'teams' => ['AST', 'Southwold', 'Severnside', 'Chippenham']],
            ['host' => 'Bridgwater', 'teams' => ['Bridgwater', 'Burnham', 'Cwmbran', 'Cheddar']],
            ['host' => 'COB', 'teams' => ['COB', 'Swindon', 'Corsham', 'Bristol North']],
            ['host' => 'Yeovil', 'teams' => ['Yeovil', 'Forest Of Dean', 'Clevedon', 'Brockworth']],
        ],
    ],
    [
        'round' => 3,
        'date' => '03/04/2027',
        'galas' => [
            ['host' => 'Clevedon', 'teams' => ['Clevedon', 'Southwold', 'COB', 'Monnow']],
            ['host' => 'Dursley', 'teams' => ['Dursley', 'Burnham', 'Yeovil', 'Chippenham']],
            ['host' => 'Severnside', 'teams' => ['Severnside', 'Swindon', 'Backwell', 'Cheddar']],
            ['host' => 'Cwmbran', 'teams' => ['Cwmbran', 'Forest Of Dean', 'AST', 'Bristol North']],
            ['host' => 'Corsham', 'teams' => ['Corsham', 'Bath Dolphin', 'Bridgwater', 'Brockworth']],
        ],
    ],
    [
        'round' => 4,
        'date' => '22/05/2027',
        'galas' => [
            ['host' => 'Bristol North', 'teams' => ['Bristol North', 'Bath Dolphin', 'Yeovil', 'Severnside']],
            ['host' => 'Brockworth', 'teams' => ['Brockworth', 'Southwold', 'Backwell', 'Cwmbran']],
            ['host' => 'Monnow', 'teams' => ['Monnow', 'Burnham', 'AST', 'Corsham']],
            ['host' => 'Chippenham', 'teams' => ['Chippenham', 'Swindon', 'Bridgwater', 'Clevedon']],
            ['host' => 'Cheddar', 'teams' => ['Cheddar', 'Forest Of Dean', 'COB', 'Dursley']],
        ],
    ],
];

function canonical_club_name(string $name, array $aliases): string
{
    return $aliases[$name] ?? $name;
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $check = $conn->query("SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    if ($check && $check->num_rows === 0) {
        if (!$conn->query("ALTER TABLE `$tableEsc` ADD COLUMN $definition")) {
            throw new RuntimeException("Failed adding $table.$column: " . $conn->error);
        }
    }
}

ensure_column($conn, 'clubs', 'is_active', 'is_active TINYINT(1) NOT NULL DEFAULT 1');
ensure_column($conn, 'venue_details', 'season_year', 'season_year INT NOT NULL DEFAULT 2026');
ensure_column($conn, 'venue_details', 'gala_type', "gala_type ENUM('round','a_final','b_final','c_final') DEFAULT 'round'");
foreach (range(1, 8) as $lane) {
    ensure_column($conn, 'venue_details', "team_{$lane}_id", "team_{$lane}_id INT DEFAULT NULL");
}
ensure_column($conn, 'venue_details', 'round_date', 'round_date VARCHAR(50) DEFAULT NULL');
ensure_column($conn, 'venue_details', 'other_info', 'other_info TEXT DEFAULT NULL');
ensure_column($conn, 'results', 'season_year', 'season_year INT NOT NULL DEFAULT 2026');
ensure_column($conn, 'results', 'total', 'total INT DEFAULT 0');

$clubsByName = [];
$clubResult = $conn->query("SELECT id, name FROM clubs WHERE is_active = 1");
if (!$clubResult) {
    throw new RuntimeException('Could not load clubs: ' . $conn->error);
}
while ($club = $clubResult->fetch_assoc()) {
    $clubsByName[strtolower(trim($club['name']))] = ['id' => (int)$club['id'], 'name' => $club['name']];
}

$requiredNames = [];
foreach ($rounds as $round) {
    foreach ($round['galas'] as $gala) {
        $requiredNames[] = canonical_club_name($gala['host'], $nameAliases);
        foreach ($gala['teams'] as $team) {
            $requiredNames[] = canonical_club_name($team, $nameAliases);
        }
    }
}
$requiredNames = array_values(array_unique($requiredNames));
sort($requiredNames);

$missing = [];
foreach ($requiredNames as $name) {
    if (!isset($clubsByName[strtolower($name)])) {
        $missing[] = $name;
    }
}

if ($missing) {
    echo "Missing active club records; no import performed:\n";
    foreach ($missing as $name) {
        echo " - $name\n";
    }
    echo "\nPlease add/confirm these clubs first, then rerun this script.\n";
    exit(2);
}

echo ($dryRun ? "Dry run" : "Applying") . " 2027 draw import\n";
echo "Required clubs: " . count($requiredNames) . "\n";

if ($dryRun) {
    foreach ($rounds as $round) {
        echo "Round {$round['round']} ({$round['date']}): " . count($round['galas']) . " galas\n";
    }
    echo "No database changes made. Rerun with --apply to import.\n";
    exit(0);
}

$placeholderValues = [
    'venue_name' => 'TBC',
    'address' => '',
    'warmup_time' => 'Check with Host',
    'start_time' => 'Check with Host',
    'payment_info' => 'Check with Host',
    'parking_info' => 'Check Centre Info',
    'other_info' => '',
];

$conn->begin_transaction();

try {
    $settingKey = 'current_season_year';
    $settingValue = (string)$seasonYear;
    $settingsStmt = $conn->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $settingsStmt->bind_param('ss', $settingKey, $settingValue);
    $settingsStmt->execute();
    $settingsStmt->close();

    foreach ($requiredNames as $name) {
        $clubId = $clubsByName[strtolower($name)]['id'];
        $resultsStmt = $conn->prepare("INSERT INTO results (club_id, season_year, round_1, round_2, round_3, round_4, total)
            SELECT ?, ?, 0, 0, 0, 0, 0
            WHERE NOT EXISTS (SELECT 1 FROM results WHERE club_id = ? AND season_year = ?)");
        $resultsStmt->bind_param('iiii', $clubId, $seasonYear, $clubId, $seasonYear);
        $resultsStmt->execute();
        $resultsStmt->close();
    }

    $deleteStmt = $conn->prepare("DELETE FROM venue_details WHERE season_year = ? AND round_number BETWEEN 1 AND 4 AND gala_type = 'round'");
    $deleteStmt->bind_param('i', $seasonYear);
    $deleteStmt->execute();
    $deleteStmt->close();

    $insertStmt = $conn->prepare("INSERT INTO venue_details
        (club_id, round_number, venue_name, address, warmup_time, start_time, payment_info, parking_info, other_info,
         team_1_id, team_2_id, team_3_id, team_4_id, team_5_id, team_6_id, team_7_id, team_8_id, round_date, season_year, gala_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, 'round')");

    foreach ($rounds as $round) {
        foreach ($round['galas'] as $gala) {
            $hostName = canonical_club_name($gala['host'], $nameAliases);
            $hostId = $clubsByName[strtolower($hostName)]['id'];
            $teamIds = [];
            foreach ($gala['teams'] as $team) {
                $teamName = canonical_club_name($team, $nameAliases);
                $teamIds[] = $clubsByName[strtolower($teamName)]['id'];
            }

            $insertStmt->bind_param(
                'iisssssssiiiisi',
                $hostId,
                $round['round'],
                $placeholderValues['venue_name'],
                $placeholderValues['address'],
                $placeholderValues['warmup_time'],
                $placeholderValues['start_time'],
                $placeholderValues['payment_info'],
                $placeholderValues['parking_info'],
                $placeholderValues['other_info'],
                $teamIds[0],
                $teamIds[1],
                $teamIds[2],
                $teamIds[3],
                $round['date'],
                $seasonYear
            );
            $insertStmt->execute();
        }
    }
    $insertStmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}

$countResult = $conn->query("SELECT round_number, COUNT(*) AS gala_count FROM venue_details WHERE season_year = $seasonYear AND round_number BETWEEN 1 AND 4 GROUP BY round_number ORDER BY round_number");
while ($row = $countResult->fetch_assoc()) {
    echo "Round {$row['round_number']}: {$row['gala_count']} galas imported\n";
}
echo "Import complete.\n";
