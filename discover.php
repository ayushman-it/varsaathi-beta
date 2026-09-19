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

<!-- Discover App Screen Container -->
<div class="discover-screen-container" style="width: 100%; max-width: 580px; margin: 0 auto; background: #FAF9FC; min-height: 100vh; padding-bottom: 90px;">

  <!-- 1. Header Section (Matching Mockup) -->
  <header style="padding: 16px 16px 12px 16px; background: #FFFFFF; display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 1px solid #F2F2F7;">
    <div>
      <h1 style="font-size: 1.85rem; font-weight: 800; color: #1C1C1E; font-family: system-ui, -apple-system, sans-serif; letter-spacing: -0.5px; margin: 0 0 2px 0;">Discover</h1>
      <div style="font-size: 0.88rem; color: #8E8E93; font-weight: 400;">Find meaningful connections</div>
    </div>
    
    <div style="display: flex; gap: 12px; align-items: center;">
      <button id="filterBtn" style="background: none; border: none; cursor: pointer; text-align: center; padding: 0;">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: #F8F8FA; border: 1px solid #EBEBEF; display: flex; align-items: center; justify-content: center; margin: 0 auto 2px auto; color: #1C1C1E;">
          <i class="fa-solid fa-sliders" style="font-size: 0.95rem;"></i>
        </div>
        <span style="font-size: 0.65rem; color: #636366; font-weight: 500;">Filters</span>
      </button>
      <button id="openSidebarBtn" style="background: none; border: none; cursor: pointer; text-align: center; padding: 0;">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: #F8F8FA; border: 1px solid #EBEBEF; display: flex; align-items: center; justify-content: center; margin: 0 auto 2px auto; color: #1C1C1E;">
          <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
        </div>
        <span style="font-size: 0.65rem; color: #636366; font-weight: 500;">Sort</span>
      </button>
    </div>
  </header>

  <!-- 2. Category Filter Tabs Bar (Matching Mockup) -->
  <div style="display: flex; gap: 6px; padding: 10px 16px; background: #FFFFFF; overflow-x: auto; scrollbar-width: none; border-bottom: 1px solid #F2F2F7;">
    <button style="background: #FFF0F4; color: #D62952; font-size: 0.82rem; font-weight: 700; padding: 8px 18px; border-radius: 20px; border: none; flex-shrink: 0; cursor: pointer;">
      For You
    </button>
    <button style="background: transparent; color: #8E8E93; font-size: 0.82rem; font-weight: 500; padding: 8px 16px; border-radius: 20px; border: none; flex-shrink: 0; cursor: pointer;">
      Nearby
    </button>
    <button style="background: transparent; color: #8E8E93; font-size: 0.82rem; font-weight: 500; padding: 8px 16px; border-radius: 20px; border: none; flex-shrink: 0; cursor: pointer;">
      New Members
    </button>
    <button style="background: transparent; color: #8E8E93; font-size: 0.82rem; font-weight: 500; padding: 8px 16px; border-radius: 20px; border: none; flex-shrink: 0; cursor: pointer;">
      Verified
    </button>
  </div>

  <main style="padding: 14px 16px 20px 16px;">

    <!-- 3. AI Recommendations Section Box & Carousel (Matching Mockup) -->
    <?php if (!empty($ai_recommendations)): ?>
      <div style="background: #FFF5F7; border-radius: 22px; padding: 14px 16px; margin-bottom: 20px; border: 1px solid #FFE0E6;">
        
        <!-- Section Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <div style="font-size: 1.25rem; color: #D62952; line-height: 1;">✨</div>
            <div>
              <div style="font-weight: 800; font-size: 0.98rem; color: #1C1C1E; font-family: system-ui, -apple-system, sans-serif;">AI Recommendations</div>
              <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 400;">People you're most likely to connect with</div>
            </div>
          </div>
          <span style="font-size: 0.74rem; font-weight: 700; color: #D62952; background: #FFFFFF; padding: 4px 10px; border-radius: 14px; border: 1px solid #FFE0E6; display: flex; align-items: center; gap: 4px;">
            95%+ Match <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
          </span>
        </div>

        <!-- Carousel Cards Track -->
        <div class="ai-carousel-track" style="display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; scroll-snap-type: x mandatory; padding: 2px 2px 6px 2px;">
          <?php foreach ($ai_recommendations as $ai_rec): 
            $rec_cand = $ai_rec['candidate'] ?? null;
            if (!$rec_cand) continue;
            $r_name = ucwords(strtolower(trim((string)$rec_cand['full_name'])));
            $r_age = calculate_age((string)($rec_cand['birthdate'] ?? '2000-01-01'));
            $r_img = get_valid_avatar_url((string)($rec_cand['avatar_url'] ?? ''));
            $r_occ = !empty($rec_cand['occupation']) ? $rec_cand['occupation'] : ($rec_cand['highest_qualification'] ?? 'Software Engineer');
            $r_city = !empty($rec_cand['location_city']) && strpos(strtolower($rec_cand['location_city']), 'san francisco') === false ? $rec_cand['location_city'] : 'India';
            $r_score = (int)($ai_rec['compatibilityScore'] ?? 97);
          ?>
            <div style="width: 200px; flex-shrink: 0; background: #FFFFFF; border-radius: 20px; border: 1px solid #F0F0F5; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,0.04); scroll-snap-align: start; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
              
              <!-- Card Top Photo Container -->
              <div style="position: relative; width: 100%; height: 185px; overflow: hidden; background: #F2F2F7;">
                <img src="<?= htmlspecialchars($r_img) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: center top;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
                
                <!-- Match Badge -->
                <div style="position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.94); color: #D62952; font-size: 0.65rem; font-weight: 800; padding: 3px 8px; border-radius: 12px; backdrop-filter: blur(4px); box-shadow: 0 2px 6px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 3px;">
                  <i class="fa-solid fa-heart" style="font-size: 0.6rem; color: #D62952;"></i> <?= $r_score ?>% Match
                </div>

                <!-- Verified Badge -->
                <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0, 0, 0, 0.65); color: #FFFFFF; font-size: 0.62rem; font-weight: 600; padding: 3px 8px; border-radius: 8px; backdrop-filter: blur(3px); display: flex; align-items: center; gap: 4px;">
                  <i class="fa-solid fa-circle-check" style="color: #FFF; font-size: 0.62rem;"></i> Verified
                </div>
              </div>

              <!-- Card Body Content -->
              <div style="padding: 12px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; box-sizing: border-box;">
                <div style="margin-bottom: 10px;">
                  <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>" style="font-weight: 800; font-size: 0.94rem; color: #1C1C1E; text-decoration: none; display: block; margin-bottom: 3px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                    <?= htmlspecialchars($r_name) ?>, <?= $r_age ?>
                  </a>
                  <div style="font-size: 0.74rem; color: #636366; font-weight: 500; margin-bottom: 2px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-briefcase" style="font-size: 0.68rem; color: #8E8E93;"></i> <?= htmlspecialchars($r_occ) ?>
                  </div>
                  <div style="font-size: 0.74rem; color: #8E8E93; font-weight: 500; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-location-dot" style="font-size: 0.68rem; color: #8E8E93;"></i> <?= htmlspecialchars($r_city) ?>
                  </div>
                </div>

                <!-- Buttons Row -->
                <div style="display: flex; gap: 8px; align-items: center;">
                  <button type="button" onclick="toggleBookmark(this, <?= $rec_cand['id'] ?>)" style="width: 36px; height: 36px; border-radius: 12px; background: #FFF0F4; border: none; color: #D62952; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;" title="Like Candidate">
                    <i class="fa-regular fa-heart" style="font-size: 0.88rem;"></i>
                  </button>
                  <button type="button" onclick="handleCandidateCardChat(<?= $rec_cand['id'] ?>, '<?= htmlspecialchars(addslashes($r_name)) ?>')" style="flex: 1; height: 36px; border-radius: 12px; background: #D62952; color: #FFFFFF; font-weight: 700; font-size: 0.78rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; white-space: nowrap;">
                    <i class="fa-solid fa-comment-dots" style="font-size: 0.8rem;"></i> Start Chat
                  </button>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endif; ?>

    <!-- 4. Discover Candidate Feed List (Matching UI Mockup) -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
      <?php foreach ($candidates as $cand): 
        $c_name = ucwords(strtolower(trim((string)$cand['full_name'])));
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
        <div style="background: #FFFFFF; border-radius: 22px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 18px rgba(0,0,0,0.03); position: relative;">
          
          <!-- Top Row: Name/Info & Photo Stack -->
          <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
            
            <!-- Left Candidate Main Metadata -->
            <div style="flex: 1; min-width: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; color: inherit;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                  <h3 style="font-size: 1.15rem; font-weight: 800; color: #1C1C1E; margin: 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_name) ?></h3>
                  <i class="fa-solid fa-circle-check" style="color: #2196F3; font-size: 1rem;" title="Verified Profile"></i>
                </div>
                <div style="font-size: 0.82rem; color: #8E8E93; font-weight: 400; margin-bottom: 4px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c_occ) ?></div>
                <div style="font-size: 0.78rem; color: #8E8E93; font-weight: 400; display: flex; align-items: center; gap: 4px; margin-bottom: 12px;">
                  <i class="fa-solid fa-location-dot" style="color: #8E8E93; font-size: 0.74rem;"></i> <?= htmlspecialchars($c_city) ?>
                </div>
              </a>

              <!-- Stats Chips Row (Age & Height Boxes - Matching Mockup) -->
              <div style="display: flex; gap: 8px;">
                <div style="background: #FFF0F4; border-radius: 14px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; flex: 1;">
                  <div style="width: 28px; height: 28px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #D62952; font-size: 0.75rem; flex-shrink: 0;">
                    <i class="fa-solid fa-cake-candles"></i>
                  </div>
                  <div>
                    <div style="font-size: 0.6rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">AGE</div>
                    <div style="font-size: 0.82rem; color: #1C1C1E; font-weight: 800; white-space: nowrap;"><?= $c_age ?> Years</div>
                  </div>
                </div>

                <div style="background: #FFF0F4; border-radius: 14px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; flex: 1;">
                  <div style="width: 28px; height: 28px; border-radius: 50%; background: #FFFFFF; display: flex; align-items: center; justify-content: center; color: #D62952; font-size: 0.75rem; flex-shrink: 0;">
                    <i class="fa-solid fa-user"></i>
                  </div>
                  <div>
                    <div style="font-size: 0.6rem; color: #8E8E93; font-weight: 700; text-transform: uppercase;">HEIGHT</div>
                    <div style="font-size: 0.82rem; color: #1C1C1E; font-weight: 800; white-space: nowrap;"><?= $c_ht ?></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Photo Gallery Thumbnails Stack (Matching Mockup) -->
            <div style="display: flex; gap: 6px; flex-shrink: 0;">
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none;">
                <img src="<?= htmlspecialchars($c_avatar) ?>" style="width: 85px; height: 110px; border-radius: 16px; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
              </a>
              <?php if (count($photos_raw) > 1): ?>
                <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="width: 75px; height: 110px; border-radius: 16px; background: #FFF0F4; border: 1px solid #FFE0E6; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #D62952; text-decoration: none;">
                  <i class="fa-solid fa-image" style="font-size: 1.1rem; margin-bottom: 2px;"></i>
                  <span style="font-size: 0.78rem; font-weight: 800;">+<?= count($photos_raw) - 1 ?></span>
                  <span style="font-size: 0.58rem; font-weight: 600; color: #8E8E93;">More Photos</span>
                </a>
              <?php endif; ?>
            </div>

          </div>

          <!-- Traits Pills Row (Matching Mockup) -->
          <div style="display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; margin-bottom: 12px; padding: 2px 0;">
            <?php foreach ($traits as $trait): ?>
              <span style="background: #F8F8FA; border: 1px solid #F0F0F5; color: #3A3A3C; font-size: 0.74rem; font-weight: 500; padding: 6px 12px; border-radius: 14px; white-space: nowrap; flex-shrink: 0;"><?= htmlspecialchars($trait) ?></span>
            <?php endforeach; ?>
          </div>

          <!-- Bio Quote Snippet with Arrow (Matching Mockup) -->
          <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid #F2F2F7; padding-top: 10px; margin-bottom: 14px;">
            <div style="font-size: 0.78rem; color: #636366; font-weight: 400; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;">
              "<?= htmlspecialchars($cand_bio) ?>"
            </div>
            <i class="fa-solid fa-chevron-right" style="color: #8E8E93; font-size: 0.75rem;"></i>
          </a>

          <!-- Bottom 50/50 Dual Action Buttons (Matching Mockup) -->
          <div style="display: flex; gap: 10px;">
            <button type="button" onclick="toggleBookmark(this, <?= $cand['id'] ?>)" style="flex: 1; height: 44px; border-radius: 14px; background: #FFF0F4; color: #D62952; font-weight: 700; font-size: 0.88rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
              <i class="fa-regular fa-heart" style="font-size: 0.9rem;"></i> Like
            </button>
            <button type="button" onclick="handleCandidateCardChat(<?= $cand['id'] ?>, '<?= htmlspecialchars(addslashes($c_name)) ?>')" style="flex: 1; height: 44px; border-radius: 14px; background: #D62952; color: #FFFFFF; font-weight: 700; font-size: 0.88rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
              <i class="fa-solid fa-comment-dots" style="font-size: 0.9rem;"></i> Start Chat
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
