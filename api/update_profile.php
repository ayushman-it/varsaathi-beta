<?php
// api/update_profile.php
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json');

$current_user_id = get_current_user_id();

$full_name = trim($_POST['full_name'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '2000-01-01');
$occupation = trim($_POST['occupation'] ?? '');
$location_city = trim($_POST['location_city'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$interests_raw = $_POST['interests'] ?? '[]';

if (is_array($interests_raw)) {
    $interests_array = array_values(array_unique(array_filter(array_map('trim', $interests_raw))));
} else {
    $decoded = json_decode($interests_raw, true);
    $interests_array = is_array($decoded) ? array_values(array_unique(array_filter(array_map('trim', $decoded)))) : [];
}

if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Full name cannot be empty']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET full_name = :full_name,
            birthdate = :birthdate,
            occupation = :occupation,
            location_city = :city,
            bio = :bio,
            interests = :interests
        WHERE id = :u
    ");
    $stmt->execute([
        ':full_name' => $full_name,
        ':birthdate' => $birthdate,
        ':occupation' => $occupation,
        ':city' => $location_city,
        ':bio' => $bio,
        ':interests' => json_encode($interests_array, JSON_UNESCAPED_UNICODE),
        ':u' => $current_user_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'full_name' => $full_name,
            'occupation' => $occupation,
            'location_city' => $location_city,
            'bio' => $bio,
            'interests' => $interests_array
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
}
