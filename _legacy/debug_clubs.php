<?php
include 'db.php';

echo "<h1>Club Name Comparison</h1>";

// Get Clubs from `clubs` table
$clubs = [];
$c_res = $conn->query("SELECT name FROM clubs ORDER BY name ASC");
while($r = $c_res->fetch_assoc()) $clubs[] = $r['name'];

// Get Host Clubs from `venue_details`
$hosts = [];
$h_res = $conn->query("SELECT DISTINCT c.name AS host_club_name FROM venue_details vd JOIN clubs c ON vd.club_id = c.id ORDER BY c.name ASC");
while($r = $h_res->fetch_assoc()) $hosts[] = $r['host_club_name'];

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
