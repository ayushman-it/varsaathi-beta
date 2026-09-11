<?php
// search.php
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'discover';
$current_user_id = get_current_user_id();

$query = trim($_GET['q'] ?? '');

$params = [':u' => $current_user_id];
$where_sql = "WHERE id != :u";

if (!empty($query)) {
    $where_sql .= " AND (full_name LIKE :q OR occupation LIKE :q OR location_city LIKE :q OR interests LIKE :q)";
    $params[':q'] = "%$query%";
}

$stmt = $pdo->prepare("SELECT * FROM users $where_sql ORDER BY id DESC LIMIT 20");
$stmt->execute($params);
$results = $stmt->fetchAll();

$css_version = filemtime(__DIR__ . '/assets/css/style.css');
require_once __DIR__ . '/includes/header.php';
?>

<!-- Search Screen iOS Header -->
<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title">
    <i class="fa-solid fa-magnifying-glass"></i>
    Search Singles
  </div>

  <div class="header-actions">
    <a href="discover.php" class="icon-btn" title="Discover Grid">
      <i class="fa-solid fa-compass"></i>
    </a>
  </div>
</header>

<main class="app-body">
  <!-- Search Input Bar -->
  <div style="padding: 14px 16px 6px 16px;">
    <form action="search.php" method="GET" style="position: relative;">
      <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--ios-muted); font-size: 0.95rem;"></i>
      <input type="text" name="q" class="form-control" style="padding-left: 44px; height: 46px; border-radius: var(--radius-full);" placeholder="Search by name, city, occupation, interest..." value="<?= htmlspecialchars($query) ?>" autocomplete="off">
    </form>
  </div>

  <!-- Search Filter Chips -->
  <div style="display: flex; gap: 8px; padding: 6px 16px 10px 16px; overflow-x: auto; scrollbar-width: none;">
    <a href="search.php" class="tag-chip" style="background: <?= empty($query) ? 'var(--ios-gradient)' : '#FFF' ?>; color: <?= empty($query) ? '#FFF' : 'var(--ios-text)' ?>; padding: 6px 14px; text-decoration: none; border: 1px solid var(--border-subtle);">All Singles</a>
    <a href="search.php?q=San+Francisco" class="tag-chip" style="background: #FFF; color: var(--ios-text); padding: 6px 14px; text-decoration: none; border: 1px solid var(--border-subtle);">📍 San Francisco</a>
    <a href="search.php?q=Designer" class="tag-chip" style="background: #FFF; color: var(--ios-text); padding: 6px 14px; text-decoration: none; border: 1px solid var(--border-subtle);">🎨 Designers</a>
    <a href="search.php?q=Coffee" class="tag-chip" style="background: #FFF; color: var(--ios-text); padding: 6px 14px; text-decoration: none; border: 1px solid var(--border-subtle);">☕ Coffee Lovers</a>
  </div>

  <!-- Search Results Count -->
  <div class="section-label" style="padding-top: 4px;">
    Found <?= count($results) ?> Matches <?= !empty($query) ? 'for "' . htmlspecialchars($query) . '"' : '' ?>
  </div>

  <!-- Discover Profiles Grid -->
  <div class="discover-grid">
    <?php if (empty($results)): ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
        <div style="font-size: 3rem; margin-bottom: 8px;">🔍</div>
        <h4 style="font-weight: 800; margin-bottom: 4px;">No Singles Found</h4>
        <p style="color: var(--ios-muted); font-size: 0.85rem;">Try searching for a different name, city, or interest!</p>
      </div>
    <?php else: ?>
      <?php foreach ($results as $cand): 
        $age = calculate_age($cand['birthdate']);
      ?>
        <div class="discover-card">
          <img src="<?= htmlspecialchars($cand['avatar_url']) ?>" alt="<?= htmlspecialchars($cand['full_name']) ?>" loading="lazy">
          <div class="discover-card-overlay">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span class="discover-badge">📍 <?= $cand['distance_km'] ?> km</span>
              <button class="icon-btn" onclick="quickLike('<?= $cand['id'] ?>', '<?= htmlspecialchars($cand['full_name']) ?>')" style="width:34px; height:34px; background:rgba(255,255,255,0.9); color:var(--ios-pink); border:none;" title="Quick Like">
                <i class="fa-solid fa-heart" style="font-size:0.85rem;"></i>
              </button>
            </div>

            <div class="discover-info">
              <span class="discover-name"><?= htmlspecialchars($cand['full_name']) ?>, <?= $age ?></span>
              <span class="discover-sub"><?= htmlspecialchars($cand['occupation'] ?: $cand['location_city']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
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
    if (data.matched) {
      alert(`🎉 IT'S A MATCH with ${userName}!`);
      location.href = `chat.php?match_id=${data.match_id}`;
    } else {
      alert(`Sent a Like to ${userName}! ❤️`);
    }
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
