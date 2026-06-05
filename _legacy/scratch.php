<?php
include 'db.php';
$tables = ['clubs', 'club_contacts', 'venue_details'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        echo implode(" | ", $row) . "\n";
    }
}
