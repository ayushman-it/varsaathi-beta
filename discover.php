<?php
// discover.php - Discover Feed (Matching Image 3 Mockup)
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'discover';
$current_user_id = get_current_user_id();

// Fetch candidates for discover feed
$stmt = $pdo->prepare("
    SELECT u.*, sp.height_cm, sp.highest_qualification, sp.occupation_type, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
    FROM users u
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
    WHERE u.id != :u
    ORDER BY u.id DESC
    LIMIT 20
");
$stmt->execute([':u' => $current_user_id]);
$candidates = $stmt->fetchAll();

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header">
  <div class="header-title" style="font-size: 1.6rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.5px; font-family: system-ui, -apple-system, sans-serif;">
    Discover
  </div>

  <div class="header-actions">
    <a href="index.php" class="icon-btn" title="Cards View" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA;">
      <i class="fa-solid fa-layer-group" style="font-size: 0.95rem;"></i>
    </a>
    <button class="icon-btn" id="openSidebarBtn" title="Menu" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA;">
      <i class="fa-solid fa-ellipsis-vertical" style="font-size: 0.95rem;"></i>
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
      Chouasiya Samaj
    </button>
    <button class="discover-filter-pill" style="background: #FFFFFF; color: #636366; font-size: 0.78rem; font-weight: 600; padding: 6px 14px; border-radius: 16px; border: 1px solid #E5E5EA; flex-shrink: 0;">
      Nearby
    </button>
  </div>

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
