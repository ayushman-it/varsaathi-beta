<?php
// api/delete_account.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    header("Location: ../login.php");
    exit;
}

$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete user record (Cascades to swipes, matches, messages, preferences)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :u");
        $stmt->execute([':u' => $user_id]);

        // Destroy session
        session_unset();
        session_destroy();

        header("Location: ../login.php?deleted=1");
        exit;
    } catch (Exception $e) {
        die("Account Deletion Error: " . $e->getMessage());
    }
}
