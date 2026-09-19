<?php
// discover.php - Discover Feed (Wide Container & Uncompressed Spacing)
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

// Helper to sanitize corrupted string names
function sanitize_candidate_name($name) {
    $clean = preg_replace('/[^\p{L}\p{N}\s\.\-\']/u', '', (string)$name);
    $clean = trim($clean);
    return !empty($clean) ? ucwords(strtolower($clean)) : 'Saathi Member';
}

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<style>
  html, body {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    height: auto !important;
    min-height: 100vh !important;
    background-color: #FAF9FC !important;
  }
  body {
    display: block !important;
    padding: 0 !important;
  }
  .app-container {
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    min-height: 100vh !important;
    max-height: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    border: none !important;
    overflow: visible !important;
  }
</style>

<<!-- Discover Page Header (Ref: media_1789806610870.png) -->
<header style="padding: 16px 20px 8px 20px; background: #FAF9FC; display: flex; align-items: flex-start; justify-content: space-between; max-width: 820px; margin: 0 auto;">
  <div>
    <h1 style="font-size: 2.2rem; font-weight: 900; color: #111111; margin: 0; line-height: 1.1; letter-spacing: -0.5px; font-family: system-ui, -apple-system, sans-serif;">Discover</h1>
    <div style="font-size: 0.9rem; color: #777777; font-weight: 500; margin-top: 4px;">Find meaningful connections</div>
  </div>

  <div style="display: flex; align-items: center; gap: 10px;">
    <!-- Filters Button -->
    <button type="button" id="filterBtn" title="Filter Matches" style="width: 44px; height: 44px; border-radius: 50%; background: #FFFFFF; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #111111; cursor: pointer;">
      <i class="fa-solid fa-sliders" style="font-size: 0.95rem;"></i>
      <span style="font-size: 0.58rem; font-weight: 700; color: #555555; margin-top: 1px;">Filters</span>
    </button>
    <!-- Sort Button -->
    <button type="button" id="openSidebarBtn" title="Sort Options" style="width: 44px; height: 44px; border-radius: 50%; background: #FFFFFF; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #111111; cursor: pointer;">
      <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
      <span style="font-size: 0.58rem; font-weight: 700; color: #555555; margin-top: 1px;">Sort</span>
    </button>
  </div>
</header>

