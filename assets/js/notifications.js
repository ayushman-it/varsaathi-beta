/**
 * VARSAATHI - Real-time FCM Push Notification Client Engine
 * Handles service worker registration, FCM token sync, and foreground notification toasts.
 */

(function () {
  if (typeof window === 'undefined') return;

  // Render In-App Toast Notification
  window.showInAppNotificationToast = function (title, body, data = {}) {
    let toastContainer = document.getElementById('fcmToastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'fcmToastContainer';
      toastContainer.style.cssText = `
        position: fixed;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        width: 90%;
        max-width: 360px;
        pointer-events: none;
      `;
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `
      background: rgba(28, 28, 30, 0.95);
      backdrop-filter: blur(20px);
      color: #FFF;
      padding: 12px 16px;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.1);
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
      cursor: pointer;
      pointer-events: auto;
      animation: slideDownToast 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    `;

    let iconHtml = '<div style="font-size: 1.4rem;">🔔</div>';
    if (data.type === 'match' || data.type === 'like') {
      iconHtml = '<div style="font-size: 1.4rem; color: #FF2D55;">💕</div>';
    } else if (data.type === 'call') {
      iconHtml = '<div style="font-size: 1.4rem; color: #AF52DE;">📞</div>';
    } else if (data.type === 'chat') {
      iconHtml = '<div style="font-size: 1.4rem; color: #34C759;">💬</div>';
    }

    toast.innerHTML = `
      ${iconHtml}
      <div style="flex: 1; min-width: 0;">
        <div style="font-weight: 800; font-size: 0.88rem; line-height: 1.2; color: #FFF;">${escapeHtml(title)}</div>
        <div style="font-size: 0.78rem; color: #D1D1D6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">${escapeHtml(body)}</div>
      </div>
      <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem; color: #8E8E93;"></i>
    `;

    toast.onclick = function () {
      if (data.type === 'chat' && data.match_id) {
        window.location.href = `chat.php?match_id=${data.match_id}`;
      } else if (data.type === 'call') {
        window.location.href = `chat.php?match_id=${data.match_id || ''}`;
      } else {
        window.location.href = 'matches.php';
      }
    };

    toastContainer.appendChild(toast);

    // Play subtle notification chime sound
    try {
      const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
      audio.volume = 0.4;
      audio.play().catch(() => {});
    } catch (e) {}

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4500);
  };

  function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // Add toast CSS animation if missing
  if (!document.getElementById('fcmToastStyle')) {
    const style = document.createElement('style');
    style.id = 'fcmToastStyle';
    style.textContent = `
      @keyframes slideDownToast {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `;
    document.head.appendChild(style);
  }

  // Expose global function to trigger permission request from anywhere (e.g., settings button or banner)
  window.requestNotificationPermission = function (callback) {
    if (!('Notification' in window)) {
      alert('Push notifications are not supported in your browser.');
      return;
    }

    Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        window.showInAppNotificationToast('Notifications Enabled! 🎉', 'You will now receive alerts for new matches, messages, and calls.');
        if ('serviceWorker' in navigator && typeof firebase !== 'undefined' && firebase.messaging) {
          if (!firebase.apps || !firebase.apps.length) {
            firebase.initializeApp(window.firebaseConfig);
          }
          navigator.serviceWorker.ready.then((registration) => {
            try {
              const messaging = firebase.messaging();
              const vapidKey = window.FCM_VAPID_KEY && window.FCM_VAPID_KEY !== 'YOUR_FCM_VAPID_PUBLIC_KEY' 
                ? window.FCM_VAPID_KEY 
                : undefined;
              messaging.getToken({ serviceWorkerRegistration: registration, vapidKey: vapidKey })
                .then((token) => { if (token) saveFcmToken(token); })
                .catch((e) => console.warn('[FCM Token Error]', e));
            } catch(e) {}
          });
        }
      } else if (permission === 'denied') {
        alert('Notification permission was blocked in browser settings. Please allow notifications in site permissions.');
      }
      if (typeof callback === 'function') callback(permission);
    });
  };

  // Render Mobile & Universal Notification Enable Banner when permission is default
  window.renderMobileNotificationBanner = function () {
    if (!('Notification' in window) || Notification.permission !== 'default') return;
    if (document.getElementById('mobileNotifBanner')) return;

    const banner = document.createElement('div');
    banner.id = 'mobileNotifBanner';
    banner.style.cssText = `
      background: linear-gradient(135deg, #C31F3A 0%, #A8162E 100%);
      color: #FFF;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.8rem;
      font-weight: 700;
      box-shadow: 0 4px 14px rgba(195, 31, 58, 0.3);
      z-index: 9999;
      position: relative;
    `;
    banner.innerHTML = `
      <div style="display:flex; align-items:center; gap:8px;">
        <i class="fa-solid fa-bell" style="font-size: 1rem;"></i>
        <span>Enable notifications for matches & messages</span>
      </div>
      <div style="display:flex; align-items:center; gap:6px;">
        <button id="enableNotifBtnBanner" style="background:#FFF; color:#C31F3A; border:none; padding:5px 12px; border-radius:14px; font-weight:800; font-size:0.75rem; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15);">Enable</button>
        <button onclick="document.getElementById('mobileNotifBanner')?.remove()" style="background:none; border:none; color:#FFF; font-size:1.1rem; cursor:pointer; opacity:0.8; line-height:1; padding:0 4px;">&times;</button>
      </div>
    `;

    const appContainer = document.querySelector('.app-container');
    const appBody = document.querySelector('.app-body');
    if (appContainer && appBody) {
      appContainer.insertBefore(banner, appBody);
      document.getElementById('enableNotifBtnBanner')?.addEventListener('click', () => {
        window.requestNotificationPermission(() => {
          document.getElementById('mobileNotifBanner')?.remove();
        });
      });
    }
  };

  // Initialize FCM Client & Render Mobile Enable Banner
  document.addEventListener('DOMContentLoaded', () => {
    window.renderMobileNotificationBanner();

    if ('serviceWorker' in navigator && 'PushManager' in window) {
      navigator.serviceWorker.register('firebase-messaging-sw.js')
        .then((registration) => {
          console.log('[FCM Engine] Service Worker registered scope:', registration.scope);

          if (typeof firebase !== 'undefined' && firebase.messaging) {
            try {
              if (!firebase.apps || !firebase.apps.length) {
                firebase.initializeApp(window.firebaseConfig);
              }
              const messaging = firebase.messaging();
              
              if (Notification.permission === 'granted') {
                const vapidKey = window.FCM_VAPID_KEY && window.FCM_VAPID_KEY !== 'YOUR_FCM_VAPID_PUBLIC_KEY' 
                  ? window.FCM_VAPID_KEY 
                  : undefined;

                messaging.getToken({ serviceWorkerRegistration: registration, vapidKey: vapidKey })
                  .then((currentToken) => {
                    if (currentToken) {
                      console.log('[FCM Token] Obtained:', currentToken);
                      saveFcmToken(currentToken);
                    }
                  })
                  .catch((err) => {
                    console.warn('[FCM Token] Token retrieval warning:', err);
                  });
              }

              // Foreground message handler
              messaging.onMessage((payload) => {
                console.log('[FCM Foreground] Received payload:', payload);
                const title = payload.notification?.title || payload.data?.title || 'New Notification';
                const body = payload.notification?.body || payload.data?.body || '';
                window.showInAppNotificationToast(title, body, payload.data || {});
              });

            } catch (err) {
              console.warn('[FCM Init] Error:', err);
            }
          }
        })
        .catch((err) => {
          console.warn('[FCM ServiceWorker] Registration failed:', err);
        });
      checkHourlyNotifications();
    }
  });

  // Trigger Hourly Automatic Push Notification Check (Once every 1 hour)
  function checkHourlyNotifications() {
    try {
      const lastRun = localStorage.getItem('varsaathi_last_hourly_cron');
      const now = Date.now();
      if (!lastRun || (now - parseInt(lastRun, 10)) > 3600000) {
        fetch('api/cron_hourly_notifications.php')
          .then(() => localStorage.setItem('varsaathi_last_hourly_cron', now.toString()))
          .catch(() => {});
      }
    } catch(e) {}
  }

  function saveFcmToken(token) {
    fetch('api/save_fcm_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fcm_token: token })
    }).catch(() => {});
  }
})();
