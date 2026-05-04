<?php
$servername = "localhost";
$username = "root"; // Default XAMPP user
$password = ""; // Default XAMPP password (empty)
$dbname = "cotswold_league";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add results_file column if it doesn't exist
try {
    $conn->query("ALTER TABLE venue_details ADD COLUMN results_file VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // Column likely already exists
}

// Add teamsheet_link column if it doesn't exist
try {
    $conn->query("ALTER TABLE venue_details ADD COLUMN teamsheet_link VARCHAR(500) DEFAULT NULL");
} catch (Exception $e) {
    // Column likely already exists
}

// =====================================================
// GALA RESULTS PORTAL - Table Migrations
// =====================================================

// 1. gala_events — Master event definitions (admin-editable per season)
$conn->query("CREATE TABLE IF NOT EXISTS gala_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_number INT NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    distance VARCHAR(10) NOT NULL,
    age_group VARCHAR(10) NOT NULL,
    gender ENUM('Girls','Boys','Mixed') NOT NULL,
    event_type ENUM('Individual','Relay','Cannon') NOT NULL,
    cut_off_time_ms INT NOT NULL,
    a_final_event_name VARCHAR(100) DEFAULT NULL,
    a_final_distance VARCHAR(10) DEFAULT NULL,
    a_final_cut_off_time_ms INT DEFAULT NULL,
    season_year INT NOT NULL DEFAULT 2027,
    UNIQUE KEY uk_event_season (event_number, season_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. gala_scoresheets — One scoresheet per gala per venue
$conn->query("CREATE TABLE IF NOT EXISTS gala_scoresheets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_detail_id INT DEFAULT NULL,
    round_number INT NOT NULL,
    gala_type ENUM('round','c_final','b_final','a_final') DEFAULT 'round',
    host_club_id INT NOT NULL,
    gala_date DATE DEFAULT NULL,
    team_count INT NOT NULL DEFAULT 4,
    status ENUM('draft','in_progress','submitted','verified','published') DEFAULT 'draft',
    recorder_club_id INT DEFAULT NULL,
    recorder_name VARCHAR(100) DEFAULT NULL,
    total_points_json TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    verified_by VARCHAR(100) DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    season_year INT NOT NULL DEFAULT 2027,
    FOREIGN KEY (host_club_id) REFERENCES clubs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 3. gala_teams — Teams participating in a specific gala scoresheet
$conn->query("CREATE TABLE IF NOT EXISTS gala_teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scoresheet_id INT NOT NULL,
    club_id INT NOT NULL,
    lane_number INT DEFAULT NULL,
    is_absent TINYINT(1) DEFAULT 0,
    FOREIGN KEY (scoresheet_id) REFERENCES gala_scoresheets(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id),
    UNIQUE KEY uk_scoresheet_club (scoresheet_id, club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 4. gala_results — Individual event results per team per event
$conn->query("CREATE TABLE IF NOT EXISTS gala_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scoresheet_id INT NOT NULL,
    event_id INT NOT NULL,
    club_id INT NOT NULL,
    time_ms INT DEFAULT NULL,
    is_dq TINYINT(1) DEFAULT 0,
    dq_reason VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    points INT DEFAULT 0,
    place INT DEFAULT NULL,
    status ENUM('pending','valid','dq','too_fast') DEFAULT 'pending',
    source_type ENUM('live','imported') DEFAULT 'live',
    source_scoresheet_id INT DEFAULT NULL,
    imported_by VARCHAR(100) DEFAULT NULL,
    imported_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (scoresheet_id) REFERENCES gala_scoresheets(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES gala_events(id),
    FOREIGN KEY (club_id) REFERENCES clubs(id),
    UNIQUE KEY uk_scoresheet_event_club (scoresheet_id, event_id, club_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-seed events if the table is empty
$event_check = $conn->query("SELECT COUNT(*) as cnt FROM gala_events");
if ($event_check) {
    $row = $event_check->fetch_assoc();
    if ((int)$row['cnt'] === 0) {
        include_once __DIR__ . '/gala_seed_events.php';
    }
}

define('LEAGUE_PASSWORD', 'Cotswold2026Galas');
define('SUPER_ADMIN_PASSWORD', 'SuperAdmin2026!');
?>