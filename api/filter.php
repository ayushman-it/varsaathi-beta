<?php
// api/filter.php - Save User Match & Discover Filter Preferences
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax']) || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    header("Location: ../login.php");
    exit;
}

$current_user_id = get_current_user_id();

// Read input whether JSON payload or form POST
$input = $_POST;
$raw_body = file_get_contents('php://input');
if ($raw_body && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $json_data = json_decode($raw_body, true);
    if (is_array($json_data)) {
        $input = array_merge($input, $json_data);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min_age = max(18, min(80, (int)($input['min_age'] ?? 18)));
    $max_age = max($min_age, min(80, (int)($input['max_age'] ?? 45)));
    $max_distance = max(5, min(500, (int)($input['max_distance'] ?? 50)));
    $gender_preference = in_array($input['gender_preference'] ?? 'everyone', ['male', 'female', 'everyone']) ? $input['gender_preference'] : 'everyone';
    
    $marital_status = trim((string)($input['marital_status'] ?? 'Any'));
    $religion_community = trim((string)($input['religion_community'] ?? 'Any'));
    $education = trim((string)($input['education'] ?? 'Any'));
    $occupation = trim((string)($input['occupation'] ?? 'Any'));
    
    $interests = $input['interests'] ?? [];
    if (is_string($interests)) {
        $interests = array_filter(array_map('trim', explode(',', $interests)));
    }
    $interests_json = json_encode(array_values((array)$interests));

    try {
        // Auto-create user_preferences table if not present
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_preferences` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT NOT NULL UNIQUE,
          `min_age` INT DEFAULT 18,
          `max_age` INT DEFAULT 45,
          `max_distance` INT DEFAULT 50,
          `gender_preference` ENUM('male', 'female', 'everyone') DEFAULT 'everyone',
          `marital_status` VARCHAR(100) DEFAULT 'Any',
          `religion_community` VARCHAR(100) DEFAULT 'Any',
          `education` VARCHAR(150) DEFAULT 'Any',
          `occupation` VARCHAR(150) DEFAULT 'Any',
          `interests` JSON DEFAULT NULL,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ensure new columns exist
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM user_preferences LIKE 'marital_status'")->fetchAll();
            if (count($cols) === 0) {
                $pdo->exec("ALTER TABLE user_preferences 
                    ADD COLUMN marital_status VARCHAR(100) DEFAULT 'Any',
                    ADD COLUMN religion_community VARCHAR(100) DEFAULT 'Any',
                    ADD COLUMN education VARCHAR(150) DEFAULT 'Any',
                    ADD COLUMN occupation VARCHAR(150) DEFAULT 'Any',
                    ADD COLUMN interests JSON DEFAULT NULL");
            }
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, min_age, max_age, max_distance, gender_preference, marital_status, religion_community, education, occupation, interests)
            VALUES (:u, :min_a, :max_a, :dist, :gen, :mar, :rel, :edu, :occ, :int)
            ON DUPLICATE KEY UPDATE
                min_age = VALUES(min_age),
                max_age = VALUES(max_age),
                max_distance = VALUES(max_distance),
                gender_preference = VALUES(gender_preference),
                marital_status = VALUES(marital_status),
                religion_community = VALUES(religion_community),
                education = VALUES(education),
                occupation = VALUES(occupation),
                interests = VALUES(interests)
        ");
        $stmt->execute([
            ':u' => $current_user_id,
            ':min_a' => $min_age,
            ':max_a' => $max_age,
            ':dist' => $max_distance,
            ':gen' => $gender_preference,
            ':mar' => $marital_status,
            ':rel' => $religion_community,
            ':edu' => $education,
            ':occ' => $occupation,
            ':int' => $interests_json
        ]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($input['ajax']) || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            json_response(['success' => true, 'message' => 'Filter preferences updated successfully!']);
        }

        $redirect = !empty($input['redirect']) ? $input['redirect'] : '../index.php';
        header("Location: " . $redirect);
        exit;

    } catch (Exception $ex) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($input['ajax']) || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            json_response(['success' => false, 'message' => 'Database error: ' . $ex->getMessage()], 500);
        }
        die("Error saving preferences: " . $ex->getMessage());
    }
}

