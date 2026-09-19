<?php
// includes/navbar.php - Original App Navigation Bar
$current_u_id = get_current_user_id();
$unread_count = 0;
$nav_avatar = '';

if ($current_u_id) {
    $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :u AND is_read = 0");
    $unread_stmt->execute([':u' => $current_u_id]);
    $unread_count = (int)$unread_stmt->fetchColumn();

    $avatar_stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = :u");
    $avatar_stmt->execute([':u' => $current_u_id]);
    $nav_avatar = $avatar_stmt->fetchColumn();
}

if (empty($nav_avatar)) {
    $nav_avatar = 'assets/images/no_image_placeholder.png';
}
?>
<nav class="app-nav" style="position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; width: 100% !important; max-width: 100% !important; z-index: 9999 !important; background: rgba(255, 255, 255, 0.98) !important; border-top: 1px solid #EBEBEF !important; box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08) !important;">
  <a href="index.php" class="nav-item <?= ($active_tab === 'home') ? 'active' : '' ?>" title="Explore Cards">
    <i class="fa-solid fa-fire-flame-curved"></i>
    <span>Cards</span>
  </a>

  <a href="discover.php" class="nav-item <?= ($active_tab === 'discover') ? 'active' : '' ?>" title="Discover Candidates">
    <i class="fa-solid fa-compass"></i>
    <span>Discover</span>
  </a>

  <a href="radar.php" class="nav-item <?= ($active_tab === 'radar') ? 'active' : '' ?>" title="Radar Live Scanner">
    <i class="fa-solid fa-satellite-dish"></i>
    <span>Radar</span>
  </a>

  <a href="matches.php" class="nav-item <?= ($active_tab === 'matches') ? 'active' : '' ?>" title="Matches & Conversations">
    <i class="fa-solid fa-comments"></i>
    <span>Chats</span>
    <?php if ($unread_count > 0): ?>
      <span class="badge"><?= $unread_count ?></span>
    <?php endif; ?>
  </a>

  <a href="profile.php" class="nav-item <?= ($active_tab === 'profile') ? 'active' : '' ?>" title="My Profile & Settings">
    <img src="<?= htmlspecialchars(get_valid_avatar_url($nav_avatar)) ?>" class="nav-avatar-img <?= ($active_tab === 'profile') ? 'active-ring' : '' ?>" alt="Account" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
    <span>Profile</span>
  </a>
</nav>
