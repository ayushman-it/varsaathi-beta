<?php
// api/swipe.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$current_user_id = get_current_user_id();

// Support both JSON body and standard POST form data
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$target_id = isset($input['target_id']) ? (int)$input['target_id'] : 0;
$action = isset($input['swipe_type']) ? $input['swipe_type'] : ($input['action'] ?? 'like');

if (!$target_id || $target_id === $current_user_id) {
    json_response(['error' => 'Invalid target user'], 400);
}

try {
    // 1. Record Swipe
    $stmt = $pdo->prepare("
        INSERT INTO swipes (swiper_id, target_id, swipe_type)
        VALUES (:swiper, :target, :action)
        ON DUPLICATE KEY UPDATE swipe_type = VALUES(swipe_type)
    ");
    $stmt->execute([
        ':swiper' => $current_user_id,
        ':target' => $target_id,
        ':action' => $action
    ]);

    $matched = false;
    $match_id = null;

    // 2. When user likes/superlikes, create match request (pending until accepted, or instant if mutual)
    if (in_array($action, ['like', 'superlike'])) {
        $matched = true;
        $u1 = min($current_user_id, $target_id);
        $u2 = max($current_user_id, $target_id);

        // Check if target user has already liked current user
        $check_recip = $pdo->prepare("SELECT id FROM swipes WHERE swiper_id = :target AND target_id = :swiper AND swipe_type IN ('like', 'superlike')");
        $check_recip->execute([':target' => $target_id, ':swiper' => $current_user_id]);
        $is_mutual = (bool)$check_recip->fetch();

        $initial_status = $is_mutual ? 'accepted' : 'pending';

        $check_m = $pdo->prepare("SELECT id, status FROM matches WHERE user1_id = :u1 AND user2_id = :u2");
        $check_m->execute([':u1' => $u1, ':u2' => $u2]);
        $existing_m = $check_m->fetch();

        if ($existing_m) {
            $match_id = (int)$existing_m['id'];
            if ($is_mutual && $existing_m['status'] !== 'accepted') {
                $up_stmt = $pdo->prepare("UPDATE matches SET status = 'accepted', matched_at = CURRENT_TIMESTAMP WHERE id = :mid");
                $up_stmt->execute([':mid' => $match_id]);
            }
        } else {
            $match_stmt = $pdo->prepare("
                INSERT INTO matches (user1_id, user2_id, status, requested_by)
                VALUES (:u1, :u2, :st, :req)
            ");
            $match_stmt->execute([
                ':u1' => $u1,
                ':u2' => $u2,
                ':st' => $initial_status,
                ':req' => $current_user_id
            ]);
            $match_id = (int)$pdo->lastInsertId();
        }

        // Send FCM Push Notification to target user
        try {
            require_once __DIR__ . '/../includes/fcm_helper.php';
            $cu_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :u");
            $cu_stmt->execute([':u' => $current_user_id]);
            $cu_name = $cu_stmt->fetchColumn() ?: 'Someone';

            send_fcm_notification(
                $target_id,
                "New Match Request! 💕",
                "$cu_name sent you a match request! Tap to view.",
                [
                    'type' => 'match',
                    'match_id' => $match_id,
                    'url' => "chat.php?match_id=$match_id"
                ]
            );
        } catch (Exception $fcm_err) {}
    }

    json_response([
        'success' => true,
        'matched' => $matched,
        'is_match' => $matched,
        'match_id' => $match_id
    ]);

} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
