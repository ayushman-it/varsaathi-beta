<?php
// api/check_chat_status.php - Check if candidate chat is allowed or send pending request notice
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/flags.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$user_id = get_current_user_id();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$target_id = (int)($input['target_id'] ?? 0);

if ($target_id <= 0 || $target_id === $user_id) {
    json_response(['error' => 'Invalid target user'], 400);
}

// Fetch target user info
$t_stmt = $pdo->prepare("SELECT id, full_name, avatar_url FROM users WHERE id = :t");
$t_stmt->execute([':t' => $target_id]);
$target_user = $t_stmt->fetch();

if (!$target_user) {
    json_response(['error' => 'User not found'], 404);
}

$target_name = ucwords(strtolower(trim($target_user['full_name'])));

// Check existing match record
$m_stmt = $pdo->prepare("
    SELECT id, status, requested_by 
    FROM matches 
    WHERE (user1_id = LEAST(:u, :t) AND user2_id = GREATEST(:u, :t))
");
$m_stmt->execute([':u' => $user_id, ':t' => $target_id]);
$match_row = $m_stmt->fetch();

if ($match_row && $match_row['status'] === 'accepted') {
    // Chat is unlocked & allowed!
    json_response([
        'can_chat' => true,
        'status' => 'accepted',
        'match_id' => (int)$match_row['id'],
        'chat_url' => "chat.php?match_id=" . (int)$match_row['id'],
        'target_name' => $target_name,
        'message' => 'Connection accepted! Redirecting to chat...'
    ]);
}

// Otherwise: match is not yet accepted. Automatically record swipe/interest if missing.
if (!$match_row) {
    // Record swipe like
    $swipe_stmt = $pdo->prepare("INSERT INTO swipes (swiper_id, target_id, swipe_type) VALUES (:s, :t, 'like') ON DUPLICATE KEY UPDATE swipe_type = 'like'");
    $swipe_stmt->execute([':s' => $user_id, ':t' => $target_id]);

    // Check if mutual swipe exists
    $check_stmt = $pdo->prepare("SELECT id FROM swipes WHERE swiper_id = :t AND target_id = :s AND swipe_type = 'like'");
    $check_stmt->execute([':t' => $target_id, ':s' => $user_id]);
    $is_mutual = (bool)$check_stmt->fetch();
    $initial_status = $is_mutual ? 'accepted' : 'pending';

    $ins_match = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, status, requested_by) VALUES (LEAST(:u1, :u2), GREATEST(:u1, :u2), :st, :req) ON DUPLICATE KEY UPDATE status = IF(status = 'accepted', 'accepted', VALUES(status))");
    $ins_match->execute([':u1' => $user_id, ':u2' => $target_id, ':st' => $initial_status, ':req' => $user_id]);

    // Re-fetch match ID
    $m_stmt->execute([':u' => $user_id, ':t' => $target_id]);
    $match_row = $m_stmt->fetch();

    // Trigger FCM push notification to target user
    try {
        require_once __DIR__ . '/../includes/fcm_helper.php';
        $u_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :u");
        $u_stmt->execute([':u' => $user_id]);
        $sender_name = $u_stmt->fetchColumn() ?: 'Someone';

        $notif_title = $is_mutual ? "It's a Match! 💕" : "New Chat Request! 💬";
        $notif_body = $is_mutual 
            ? "$sender_name matched with you! Tap to chat now." 
            : "$sender_name sent a matrimonial connection request! Accept to start chatting.";

        send_fcm_notification(
            $target_id,
            $notif_title,
            $notif_body,
            [
                'type' => $is_mutual ? 'match' : 'like',
                'match_id' => (int)($match_row['id'] ?? 0),
                'url' => $is_mutual ? "chat.php?match_id=" . (int)($match_row['id'] ?? 0) : "matches.php"
            ]
        );
    } catch (Exception $e) {}

    if ($is_mutual && $match_row) {
        json_response([
            'can_chat' => true,
            'status' => 'accepted',
            'match_id' => (int)$match_row['id'],
            'chat_url' => "chat.php?match_id=" . (int)$match_row['id'],
            'target_name' => $target_name,
            'message' => 'It\'s a Match! You can now chat.'
        ]);
    }
}

// Request is pending acceptance
$match_id = (int)($match_row['id'] ?? 0);
$chat_url = $match_id > 0 ? "chat.php?match_id={$match_id}" : "chat.php?target_id={$target_id}";

json_response([
    'can_chat' => false,
    'status' => 'pending',
    'match_id' => $match_id,
    'chat_url' => $chat_url,
    'target_name' => $target_name,
    'message' => "Matrimonial chat request sent to {$target_name}! As soon as {$target_name} accepts your request, chat and calling will open automatically."
]);

