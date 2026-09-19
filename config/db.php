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

if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'] ?? 'varsaathi.cuboidsoft.in';
    define('SITE_URL', rtrim($protocol . $domain, '/') . '/');
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

            // Auto-migrate Saathi Matrimonial tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS `saathi_profiles` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL UNIQUE,
              `status` ENUM('draft', 'published', 'paused') DEFAULT 'draft',
              `created_by` VARCHAR(50) DEFAULT 'Self',
              `weight_kg` INT DEFAULT NULL,
              `body_type` VARCHAR(50) DEFAULT 'Average',
              `complexion` VARCHAR(50) DEFAULT 'Fair',
              `languages_spoken` TEXT DEFAULT NULL,
              `company_name` VARCHAR(150) DEFAULT NULL,
              `family_status` VARCHAR(50) DEFAULT 'Middle Class',
              `family_location` VARCHAR(150) DEFAULT NULL,
              `brothers_married` INT DEFAULT 0,
              `sisters_married` INT DEFAULT 0,
              `own_house` VARCHAR(20) DEFAULT 'No',
              `own_car` VARCHAR(20) DEFAULT 'No',
              `headline` VARCHAR(255) DEFAULT NULL,
              `marital_status` VARCHAR(50) DEFAULT 'Never Married',
              `have_children` VARCHAR(50) DEFAULT 'No',
              `height_cm` INT DEFAULT 165,
              `mother_tongue` VARCHAR(100) DEFAULT 'Hindi',
              `state` VARCHAR(100) DEFAULT 'Chhattisgarh',
              `country` VARCHAR(100) DEFAULT 'India',
              `highest_qualification` VARCHAR(150) DEFAULT 'Bachelor\'s Degree',
              `degree` VARCHAR(150) DEFAULT NULL,
              `specialization` VARCHAR(150) DEFAULT NULL,
              `college` VARCHAR(150) DEFAULT NULL,
              `occupation_type` VARCHAR(100) DEFAULT 'Private Job',
              `annual_income` VARCHAR(100) DEFAULT 'Prefer not to say',
              `religion` VARCHAR(100) DEFAULT NULL,
              `caste_community` VARCHAR(100) DEFAULT NULL,
              `sub_caste` VARCHAR(100) DEFAULT NULL,
              `gotra` VARCHAR(100) DEFAULT NULL,
              `manglik_status` VARCHAR(50) DEFAULT 'Prefer not to say',
              `rashi` VARCHAR(100) DEFAULT NULL,
              `nakshatra` VARCHAR(100) DEFAULT NULL,
              `birth_time` VARCHAR(50) DEFAULT NULL,
              `birth_place` VARCHAR(150) DEFAULT NULL,
              `family_type` VARCHAR(50) DEFAULT 'Nuclear Family',
              `family_values` VARCHAR(50) DEFAULT 'Moderate',
              `father_occupation` VARCHAR(150) DEFAULT NULL,
              `mother_occupation` VARCHAR(150) DEFAULT NULL,
              `brothers_count` INT DEFAULT 0,
              `sisters_count` INT DEFAULT 0,
              `diet` VARCHAR(50) DEFAULT 'Vegetarian',
              `smoking` VARCHAR(50) DEFAULT 'No',
              `drinking` VARCHAR(50) DEFAULT 'No',
              `fitness` VARCHAR(50) DEFAULT 'Occasionally',
              `partner_min_age` INT DEFAULT 18,
              `partner_max_age` INT DEFAULT 45,
              `partner_min_height` INT DEFAULT 140,
              `partner_max_height` INT DEFAULT 210,
              `partner_marital_status` VARCHAR(255) DEFAULT 'Any',
              `partner_religion` VARCHAR(255) DEFAULT 'Any',
              `partner_community` VARCHAR(255) DEFAULT 'Any',
              `partner_education` VARCHAR(255) DEFAULT 'Any',
              `partner_occupation` VARCHAR(255) DEFAULT 'Any',
              `partner_location` VARCHAR(255) DEFAULT 'Any',
              `marriage_timeline` VARCHAR(100) DEFAULT 'Within 1 Year',
              `partner_expectations` TEXT DEFAULT NULL,
              `is_paused` TINYINT(1) DEFAULT 0,
              `is_published` TINYINT(1) DEFAULT 0,
              `completion_pct` INT DEFAULT 0,
              `privacy_json` JSON DEFAULT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Auto-add missing columns to saathi_profiles if table already existed
            $cols = $pdo->query("SHOW COLUMNS FROM saathi_profiles LIKE 'created_by'")->fetchAll();
            if (count($cols) === 0) {
                $pdo->exec("ALTER TABLE saathi_profiles 
                    ADD COLUMN `created_by` VARCHAR(50) DEFAULT 'Self',
                    ADD COLUMN `weight_kg` INT DEFAULT NULL,
                    ADD COLUMN `body_type` VARCHAR(50) DEFAULT 'Average',
                    ADD COLUMN `complexion` VARCHAR(50) DEFAULT 'Fair',
                    ADD COLUMN `languages_spoken` TEXT DEFAULT NULL,
                    ADD COLUMN `company_name` VARCHAR(150) DEFAULT NULL,
                    ADD COLUMN `family_status` VARCHAR(50) DEFAULT 'Middle Class',
                    ADD COLUMN `family_location` VARCHAR(150) DEFAULT NULL,
                    ADD COLUMN `brothers_married` INT DEFAULT 0,
                    ADD COLUMN `sisters_married` INT DEFAULT 0,
                    ADD COLUMN `own_house` VARCHAR(20) DEFAULT 'No',
                    ADD COLUMN `own_car` VARCHAR(20) DEFAULT 'No'");
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS `saathi_ai_cache` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `candidate_id` INT NOT NULL,
              `score` INT NOT NULL,
              `confidence` VARCHAR(50) DEFAULT 'high',
              `reasons_json` JSON NOT NULL,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `unique_cache` (`user_id`, `candidate_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`candidate_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        } catch (Exception $ex) {
            // Ignore migration error if tables exist or already created
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

// Valid Avatar Helper: Replaces dummy unsplash links or empty avatars with standard no_image_placeholder
function get_valid_avatar_url($avatar_url) {
    if (empty($avatar_url) || 
        strpos($avatar_url, 'unsplash.com') !== false || 
        strpos($avatar_url, 'default_avatar') !== false || 
        strpos($avatar_url, 'placeholder') !== false) {
        return 'assets/images/no_image_placeholder.png';
    }
    return $avatar_url;
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
    if (empty($birthdate) || $birthdate === '0000-00-00') return 22;
    try {
        $dob = new DateTime($birthdate);
        $now = new DateTime();
        return $dob->diff($now)->y;
    } catch (Exception $e) {
        return 22;
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Fetch or create initial Saathi profile for user
function get_saathi_profile($user_id) {
    global $pdo;
    $user_id = (int)$user_id;
    if ($user_id <= 0) return [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM saathi_profiles WHERE user_id = :u");
        $stmt->execute([':u' => $user_id]);
        $profile = $stmt->fetch();

        if (!$profile) {
            $u_check = $pdo->prepare("SELECT id FROM users WHERE id = :u");
            $u_check->execute([':u' => $user_id]);
            if ($u_check->fetch()) {
                $ins = $pdo->prepare("INSERT INTO saathi_profiles (user_id, status, is_published, is_paused, completion_pct) VALUES (:u, 'draft', 0, 0, 15)");
                $ins->execute([':u' => $user_id]);

                $stmt->execute([':u' => $user_id]);
                $profile = $stmt->fetch();
            }
        }
        return is_array($profile) ? $profile : [];
    } catch (Exception $e) {
        return [];
    }
}

// Calculate Saathi Profile completion percentage intelligently
function calculate_saathi_completion($sp, $user) {
    if (!$sp) return 0;
    $total_weight = 0;
    $earned_weight = 0;

    $fields = [
        // Basic & Photos (Required - 35%)
        'avatar_url' => ['val' => $user['avatar_url'] ?? '', 'weight' => 10],
        'marital_status' => ['val' => $sp['marital_status'] ?? '', 'weight' => 10],
        'height_cm' => ['val' => $sp['height_cm'] ?? '', 'weight' => 5],
        'mother_tongue' => ['val' => $sp['mother_tongue'] ?? '', 'weight' => 5],
        'headline' => ['val' => $sp['headline'] ?? '', 'weight' => 5],

        // Education & Career (Recommended - 25%)
        'highest_qualification' => ['val' => $sp['highest_qualification'] ?? '', 'weight' => 10],
        'occupation_type' => ['val' => $sp['occupation_type'] ?? '', 'weight' => 10],
        'annual_income' => ['val' => $sp['annual_income'] ?? '', 'weight' => 5],

        // Lifestyle & Family (20%)
        'diet' => ['val' => $sp['diet'] ?? '', 'weight' => 5],
        'family_type' => ['val' => $sp['family_type'] ?? '', 'weight' => 5],
        'father_occupation' => ['val' => $sp['father_occupation'] ?? '', 'weight' => 5],
        'mother_occupation' => ['val' => $sp['mother_occupation'] ?? '', 'weight' => 5],

        // Partner Expectations & Timeline (20%)
        'marriage_timeline' => ['val' => $sp['marriage_timeline'] ?? '', 'weight' => 10],
        'partner_expectations' => ['val' => $sp['partner_expectations'] ?? '', 'weight' => 10],
    ];

    foreach ($fields as $k => $f) {
        $total_weight += $f['weight'];
        if (!empty($f['val'])) {
            $earned_weight += $f['weight'];
        }
    }

    $pct = round(($earned_weight / $total_weight) * 100);
    return min(100, max(10, $pct));
}

// Helper to extract dynamic trait pills from candidate user data (PHP 8.1+ type-safe)
function get_candidate_traits($cand) {
    $traits = [];
    
    // Helper to sanitize trait string (remove quotes, brackets, backslashes, null-safe)
    $clean_trait = function($val) {
        if ($val === null || is_array($val) || is_object($val)) return '';
        $str = trim((string)$val, " \t\n\r\0\x0B[]\"'\\");
        if ($str === '') return '';
        return ucwords(strtolower($str));
    };

    // 1. Marital status / Caste
    $s_marital = (string)($cand['saathi_marital_status'] ?? '');
    $u_marital = (string)($cand['marital_status'] ?? '');
    if ($s_marital !== '' && strtolower($s_marital) !== 'unspecified') {
        $c = $clean_trait($s_marital);
        if ($c !== '') $traits[] = $c;
    } elseif ($u_marital !== '') {
        $c = $clean_trait($u_marital);
        if ($c !== '') $traits[] = $c;
    }
    
    // 2. Diet or Religion or Community
    $diet = (string)($cand['diet'] ?? '');
    $caste = (string)($cand['caste_community'] ?? '');
    $rel = (string)($cand['religion'] ?? '');
    if ($diet !== '' && strtolower($diet) !== 'any') {
        $c = $clean_trait($diet);
        if ($c !== '') $traits[] = $c;
    } elseif ($caste !== '') {
        $c = $clean_trait($caste);
        if ($c !== '') $traits[] = $c;
    } elseif ($rel !== '') {
        $c = $clean_trait($rel);
        if ($c !== '') $traits[] = $c;
    }
    
    // 3. Qualification / Degree / Hobbies
    $degree = (string)($cand['degree'] ?? '');
    $qual = (string)($cand['highest_qualification'] ?? '');
    $h_raw = (string)($cand['hobbies'] ?? '');
    if ($degree !== '') {
        $c = $clean_trait($degree);
        if ($c !== '') $traits[] = $c;
    } elseif ($qual !== '') {
        $c = $clean_trait($qual);
        if ($c !== '') $traits[] = $c;
    } elseif ($h_raw !== '') {
        $h_arr = json_decode($h_raw, true);
        if (!is_array($h_arr)) {
            $h_arr = explode(',', $h_raw);
        }
        if (is_array($h_arr)) {
            foreach ($h_arr as $h_item) {
                $cleaned = $clean_trait($h_item);
                if ($cleaned !== '' && !in_array($cleaned, $traits) && count($traits) < 3) {
                    $traits[] = $cleaned;
                }
            }
        }
    }
    
    // 4. Interests / Bio keywords
    $i_raw = (string)($cand['interests'] ?? '');
    if (count($traits) < 3 && $i_raw !== '') {
        $i_arr = json_decode($i_raw, true);
        if (!is_array($i_arr)) {
            $i_arr = explode(',', $i_raw);
        }
        if (is_array($i_arr)) {
            foreach ($i_arr as $item) {
                $cleaned = $clean_trait($item);
                if ($cleaned !== '' && !in_array($cleaned, $traits) && count($traits) < 3) {
                    $traits[] = $cleaned;
                }
            }
        }
    }
    
    // 5. Intelligent defaults matching database context if still less than 3
    $default_pool = ['Chourasiya Samaj', 'Verified Member', 'Family Oriented', 'Looking for Partner'];
    foreach ($default_pool as $def) {
        if (count($traits) >= 3) break;
        if (!in_array($def, $traits)) {
            $traits[] = $def;
        }
    }
    
    return array_slice($traits, 0, 3);
}


