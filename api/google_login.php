<?php
// api/google_login.php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true) ?: $_POST;

$credential = $input['credential'] ?? '';
$google_id = trim($input['google_id'] ?? '');
$email = trim($input['email'] ?? '');
$full_name = trim($input['full_name'] ?? $input['name'] ?? '');
$avatar_url = trim($input['avatar_url'] ?? $input['picture'] ?? '');

// Parse Google JWT Token if credential string provided
if (!empty($credential)) {
    $parts = explode('.', $credential);
    if (count($parts) >= 2) {
        $payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
        $payload_b64 = pad_b64($payload_b64);
        $payload = json_decode(base64_decode($payload_b64), true);
        if (is_array($payload)) {
            if (empty($google_id)) $google_id = $payload['sub'] ?? '';
            if (empty($email)) $email = $payload['email'] ?? '';
            if (empty($full_name)) $full_name = $payload['name'] ?? '';
            if (empty($avatar_url)) $avatar_url = $payload['picture'] ?? '';
        }
    }
}

function pad_b64($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return $data;
}

if (empty($email) && !empty($google_id)) {
    $email = 'google_' . substr(md5($google_id), 0, 10) . '@varsaathi.com';
}
if (empty($google_id) && !empty($email)) {
    $google_id = 'g_' . md5($email);
}

// Convert empty strings to null to prevent MySQL UNIQUE key constraint violation (Duplicate entry '')
$google_id = !empty($google_id) ? $google_id : null;
$email = !empty($email) ? $email : null;

if (empty($email) && empty($google_id)) {
    json_response(['error' => 'Google authentication incomplete. Please try again.'], 400);
}

try {
    // Check if user exists by google_id or email
    $user = null;
    if (!empty($google_id) && !empty($email)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :gid OR email = :e LIMIT 1");
        $stmt->execute([':gid' => $google_id, ':e' => $email]);
        $user = $stmt->fetch();
    } else if (!empty($google_id)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :gid LIMIT 1");
        $stmt->execute([':gid' => $google_id]);
        $user = $stmt->fetch();
    } else if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();
    }

    if ($user) {
        // Update google_id if missing in user record
        if (empty($user['google_id']) && !empty($google_id)) {
            $u_stmt = $pdo->prepare("UPDATE users SET google_id = :gid WHERE id = :u");
            $u_stmt->execute([':gid' => $google_id, ':u' => $user['id']]);
        }
        
        login_user_session($user['id']);
        json_response([
            'success' => true,
            'message' => 'Logged in successfully with Google',
            'redirect' => 'index.php'
        ]);
    } else {
        // Register new user
        if (empty($avatar_url)) {
            $avatar_url = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';
        }

        $ins_stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, google_id, password_hash, avatar_url, photos)
            VALUES (:fn, :e, :gid, :ph, :av, :p)
        ");
        $random_password = bin2hex(random_bytes(16));
        $ins_stmt->execute([
            ':fn' => !empty($full_name) ? $full_name : 'Varsaathi User',
            ':e' => $email,
            ':gid' => $google_id,
            ':ph' => password_hash($random_password, PASSWORD_DEFAULT),
            ':av' => $avatar_url,
            ':p' => json_encode([$avatar_url])
        ]);

        $new_user_id = $pdo->lastInsertId();
        login_user_session($new_user_id);

        // Create default user preferences
        $pref_stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, min_age, max_age, max_distance, gender_preference)
            VALUES (:u, 18, 35, 50, 'everyone')
        ");
        $pref_stmt->execute([':u' => $new_user_id]);

        json_response([
            'success' => true,
            'message' => 'Account created with Google',
            'redirect' => 'onboarding-steps.php?step=1'
        ]);
    }
} catch (PDOException $e) {
    json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
}
