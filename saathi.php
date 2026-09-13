<?php
// saathi.php - Saathi Matrimonial Match Deck & AI Compatibility (Matching UI Mockup)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

$active_tab = 'saathi';
$current_user_id = get_current_user_id();

$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$u_stmt->execute([':u' => $current_user_id]);
$current_user = $u_stmt->fetch();

$saathi_profile = get_saathi_profile($current_user_id);

// Fetch candidates for Saathi Match card deck
$candidates_stmt = $pdo->prepare("
    SELECT u.*, sp.highest_qualification, sp.occupation_type, sp.annual_income, sp.religion, sp.caste_community
    FROM users u
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
    WHERE u.id != :cur_u
      AND u.id NOT IN (
          SELECT target_id FROM swipes WHERE swiper_id = :cur_u2
      )
    ORDER BY RAND()
    LIMIT 10
");
$candidates_stmt->execute([
    ':cur_u' => $current_user_id,
    ':cur_u2' => $current_user_id
]);
$match_candidates = $candidates_stmt->fetchAll();

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px;">
  <!-- Left: Logo -->
  <div class="header-logo-left" style="display: flex; align-items: center; flex: 1;">
    <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 155px; object-fit: contain; display: block;">
  </div>

  <!-- Center: For Choursaiyas -->
  <div class="header-center-title" style="flex: 2; text-align: center; display: flex; align-items: center; justify-content: center;">
    <span style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">For Choursaiyas</span>
  </div>

  <!-- Right: Hamburger Menu Button -->
  <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; flex: 1;">
    <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
      <i class="fa-solid fa-bars-staggered"></i>
    </button>
  </div>
</header>

<main class="app-body" style="padding: 14px 16px 90px 16px; background: #FAF9FC; display: flex; flex-direction: column; justify-content: center;">

  <?php if (empty($match_candidates)): ?>
    <!-- No Matches Fallback State -->
    <div style="background: #FFFFFF; border-radius: 28px; padding: 40px 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.06);">
      <div style="font-size: 3.5rem; margin-bottom: 12px;">❤️</div>
      <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 8px; color: #1C1C1E;">No More Matches Right Now!</h3>
      <p style="color: #8E8E93; font-size: 0.86rem; max-width: 260px; margin: 0 auto 20px auto;">You have reviewed all available profiles. Check back soon or refresh your feed!</p>
      <button onclick="location.reload()" class="btn-block btn-primary" style="max-width: 200px; margin: 0 auto; height: 44px; border-radius: 22px; font-weight: 800;">
        <i class="fa-solid fa-rotate-right" style="margin-right: 6px;"></i> Refresh Feed
      </button>
    </div>

  <?php else: 
    $cand = $match_candidates[0]; // Hero card candidate
    $c_age = calculate_age($cand['birthdate']);
    $c_img = !empty($cand['avatar_url']) ? $cand['avatar_url'] : 'assets/images/no_image_placeholder.png';
    $c_city = !empty($cand['location_city']) && strpos(strtolower($cand['location_city']), 'san francisco') === false ? $cand['location_city'] : 'India';
    $match_score = rand(88, 98); // High AI Compatibility Match Percentage
  ?>
    
    <!-- Hero Match Candidate Card -->
    <div class="saathi-hero-card" id="saathiHeroCard">
      <img src="<?= htmlspecialchars($c_img) ?>" class="saathi-hero-img" alt="<?= htmlspecialchars($cand['full_name']) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
      
      <!-- Gradient Info Overlay -->
      <div style="position: absolute; inset: 0; background: linear-gradient(180deg, transparent 55%, rgba(0,0,0,0.85) 100%);"></div>

      <!-- Candidate Details Bottom Overlay -->
      <div style="position: absolute; bottom: 20px; left: 20px; right: 60px; color: #FFFFFF;">
        <div style="font-size: 1.3rem; font-weight: 900; line-height: 1.2; text-shadow: 0 2px 8px rgba(0,0,0,0.6);">
          <?= htmlspecialchars($cand['full_name']) ?>, <?= $c_age ?>
        </div>
        <div style="font-size: 0.82rem; color: rgba(255,255,255,0.85); font-weight: 500; margin-top: 4px;">
          <?= htmlspecialchars($cand['occupation'] ?: ($cand['occupation_type'] ?: 'Candidate')) ?> • <?= htmlspecialchars($c_city) ?>
        </div>
      </div>

      <!-- Corner View Profile Info Icon -->
      <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="position: absolute; bottom: 20px; right: 20px; width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.3); backdrop-filter: blur(10px); color: #FFF; border: 1px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; text-decoration: none;" title="View Full Profile">
        <i class="fa-solid fa-user-check"></i>
      </a>
    </div>

    <!-- Match Percentage & Action Control Row -->
    <div class="saathi-match-action-pill">
      <!-- Pass Button (X) -->
      <button class="action-circle-btn pass" onclick="handleMatchAction(<?= $cand['id'] ?>, 'pass')" title="Pass">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <!-- Center 96% Match Percentage Badge -->
      <div class="match-percent-badge">
        <span class="percent-chip"><?= $match_score ?>%</span>
        <span class="match-label-text">Match Your Profile</span>
      </div>

      <!-- Like / Send Interest Button (Heart) -->
      <button class="action-circle-btn like" onclick="handleMatchAction(<?= $cand['id'] ?>, 'like')" title="Send Matrimonial Interest">
        <i class="fa-solid fa-heart"></i>
      </button>
    </div>

  <?php endif; ?>

</main>

<script>
function handleMatchAction(targetId, action) {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: action === 'like' ? 'send_interest' : 'pass', target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (action === 'like') {
      alert('❤️ Matrimonial Interest Sent!');
    }
    location.reload();
  })
  .catch(err => {
    location.reload();
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
