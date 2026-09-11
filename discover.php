<?php
// discover.php
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'discover';
$current_user_id = get_current_user_id();

// Fetch candidates for grid view
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id != :u
    ORDER BY id DESC
    LIMIT 12
");
$stmt->execute([':u' => $current_user_id]);
$candidates = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title">
    <i class="fa-solid fa-compass"></i>
    Discover
  </div>

  <div class="header-actions">
    <a href="index.php" class="icon-btn" title="Switch to Cards View">
      <i class="fa-solid fa-layer-group"></i>
    </a>
  </div>
</header>

<main class="app-body">
  <!-- Category Filter Pills (Icons only, matching reference mockup) -->
  <div style="display: flex; gap: 10px; padding: 14px 16px 6px 16px; overflow-x: auto; scrollbar-width: none;">
    <button class="discover-filter-pill active">
      <i class="fa-solid fa-fire" style="color: #FF2D55;"></i>
      <span>All Profiles</span>
    </button>
    <button class="discover-filter-pill">
      <i class="fa-solid fa-circle" style="color: #34C759; font-size: 0.65rem;"></i>
      <span>Online Now</span>
    </button>
    <button class="discover-filter-pill">
      <i class="fa-solid fa-location-dot" style="color: #FF2D55;"></i>
      <span>Near Me</span>
    </button>
    <button class="discover-filter-pill">
      <i class="fa-solid fa-star" style="color: #FFCC00;"></i>
      <span>Popular</span>
    </button>
  </div>

  <!-- Discover Profiles Grid -->
  <div class="discover-grid">
    <?php foreach ($candidates as $cand): 
      $age = calculate_age($cand['birthdate']);
      $placeholder_img = 'assets/images/no_image_placeholder.png';
      $avatar_img = !empty($cand['avatar_url']) ? $cand['avatar_url'] : $placeholder_img;
      $is_cand_private = (bool)($cand['is_private'] ?? 0);
    ?>
      <a href="profile-view.php?id=<?= $cand['id'] ?>" class="discover-card">
        <img src="<?= htmlspecialchars($avatar_img) ?>" alt="<?= htmlspecialchars($cand['full_name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
        <div class="discover-card-overlay">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span class="discover-badge">
              <i class="fa-solid fa-location-dot" style="color: var(--ios-pink); margin-right: 2px;"></i> <?= $cand['distance_km'] ?> km
              <?php if ($is_cand_private): ?>
                • <i class="fa-solid fa-lock" style="color: #FFF;" title="Private Profile"></i>
              <?php endif; ?>
            </span>
            <button class="icon-btn" onclick="event.preventDefault(); quickLike('<?= $cand['id'] ?>', '<?= htmlspecialchars(addslashes($cand['full_name'])) ?>')" style="width:36px; height:36px; background:rgba(255,255,255,0.92); color:var(--ios-pink); border:none;" title="Quick Like">
              <i class="fa-solid fa-heart" style="font-size:0.95rem;"></i>
            </button>
          </div>

          <div class="discover-info">
            <span class="discover-name"><?= htmlspecialchars($cand['full_name']) ?>, <?= $age ?></span>
            <span class="discover-sub"><i class="fa-solid fa-briefcase" style="font-size: 0.68rem; margin-right: 3px;"></i> <?= htmlspecialchars($cand['occupation'] ?: $cand['location_city']) ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</main>

<script>
function quickLike(targetId, userName) {
  fetch('api/swipe.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ target_id: targetId, action: 'like' })
  })
  .then(res => res.json())
  .then(data => {
    alert(`❤️ Matched with ${userName}! Added to Messages.`);
    if (data.match_id) {
      location.href = `chat.php?match_id=${data.match_id}`;
    } else {
      location.href = 'matches.php';
    }
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
