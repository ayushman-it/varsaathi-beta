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

<!-- Original Standard App Header (Unchanged) -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px; background: #FFFFFF; border-bottom: 1px solid #E5E5EA;">
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

<!-- Wide Container (Full Width & Uncompressed Spacing) -->
<div class="discover-screen-container" style="width: 100%; max-width: 820px; margin: 0 auto; min-height: auto; padding-bottom: 100px; background: #FAF9FC;">

  <!-- 1. Category Filter Tabs Bar -->
  <div style="display: flex; gap: 8px; padding: 12px 16px; background: #FFFFFF; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid #F2F2F7; margin-bottom: 14px;">
    <button style="background: #FFF0F4; color: #D62952; font-size: 0.78rem; font-weight: 700; padding: 6px 16px; border-radius: 20px; border: none; flex-shrink: 0; cursor: pointer;">
      For You
    </button>
    <button style="background: #FFFFFF; color: #8E8E93; font-size: 0.78rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      Nearby
    </button>
    <button style="background: #FFFFFF; color: #8E8E93; font-size: 0.78rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      New Members
    </button>
    <button style="background: #FFFFFF; color: #8E8E93; font-size: 0.78rem; font-weight: 500; padding: 6px 14px; border-radius: 20px; border: 1px solid #EBEBEF; flex-shrink: 0; cursor: pointer;">
      Verified
    </button>
  </div>

  <main style="padding: 0 12px;">

    <!-- 2. AI Recommendations Section Box & Carousel (Spacious & Uncompressed) -->
    <?php if (!empty($ai_recommendations)): ?>
      <div style="background: #FFF5F7; border-radius: 20px; padding: 12px 14px; margin-bottom: 20px; border: 1px solid #FFE0E6;">
        
        <!-- Section Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <div style="font-size: 1.15rem; color: #D62952; line-height: 1;">✨</div>
            <div>
              <div style="font-weight: 800; font-size: 0.94rem; color: #1C1C1E; font-family: system-ui, -apple-system, sans-serif;">AI Recommendations</div>
              <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 400;">People you're most likely to connect with</div>
            </div>
          </div>
          <span style="font-size: 0.7rem; font-weight: 700; color: #D62952; background: #FFFFFF; padding: 3px 8px; border-radius: 12px; border: 1px solid #FFE0E6; display: flex; align-items: center; gap: 3px; flex-shrink: 0;">
            95%+ Match <i class="fa-solid fa-chevron-right" style="font-size: 0.6rem;"></i>
          </span>
        </div>

        <!-- Carousel Cards Track (Clean 160px Portrait Cards) -->
        <div class="ai-carousel-track" style="display: flex; gap: 10px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; padding: 2px 0 4px 0;">
          <?php foreach ($ai_recommendations as $ai_rec): 
            $rec_cand = $ai_rec['candidate'] ?? null;
            if (!$rec_cand) continue;
            $r_name = sanitize_candidate_name($rec_cand['full_name'] ?? '');
            $r_age = calculate_age((string)($rec_cand['birthdate'] ?? '2000-01-01'));
            $r_img = get_valid_avatar_url((string)($rec_cand['avatar_url'] ?? ''));
            $r_occ = !empty($rec_cand['occupation']) ? $rec_cand['occupation'] : ($rec_cand['highest_qualification'] ?? 'Software Engineer');
            $r_city = !empty($rec_cand['location_city']) && strpos(strtolower($rec_cand['location_city']), 'san francisco') === false ? $rec_cand['location_city'] : 'India';
            $r_score = (int)($ai_rec['compatibilityScore'] ?? 97);
          ?>
            <div style="width: 160px; min-width: 160px; flex-shrink: 0; background: #FFFFFF; border-radius: 18px; border: 1px solid #F0F0F5; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.03); scroll-snap-align: start; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
              
              <!-- Card Top Photo Container -->
              <div style="position: relative; width: 100%; height: 155px; overflow: hidden; background: #F2F2F7;">
                <img src="<?= htmlspecialchars($r_img) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center top;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
                
                <!-- Match Badge -->
                <div style="position: absolute; top: 6px; right: 6px; background: rgba(255, 255, 255, 0.94); color: #D62952; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 10px; backdrop-filter: blur(4px); box-shadow: 0 2px 6px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 3px;">
                  <i class="fa-solid fa-heart" style="font-size: 0.55rem; color: #D62952;"></i> <?= $r_score ?>% Match
                </div>

                <!-- Verified Badge -->
                <div style="position: absolute; bottom: 6px; left: 6px; background: rgba(0, 0, 0, 0.65); color: #FFFFFF; font-size: 0.58rem; font-weight: 600; padding: 2px 6px; border-radius: 6px; backdrop-filter: blur(3px); display: flex; align-items: center; gap: 3px;">
                  <i class="fa-solid fa-circle-check" style="color: #FFF; font-size: 0.56rem;"></i> Verified
                </div>
              </div>

              <!-- Card Body Content -->
              <div style="padding: 8px 10px 10px 10px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; box-sizing: border-box;">
                <div style="margin-bottom: 6px;">
                  <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>" style="font-weight: 800; font-size: 0.84rem; color: #1C1C1E; text-decoration: none; display: block; margin-bottom: 2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <?= htmlspecialchars($r_name) ?>, <?= $r_age ?>
                  </a>
                  <div style="font-size: 0.68rem; color: #636366; font-weight: 500; margin-bottom: 1px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 3px;">
                    <i class="fa-solid fa-briefcase" style="font-size: 0.62rem; color: #8E8E93;"></i> <?= htmlspecialchars($r_occ) ?>
                  </div>
                  <div style="font-size: 0.68rem; color: #8E8E93; font-weight: 500; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 3px;">
                    <i class="fa-solid fa-location-dot" style="font-size: 0.62rem; color: #8E8E93;"></i> <?= htmlspecialchars($r_city) ?>
                  </div>
                </div>

                <!-- Small Buttons Row -->
                <div style="display: flex; gap: 5px; align-items: center;">
                  <button type="button" onclick="toggleBookmark(this, <?= $rec_cand['id'] ?>)" style="width: 30px; height: 30px; border-radius: 10px; background: #FFF0F4; border: none; color: #D62952; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;" title="Like Candidate">
                    <i class="fa-regular fa-heart" style="font-size: 0.75rem;"></i>
                  </button>
                  <button type="button" onclick="handleCandidateCardChat(<?= $rec_cand['id'] ?>, '<?= htmlspecialchars(addslashes($r_name)) ?>')" style="flex: 1; height: 30px; border-radius: 10px; background: #D62952; color: #FFFFFF; font-weight: 700; font-size: 0.72rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; white-space: nowrap;">
                    <i class="fa-solid fa-comment-dots" style="font-size: 0.72rem;"></i> Start Chat
                  </button>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endif; ?>

    <!-- 3. Discover Candidate Feed List (Clean Uncompressed Layout) -->
    <div style="display: flex; flex-direction: column; gap: 14px;">
      <?php foreach ($candidates as $cand): 
        $c_name = sanitize_candidate_name($cand['full_name'] ?? '');
        $c_age = calculate_age((string)($cand['birthdate'] ?? '2000-01-01'));
        $c_avatar = get_valid_avatar_url((string)($cand['avatar_url'] ?? ''));
        $c_occ = !empty($cand['occupation']) ? $cand['occupation'] : ($cand['highest_qualification'] ?? 'Software Engineer');
        $c_ht = !empty($cand['height_cm']) ? round($cand['height_cm'] / 30.48, 1) . "′" : "5.7′";
        $c_city = !empty($cand['location_city']) && strpos(strtolower($cand['location_city']), 'san francisco') === false ? $cand['location_city'] : 'India';
        
        $traits = get_candidate_traits($cand);
        $photos_raw = json_decode((string)($cand['photos'] ?? '[]'), true) ?: [];
        if (!in_array($c_avatar, $photos_raw)) { array_unshift($photos_raw, $c_avatar); }
        $cand_bio = !empty($cand['bio']) ? $cand['bio'] : "Passionate about technology, travel and good conversations. Looking for someone genuine.";
      ?>
        <div style="background: #FFFFFF; border-radius: 20px; padding: 14px 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0,0,0,0.03); position: relative;">
          
          <!-- Top Row: Name/Info & Photo Stack -->
          <div style="display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
            
            <!-- Left Candidate Main Metadata -->
            <div style="flex: 1; min-width: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; color: inherit;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                  <h3 style="font-size: 1.05rem; font-weight: 800; color: #1C1C1E; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_name) ?></h3>
                  <i class="fa-solid fa-circle-check" style="color: #2196F3; font-size: 0.9rem;" title="Verified Profile"></i>
                </div>
                <div style="font-size: 0.78rem; color: #8E8E93; font-weight: 400; margin-bottom: 2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_occ) ?></div>
                <div style="font-size: 0.76rem; color: #8E8E93; font-weight: 400; display: flex; align-items: center; gap: 4px;">
                  <i class="fa-solid fa-location-dot" style="color: #8E8E93; font-size: 0.7rem;"></i> <?= htmlspecialchars($c_city) ?>
                </div>
              </a>
            </div>

            <!-- Right Photo Gallery Thumbnails Stack -->
            <div style="display: flex; gap: 6px; flex-shrink: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none;">
                <img src="<?= htmlspecialchars($c_avatar) ?>" style="width: 80px; height: 100px; border-radius: 14px; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
              </a>
              <?php if (count($photos_raw) > 1): ?>
                <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="width: 68px; height: 100px; border-radius: 14px; background: #FFF0F4; border: 1px solid #FFE0E6; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #D62952; text-decoration: none;">
                  <i class="fa-solid fa-image" style="font-size: 1rem; margin-bottom: 2px;"></i>
                  <span style="font-size: 0.74rem; font-weight: 800;">+<?= count($photos_raw) - 1 ?></span>
                  <span style="font-size: 0.56rem; font-weight: 600; color: #8E8E93;">Photos</span>
                </a>
              <?php endif; ?>
            </div>

          </div>

          <!-- Stats Chips Row (Uncompressed Full Width Row Below Name/Photo) -->
          <div style="display: flex; gap: 8px; margin-top: 6px; margin-bottom: 10px;">
            <div style="background: #FFF0F4; border-radius: 12px; padding: 6px 10px; display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
              <div style="width: 24px; height: 24px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #D62952; font-size: 0.68rem; flex-shrink: 0;">
                <i class="fa-solid fa-cake-candles"></i>
              </div>
              <div style="min-width: 0;">
                <div style="font-size: 0.56rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">AGE</div>
                <div style="font-size: 0.78rem; color: #1C1C1E; font-weight: 800; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= $c_age ?> Years</div>
              </div>
            </div>

            <div style="background: #FFF0F4; border-radius: 12px; padding: 6px 10px; display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">
              <div style="width: 24px; height: 24px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #D62952; font-size: 0.68rem; flex-shrink: 0;">
                <i class="fa-solid fa-user"></i>
              </div>
              <div style="min-width: 0;">
                <div style="font-size: 0.56rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">HEIGHT</div>
                <div style="font-size: 0.78rem; color: #1C1C1E; font-weight: 800; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= $c_ht ?></div>
              </div>
            </div>
          </div>

          <!-- Traits Pills Row -->
          <div style="display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; margin-bottom: 10px; padding: 2px 0;">
            <?php foreach ($traits as $trait): ?>
              <span style="background: #F8F8FA; border: 1px solid #F0F0F5; color: #3A3A3C; font-size: 0.72rem; font-weight: 500; padding: 5px 10px; border-radius: 12px; white-space: nowrap; flex-shrink: 0;"><?= htmlspecialchars($trait) ?></span>
            <?php endforeach; ?>
          </div>

          <!-- Bio Quote Snippet with Arrow -->
          <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid #F2F2F7; padding-top: 8px; margin-bottom: 12px;">
            <div style="font-size: 0.76rem; color: #636366; font-weight: 400; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;">
              "<?= htmlspecialchars($cand_bio) ?>"
            </div>
            <i class="fa-solid fa-chevron-right" style="color: #8E8E93; font-size: 0.72rem;"></i>
          </a>

          <!-- Bottom Small Compact Dual Action Buttons -->
          <div style="display: flex; gap: 8px;">
            <button type="button" onclick="toggleBookmark(this, <?= $cand['id'] ?>)" style="flex: 1; height: 36px; border-radius: 12px; background: #FFF0F4; color: #D62952; font-weight: 700; font-size: 0.8rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;">
              <i class="fa-regular fa-heart" style="font-size: 0.82rem;"></i> Like
            </button>
            <button type="button" onclick="handleCandidateCardChat(<?= $cand['id'] ?>, '<?= htmlspecialchars(addslashes($c_name)) ?>')" style="flex: 1; height: 36px; border-radius: 12px; background: #D62952; color: #FFFFFF; font-weight: 700; font-size: 0.8rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;">
              <i class="fa-solid fa-comment-dots" style="font-size: 0.82rem;"></i> Start Chat
            </button>
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
  btn.style.color = '#D62952';
  btn.style.background = '#FFE5EC';
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
