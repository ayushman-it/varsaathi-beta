<?php
// api/call_signal.php
require_once __DIR__ . '/../config/db.php';
require_login();

header('Content-Type: application/json');

$current_user_id = get_current_user_id();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Auto-create call_signals table if not existing
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS call_signals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            match_id INT NOT NULL,
            caller_id INT NOT NULL,
            receiver_id INT NOT NULL,
            call_type ENUM('audio', 'video') NOT NULL DEFAULT 'video',
            peer_id VARCHAR(100) NOT NULL,
            status ENUM('calling', 'accepted', 'rejected', 'ended') NOT NULL DEFAULT 'calling',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_match (match_id),
            INDEX idx_receiver (receiver_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

if ($action === 'initiate') {
    $match_id = (int)($_POST['match_id'] ?? 0);
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $call_type = ($_POST['call_type'] === 'audio') ? 'audio' : 'video';
    $peer_id = trim($_POST['peer_id'] ?? '');

    if (!$match_id || !$receiver_id || !$peer_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    // Verify that match status is accepted before allowing call initiation
    $check_m = $pdo->prepare("SELECT status FROM matches WHERE id = :mid AND (user1_id = :u1 OR user2_id = :u2)");
    $check_m->execute([':mid' => $match_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
    $match_st = $check_m->fetchColumn();

    if ($match_st !== 'accepted') {
        echo json_encode(['success' => false, 'message' => 'Match request must be accepted before initiating calls']);
        exit;
    }

    // Cancel any older calling signals for this match
    $stmt = $pdo->prepare("UPDATE call_signals SET status = 'ended' WHERE match_id = :mid AND status = 'calling'");
    $stmt->execute([':mid' => $match_id]);

    $stmt = $pdo->prepare("
        INSERT INTO call_signals (match_id, caller_id, receiver_id, call_type, peer_id, status)
        VALUES (:mid, :caller, :receiver, :ctype, :peer, 'calling')
    ");
    $stmt->execute([
        ':mid' => $match_id,
        ':caller' => $current_user_id,
        ':receiver' => $receiver_id,
        ':ctype' => $call_type,
        ':peer' => $peer_id
    ]);
    $call_id = (int)$pdo->lastInsertId();

    // Send FCM Push Notification to receiver
    try {
        require_once __DIR__ . '/../includes/fcm_helper.php';
        $caller_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :u");
        $caller_stmt->execute([':u' => $current_user_id]);
        $caller_name = $caller_stmt->fetchColumn() ?: 'Someone';
        
        $call_label = ($call_type === 'video') ? 'Video' : 'Voice';

        send_fcm_notification(
            $receiver_id,
            "Incoming $call_label Call 📞",
            "$caller_name is calling you on Varsaathi!",
            [
                'type' => 'call',
                'call_type' => $call_type,
                'match_id' => $match_id,
                'caller_id' => $current_user_id,
                'peer_id' => $peer_id,
                'url' => "chat.php?match_id=$match_id&auto_answer=1"
            ]
        );
    } catch (Exception $fcm_err) {}

    echo json_encode(['success' => true, 'call_id' => $call_id]);
    exit;
}

if ($action === 'poll') {
    $match_id = (int)($_GET['match_id'] ?? 0);

    // Fetch latest active call signal for this match
    $stmt = $pdo->prepare("
        SELECT cs.*, u.full_name AS caller_name, u.avatar_url AS caller_avatar
        FROM call_signals cs
        JOIN users u ON u.id = cs.caller_id
        WHERE cs.match_id = :mid
        ORDER BY cs.id DESC LIMIT 1
    ");
    $stmt->execute([':mid' => $match_id]);
    $signal = $stmt->fetch();

    echo json_encode(['success' => true, 'signal' => $signal ?: null]);
    exit;
}

if ($action === 'poll_global') {
    // Check if there is ANY active incoming call signal for current user
    $stmt = $pdo->prepare("
        SELECT cs.*, u.full_name AS caller_name, u.avatar_url AS caller_avatar
        FROM call_signals cs
        JOIN users u ON u.id = cs.caller_id
        WHERE cs.receiver_id = :u AND cs.status = 'calling' AND cs.created_at >= NOW() - INTERVAL 1 MINUTE
        ORDER BY cs.id DESC LIMIT 1
    ");
    $stmt->execute([':u' => $current_user_id]);
    $signal = $stmt->fetch();

    echo json_encode(['success' => true, 'signal' => $signal ?: null]);
    exit;
}

if ($action === 'update_status') {
    $call_id = (int)($_POST['call_id'] ?? 0);
    $status = $_POST['status'] ?? ''; // accepted, rejected, ended
    $duration = trim($_POST['duration'] ?? '');

    if (!in_array($status, ['accepted', 'rejected', 'ended'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Fetch call signal info
    $stmt = $pdo->prepare("SELECT * FROM call_signals WHERE id = :cid AND (caller_id = :u1 OR receiver_id = :u2)");
    $stmt->execute([':cid' => $call_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
    $call_signal = $stmt->fetch();

    if ($call_signal) {
        $up = $pdo->prepare("UPDATE call_signals SET status = :st WHERE id = :cid");
        $up->execute([':st' => $status, ':cid' => $call_id]);

        // Instagram / WhatsApp Style Call Log System Message in Chat
        if (in_array($status, ['ended', 'rejected'])) {
            $match_id = (int)$call_signal['match_id'];
            $caller_id = (int)$call_signal['caller_id'];
            $receiver_id = (int)$call_signal['receiver_id'];
            $call_type = $call_signal['call_type'];
            $icon = ($call_type === 'video') ? 'fa-video' : 'fa-phone';
            $label = ($call_type === 'video') ? 'Video call' : 'Voice call';

            if ($status === 'ended') {
                $sub = !empty($duration) ? $duration : 'Call ended';
                $card_html = "<div class=\"call-log-card\" data-call-type=\"{$call_type}\"><div class=\"call-log-icon-circle\"><i class=\"fa-solid {$icon}\"></i></div><div class=\"call-log-details\"><div class=\"call-log-title\">{$label}</div><div class=\"call-log-sub\">{$sub}</div></div></div>";
            } else {
                $card_html = "<div class=\"call-log-card missed\" data-call-type=\"{$call_type}\"><div class=\"call-log-icon-circle missed\"><i class=\"fa-solid fa-phone-slash\"></i></div><div class=\"call-log-details\"><div class=\"call-log-title\">Missed {$label}</div><div class=\"call-log-sub\">Tap to call back</div></div></div>";
            }

            // Check if call log message was already recorded for this call ID
            $chk_msg = $pdo->prepare("SELECT id FROM messages WHERE match_id = :mid AND sender_id = :sid AND message_text LIKE :pattern");
            $chk_msg->execute([
                ':mid' => $match_id,
                ':sid' => $caller_id,
                ':pattern' => "%call_id_{$call_id}%"
            ]);

            if ($chk_msg->rowCount() === 0) {
                $log_payload = "<!-- call_id_{$call_id} -->" . $card_html;
                $msg_stmt = $pdo->prepare("
                    INSERT INTO messages (match_id, sender_id, receiver_id, message_text, is_read)
                    VALUES (:mid, :sender, :receiver, :text, 1)
                ");
                $msg_stmt->execute([
                    ':mid' => $match_id,
                    ':sender' => $caller_id,
                    ':receiver' => $receiver_id,
                    ':text' => $log_payload
                ]);
            }
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
