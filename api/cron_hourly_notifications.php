<?php
// api/cron_hourly_notifications.php
// Automated Hourly Match & Engagement FCM Push Notification Engine
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fcm_helper.php';

header('Content-Type: application/json');

// Optional secret token validation for cPanel / crontab execution
$secret = $_GET['secret'] ?? $_POST['secret'] ?? '';
if (!empty($secret) && $secret !== 'varsaathi2026') {
    json_response(['error' => 'Unauthorized cron execution key'], 401);
}

try {
    // 1. Fetch active users who have FCM Push Tokens
    $u_stmt = $pdo->query("
        SELECT u.id, u.full_name, u.gender, u.looking_for, u.interests, u.fcm_token 
        FROM users u 
        WHERE u.fcm_token IS NOT NULL AND CHAR_LENGTH(u.fcm_token) > 10
        ORDER BY RAND() 
        LIMIT 30
    ");
    $users = $u_stmt->fetchAll();

    if (empty($users)) {
        json_response([
            'success' => true,
            'sent_count' => 0,
            'message' => 'No users found with active FCM push tokens.'
        ]);
    }

    $hindi_templates = [
        [
            'title' => "Aapka koi intezar kar raha hai... 💕",
            'body' => "{NAME} ({AGE} yrs, {CITY}) is active on Varsaathi! Tap to connect and chat."
        ],
        [
            'title' => "Chalo mingle ho jayein! 😉",
            'body' => "{NAME} ({CITY}) shares your passion for {INTERESTS}. Tap to view profile!"
        ],
        [
            'title' => "Kya {NAME} aapke liye sahi hai? 🌟",
            'body' => "High compatibility match found ({SCORE}% Match)! Check their matrimonial profile."
        ],
        [
            'title' => "Pyaar ki nayi shuruat! ❤️",
            'body' => "{NAME} ({AGE} yrs, {CITY}) fits your partner expectations on Varsaathi."
        ],
        [
            'title' => "Naye rishte ka mauka... ✨",
            'body' => "Explore verified candidate profiles matching your preference near {CITY}."
        ]
    ];

    $sent_count = 0;

    foreach ($users as $user) {
        $user_id = (int)$user['id'];
        $looking_for = $user['looking_for'] ?? 'everyone';
        
        $gender_sql = "";
        $params = [':u' => $user_id];
        if ($looking_for !== 'everyone') {
            $gender_sql = " AND u.gender = :gen";
            $params[':gen'] = $looking_for;
        }

        // Pick 1 random candidate matching user's preference
        $cand_stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.birthdate, u.location_city, u.occupation, u.interests
            FROM users u
            WHERE u.id != :u $gender_sql
            ORDER BY RAND()
            LIMIT 1
        ");
        $cand_stmt->execute($params);
        $cand = $cand_stmt->fetch();

        if (!$cand) continue;

        $c_name = ucwords(strtolower(trim((string)($cand['full_name'] ?? 'Someone'))));
        $c_age = calculate_age($cand['birthdate'] ?? '2000-01-01');
        $c_city = (!empty($cand['location_city']) && strpos(strtolower((string)$cand['location_city']), 'san francisco') === false) ? $cand['location_city'] : 'India';
        
        $c_interests_arr = json_decode($cand['interests'] ?? '[]', true) ?: ['Music', 'Travel', 'Dining'];
        $c_interests_str = implode(' & ', array_slice($c_interests_arr, 0, 2));

        $score = rand(92, 98);

        // Select random template
        $template = $hindi_templates[array_rand($hindi_templates)];
        
        $title = str_replace('{NAME}', $c_name, $template['title']);
        $body = str_replace(
            ['{NAME}', '{AGE}', '{CITY}', '{INTERESTS}', '{SCORE}'],
            [$c_name, $c_age, $c_city, $c_interests_str, $score],
            $template['body']
        );

        $res = send_fcm_notification(
            $user_id,
            $title,
            $body,
            [
                'type' => 'hourly_recommendation',
                'target_id' => $cand['id'],
                'url' => "saathi-profile.php?id=" . $cand['id']
            ]
        );

        if (!empty($res['success'])) {
            $sent_count++;
        }
    }

    json_response([
        'success' => true,
        'sent_count' => $sent_count,
        'message' => "Hourly automated FCM notifications dispatched to $sent_count users!"
    ]);

} catch (Exception $e) {
    json_response([
        'success' => false,
        'error' => 'Cron execution error: ' . $e->getMessage()
    ], 500);
}
