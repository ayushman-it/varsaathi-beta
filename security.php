<?php
// security.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
require_login();
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Security Settings</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 16px;">
    <h3 style="font-weight: 800; margin-bottom: 12px; font-size: 1rem;"><i class="fa-solid fa-key" style="color: var(--ios-pink); margin-right: 8px;"></i> Change Password</h3>
    <form onsubmit="event.preventDefault(); alert('Password updated successfully!');">
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" class="form-control" placeholder="••••••••" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-block btn-primary">Update Password</button>
    </form>
  </div>

  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <h3 style="font-weight: 800; margin-bottom: 14px; font-size: 1rem;"><i class="fa-solid fa-shield" style="color: var(--ios-pink); margin-right: 8px;"></i> Account Protection</h3>
    
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Two-Factor Authentication (2FA)</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Add extra layer of security via SMS/Email</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0;">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Biometric Face ID / Touch ID</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Require Face ID to open Varsaathi app</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
