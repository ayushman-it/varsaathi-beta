<?php
// delete-account.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
require_login();
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title" style="color: #FF3B30;">Delete Account</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); text-align: center;">
    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.8rem; color: #FF3B30; margin-bottom: 12px;"></i>
    <h2 style="font-weight: 800; font-size: 1.25rem; color: var(--ios-text); margin-bottom: 6px;">Are you sure?</h2>
    <p style="font-size: 0.84rem; color: var(--ios-muted); margin-bottom: 20px; line-height: 1.4;">
      Deleting your account is permanent. All your matches, chat message histories, and active VIP features will be completely erased.
    </p>

    <div style="background: #FFF0F0; border: 1.5px solid #FFCDCD; border-radius: 16px; padding: 14px; margin-bottom: 20px; text-align: left;">
      <div style="font-weight: 800; font-size: 0.88rem; color: #D70000; margin-bottom: 4px;">Alternatively, pause your profile</div>
      <div style="font-size: 0.78rem; color: #660000;">You can temporarily hide your profile from nearby radar and cards without losing your match data.</div>
    </div>

    <form action="api/delete_account.php" method="POST" onsubmit="return confirm('WARNING: Are you 100% sure you want to permanently delete your Varsaathi account? This cannot be undone.');">
      <button type="submit" class="btn-block" style="background: #FF3B30; color: #FFF; font-weight: 800; height: 50px; border-radius: 25px; border: none; margin-bottom: 10px; cursor: pointer;">
        <i class="fa-solid fa-trash"></i> Permanently Delete Account
      </button>
      <a href="index.php" class="btn-block btn-light">Keep My Account</a>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
