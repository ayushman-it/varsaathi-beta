<?php
// api/upload_photos.php
require_once __DIR__ . '/../config/db.php';
require_login();

$user_id = get_current_user_id();
$action = $_REQUEST['action'] ?? 'upload';

// Fetch existing user photos
$stmt = $pdo->prepare("SELECT photos, avatar_url FROM users WHERE id = :u");
$stmt->execute([':u' => $user_id]);
$row = $stmt->fetch();
$photos = json_decode($row['photos'] ?? '[]', true) ?: [];
$avatar_url = $row['avatar_url'] ?? '';

if ($action === 'upload') {
    $uploaded_files = $_FILES['photos'] ?? null;
    if ($uploaded_files && isset($uploaded_files['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $count = count((array)$uploaded_files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $uploaded_files['tmp_name'][$i];
                $file_name = basename($uploaded_files['name'][$i]);
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $new_name = 'photo_' . $user_id . '_' . time() . '_' . $i . '.' . $ext;
                    $target_file = $upload_dir . $new_name;
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $web_path = 'uploads/' . $new_name;
                        $photos[] = $web_path;
                        if (empty($avatar_url)) {
                            $avatar_url = $web_path;
                        }
                    }
                }
            }
        }

        // Keep maximum 6 photos
        $photos = array_slice(array_unique($photos), 0, 6);
        if (empty($avatar_url) && !empty($photos)) {
            $avatar_url = $photos[0];
        }

        $up_stmt = $pdo->prepare("UPDATE users SET photos = :p, avatar_url = :av WHERE id = :u");
        $up_stmt->execute([':p' => json_encode($photos), ':av' => $avatar_url, ':u' => $user_id]);
    }
    header("Location: ../profile.php");
    exit;

} elseif ($action === 'delete') {
    $photo_to_delete = $_POST['photo_url'] ?? '';
    if (!empty($photo_to_delete)) {
        $photos = array_values(array_filter($photos, fn($p) => $p !== $photo_to_delete));
        if ($avatar_url === $photo_to_delete) {
            $avatar_url = $photos[0] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';
        }
        $up_stmt = $pdo->prepare("UPDATE users SET photos = :p, avatar_url = :av WHERE id = :u");
        $up_stmt->execute([':p' => json_encode($photos), ':av' => $avatar_url, ':u' => $user_id]);
    }
    header("Location: ../profile.php");
    exit;

} elseif ($action === 'set_primary') {
    $primary_photo = $_POST['photo_url'] ?? '';
    if (!empty($primary_photo)) {
        $up_stmt = $pdo->prepare("UPDATE users SET avatar_url = :av WHERE id = :u");
        $up_stmt->execute([':av' => $primary_photo, ':u' => $user_id]);
    }
    header("Location: ../profile.php");
    exit;

} elseif ($action === 'upload_avatar') {
    $uploaded_file = $_FILES['avatar_file'] ?? null;
    if ($uploaded_file && $uploaded_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($uploaded_file['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $target_file = $upload_dir . $new_name;
            if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                $web_path = 'uploads/' . $new_name;
                array_unshift($photos, $web_path);
                $photos = array_slice(array_unique($photos), 0, 6);
                $up_stmt = $pdo->prepare("UPDATE users SET avatar_url = :av, photos = :p WHERE id = :u");
                $up_stmt->execute([':av' => $web_path, ':p' => json_encode($photos), ':u' => $user_id]);
            }
        }
    }
    header("Location: ../profile.php");
    exit;
}
