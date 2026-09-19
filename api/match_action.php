<?php
// api/match_action.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$current_user_id = get_current_user_id();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$match_id = (int)($input['match_id'] ?? 0);
$action = trim($input['action'] ?? '');

if (!$match_id || !in_array($action, ['accept', 'decline', 'reject', 'delete', 'unmatch'])) {
    json_response(['error' => 'Invalid parameters'], 400);
}

try {
    // Verify match exists and current user is part of the match
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name AS responder_name
        FROM matches m
        JOIN users u ON u.id = :cur_user
        WHERE m.id = :mid AND (m.user1_id = :u1 OR m.user2_id = :u2)
    ");
    $stmt->execute([
        ':cur_user' => $current_user_id,
        ':mid' => $match_id,
        ':u1' => $current_user_id,
        ':u2' => $current_user_id
    ]);
    $match = $stmt->fetch();

    if (!$match) {
        json_response(['error' => 'Match request not found'], 404);
    }

    $requester_id = $match['requested_by'] ?? ($match['user1_id'] == $current_user_id ? $match['user2_id'] : $match['user1_id']);

    if ($action === 'accept') {
        $up_stmt = $pdo->prepare("UPDATE matches SET status = 'accepted', matched_at = CURRENT_TIMESTAMP WHERE id = :mid");
        $up_stmt->execute([':mid' => $match_id]);

        // Send push notification to requester
        try {
            require_once __DIR__ . '/../includes/fcm_helper.php';
            $responder_name = $match['responder_name'] ?: 'Someone';
            send_fcm_notification(
                $requester_id,
                "Match Accepted! 💕",
                "$responder_name accepted your match request! Tap to start chatting.",
                [
                    'type' => 'match_accepted',
                    'match_id' => $match_id,
                    'url' => "chat.php?match_id=$match_id"
                ]
            );
        } catch (Exception $ex) {}

        json_response([
            'success' => true,
            'status' => 'accepted',
            'chat_url' => "chat.php?match_id=$match_id",
            'message' => 'Match request accepted!'
        ]);
    } else if (in_array($action, ['delete', 'unmatch'])) {
        $del_stmt = $pdo->prepare("DELETE FROM matches WHERE id = :mid");
        $del_stmt->execute([':mid' => $match_id]);

        json_response([
            'success' => true,
            'status' => 'deleted',
            'message' => 'Match conversation removed.'
        ]);
    } else {
        // Decline/Reject match
        $del_stmt = $pdo->prepare("UPDATE matches SET status = 'rejected' WHERE id = :mid");
        $del_stmt->execute([':mid' => $match_id]);

        json_response([
            'success' => true,
            'status' => 'rejected',
            'message' => 'Match request declined.'
        ]);
    }

} catch (PDOException $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
