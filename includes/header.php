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

$og_title = $og_title ?? 'VARSAATHI - Find Your Right Life Partner';
$og_description = $og_description ?? 'Verified Profiles • AI Matching • Free Voice & Video Calling. Join Varsaathi Matrimony today!';
$og_image = $og_image ?? (SITE_URL . 'assets/images/welcome_banner.jpg');
$og_url = $og_url ?? (SITE_URL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?= htmlspecialchars($og_title) ?></title>

  <!-- HTML Meta Tags -->
  <meta name="description" content="<?= htmlspecialchars($og_description) ?>">

  <!-- Open Graph / WhatsApp / Facebook Link Preview Tags -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="VARSAATHI">
  <meta property="og:url" content="<?= htmlspecialchars($og_url) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($og_description) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
  <meta property="og:image:secure_url" content="<?= htmlspecialchars($og_image) ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="VARSAATHI Matrimony">

  <!-- Twitter Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="<?= htmlspecialchars($og_url) ?>">
  <meta name="twitter:title" content="<?= htmlspecialchars($og_title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($og_description) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

  <!-- Legacy & WhatsApp image preview fallback -->
  <link rel="image_src" href="<?= htmlspecialchars($og_image) ?>">

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
        <div style="background: rgba(255, 255, 255, 0.95); padding: 10px 22px; border-radius: 18px; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.2); gap: 2px;">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; object-fit: contain; margin: 0;">
          <span style="font-family: 'Outfit', 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; font-size: 0.63rem; font-weight: 500; color: #9B1C31; letter-spacing: 1.6px; text-transform: uppercase;">FOR CHOUASIYAS</span>
        </div>
      </div>
    </div>





    <!-- Global Fullscreen & Minimized WebRTC Call Modal Overlay -->
    <div class="call-modal-overlay" id="callModal">
      <button class="icon-btn" id="minimizeCallBtn" title="Minimize Call into Floating Bubble">
        <i class="fa-solid fa-chevron-down"></i>
      </button>

      <div class="video-stream-container">
        <video id="remoteVideo" class="remote-video-full" autoplay playsinline></video>
        <video id="localVideo" class="local-video-pip" autoplay playsinline muted></video>
      </div>

      <div class="call-info-layer">
        <div class="call-avatar-wrapper">
          <div class="call-pulse-ring"></div>
          <img id="callAvatar" src="assets/images/no_image_placeholder.png" class="call-avatar-img" alt="Candidate Avatar">
        </div>
        <h3 id="callPartnerName" class="call-partner-name">User</h3>
        <span id="callStatusLabel" class="call-status-text">Connecting Call...</span>
        <span id="callTimer" class="call-timer-badge">00:00</span>
      </div>

      <!-- Quick Actions for Minimized PIP Bubble -->
      <div class="pip-quick-actions" id="pipQuickActions">
        <button class="pip-btn pip-btn-mute" id="pipMuteBtn" title="Mute Microphone">
          <i class="fa-solid fa-microphone"></i>
        </button>
        <button class="pip-btn pip-btn-end" id="pipEndBtn" title="End Call Immediately">
          <i class="fa-solid fa-phone-slash"></i>
        </button>
        <button class="pip-btn pip-btn-expand" id="pipExpandBtn" title="Expand Fullscreen">
          <i class="fa-solid fa-expand"></i>
        </button>
      </div>

      <div class="call-actions-toolbar">
        <button class="call-btn call-btn-action" id="muteAudioBtn" title="Mute Microphone">
          <i class="fa-solid fa-microphone"></i>
        </button>
        <button class="call-btn call-btn-end" id="endCallBtn" title="End Call">
          <i class="fa-solid fa-phone-slash"></i>
        </button>
        <button class="call-btn call-btn-action" id="toggleVideoBtn" title="Toggle Camera">
          <i class="fa-solid fa-video"></i>
        </button>
      </div>
    </div>

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
            <span class="sidebar-user-sub">Varsaathi Matrimony</span>
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
          <a href="subscription.php" class="sidebar-item" style="background: linear-gradient(135deg, #FFF0F4 0%, #FFEBF0 100%); border: 1px solid rgba(195,31,58,0.2); border-radius: 16px; margin-top: 4px; margin-bottom: 4px;">
            <i class="fa-solid fa-crown" style="color: #C31F3A; font-size: 1rem;"></i>
            <span style="font-weight: 600; color: #1C1C1E; font-size: 0.88rem;">Varsaathi Prime</span>
            <span class="sidebar-badge" style="background: #C31F3A; color: #FFF; font-weight: 700; font-size: 0.62rem; padding: 2px 8px; border-radius: 8px;">FREE</span>
          </a>

          <div class="sidebar-section-title">Settings & Preferences</div>
          <a href="javascript:void(0)" onclick="window.triggerPwaInstallModal()" class="sidebar-item" style="background: #F8F8FA; border: 1px solid #E5E5EA; border-radius: 16px; margin-top: 4px;">
            <i class="fa-solid fa-mobile-screen-button" style="color: #C31F3A; font-size: 1rem;"></i>
            <span style="font-weight: 600; color: #1C1C1E; font-size: 0.88rem;">Install App</span>
            <span class="sidebar-badge" style="background: #F2F2F7; color: #636366; font-weight: 700; font-size: 0.62rem; padding: 2px 8px; border-radius: 8px; border: 1px solid #E5E5EA;">GET</span>
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
