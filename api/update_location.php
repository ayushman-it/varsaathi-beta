<?php
// api/update_location.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$user_id = get_current_user_id();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$city = trim($input['city'] ?? '');
$latitude = isset($input['latitude']) ? (float)$input['latitude'] : null;
$longitude = isset($input['longitude']) ? (float)$input['longitude'] : null;

// Reverse geocode city from lat/lng if city name is empty
if (empty($city) && $latitude !== null && $longitude !== null) {
    try {
        $opts = [
            'http' => [
                'header' => "User-Agent: VarsaathiDatingApp/1.0\r\n",
                'timeout' => 5
            ]
        ];
        $context = stream_context_create($opts);
        $geo_url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}";
        $geo_json = @file_get_contents($geo_url, false, $context);
        if ($geo_json) {
            $geo_data = json_decode($geo_json, true);
            $city = $geo_data['address']['city'] ?? $geo_data['address']['town'] ?? $geo_data['address']['suburb'] ?? $geo_data['address']['county'] ?? 'Nearby City';
        }
    } catch (Exception $ex) {
        $city = 'Nearby City';
    }
}

if (empty($city)) {
    $city = 'Location Set';
}

try {
    if ($latitude !== null && $longitude !== null) {
        $stmt = $pdo->prepare("UPDATE users SET location_city = :city, latitude = :lat, longitude = :lng WHERE id = :u");
        $stmt->execute([':city' => $city, ':lat' => $latitude, ':lng' => $longitude, ':u' => $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET location_city = :city WHERE id = :u");
        $stmt->execute([':city' => $city, ':u' => $user_id]);
    }

    json_response([
        'success' => true,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'message' => 'Real GPS location updated successfully!'
    ]);
} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
