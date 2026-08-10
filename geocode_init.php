<?php

function cotswold_geocode_request(string $url, string $userAgent = 'CotswoldLeague/1.0'): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status !== 200) {
        return [null, $error !== '' ? $error : 'HTTP ' . $status];
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? [$decoded, null] : [null, 'Invalid JSON response'];
}

function cotswold_valid_coordinates($latitude, $longitude): bool
{
    return is_numeric($latitude) && is_numeric($longitude)
        && (float)$latitude >= 49.0 && (float)$latitude <= 61.0
        && (float)$longitude >= -9.0 && (float)$longitude <= 3.0;
}

function cotswold_geocode_postcode(string $postcode): array
{
    $postcode = strtoupper(trim($postcode));
    if (!preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}$/', $postcode)) {
        return [null, null, 'Invalid UK postcode'];
    }

    [$postcodesData, $postcodesError] = cotswold_geocode_request(
        'https://api.postcodes.io/postcodes/' . rawurlencode($postcode)
    );
    $latitude = $postcodesData['result']['latitude'] ?? null;
    $longitude = $postcodesData['result']['longitude'] ?? null;
    if (cotswold_valid_coordinates($latitude, $longitude)) {
        return [(float)$latitude, (float)$longitude, null];
    }

    [$nominatimData, $nominatimError] = cotswold_geocode_request(
        'https://nominatim.openstreetmap.org/search?format=json&countrycodes=gb&limit=1&q=' . rawurlencode($postcode),
        'CotswoldSwimmingLeague/1.0 (league website administrator)'
    );
    $latitude = $nominatimData[0]['lat'] ?? null;
    $longitude = $nominatimData[0]['lon'] ?? null;
    if (cotswold_valid_coordinates($latitude, $longitude)) {
        return [(float)$latitude, (float)$longitude, null];
    }

    return [null, null, $nominatimError ?? $postcodesError ?? 'No location returned'];
}

function cotswold_geocode_missing_clubs($conn): array
{
    $summary = ['updated' => 0, 'failed' => []];
    $result = $conn->query("SELECT id, name, postcode FROM clubs WHERE is_active = 1 AND (latitude IS NULL OR longitude IS NULL) ORDER BY id ASC");
    if (!$result) {
        throw new RuntimeException('Could not load clubs requiring geocoding.');
    }

    $update = $conn->prepare('UPDATE clubs SET latitude = ?, longitude = ? WHERE id = ?');
    while ($row = $result->fetch_assoc()) {
        [$latitude, $longitude, $error] = cotswold_geocode_postcode((string)$row['postcode']);
        if ($error !== null) {
            $summary['failed'][] = ['club' => (string)$row['name'], 'error' => $error];
            continue;
        }
        $clubId = (int)$row['id'];
        $update->bind_param('ddi', $latitude, $longitude, $clubId);
        if ($update->execute()) {
            $summary['updated']++;
        } else {
            $summary['failed'][] = ['club' => (string)$row['name'], 'error' => 'Database update failed'];
        }
        usleep(250000);
    }
    $update->close();
    return $summary;
}
