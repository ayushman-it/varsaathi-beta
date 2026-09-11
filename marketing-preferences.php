<?php
// marketing-preferences.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
require_login();
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Marketing Preferences</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <p style="font-size: 0.84rem; color: var(--ios-muted); margin-bottom: 16px;">Manage how Varsaathi communicates news, tailored promotions, and date tips to you.</p>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Email Newsletters</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Weekly romance recommendations & stories</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Promotional Discounts</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Special Varsaathi VIP Super Like deals</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Personalized Partner Ads</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Date spot deals & local restaurant offers</div>
      </div>
      <input type="checkbox" style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0;">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">SMS Product Updates</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Text updates regarding new app features</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <button onclick="alert('Marketing preferences saved!');" class="btn-block btn-primary" style="margin-top: 20px;">Save Preferences</button>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