<!-- Wide Container (Full Width & Uncompressed Spacing) -->
<div class="discover-screen-container" style="width: 100%; max-width: 820px; margin: 0 auto; min-height: auto; padding-bottom: 100px; background: #FAF9FC;">

  <!-- 1. Category Filter Tabs Bar -->
  <div style="display: flex; gap: 8px; padding: 10px 20px; background: #FAF9FC; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; margin-bottom: 12px;">
    <button style="background: #FDF0F3; color: #E02847; font-size: 0.82rem; font-weight: 700; padding: 8px 18px; border-radius: 20px; border: 1px solid #FCE4EC; flex-shrink: 0; cursor: pointer;">
      For You
    </button>
    <button style="background: #FFFFFF; color: #666666; font-size: 0.82rem; font-weight: 600; padding: 8px 16px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      Nearby
    </button>
    <button style="background: #FFFFFF; color: #666666; font-size: 0.82rem; font-weight: 600; padding: 8px 16px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      New Members
    </button>
    <button style="background: #FFFFFF; color: #666666; font-size: 0.82rem; font-weight: 600; padding: 8px 16px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      Verified
    </button>
  </div>

  <main style="padding: 0 16px;">

    <!-- 2. AI Recommendations Section Box & Carousel -->
    <?php if (!empty($ai_recommendations)): ?>
      <div style="background: #FDF0F3; border-radius: 20px; padding: 14px 16px; margin-bottom: 20px; border: 1px solid #FDE8EE;">
        
        <!-- Section Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-sparkles" style="font-size: 1.2rem; color: #E02847;"></i>
            <div>
              <div style="font-weight: 900; font-size: 1.02rem; color: #111111; font-family: system-ui, -apple-system, sans-serif;">AI Recommendations</div>
              <div style="font-size: 0.76rem; color: #666666; font-weight: 500;">People you're most likely to connect with</div>
            </div>
          </div>
          <span style="font-size: 0.74rem; font-weight: 700; color: #E02847; background: #FFFFFF; padding: 5px 12px; border-radius: 14px; border: 1px solid #FCE4EC; display: flex; align-items: center; gap: 4px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(224, 40, 71, 0.08);">
            95%+ Match <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
          </span>
        </div>

        <!-- Carousel Cards Track -->
        <div class="ai-carousel-track" style="display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; padding: 2px 0 6px 0;">
          <?php foreach ($ai_recommendations as $ai_rec): 
            $rec_cand = $ai_rec['candidate'] ?? null;
            if (!$rec_cand) continue;
            $r_name = sanitize_candidate_name($rec_cand['full_name'] ?? '');
            $r_age = calculate_age((string)($rec_cand['birthdate'] ?? '2000-01-01'));
            $r_img = get_valid_avatar_url((string)($rec_cand['avatar_url'] ?? ''));
            $r_occ = !empty($rec_cand['occupation']) ? $rec_cand['occupation'] : ($rec_cand['highest_qualification'] ?? 'Working Professional');
            $r_city = !empty($rec_cand['location_city']) && strpos(strtolower($rec_cand['location_city']), 'san francisco') === false ? $rec_cand['location_city'] : 'India';
            $r_score = (int)($ai_rec['compatibilityScore'] ?? 97);
          ?>
            <div style="width: 210px; min-width: 210px; flex-shrink: 0; background: #FFFFFF; border-radius: 20px; border: 1px solid #F0F0F5; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
              
              <!-- Card Top Photo Container -->
              <div style="position: relative; width: 100%; height: 200px; overflow: hidden; background: #F8F8FA;">
                <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>">
                  <img src="<?= htmlspecialchars($r_img) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center top;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
                </a>
                
                <!-- Match Badge -->
                <div style="position: absolute; top: 8px; right: 8px; background: rgba(255, 255, 255, 0.94); color: #E02847; font-size: 0.68rem; font-weight: 800; padding: 3px 8px; border-radius: 12px; backdrop-filter: blur(4px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 4px;">
                  <i class="fa-solid fa-heart" style="font-size: 0.6rem; color: #E02847;"></i> <?= $r_score ?>% Match
                </div>

                <!-- Verified Badge -->
                <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0, 0, 0, 0.65); color: #FFFFFF; font-size: 0.62rem; font-weight: 600; padding: 3px 8px; border-radius: 8px; backdrop-filter: blur(3px); display: flex; align-items: center; gap: 4px;">
                  <i class="fa-solid fa-circle-check" style="color: #FFF; font-size: 0.6rem;"></i> Verified
                </div>
              </div>

              <!-- Card Body Content -->
              <div style="padding: 10px 12px 12px 12px; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                <div style="margin-bottom: 8px;">
                  <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>" style="font-weight: 900; font-size: 0.9rem; color: #111111; text-decoration: none; display: block; margin-bottom: 3px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <?= htmlspecialchars($r_name) ?>, <?= $r_age ?>
                  </a>
                  <div style="font-size: 0.74rem; color: #666666; font-weight: 500; margin-bottom: 2px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-briefcase" style="font-size: 0.7rem; color: #888888;"></i> <?= htmlspecialchars($r_occ) ?>
                  </div>
                  <div style="font-size: 0.74rem; color: #888888; font-weight: 500; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-location-dot" style="font-size: 0.7rem; color: #888888;"></i> <?= htmlspecialchars($r_city) ?>
                  </div>
                </div>

                <!-- Small Buttons Row -->
                <div style="display: flex; gap: 6px; align-items: center;">
                  <button type="button" onclick="toggleBookmark(this, <?= $rec_cand['id'] ?>)" style="width: 36px; height: 36px; border-radius: 12px; background: #FDF0F3; border: 1px solid #FCE4EC; color: #E02847; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;" title="Like Candidate">
                    <i class="fa-regular fa-heart" style="font-size: 0.85rem;"></i>
                  </button>
                  <a href="chat.php?user_id=<?= $rec_cand['id'] ?>" style="flex: 1; height: 36px; border-radius: 12px; background: linear-gradient(135deg, #E02847 0%, #C31F3A 100%); color: #FFFFFF; font-weight: 700; font-size: 0.76rem; border: none; text-decoration: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; white-space: nowrap; box-shadow: 0 3px 10px rgba(224, 40, 71, 0.25);">
                    <i class="fa-solid fa-comment-dots" style="font-size: 0.76rem;"></i> Start Chat
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endif; ?>

    <!-- 3. Discover Candidate Feed List -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($candidates as $cand): 
        $c_name = sanitize_candidate_name($cand['full_name'] ?? '');
        $c_age = calculate_age((string)($cand['birthdate'] ?? '2000-01-01'));
        $c_avatar = get_valid_avatar_url((string)($cand['avatar_url'] ?? ''));
        $c_occ = !empty($cand['occupation']) ? $cand['occupation'] : ($cand['highest_qualification'] ?? 'Working Professional');
        $c_ht = !empty($cand['height_cm']) ? round($cand['height_cm'] / 30.48, 1) . "′" : "5.7′";
        $c_city = !empty($cand['location_city']) && strpos(strtolower($cand['location_city']), 'san francisco') === false ? $cand['location_city'] : 'India';
        
        $traits = get_candidate_traits($cand);
        $photos_raw = json_decode((string)($cand['photos'] ?? '[]'), true) ?: [];
        if (!in_array($c_avatar, $photos_raw)) { array_unshift($photos_raw, $c_avatar); }
        $cand_bio = !empty($cand['bio']) ? $cand['bio'] : "Passionate about technology, travel and good conversations. Looking for someone genuine.";
      ?>
        <div style="background: #FFFFFF; border-radius: 24px; padding: 18px; border: 1px solid #F0F0F5; box-shadow: 0 4px 20px rgba(0,0,0,0.03); position: relative;">
          
          <!-- Top Row: Info & Photos Stack -->
          <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
            
            <!-- Left Candidate Main Metadata -->
            <div style="flex: 1; min-width: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; color: inherit;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                  <h3 style="font-size: 1.15rem; font-weight: 900; color: #111111; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_name) ?></h3>
                  <i class="fa-solid fa-circle-check" style="color: #3B82F6; font-size: 0.95rem;" title="Verified Profile"></i>
                </div>
                <div style="font-size: 0.82rem; color: #666666; font-weight: 500; margin-bottom: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_occ) ?></div>
                <div style="font-size: 0.8rem; color: #888888; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                  <i class="fa-solid fa-location-dot" style="color: #E02847; font-size: 0.75rem;"></i> <?= htmlspecialchars($c_city) ?>
                </div>
              </a>
            </div>

            <!-- Right Photo Gallery Thumbnails Stack -->
            <div style="display: flex; gap: 8px; flex-shrink: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none;">
                <img src="<?= htmlspecialchars($c_avatar) ?>" style="width: 85px; height: 110px; border-radius: 16px; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
              </a>
              <?php if (count($photos_raw) > 1): ?>
                <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="width: 75px; height: 110px; border-radius: 16px; background: #FDF0F3; border: 1px solid #FCE4EC; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #E02847; text-decoration: none;">
                  <i class="fa-solid fa-image" style="font-size: 1.1rem; margin-bottom: 2px;"></i>
                  <span style="font-size: 0.82rem; font-weight: 900;">+<?= count($photos_raw) - 1 ?></span>
                  <span style="font-size: 0.58rem; font-weight: 600; color: #888888;">More Photos</span>
                </a>
              <?php endif; ?>
            </div>

          </div>

          <!-- Stats Chips Row -->
          <div style="display: flex; gap: 10px; margin-top: 8px; margin-bottom: 12px;">
            <div style="background: #FDF0F3; border-radius: 14px; padding: 8px 12px; display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
              <div style="width: 28px; height: 28px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #E02847; font-size: 0.75rem; flex-shrink: 0;">
                <i class="fa-solid fa-cake-candles"></i>
              </div>
              <div style="min-width: 0;">
                <div style="font-size: 0.58rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">AGE</div>
                <div style="font-size: 0.82rem; color: #111111; font-weight: 800; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= $c_age ?> Years</div>
              </div>
            </div>

            <div style="background: #FDF0F3; border-radius: 14px; padding: 8px 12px; display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
              <div style="width: 28px; height: 28px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #E02847; font-size: 0.75rem; flex-shrink: 0;">
                <i class="fa-solid fa-user"></i>
              </div>
              <div style="min-width: 0;">
                <div style="font-size: 0.58rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">HEIGHT</div>
                <div style="font-size: 0.82rem; color: #111111; font-weight: 800; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= $c_ht ?></div>
              </div>
            </div>
          </div>

          <!-- Traits Pills Row -->
          <div style="display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; margin-bottom: 12px; padding: 2px 0;">
            <?php foreach ($traits as $trait): ?>
              <span style="background: #F8F8FA; border: 1px solid #F0F0F5; color: #444444; font-size: 0.76rem; font-weight: 600; padding: 6px 14px; border-radius: 14px; white-space: nowrap; flex-shrink: 0;"><?= htmlspecialchars($trait) ?></span>
            <?php endforeach; ?>
          </div>

          <!-- Bio Quote Snippet with Arrow -->
          <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid #F2F2F7; padding-top: 10px; margin-bottom: 14px; color: inherit;">
            <div style="font-size: 0.8rem; color: #555555; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;">
              "<?= htmlspecialchars($cand_bio) ?>"
            </div>
            <i class="fa-solid fa-chevron-right" style="color: #999999; font-size: 0.75rem;"></i>
          </a>

          <!-- Bottom Action Buttons -->
          <div style="display: flex; gap: 10px;">
            <button type="button" onclick="toggleBookmark(this, <?= $cand['id'] ?>)" class="btn-action-outline" style="height: 40px; font-size: 0.86rem; margin: 0;">
              <i class="fa-regular fa-heart" style="color: #E02847;"></i> Like
            </button>
            <a href="chat.php?user_id=<?= $cand['id'] ?>" class="btn-action-filled" style="height: 40px; font-size: 0.86rem; margin: 0;">
              <i class="fa-solid fa-comment-dots"></i> Start Chat
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

  </main>

</div>

<script>
function toggleBookmark(btn, targetId) {
  const icon = btn.querySelector('i');
  if (icon) {
    icon.className = 'fa-solid fa-heart';
  }
  btn.style.color = '#E02847';
  btn.style.background = '#FDF0F3';
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
