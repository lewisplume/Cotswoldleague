<?php
include 'db.php';
$sql = "SELECT name FROM clubs ORDER BY name ASC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    echo $row['name'] . "\n";
}
?>