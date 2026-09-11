<?php
// api/chat.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$current_user_id = get_current_user_id();
$action = $_GET['action'] ?? 'fetch';

if ($action === 'fetch') {
    $match_id = (int)($_GET['match_id'] ?? 0);
    $last_id = (int)($_GET['last_id'] ?? 0);

    if (!$match_id) {
        json_response(['error' => 'Invalid match ID'], 400);
    }

    // Verify user belongs to match and fetch match status
    $check = $pdo->prepare("SELECT user1_id, user2_id, status AS match_status, requested_by FROM matches WHERE id = :id AND (user1_id = :u1 OR user2_id = :u2)");
    $check->execute([':id' => $match_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
    $match = $check->fetch();

    if (!$match) {
        json_response(['error' => 'Match not found'], 404);
    }

    // Fetch messages after last_id
    $stmt = $pdo->prepare("
        SELECT id, match_id, sender_id, receiver_id, message_text, is_read, created_at
        FROM messages
        WHERE match_id = :match_id AND id > :last_id
        ORDER BY id ASC
    ");
    $stmt->execute([':match_id' => $match_id, ':last_id' => $last_id]);
    $messages = $stmt->fetchAll();

    // Mark received messages as read
    $mark = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE match_id = :match_id AND receiver_id = :u AND is_read = 0");
    $mark->execute([':match_id' => $match_id, ':u' => $current_user_id]);

    // Fetch list of message IDs sent by current user that are read by partner
    $read_stmt = $pdo->prepare("
        SELECT id FROM messages 
        WHERE match_id = :match_id AND sender_id = :u AND is_read = 1
    ");
    $read_stmt->execute([':match_id' => $match_id, ':u' => $current_user_id]);
    $read_ids = $read_stmt->fetchAll(PDO::FETCH_COLUMN);

    $req_by = (int)($match['requested_by'] ?? 0);
    $raw_st = $match['match_status'] ?? 'accepted';
    $effective_status = ($raw_st === 'pending' && $req_by > 0) ? 'pending' : 'accepted';

    json_response([
        'success' => true,
        'messages' => $messages,
        'read_ids' => array_map('intval', $read_ids),
        'match_status' => $effective_status,
        'requested_by' => $req_by
    ]);

} elseif ($action === 'send') {
    $match_id = (int)($_POST['match_id'] ?? 0);
    $raw_text = trim($_POST['message_text'] ?? '');

    // Handle JSON payload if multipart form data was not used
    if (!$match_id && empty($raw_text)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $match_id = (int)($input['match_id'] ?? 0);
        $raw_text = trim($input['message_text'] ?? '');
    }

    if (!$match_id) {
        json_response(['error' => 'Invalid match ID'], 400);
    }

    // Verify match, status & get recipient
    $check = $pdo->prepare("SELECT user1_id, user2_id, status FROM matches WHERE id = :id AND (user1_id = :u1 OR user2_id = :u2)");
    $check->execute([':id' => $match_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
    $match = $check->fetch();

    if (!$match) {
        json_response(['error' => 'Match not found'], 404);
    }

    if ($match['status'] !== 'accepted') {
        json_response(['error' => 'Match request must be accepted before sending messages'], 403);
    }

    $media_html = '';

    // Handle Media File Upload (Photo / Video) safely
    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $_FILES['media']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov'];

        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid('media_', true) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
                $relative_path = 'assets/uploads/' . $new_filename;
                if (in_array($file_ext, ['mp4', 'webm', 'mov'])) {
                    $media_html = '<video src="' . htmlspecialchars($relative_path, ENT_QUOTES, 'UTF-8') . '" controls style="max-width:100%; border-radius:12px; margin-top:4px; display:block;"></video>';
                } else {
                    $media_html = '<img src="' . htmlspecialchars($relative_path, ENT_QUOTES, 'UTF-8') . '" style="max-width:100%; border-radius:12px; margin-top:4px; display:block;" loading="lazy">';
                }
            }
        }
    }

    // Sanitize user plain text against XSS before saving
    $safe_text = htmlspecialchars($raw_text, ENT_QUOTES, 'UTF-8');
    
    if (!empty($safe_text) && !empty($media_html)) {
        $final_text = nl2br($safe_text) . '<br>' . $media_html;
    } elseif (!empty($media_html)) {
        $final_text = $media_html;
    } elseif (!empty($safe_text)) {
        $final_text = nl2br($safe_text);
    } else {
        json_response(['error' => 'Empty message content'], 400);
    }

    $receiver_id = ($match['user1_id'] == $current_user_id) ? $match['user2_id'] : $match['user1_id'];

    $stmt = $pdo->prepare("
        INSERT INTO messages (match_id, sender_id, receiver_id, message_text, is_read)
        VALUES (:match_id, :sender_id, :receiver_id, :text, 0)
    ");
    $stmt->execute([
        ':match_id' => $match_id,
        ':sender_id' => $current_user_id,
        ':receiver_id' => $receiver_id,
        ':text' => $final_text
    ]);

    $new_msg_id = (int)$pdo->lastInsertId();

    // Send FCM Push Notification to recipient
    try {
        require_once __DIR__ . '/../includes/fcm_helper.php';
        $sender_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :u");
        $sender_stmt->execute([':u' => $current_user_id]);
        $sender_name = $sender_stmt->fetchColumn() ?: 'Someone';
        
        $clean_notification_body = strip_tags($safe_text);
        if (empty($clean_notification_body)) $clean_notification_body = '📷 Sent a media attachment';

        send_fcm_notification(
            $receiver_id,
            $sender_name,
            $clean_notification_body,
            [
                'type' => 'chat',
                'match_id' => $match_id,
                'sender_id' => $current_user_id,
                'url' => "chat.php?match_id=$match_id"
            ]
        );
    } catch (Exception $fcm_err) {}

    $get_msg = $pdo->prepare("SELECT * FROM messages WHERE id = :id");
    $get_msg->execute([':id' => $new_msg_id]);
    $msg = $get_msg->fetch();

    json_response([
        'success' => true,
        'message' => $msg
    ]);

} elseif ($action === 'respond_match') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $match_id = (int)($input['match_id'] ?? $_GET['match_id'] ?? 0);
    $response_action = trim($input['response'] ?? $_GET['response'] ?? '');

    if (!$match_id || !in_array($response_action, ['accept', 'reject'])) {
        json_response(['error' => 'Invalid parameters'], 400);
    }

    $check = $pdo->prepare("SELECT user1_id, user2_id, status FROM matches WHERE id = :id AND (user1_id = :u1 OR user2_id = :u2)");
    $check->execute([':id' => $match_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
    $match = $check->fetch();

    if (!$match) {
        json_response(['error' => 'Match request not found'], 404);
    }

    $new_status = ($response_action === 'accept') ? 'accepted' : 'rejected';
    $update = $pdo->prepare("UPDATE matches SET status = :st WHERE id = :id");
    $update->execute([':st' => $new_status, ':id' => $match_id]);

    if ($response_action === 'accept') {
        // Send initial system announcement message
        $partner_id = ($match['user1_id'] == $current_user_id) ? $match['user2_id'] : $match['user1_id'];
        $announce = $pdo->prepare("
            INSERT INTO messages (match_id, sender_id, receiver_id, message_text, is_read)
            VALUES (:mid, :sender, :rec, :txt, 1)
        ");
        $announce->execute([
            ':mid' => $match_id,
            ':sender' => $partner_id,
            ':rec' => $current_user_id,
            ':txt' => "❤️ Match request accepted! You can now chat and make audio/video calls."
        ]);
    }

    json_response([
        'success' => true,
        'status' => $new_status,
        'match_id' => $match_id
    ]);
}
