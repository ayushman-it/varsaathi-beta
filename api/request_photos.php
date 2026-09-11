<?php
// api/request_photos.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$current_user_id = get_current_user_id();

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$target_id = isset($input['target_id']) ? (int)$input['target_id'] : 0;

if (!$target_id || $target_id === $current_user_id) {
    json_response(['error' => 'Invalid target user'], 400);
}

// Get requesting user name
$u_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :u");
$u_stmt->execute([':u' => $current_user_id]);
$sender_name = $u_stmt->fetchColumn() ?: 'Someone';

// Check or create match
$u1 = min($current_user_id, $target_id);
$u2 = max($current_user_id, $target_id);

$check_m = $pdo->prepare("SELECT id, status FROM matches WHERE user1_id = :u1 AND user2_id = :u2");
$check_m->execute([':u1' => $u1, ':u2' => $u2]);
$match = $check_m->fetch();

if (!$match) {
    $ins = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, status, requested_by) VALUES (:u1, :u2, 'pending', :req)");
    $ins->execute([':u1' => $u1, ':u2' => $u2, ':req' => $current_user_id]);
    $match_id = (int)$pdo->lastInsertId();
} else {
    $match_id = (int)$match['id'];
}

// Insert notification message in messages if match exists
try {
    $msg_text = "📸 *Photo Access Request*: " . htmlspecialchars($sender_name) . " requested to view your private photos! Accept match or unlock your profile to share photos.";
    $ins_msg = $pdo->prepare("INSERT INTO messages (match_id, sender_id, receiver_id, message_text, is_read) VALUES (:mid, :sid, :rid, :txt, 0)");
    $ins_msg->execute([
        ':mid' => $match_id,
        ':sid' => $current_user_id,
        ':rid' => $target_id,
        ':txt' => $msg_text
    ]);
} catch (Exception $e) {}

// Send FCM Push Notification
try {
    require_once __DIR__ . '/../includes/fcm_helper.php';
    send_fcm_notification(
        $target_id,
        "Photo Access Requested! 📸",
        "$sender_name requested to view your private photo gallery.",
        ['type' => 'photo_request', 'match_id' => $match_id]
    );
} catch (Exception $e) {}

json_response([
    'success' => true,
    'message' => "Photo request sent to user! You'll be notified when they accept."
]);
