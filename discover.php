<?php
// discover.php - Discover Feed (Matching Image 3 Mockup)
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'discover';
$current_user_id = get_current_user_id();

$pref_stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = :id");
$pref_stmt->execute([':id' => $current_user_id]);
$pref = $pref_stmt->fetch() ?: [
    'min_age' => 18,
    'max_age' => 60,
    'max_distance' => 300,
    'gender_preference' => 'everyone',
    'marital_status' => 'Any',
    'religion_community' => 'Any',
    'education' => 'Any',
    'occupation' => 'Any'
];

// Fetch candidates for discover feed with filter preference support
$where_clauses = ["u.id != :u"];
$params = [':u' => $current_user_id];

if (!empty($pref['gender_preference']) && $pref['gender_preference'] !== 'everyone') {
    $where_clauses[] = "u.gender = :gen";
    $params[':gen'] = $pref['gender_preference'];
}

if (!empty($pref['min_age']) && !empty($pref['max_age'])) {
    $where_clauses[] = "TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) BETWEEN :min_age AND :max_age";
    $params[':min_age'] = (int)$pref['min_age'];
    $params[':max_age'] = (int)$pref['max_age'];
}

if (!empty($pref['marital_status']) && $pref['marital_status'] !== 'Any') {
    $where_clauses[] = "(sp.marital_status LIKE :m_status OR u.marital_status LIKE :m_status)";
    $params[':m_status'] = '%' . $pref['marital_status'] . '%';
}

if (!empty($pref['religion_community']) && $pref['religion_community'] !== 'Any') {
    $where_clauses[] = "(sp.caste_community LIKE :rel OR sp.religion LIKE :rel OR u.bio LIKE :rel)";
    $params[':rel'] = '%' . $pref['religion_community'] . '%';
}

if (!empty($pref['education']) && $pref['education'] !== 'Any') {
    $where_clauses[] = "(sp.highest_qualification LIKE :edu OR sp.degree LIKE :edu)";
    $params[':edu'] = '%' . $pref['education'] . '%';
}

if (!empty($pref['occupation']) && $pref['occupation'] !== 'Any') {
    $where_clauses[] = "(sp.occupation_type LIKE :occ OR u.occupation LIKE :occ)";
    $params[':occ'] = '%' . $pref['occupation'] . '%';
}

$where_sql = implode(' AND ', $where_clauses);

