<?php
include 'db.php';

echo "<h1>Club Name Comparison</h1>";

// Get Clubs from `clubs` table
$clubs = [];
$c_res = $conn->query("SELECT name FROM clubs ORDER BY name ASC");
while($r = $c_res->fetch_assoc()) $clubs[] = $r['name'];

// Get Host Clubs from `venue_details`
$hosts = [];
$h_res = $conn->query("SELECT DISTINCT host_club FROM venue_details ORDER BY host_club ASC");
while($r = $h_res->fetch_assoc()) $hosts[] = $r['host_club'];

echo "<h2>Clubs Table (" . count($clubs) . ")</h2><ul>";
foreach($clubs as $c) echo "<li>$c</li>";
echo "</ul>";

echo "<h2>Venue Details Hosts (" . count($hosts) . ")</h2><ul>";
foreach($hosts as $h) echo "<li>$h</li>";
echo "</ul>";

echo "<h2>Mismatches (Hosts not in Clubs table)</h2><ul>";
foreach($hosts as $h) {
    if (!in_array($h, $clubs)) {
        echo "<li style='color:red'>$h</li>";
    }
}
echo "</ul>";
?>
