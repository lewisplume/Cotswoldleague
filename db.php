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

// Create settings table
$conn->query("CREATE TABLE IF NOT EXISTS global_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Initialize default season if not exists
$conn->query("INSERT IGNORE INTO global_settings (setting_key, setting_value) VALUES ('current_season_year', '2026')");

// Fetch current season dynamically
$season_query = $conn->query("SELECT setting_value FROM global_settings WHERE setting_key = 'current_season_year'");
if ($season_query && $row = $season_query->fetch_assoc()) {
    $current_season_year = (int)$row['setting_value'];
} else {
    $current_season_year = 2026;
}

// Venue Details Updates for Season & Finals (8 Lanes)
try { $conn->query("ALTER TABLE venue_details ADD COLUMN season_year INT NOT NULL DEFAULT 2026"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN gala_type ENUM('round','a_final','b_final','c_final') DEFAULT 'round'"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN team_5_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN team_6_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN team_7_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN team_8_id INT DEFAULT NULL"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE venue_details ADD COLUMN round_date VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}

// Results Table Updates for Season Tracking
try { $conn->query("ALTER TABLE results ADD COLUMN season_year INT NOT NULL DEFAULT 2026"); } catch (Exception $e) {}
try { $conn->query("ALTER TABLE results ADD COLUMN total INT DEFAULT 0"); } catch (Exception $e) {}


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

// =====================================================
// DIGITAL TEAMSHEETS - Parallel beta module
// =====================================================

$conn->query("CREATE TABLE IF NOT EXISTS club_swimmers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    season_year INT NOT NULL,
    swimmer_name VARCHAR(120) NOT NULL,
    age_group VARCHAR(20) DEFAULT '',
    pb_free_25 VARCHAR(20) DEFAULT '',
    pb_back_25 VARCHAR(20) DEFAULT '',
    pb_breast_25 VARCHAR(20) DEFAULT '',
    pb_fly_25 VARCHAR(20) DEFAULT '',
    pb_free_50 VARCHAR(20) DEFAULT '',
    pb_back_50 VARCHAR(20) DEFAULT '',
    pb_breast_50 VARCHAR(20) DEFAULT '',
    pb_fly_50 VARCHAR(20) DEFAULT '',
    pb_im VARCHAR(20) DEFAULT '',
    pb_free_100 VARCHAR(20) DEFAULT '',
    pb_back_100 VARCHAR(20) DEFAULT '',
    pb_breast_100 VARCHAR(20) DEFAULT '',
    pb_fly_100 VARCHAR(20) DEFAULT '',
    availability_json TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_swimmer_club_season_name (club_id, season_year, swimmer_name),
    INDEX idx_swimmer_club_season (club_id, season_year),
    FOREIGN KEY (club_id) REFERENCES clubs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS club_teamsheets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    season_year INT NOT NULL,
    round_key VARCHAR(20) NOT NULL,
    gala_type ENUM('round','b_final','c_final','a_final') DEFAULT 'round',
    venue_detail_id INT DEFAULT NULL,
    status ENUM('draft','submitted') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    submitted_by VARCHAR(120) DEFAULT NULL,
    last_reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_teamsheet_club_round (club_id, season_year, round_key),
    INDEX idx_teamsheet_round (season_year, round_key, venue_detail_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS club_teamsheet_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teamsheet_id INT NOT NULL,
    event_id INT NOT NULL,
    selected_swimmers_json TEXT DEFAULT NULL,
    pb_snapshot VARCHAR(255) DEFAULT '',
    notes VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_teamsheet_event (teamsheet_id, event_id),
    FOREIGN KEY (teamsheet_id) REFERENCES club_teamsheets(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES gala_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS club_teamsheet_audit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teamsheet_id INT NOT NULL,
    club_id INT NOT NULL,
    changed_by VARCHAR(120) DEFAULT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    change_summary TEXT DEFAULT NULL,
    snapshot_json MEDIUMTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teamsheet_audit (teamsheet_id, created_at),
    FOREIGN KEY (teamsheet_id) REFERENCES club_teamsheets(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id)
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
