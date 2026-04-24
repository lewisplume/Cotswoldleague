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

define('LEAGUE_PASSWORD', 'Cotswold2026Galas');
define('SUPER_ADMIN_PASSWORD', 'SuperAdmin2026!');
?>