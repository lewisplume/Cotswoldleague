<?php
include 'db.php';

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS tracking_stats (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action_name VARCHAR(50) NOT NULL UNIQUE,
    count INT(10) UNSIGNED DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table tracking_stats created successfully<br>";
    
    // Insert initial rows if they don't exist
    $actions = ['programme_generated', 'report_generated'];
    
    foreach ($actions as $action) {
        $check = "SELECT * FROM tracking_stats WHERE action_name = '$action'";
        $result = $conn->query($check);
        
        if ($result->num_rows == 0) {
            $insert = "INSERT INTO tracking_stats (action_name, count) VALUES ('$action', 0)";
            if ($conn->query($insert) === TRUE) {
                echo "Inserted initial record for $action<br>";
            } else {
                echo "Error inserting $action: " . $conn->error . "<br>";
            }
        } else {
             echo "Record for $action already exists<br>";
        }
    }
    
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
