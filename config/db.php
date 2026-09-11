<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 2592000);
    ini_set('session.cookie_lifetime', 2592000);
    
    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params([
            'lifetime' => 2592000,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        @session_set_cookie_params(2592000, '/', '', false, true);
    }
    @session_start();
}

$db_host = 'localhost';
$db_user = 'u325640649_varsaathi';
$db_pass = 'AyushmanIndia@2026';
$db_name = 'u325640649_varsaathi';

try {
    // First connect without DB name to check/create database automatically if needed
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Ensure database exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");

    // Check if tables exist or if seed reset is requested
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) === 0 || isset($_GET['seed'])) {
        $sql_file = __DIR__ . '/../sql/schema.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
        }
    } else {
        // Auto-migrate google_id, fcm_token, and password_hash columns if missing or strict
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'")->fetchAll();
            if (count($cols) === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(150) NULL UNIQUE AFTER email");
            }
            $cols_fcm = $pdo->query("SHOW COLUMNS FROM users LIKE 'fcm_token'")->fetchAll();
            if (count($cols_fcm) === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN fcm_token TEXT NULL AFTER interests");
            }
            $pdo->exec("ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL");

            // Auto-migrate matches table for pending/accepted match requests
            $cols_m_status = $pdo->query("SHOW COLUMNS FROM matches LIKE 'status'")->fetchAll();
            if (count($cols_m_status) === 0) {
                $pdo->exec("ALTER TABLE matches ADD COLUMN status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending'");
            }
            $cols_m_req = $pdo->query("SHOW COLUMNS FROM matches LIKE 'requested_by'")->fetchAll();
            if (count($cols_m_req) === 0) {
                $pdo->exec("ALTER TABLE matches ADD COLUMN requested_by INT NULL");
            }

            // Auto-migrate last_seen column on users table for online/away status
            $cols_ls = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_seen'")->fetchAll();
            if (count($cols_ls) === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }

            // Auto-migrate is_private column for private profile mode
            $cols_priv = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_private'")->fetchAll();
            if (count($cols_priv) === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN is_private TINYINT(1) DEFAULT 0");
            }

            // Auto-migrate latitude and longitude columns for GPS location tracking
            $cols_lat = $pdo->query("SHOW COLUMNS FROM users LIKE 'latitude'")->fetchAll();
            if (count($cols_lat) === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) NULL, ADD COLUMN longitude DECIMAL(11, 8) NULL");
            }
        } catch (Exception $ex) {
            // Ignore migration error if columns exist or already modified
        }
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Calculate exact GPS Haversine distance in KM
function calculate_distance_km($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return null;
    }
    $earth_radius = 6371; // Earth Radius in KM
    $dLat = deg2rad((float)$lat2 - (float)$lat1);
    $dLon = deg2rad((float)$lon2 - (float)$lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;
    return round($distance, 1);
}

// Automatically touch last_seen timestamp for active logged-in user
if (is_logged_in()) {
    $cur_uid = get_current_user_id();
    if ($cur_uid) {
        try {
            $pdo->exec("UPDATE users SET last_seen = NOW() WHERE id = " . (int)$cur_uid);
        } catch (Exception $ex) {}
    }
}

// Online / Away / Offline Status Helper Function
function get_user_online_status($last_seen) {
    if (empty($last_seen)) {
        return ['status' => 'offline', 'label' => 'Offline', 'color' => '#8E8E93'];
    }
    $time_diff = time() - strtotime($last_seen);
    if ($time_diff <= 180) {
        return ['status' => 'online', 'label' => 'Online Now', 'color' => '#34C759'];
    } elseif ($time_diff <= 900) {
        return ['status' => 'away', 'label' => 'Away', 'color' => '#FFCC00'];
    } else {
        $mins = floor($time_diff / 60);
        if ($mins < 60) {
            $label = "Active {$mins}m ago";
        } else {
            $hours = floor($mins / 60);
            $label = ($hours < 24) ? "Active {$hours}h ago" : "Active " . date('M d', strtotime($last_seen));
        }
        return ['status' => 'offline', 'label' => $label, 'color' => '#8E8E93'];
    }
}

// 30-Day Session & Login Helpers
function login_user_session($user_id) {
    $uid = (int)$user_id;
    $_SESSION['user_id'] = $uid;
}

function clear_user_session() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        @setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    unset($_COOKIE['varsaathi_remember']);
    @setcookie('varsaathi_remember', '', time() - 36000, '/');

    session_unset();
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    global $pdo;
    if (!is_logged_in()) {
        header("Location: splash.php");
        exit;
    }
    $uid = get_current_user_id();
    if ($uid) {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE id = :u");
        $check_stmt->execute([':u' => $uid]);
        if (!$check_stmt->fetch()) {
            clear_user_session();
            header("Location: splash.php");
            exit;
        }
    } else {
        header("Location: splash.php");
        exit;
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function calculate_age($birthdate) {
    if (empty($birthdate)) return 22;
    $dob = new DateTime($birthdate);
    $now = new DateTime();
    return $dob->diff($now)->y;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
