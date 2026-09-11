<?php
// api/filter.php
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    header("Location: ../login.php");
    exit;
}

$current_user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min_age = (int)($_POST['min_age'] ?? 18);
    $max_age = (int)($_POST['max_age'] ?? 35);
    $max_distance = (int)($_POST['max_distance'] ?? 50);
    $gender_preference = $_POST['gender_preference'] ?? 'everyone';

    $stmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, min_age, max_age, max_distance, gender_preference)
        VALUES (:u, :min_a, :max_a, :dist, :gen)
        ON DUPLICATE KEY UPDATE
            min_age = VALUES(min_age),
            max_age = VALUES(max_age),
            max_distance = VALUES(max_distance),
            gender_preference = VALUES(gender_preference)
    ");
    $stmt->execute([
        ':u' => $current_user_id,
        ':min_a' => $min_age,
        ':max_a' => $max_age,
        ':dist' => $max_distance,
        ':gen' => $gender_preference
    ]);

    header("Location: ../index.php");
    exit;
}
