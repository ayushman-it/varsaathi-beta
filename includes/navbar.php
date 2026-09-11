<?php
// includes/navbar.php
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
    $nav_avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';
}
?>
<nav class="app-nav">
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
    <img src="<?= htmlspecialchars($nav_avatar) ?>" class="nav-avatar-img <?= ($active_tab === 'profile') ? 'active-ring' : '' ?>" alt="Account" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';">
    <span>Profile</span>
  </a>
</nav>
