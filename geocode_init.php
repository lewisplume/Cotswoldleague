<?php
// Include this file to automatically initialize and geocode club coordinates.

// 1. Check if latitude column exists
$check_col = $conn->query("SHOW COLUMNS FROM clubs LIKE 'latitude'");
if ($check_col->num_rows == 0) {
    // Add columns
    $conn->query("ALTER TABLE clubs ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL");
    $conn->query("ALTER TABLE clubs ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL");
}

// 2. Fetch clubs that need geocoding
$sql = "SELECT id, postcode FROM clubs WHERE latitude IS NULL OR longitude IS NULL";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $postcode = trim($row['postcode']);
        
        // Postcodes.io API
        $url = 'https://api.postcodes.io/postcodes/' . urlencode($postcode);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Just in case of local cert issues
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode == 200) {
            $data = json_decode($response, true);
            if (isset($data['result']['latitude']) && isset($data['result']['longitude'])) {
                $lat = $data['result']['latitude'];
                $lng = $data['result']['longitude'];
                
                $update_stmt = $conn->prepare("UPDATE clubs SET latitude=?, longitude=? WHERE id=?");
                $update_stmt->bind_param("ddi", $lat, $lng, $id);
                $update_stmt->execute();
            }
        } else {
            // Fallback for API fail
            $nominatim_url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($postcode . ', UK');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $nominatim_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'CotswoldLeagueMap/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $nom_res = curl_exec($ch);
            curl_close($ch);
            
            $nom_data = json_decode($nom_res, true);
            if (is_array($nom_data) && count($nom_data) > 0) {
                $lat = $nom_data[0]['lat'];
                $lng = $nom_data[0]['lon'];
                $update_stmt = $conn->prepare("UPDATE clubs SET latitude=?, longitude=? WHERE id=?");
                $update_stmt->bind_param("ddi", $lat, $lng, $id);
                $update_stmt->execute();
            }
        }
        // Be nice to API
        usleep(100000); 
    }
}
?>
