<?php
// api/saathi_recommendations.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/flags.php';
require_once __DIR__ . '/../config/groq.php';

if (!is_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

$user_id = get_current_user_id();

// Load current user profile & Saathi profile
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$u_stmt->execute([':u' => $user_id]);
$user = $u_stmt->fetch();
$saathi = get_saathi_profile($user_id);

$target_gender = ($user['gender'] === 'female') ? 'male' : (($user['gender'] === 'male') ? 'female' : 'everyone');

// Stage 1: Backend Candidate Hard Filtering
$cand_stmt = $pdo->prepare("
    SELECT u.*, sp.headline, sp.marital_status AS saathi_marital_status, sp.height_cm, sp.highest_qualification, sp.degree, sp.specialization, sp.occupation_type, sp.annual_income, sp.religion, sp.caste_community, sp.diet, sp.marriage_timeline, sp.completion_pct
    FROM users u
    INNER JOIN saathi_profiles sp ON u.id = sp.user_id
    WHERE u.id != :u
      AND sp.is_published = 1
      AND sp.is_paused = 0
      AND (u.gender = :g OR :g = 'everyone')
    LIMIT 30
");
$cand_stmt->execute([':u' => $user_id, ':g' => $target_gender]);
$candidates = $cand_stmt->fetchAll();

if (empty($candidates)) {
    json_response(['recommendations' => [], 'message' => 'No published Saathi profiles found yet']);
}

// Stage 2 & 3: Groq AI Ranking with Fallback Engine
$ai_scores = rank_candidates_with_groq($user, $saathi, $candidates);

// Index AI scores by candidateId
$scores_by_id = [];
foreach ($ai_scores as $res) {
    $scores_by_id[$res['candidateId']] = $res;
}

// Combine candidate user details with AI score payload
$ranked_cards = [];
foreach ($candidates as $c) {
    $cid = (int)$c['id'];
    $ai_info = $scores_by_id[$cid] ?? [
        'compatibilityScore' => 82,
        'confidence' => 'medium',
        'reasons' => ['Meaningful compatibility', 'Aligned intentions'],
        'potentialDifferences' => []
    ];

    $age = calculate_age($c['birthdate']);
    $first_name = explode(' ', $c['full_name'])[0];

    // Privacy mask check
    $privacy = json_decode($c['privacy_json'] ?? '{}', true) ?: [];

    $ranked_cards[] = [
        'id' => $c['id'],
        'full_name' => $c['full_name'],
        'first_name' => $first_name,
        'age' => $age,
        'avatar_url' => $c['avatar_url'],
        'photos' => json_decode($c['photos'] ?? '[]', true) ?: [],
        'location_city' => $c['location_city'],
        'state' => $c['state'] ?? '',
        'occupation' => $c['occupation'] ?: ($c['occupation_type'] ?? 'Member'),
        'education' => $c['highest_qualification'] ?? $c['degree'] ?? 'Graduate',
        'height_cm' => $c['height_cm'] ?? 165,
        'marital_status' => $c['saathi_marital_status'] ?? 'Never Married',
        'diet' => $c['diet'] ?? 'Vegetarian',
        'marriage_timeline' => $c['marriage_timeline'] ?? 'Within 1 Year',
        'religion' => (!empty($privacy['show_religion']) && !empty($c['religion'])) ? $c['religion'] : null,
        'compatibilityScore' => $ai_info['compatibilityScore'],
        'confidence' => $ai_info['confidence'],
        'reasons' => $ai_info['reasons'],
        'potentialDifferences' => $ai_info['potentialDifferences'] ?? []
    ];
}

// Sort cards by compatibility score descending
usort($ranked_cards, function($a, $b) {
    return $b['compatibilityScore'] <=> $a['compatibilityScore'];
});

json_response([
    'success' => true,
    'count' => count($ranked_cards),
    'recommendations' => $ranked_cards
]);
