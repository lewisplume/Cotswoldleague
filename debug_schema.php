<?php
include 'db.php';

$table = 'audit_log';
$sql = "SHOW COLUMNS FROM $table";
$result = $conn->query($sql);

if ($result) {
    echo "<h1>Columns in $table</h1>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $key => $val) {
            echo "<td>$val</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}
?>
