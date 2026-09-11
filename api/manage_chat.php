<?php
// api/manage_chat.php
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
$partner_id = (int)($input['partner_id'] ?? 0);
$action = trim($input['action'] ?? '');
$reason = trim($input['reason'] ?? 'Inappropriate behavior or content');

if (!$match_id && !$partner_id) {
    json_response(['error' => 'Invalid target parameters'], 400);
}

// Auto-create user_blocks and user_reports tables if missing
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blocked_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blocker_id INT NOT NULL,
            blocked_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_block (blocker_id, blocked_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS user_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            reported_id INT NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

try {
    // If match_id not provided, look it up by partner_id
    if (!$match_id && $partner_id) {
        $m_stmt = $pdo->prepare("SELECT id FROM matches WHERE (user1_id = :u1 AND user2_id = :u2) OR (user1_id = :u2 AND user2_id = :u1)");
        $m_stmt->execute([':u1' => $current_user_id, ':u2' => $partner_id]);
        $match_id = (int)$m_stmt->fetchColumn();
    }

    if ($action === 'delete') {
        // Delete messages in conversation
        if ($match_id) {
            $del_msg = $pdo->prepare("DELETE FROM messages WHERE match_id = :mid");
            $del_msg->execute([':mid' => $match_id]);

            $del_m = $pdo->prepare("DELETE FROM matches WHERE id = :mid AND (user1_id = :u1 OR user2_id = :u2)");
            $del_m->execute([':mid' => $match_id, ':u1' => $current_user_id, ':u2' => $current_user_id]);
        }

        json_response([
            'success' => true,
            'action' => 'delete',
            'message' => 'Chat deleted successfully'
        ]);

    } elseif ($action === 'block') {
        if ($partner_id) {
            $ins_block = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (:b1, :b2)");
            $ins_block->execute([':b1' => $current_user_id, ':b2' => $partner_id]);
        }

        // Delete match & messages
        if ($match_id) {
            $del_msg = $pdo->prepare("DELETE FROM messages WHERE match_id = :mid");
            $del_msg->execute([':mid' => $match_id]);

            $del_m = $pdo->prepare("DELETE FROM matches WHERE id = :mid");
            $del_m->execute([':mid' => $match_id]);
        }

        json_response([
            'success' => true,
            'action' => 'block',
            'message' => 'User has been blocked'
        ]);

    } elseif ($action === 'report') {
        if ($partner_id) {
            $ins_rep = $pdo->prepare("INSERT INTO user_reports (reporter_id, reported_id, reason) VALUES (:r1, :r2, :rs)");
            $ins_rep->execute([':r1' => $current_user_id, ':r2' => $partner_id, ':rs' => $reason]);

            // Also block user after report
            $ins_block = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (:b1, :b2)");
            $ins_block->execute([':b1' => $current_user_id, ':b2' => $partner_id]);
        }

        if ($match_id) {
            $del_msg = $pdo->prepare("DELETE FROM messages WHERE match_id = :mid");
            $del_msg->execute([':mid' => $match_id]);

            $del_m = $pdo->prepare("DELETE FROM matches WHERE id = :mid");
            $del_m->execute([':mid' => $match_id]);
        }

        json_response([
            'success' => true,
            'action' => 'report',
            'message' => 'User reported and blocked'
        ]);

    } else {
        json_response(['error' => 'Invalid action'], 400);
    }

} catch (PDOException $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
