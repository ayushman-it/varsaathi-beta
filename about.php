<?php
// about.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">About Varsaathi</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 24px 16px; text-align: center;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 28px 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div style="
      width: 72px;
      height: 72px;
      border-radius: 20px;
      background: var(--ios-gradient);
      box-shadow: var(--ios-glow);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
    ">
      <i class="fa-solid fa-heart" style="font-size: 2.2rem; color: #FFF;"></i>
    </div>
    
    <h2 style="font-weight: 900; font-size: 1.6rem; color: var(--ios-text); font-family: 'Outfit', sans-serif;">VARSAATHI</h2>
    <span style="background: rgba(255,45,85,0.1); color: var(--ios-pink); font-weight: 800; font-size: 0.76rem; padding: 4px 12px; border-radius: 12px;">Version 1.0.1 (Build 2026.9)</span>

    <p style="font-size: 0.85rem; color: var(--ios-muted); margin-top: 16px; line-height: 1.5;">
      Varsaathi is the ultimate next-generation dating app engineered for authentic romantic connections, instant radar proximity matching, and high-quality communication.
    </p>

    <div style="margin-top: 24px; border-top: 1px solid var(--border-subtle); padding-top: 16px; display: flex; justify-content: space-around; font-size: 0.82rem; font-weight: 700;">
      <a href="data-privacy.php" style="color: var(--ios-pink); text-decoration: none;">Terms of Service</a>
      <a href="data-privacy.php" style="color: var(--ios-pink); text-decoration: none;">Privacy Policy</a>
      <a href="faq.php" style="color: var(--ios-pink); text-decoration: none;">Licenses</a>
    </div>

    <div style="font-size: 0.72rem; color: var(--ios-muted); margin-top: 18px;">
      © 2026 Varsaathi Inc. All rights reserved.
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
