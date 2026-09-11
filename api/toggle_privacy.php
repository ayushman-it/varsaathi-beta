<?php
// api/toggle_privacy.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$current_user_id = get_current_user_id();

// Read input
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$is_private = isset($input['is_private']) ? (int)$input['is_private'] : null;

if ($is_private === null) {
    // Toggle current state if not specified
    $stmt = $pdo->prepare("SELECT is_private FROM users WHERE id = :u");
    $stmt->execute([':u' => $current_user_id]);
    $curr = (int)$stmt->fetchColumn();
    $is_private = ($curr === 1) ? 0 : 1;
} else {
    $is_private = ($is_private ? 1 : 0);
}

$update = $pdo->prepare("UPDATE users SET is_private = :p WHERE id = :u");
$update->execute([':p' => $is_private, ':u' => $current_user_id]);

json_response([
    'success' => true,
    'is_private' => (bool)$is_private,
    'message' => $is_private ? 'Profile is now Private 🔒' : 'Profile is now Public 🔓'
]);
