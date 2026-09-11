<?php
// notification-settings.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
require_login();
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Notifications</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">

  <!-- Browser Push Notification Permission Status Box -->
  <div id="notifPermissionBox" style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 16px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h3 style="font-weight: 800; font-size: 1rem; margin-bottom: 4px;"><i class="fa-solid fa-bell" style="color: var(--ios-pink); margin-right: 8px;"></i> Push Notifications</h3>
        <p style="font-size: 0.78rem; color: var(--ios-muted);" id="notifStatusText">Checking permission status...</p>
      </div>
      <span id="notifBadge" style="display: none; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;"></span>
    </div>

    <div id="notifActionContainer" style="margin-top: 14px;">
      <!-- Dynamic Button / Instructions inserted by JS -->
    </div>
  </div>

  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <h3 style="font-weight: 800; font-size: 1rem; margin-bottom: 14px;"><i class="fa-solid fa-sliders" style="color: var(--ios-pink); margin-right: 8px;"></i> Notification Preferences</h3>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">New Matches</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Notify when someone matches with you</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">New Messages</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Notify when you receive a message</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-subtle);">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">Super Likes Received</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Instant alert for Super Likes</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0;">
      <div>
        <div style="font-weight: 700; font-size: 0.9rem;">In-App Sounds & Vibration</div>
        <div style="font-size: 0.76rem; color: var(--ios-muted);">Haptic feedback on match swipe</div>
      </div>
      <input type="checkbox" checked style="accent-color: var(--ios-pink); width: 20px; height: 20px;">
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const statusText = document.getElementById('notifStatusText');
  const badge = document.getElementById('notifBadge');
  const actionContainer = document.getElementById('notifActionContainer');

  function checkPermission() {
    if (!('Notification' in window)) {
      statusText.textContent = 'Push Notifications not supported by your browser';
      badge.style.display = 'inline-block';
      badge.style.background = '#E5E5EA';
      badge.style.color = '#8E8E93';
      badge.textContent = 'Not Supported';
      return;
    }

    if (Notification.permission === 'granted') {
      statusText.textContent = 'Push Notifications are ACTIVE & ENABLED';
      badge.style.display = 'inline-block';
      badge.style.background = '#E4F9E0';
      badge.style.color = '#34C759';
      badge.textContent = '✓ Enabled';

      actionContainer.innerHTML = `
        <button onclick="window.showInAppNotificationToast('VARSAATHI Alert 💖', 'Your push notification system is working perfectly!')" style="width: 100%; height: 44px; border-radius: 22px; background: #F8F8FC; border: 1.5px solid #E4E4E7; color: var(--ios-text); font-weight: 700; font-size: 0.88rem; cursor: pointer;">
          <i class="fa-solid fa-paper-plane" style="color: var(--ios-pink); margin-right: 6px;"></i> Send Test Notification
        </button>
      `;
    } else if (Notification.permission === 'default') {
      statusText.textContent = 'Permission not granted yet';
      badge.style.display = 'inline-block';
      badge.style.background = '#FFF3CD';
      badge.style.color = '#856404';
      badge.textContent = 'Pending';

      actionContainer.innerHTML = `
        <button onclick="window.requestNotificationPermission(() => checkPermission())" class="btn-signup" style="width: 100%; height: 48px; border-radius: 24px; font-weight: 800; font-size: 0.95rem; margin-bottom: 0; cursor: pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
          <i class="fa-solid fa-bell"></i> Enable Push Notifications Now
        </button>
      `;
    } else if (Notification.permission === 'denied') {
      statusText.textContent = 'Notifications are BLOCKED by your browser';
      badge.style.display = 'inline-block';
      badge.style.background = '#FEE2E2';
      badge.style.color = '#DC2626';
      badge.textContent = '✖ Blocked';

      actionContainer.innerHTML = `
        <div style="background: #FFF5F5; border: 1px solid #FECACA; padding: 12px; border-radius: 12px; font-size: 0.78rem; color: #991B1B;">
          <strong>How to Enable:</strong> Tap the lock or tune icon in your browser's address bar near the site URL, select <em>Permissions</em>, and set Notifications to <strong>Allow</strong>.
        </div>
      `;
    }
  }

  checkPermission();
});
</script>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
