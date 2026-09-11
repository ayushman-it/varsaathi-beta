<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
$active_tab = $active_tab ?? 'home';
$current_u_id = get_current_user_id();

$current_user_data = null;
if ($current_u_id) {
    $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
    $u_stmt->execute([':u' => $current_u_id]);
    $current_user_data = $u_stmt->fetch();
}

$placeholder_img = 'assets/images/no_image_placeholder.png';
$user_avatar = !empty($current_user_data['avatar_url']) ? $current_user_data['avatar_url'] : $placeholder_img;
$user_name = $current_user_data['full_name'] ?? 'Guest User';

$is_profile_incomplete = false;
if ($current_user_data) {
    $has_avatar = (!empty($current_user_data['avatar_url']) && strpos($current_user_data['avatar_url'], 'default_avatar') === false && strpos($current_user_data['avatar_url'], 'unsplash.com') === false);
    $has_bio = !empty(trim($current_user_data['bio'] ?? ''));
    if (!$has_avatar || !$has_bio) {
        $is_profile_incomplete = true;
    }
}

$css_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>VARSAATHI - iOS Dating App</title>
  <!-- Google Fonts: Poppins, Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Favicon & App Icon -->
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <!-- Mobile & PWA Web Push Meta Tags -->
  <link rel="manifest" href="manifest.json">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="theme-color" content="#FFFFFF">
  <!-- iOS Native Cache-Busted CSS -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
</head>
<body>
  <div class="app-container">

    <!-- Varsaathi Animated Splash Loader -->
    <div class="page-loader" id="pageLoader">
      <div class="loader-brand">
        <div style="background: rgba(255, 255, 255, 0.95); padding: 10px 22px; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 44px; object-fit: contain;">
        </div>
      </div>
    </div>



    <!-- Compact Single-Line Incomplete Profile Custom Snackbar -->
    <?php if ($is_profile_incomplete && strpos($_SERVER['PHP_SELF'], 'profile.php') === false): ?>
      <div id="incompleteProfileSnackbar" style="position: fixed; top: 10px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 360px; background: rgba(28, 28, 30, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); color: #FFF; border-radius: 20px; padding: 6px 10px 6px 12px; z-index: 999999; box-shadow: 0 6px 20px rgba(0,0,0,0.25); border: 1px solid rgba(255,45,85,0.3); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
        <div style="display: flex; align-items: center; gap: 6px; flex: 1; min-width: 0;">
          <i class="fa-solid fa-circle-exclamation" style="color: var(--ios-pink); font-size: 0.85rem; flex-shrink: 0;"></i>
          <span style="font-size: 0.74rem; font-weight: 700; color: #FFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Please complete your profile</span>
        </div>
        <a href="profile.php" style="background: var(--ios-gradient); color: #FFF; font-weight: 800; font-size: 0.68rem; padding: 4px 10px; border-radius: 12px; text-decoration: none; flex-shrink: 0;">
          Complete
        </a>
        <button type="button" onclick="document.getElementById('incompleteProfileSnackbar').remove()" style="background: none; border: none; color: #A1A1AA; font-size: 0.8rem; cursor: pointer; padding: 2px; flex-shrink: 0;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    <?php endif; ?>

    <!-- Global Incoming Call Alert Modal Banner -->
    <div class="incoming-call-modal" id="incomingModal">
      <div style="display:flex; align-items:center; gap:12px;">
        <img id="incomingAvatar" src="" style="width:46px; height:46px; border-radius:50%; object-fit:cover; border:2px solid var(--ios-pink);" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        <div>
          <h4 id="incomingName" style="font-weight:800; font-size:0.92rem; color:#FFF; margin-bottom:2px;">Incoming Call</h4>
          <span id="incomingTypeLabel" style="font-size:0.72rem; color:var(--ios-pink); font-weight:700;">Incoming Call...</span>
        </div>
      </div>
      <div style="display:flex; align-items:center; gap:10px;">
        <button class="call-btn call-btn-end" id="rejectCallBtn" style="width:42px; height:42px; font-size:1rem;" title="Decline Call">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <button class="call-btn call-btn-accept" id="acceptCallBtn" style="width:42px; height:42px; font-size:1rem;" title="Accept Call">
          <i class="fa-solid fa-phone"></i>
        </button>
      </div>
    </div>

    <!-- iOS Sidebar Navigation Drawer -->
    <div class="sidebar-overlay" id="sidebarOverlay">
      <aside class="sidebar-drawer" id="sidebarDrawer">
        <div class="sidebar-user">
          <img src="<?= htmlspecialchars($user_avatar) ?>" class="sidebar-avatar" alt="<?= htmlspecialchars($user_name) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
          <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= htmlspecialchars($user_name) ?></span>
            <span class="sidebar-user-sub">Member Account</span>
          </div>
        </div>

        <nav class="sidebar-menu">
          <div class="sidebar-section-title">Navigation</div>
          <a href="index.php" class="sidebar-item">
            <i class="fa-solid fa-layer-group"></i>
            <span>Cards Deck</span>
          </a>
          <a href="discover.php" class="sidebar-item">
            <i class="fa-solid fa-compass"></i>
            <span>Discover Nearby</span>
          </a>
          <a href="radar.php" class="sidebar-item">
            <i class="fa-solid fa-crosshairs"></i>
            <span>Radar Scanner</span>
          </a>
          <a href="matches.php" class="sidebar-item">
            <i class="fa-solid fa-comment-dots"></i>
            <span>Matches & Messages</span>
          </a>

          <div class="sidebar-section-title">Settings & Preferences</div>
          <a href="javascript:void(0)" onclick="window.triggerPwaInstallModal()" class="sidebar-item" style="background: rgba(195,31,58,0.08); color: var(--ios-pink); font-weight: 800;">
            <i class="fa-solid fa-mobile-screen-button" style="color: var(--ios-pink);"></i>
            <span>Install Varsaathi App</span>
            <span class="sidebar-badge" style="background: var(--ios-pink); color: #FFF; font-weight: 800;">PWA</span>
          </a>
          <a href="security.php" class="sidebar-item">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Security</span>
          </a>
          <a href="notification-settings.php" class="sidebar-item">
            <i class="fa-solid fa-bell"></i>
            <span>Notification Settings</span>
          </a>
          <a href="faq.php" class="sidebar-item">
            <i class="fa-solid fa-circle-question"></i>
            <span>FAQs</span>
          </a>
          <a href="data-privacy.php" class="sidebar-item">
            <i class="fa-solid fa-user-shield"></i>
            <span>Data & Privacy Policy</span>
          </a>

          <div class="sidebar-section-title">Account Action</div>
          <a href="delete-account.php" class="sidebar-item danger">
            <i class="fa-solid fa-user-xmark"></i>
            <span>Delete or Deactivate Account</span>
          </a>
          <a href="logout.php" class="sidebar-item logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
          </a>
        </nav>
      </aside>
    </div>
