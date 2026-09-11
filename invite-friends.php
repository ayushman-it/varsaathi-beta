<?php
// invite-friends.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Invite Friends</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 20px 16px; text-align: center;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 24px 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <div style="
      width: 76px;
      height: 76px;
      border-radius: 50%;
      background: #FDF0F6;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--ios-pink);
      font-size: 2rem;
      margin-bottom: 14px;
    ">
      <i class="fa-solid fa-user-plus"></i>
    </div>

    <h2 style="font-weight: 800; font-size: 1.3rem; margin-bottom: 6px;">Earn Free Super Likes!</h2>
    <p style="font-size: 0.84rem; color: var(--ios-muted); margin-bottom: 20px; line-height: 1.4;">
      Invite single friends to join Varsaathi. Get 5 FREE Super Likes when a friend registers using your link!
    </p>

    <div class="form-group" style="margin-bottom: 20px;">
      <label class="form-label">Your Exclusive Referral Link</label>
      <div style="display: flex; gap: 8px;">
        <input type="text" class="form-control" value="https://varsaathi.app/join?ref=ALEX2026" readonly style="font-weight: 700;">
        <button class="icon-btn primary-btn" style="width: 48px; height: 48px; flex-shrink: 0;" onclick="navigator.clipboard.writeText('https://varsaathi.app/join?ref=ALEX2026'); alert('Referral link copied to clipboard!');">
          <i class="fa-solid fa-copy"></i>
        </button>
      </div>
    </div>

    <div style="display: flex; gap: 10px;">
      <button class="btn-block" style="background: #25D366; color: #FFF; flex: 1;" onclick="alert('Opening WhatsApp share...');">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp
      </button>
      <button class="btn-block" style="background: #0088CC; color: #FFF; flex: 1;" onclick="alert('Opening Telegram share...');">
        <i class="fa-brands fa-telegram"></i> Telegram
      </button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
