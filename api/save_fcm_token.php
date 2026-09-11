<?php
// api/save_fcm_token.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$user_id = get_current_user_id();
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;
$fcm_token = trim($input['fcm_token'] ?? '');

if (empty($fcm_token)) {
    json_response(['error' => 'FCM token is required'], 400);
}

try {
    $stmt = $pdo->prepare("UPDATE users SET fcm_token = :token WHERE id = :u");
    $stmt->execute([':token' => $fcm_token, ':u' => $user_id]);

    json_response([
        'success' => true,
        'message' => 'FCM push token saved successfully'
    ]);
} catch (PDOException $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