$stmt = $pdo->prepare("
    SELECT u.*, sp.height_cm, sp.highest_qualification, sp.occupation_type, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
    FROM users u
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
    WHERE $where_sql
    ORDER BY u.id DESC
    LIMIT 20
");
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Graceful fallback to all candidates if strict filter returns 0 results
if (empty($candidates)) {
    $fallback_stmt = $pdo->prepare("
        SELECT u.*, sp.height_cm, sp.highest_qualification, sp.occupation_type, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
        FROM users u
        LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
        WHERE u.id != :u
        ORDER BY u.id DESC
        LIMIT 20
    ");
    $fallback_stmt->execute([':u' => $current_user_id]);
    $candidates = $fallback_stmt->fetchAll();
}

// Groq AI Integration for Top Recommendations Section
require_once __DIR__ . '/config/groq.php';
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$u_stmt->execute([':u' => $current_user_id]);
$current_user_profile = $u_stmt->fetch() ?: [];
$current_saathi = get_saathi_profile($current_user_id);
$ai_recommendations = rank_candidates_with_groq($current_user_profile, $current_saathi, array_slice($candidates, 0, 8));

// Strictly filter AI recommendations to keep only 95%+ match scores
$ai_recommendations = array_values(array_filter($ai_recommendations, function($item) {
    $score = (int)($item['compatibilityScore'] ?? 0);
    return $score >= 95 && !empty($item['candidate']);
}));

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px;">
  <div class="header-title" style="font-size: 1.6rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.5px; font-family: system-ui, -apple-system, sans-serif;">
    Discover
  </div>

  <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
    <button class="icon-btn" id="filterBtn" title="Filter Matches" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E; display: flex; align-items: center; justify-content: center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1C1C1E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="21" x2="4" y2="14"></line>
        <line x1="4" y1="10" x2="4" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12" y2="3"></line>
        <line x1="20" y1="21" x2="20" y2="16"></line>
        <line x1="20" y1="12" x2="20" y2="3"></line>
        <line x1="1" y1="14" x2="7" y2="14"></line>
        <line x1="9" y1="8" x2="15" y2="8"></line>
        <line x1="17" y1="16" x2="23" y2="16"></line>
      </svg>
    </button>
    <button class="icon-btn" id="openSidebarBtn" title="Menu" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E;">
      <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
    </button>
  </div>
</header>

<main class="app-body" style="padding: 12px 16px 90px 16px; background: #FAF9FC;">

  <!-- Discover Category Filters Bar -->
  <div style="display: flex; gap: 8px; margin-bottom: 14px; overflow-x: auto; scrollbar-width: none;">
    <button class="discover-filter-pill active" style="background: #1C1C1E; color: #FFF; font-size: 0.78rem; font-weight: 700; padding: 6px 14px; border-radius: 16px; border: none; flex-shrink: 0;">
      All Profiles
    </button>
    <button class="discover-filter-pill" style="background: #FFFFFF; color: #636366; font-size: 0.78rem; font-weight: 600; padding: 6px 14px; border-radius: 16px; border: 1px solid #E5E5EA; flex-shrink: 0;">
      Online Now
    </button>
    <button class="discover-filter-pill" style="background: #FFFFFF; color: #636366; font-size: 0.78rem; font-weight: 600; padding: 6px 14px; border-radius: 16px; border: 1px solid #E5E5EA; flex-shrink: 0;">
      Chourasiya Samaj
    </button>
    <button class="discover-filter-pill" style="background: #FFFFFF; color: #636366; font-size: 0.78rem; font-weight: 600; padding: 6px 14px; border-radius: 16px; border: 1px solid #E5E5EA; flex-shrink: 0;">
      Nearby
    </button>
  </div>

  <!-- AI Recommendations Top Section (Only >= 95% AI Match) -->
  <?php if (!empty($ai_recommendations)): ?>
    <div style="margin-bottom: 22px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div style="font-weight: 800; font-size: 1.05rem; color: #1C1C1E; font-family: system-ui, -apple-system, sans-serif;">
          AI Recommendations
        </div>
        <span style="font-size: 0.72rem; font-weight: 700; color: #C31F3A; background: #FFF0F4; padding: 3px 10px; border-radius: 12px; border: 1px solid #FFE0E6;">
          95%+ Match
        </span>
      </div>

      <!-- Horizontal Carousel Container for AI Recommendations -->
      <div class="ai-carousel-track" style="display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; padding: 4px 2px 12px 2px;">
        <?php foreach ($ai_recommendations as $ai_rec): 
          $rec_cand = $ai_rec['candidate'] ?? null;
          if (!$rec_cand) continue;
          $r_name = ucwords(strtolower(trim($rec_cand['full_name'])));
          $r_age = calculate_age($rec_cand['birthdate'] ?? '2000-01-01');
          $r_img = get_valid_avatar_url($rec_cand['avatar_url'] ?? '');
          $r_city = !empty($rec_cand['location_city']) && strpos(strtolower($rec_cand['location_city']), 'san francisco') === false ? $rec_cand['location_city'] : 'India';
          $r_score = (int)($ai_rec['compatibilityScore'] ?? 95);
          $r_reasons = $ai_rec['reasons'] ?? ['High Compatibility Match'];
        ?>
          <div style="width: 255px; flex-shrink: 0; background: #FFFFFF; border-radius: 20px; border: 1px solid #E5E5EA; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,0.05); scroll-snap-align: start; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
            
            <div style="position: relative; width: 100%; height: 165px; overflow: hidden; background: #F2F2F7;">
              <img src="<?= htmlspecialchars($r_img) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
              <div style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #E02847, #C31F3A); color: #FFF; font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 12px; box-shadow: 0 3px 10px rgba(195,31,58,0.35); z-index: 2;">
                <?= $r_score ?>% AI Match
              </div>
            </div>

            <div style="padding: 12px 14px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; box-sizing: border-box;">
              <div>
                <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>" style="font-weight: 700; font-size: 0.94rem; color: #1C1C1E; text-decoration: none; display: block; margin-bottom: 2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: 100%; box-sizing: border-box;">
                  <?= htmlspecialchars($r_name) ?>, <?= $r_age ?>
                </a>
                <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 500; margin-bottom: 8px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                  <i class="fa-solid fa-location-dot" style="font-size: 0.68rem; margin-right: 3px;"></i> <?= htmlspecialchars($r_city) ?>
                </div>
                <div style="font-size: 0.73rem; color: #3A3A3C; font-weight: 500; line-height: 1.35; background: #F8F8FA; border: 1px solid #F0F0F5; padding: 8px 10px; border-radius: 10px; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; box-sizing: border-box;">
                  <?= htmlspecialchars($r_reasons[0] ?? 'Top recommendation') ?>
                </div>
              </div>

              <button type="button" onclick="handleCandidateCardChat(<?= $rec_cand['id'] ?>, '<?= htmlspecialchars(addslashes($r_name)) ?>')" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 14px; background: #1C1C1E; color: #FFFFFF; font-weight: 700; font-size: 0.82rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <i class="fa-solid fa-comments" style="font-size: 0.82rem;"></i> Start Chat
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Discover Candidate Feed List (Matching UI Mockup) -->
  <div style="display: flex; flex-direction: column; gap: 14px;">
    <?php foreach ($candidates as $cand): 
      $c_name = ucwords(strtolower(trim($cand['full_name'])));
      $c_age = calculate_age($cand['birthdate'] ?? '2000-01-01');
      $c_avatar = get_valid_avatar_url($cand['avatar_url'] ?? '');
      $c_occ = !empty($cand['occupation']) ? $cand['occupation'] : ($cand['highest_qualification'] ?? 'Member');
      $c_ht = !empty($cand['height_cm']) ? round($cand['height_cm'] / 30.48, 1) . "′" : "5′ 5″";
      $c_city = !empty($cand['location_city']) && strpos(strtolower($cand['location_city']), 'san francisco') === false ? $cand['location_city'] . ', India' : 'India';
      
      $traits = get_candidate_traits($cand);
      $photos_raw = json_decode($cand['photos'] ?? '[]', true) ?: [];
      if (!in_array($c_avatar, $photos_raw)) { array_unshift($photos_raw, $c_avatar); }
    ?>
      <div class="discover-feed-card" style="background: #FFFFFF; border-radius: 22px; padding: 14px 16px; border: 1px solid #F0F0F5; box-shadow: 0 6px 20px rgba(0,0,0,0.03); position: relative;">
        
        <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
          
          <!-- Left Info Block -->
          <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; color: inherit; flex: 1;">
            <div>
              <h3 style="font-size: 0.98rem; font-weight: 600; color: #1C1C1E; margin-bottom: 2px; line-height: 1.25;"><?= htmlspecialchars($c_name) ?></h3>
              <div style="font-size: 0.76rem; color: #8E8E93; font-weight: 400; margin-bottom: 8px;"><?= htmlspecialchars($c_occ) ?></div>
              
              <div style="display: flex; gap: 14px; margin-bottom: 8px;">
                <div>
                  <div style="font-size: 0.65rem; color: #8E8E93; text-transform: uppercase; font-weight: 500; margin-bottom: 1px;">Age</div>
                  <div style="font-size: 0.84rem; color: #C31F3A; font-weight: 600;"><?= $c_age ?> Years</div>
                </div>
                <div style="border-left: 1px solid #E5E5EA; padding-left: 14px;">
                  <div style="font-size: 0.65rem; color: #8E8E93; text-transform: uppercase; font-weight: 500; margin-bottom: 1px;">Height</div>
                  <div style="font-size: 0.84rem; color: #C31F3A; font-weight: 600;"><?= $c_ht ?></div>
                </div>
              </div>

              <div style="font-size: 0.74rem; color: #636366; font-weight: 400; display: flex; align-items: center; gap: 4px;">
                <i class="fa-solid fa-location-dot" style="color: #8E8E93; font-size: 0.7rem;"></i> <?= htmlspecialchars($c_city) ?>
              </div>
            </div>
          </a>

          <!-- Right Photo Carousel Horizontal Strip -->
          <div class="photo-carousel-wrap" style="display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; width: 145px; flex-shrink: 0; padding-bottom: 2px;">
            <?php foreach ($photos_raw as $p_img): ?>
              <img src="<?= htmlspecialchars(get_valid_avatar_url($p_img)) ?>" style="width: 70px; height: 105px; border-radius: 16px; object-fit: cover; scroll-snap-align: start; flex-shrink: 0;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
            <?php endforeach; ?>
          </div>

        </div>

        <!-- Bottom Trait Pills Row & Actions -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; border-top: 1px solid #F2F2F7; padding-top: 8px;">
          
          <div style="display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; flex: 1; align-items: center;">
            <button class="icon-btn" onclick="toggleBookmark(this, <?= $cand['id'] ?>)" style="width: 30px; height: 30px; border-radius: 10px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #8E8E93; flex-shrink: 0;" title="Bookmark">
              <i class="fa-solid fa-bookmark" style="font-size: 0.75rem;"></i>
            </button>
            <?php foreach ($traits as $trait): ?>
              <span style="background: #F2F2F7; color: #636366; font-size: 0.7rem; font-weight: 500; padding: 4px 10px; border-radius: 10px; white-space: nowrap; flex-shrink: 0;"><?= htmlspecialchars($trait) ?></span>
            <?php endforeach; ?>
          </div>

          <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="background: #FFF0F4; color: #C31F3A; border-radius: 12px; padding: 5px 12px; font-weight: 600; font-size: 0.72rem; text-decoration: none; flex-shrink: 0;">
            View Profile
          </a>

        </div>

      </div>
    <?php endforeach; ?>
  </div>


</main>

<script>
function toggleBookmark(btn, targetId) {
  btn.style.color = '#C31F3A';
  btn.style.background = '#FFF0F4';
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'favorite', target_id: targetId })
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
