<?php
// includes/fcm_helper.php
require_once __DIR__ . '/../config/db.php';

/**
 * Send Firebase Cloud Messaging (FCM) Push Notification to Target User
 * 
 * @param int $target_user_id Target user database ID
 * @param string $title Notification title (e.g. "It's a Match! 💕", "New Message 💬", "Incoming Call 📞")
 * @param string $body Notification message body text
 * @param array $data Extra custom payload data (type, match_id, url, etc.)
 * @return array Response status details
 */
function send_fcm_notification($target_user_id, $title, $body, $data = []) {
    global $pdo;

    // Get Target User's FCM Token from DB
    $stmt = $pdo->prepare("SELECT fcm_token, full_name FROM users WHERE id = :u LIMIT 1");
    $stmt->execute([':u' => $target_user_id]);
    $user = $stmt->fetch();

    if (!$user || empty($user['fcm_token'])) {
        return [
            'success' => false,
            'error' => 'No active FCM token found for target user ID ' . $target_user_id
        ];
    }

    $fcm_token = $user['fcm_token'];

    // Check if Service Account JSON file exists in config/service-account.json
    $json_key_path = __DIR__ . '/../config/service-account.json';
    $auto_token = get_fcm_v1_access_token($json_key_path);

    // Support both Legacy FCM Server Key (AAAA...), Bearer Token, and Auto Service Account JSON
    $server_key = !empty($auto_token) 
        ? $auto_token 
        : (defined('FCM_SERVER_KEY') ? FCM_SERVER_KEY : 'YOUR_FIREBASE_SERVER_KEY_OR_BEARER_TOKEN');

    $is_v1 = (!empty($auto_token) || strpos($server_key, 'ya29.') === 0 || strpos($server_key, 'Bearer ') === 0);

    if ($is_v1) {
        // FCM HTTP v1 API Endpoint & Payload Format
        $bearer_token = str_replace('Bearer ', '', $server_key);
        $project_id = 'varsaathi';
        $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";
        
        $is_call = (($data['type'] ?? '') === 'call');
        $payload = [
            'message' => [
                'token' => $fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                    'click_action' => $data['url'] ?? 'matches.php',
                    'timestamp' => date('c')
                ], array_map('strval', $data)),
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high',
                        'TTL' => '30'
                    ],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => $data['icon'] ?? 'assets/images/favicon.png',
                        'badge' => 'assets/images/favicon.png',
                        'sound' => 'default',
                        'requireInteraction' => $is_call,
                        'click_action' => $data['url'] ?? 'matches.php'
                    ]
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $bearer_token,
            'Content-Type: application/json'
        ];
    } else {
        // Legacy FCM API Endpoint & Payload Format
        $url = 'https://fcm.googleapis.com/fcm/send';
        $payload = [
            'to' => $fcm_token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => $data['icon'] ?? 'assets/images/favicon.png',
                'sound' => 'default',
                'click_action' => $data['url'] ?? 'matches.php'
            ],
            'data' => array_merge([
                'title' => $title,
                'body' => $body,
                'timestamp' => date('c')
            ], array_map('strval', $data)),
            'priority' => 'high'
        ];

        $headers = [
            'Authorization: key=' . str_replace('key=', '', $server_key),
            'Content-Type: application/json'
        ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($result === FALSE) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curl_error
        ];
    }

    return [
        'success' => ($http_code === 200),
        'http_code' => $http_code,
        'response' => json_decode($result, true) ?: $result
    ];
}

/**
 * Generate OAuth2 Bearer Token from Service Account JSON file for Firebase HTTP v1 API
 */
function get_fcm_v1_access_token($json_path) {
    if (!file_exists($json_path)) return null;
    $sa = json_decode(file_get_contents($json_path), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) return null;

    $now = time();
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $claim = json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

    $signature = '';
    openssl_sign($base64UrlHeader . "." . $base64UrlClaim, $signature, $sa['private_key'], 'SHA256');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $base64UrlHeader . "." . $base64UrlClaim . "." . $base64UrlSignature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $res['access_token'] ?? null;
}
