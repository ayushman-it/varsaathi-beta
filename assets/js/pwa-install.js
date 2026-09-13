/**
 * VARSAATHI Dating App - PWA Installation Engine & Custom UI Modal
 * Handles Android/Desktop Chrome native install prompts and iOS Safari step-by-step PWA guide.
 */

(function () {
  if (typeof window === 'undefined') return;

  // Check if running in PWA standalone display mode
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                       window.navigator.standalone === true || 
                       document.referrer.includes('android-app://');

  if (isStandalone) return;

  let deferredPrompt = null;
  const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
  const DISMISS_KEY = 'varsaathi_pwa_dismissed_time';

  // Check dismissal cooldown (3 days)
  function isDismissedRecently() {
    const lastDismissed = localStorage.getItem(DISMISS_KEY);
    if (!lastDismissed) return false;
    const cooldownMs = 3 * 24 * 60 * 60 * 1000;
    return (Date.now() - parseInt(lastDismissed, 10)) < cooldownMs;
  }

  // Create PWA Install Modal UI
  function renderPwaModal(isIosGuide = false) {
    if (document.getElementById('pwaInstallModal')) return;

    const modal = document.createElement('div');
    modal.id = 'pwaInstallModal';
    modal.style.cssText = `
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: rgba(0, 0, 0, 0.65);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding: 0;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.35s ease;
    `;

    let actionContent = '';
    if (isIosGuide) {
      actionContent = `
        <div style="background: #F2F2F7; border-radius: 18px; padding: 14px 16px; text-align: left; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-size: 0.85rem; font-weight: 700; color: #1C1C1E;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #C31F3A; color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">1</div>
            <span>Tap the <strong>Share</strong> button <i class="fa-solid fa-arrow-up-from-bracket" style="color: #007AFF; margin: 0 4px;"></i> in Safari toolbar</span>
          </div>
          <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 700; color: #1C1C1E;">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: #C31F3A; color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">2</div>
            <span>Scroll down and select <strong>'Add to Home Screen'</strong> <i class="fa-regular fa-square-plus" style="color: #1C1C1E; margin-left: 4px;"></i></span>
          </div>
        </div>
        <button id="pwaIosDoneBtn" class="btn-block btn-primary" style="height: 48px; border-radius: 24px; font-weight: 800; font-size: 0.95rem; width: 100%;">
          Got It!
        </button>
      `;
    } else {
      actionContent = `
        <button id="pwaInstallBtn" class="btn-block btn-primary" style="height: 48px; border-radius: 24px; font-weight: 700; font-size: 0.95rem; width: 100%; margin-bottom: 10px; box-shadow: 0 6px 20px rgba(195,31,58,0.35); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
          <i class="fa-solid fa-mobile-screen-button" style="font-size: 1rem;"></i> Install App
        </button>
        <button id="pwaDismissBtn" style="background: none; border: none; color: #8E8E93; font-weight: 600; font-size: 0.85rem; width: 100%; padding: 10px; cursor: pointer;">
          Maybe Later
        </button>
      `;
    }

    modal.innerHTML = `
      <div style="width: 100%; max-width: 420px; background: #FFFFFF; border-radius: 30px 30px 0 0; padding: 26px 22px calc(26px + env(safe-area-inset-bottom, 0px)) 22px; text-align: center; box-shadow: 0 -12px 40px rgba(0,0,0,0.25); transform: translateY(100%); transition: transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.1);" id="pwaModalCard">
        <div style="width: 38px; height: 4px; border-radius: 2px; background: #E5E5EA; margin: 0 auto 16px auto;"></div>
        
        <div style="width: 72px; height: 72px; border-radius: 22px; background: #FFFFFF; padding: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(195,31,58,0.2); margin-bottom: 14px; border: 1px solid rgba(195,31,58,0.15);">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="width: 100%; height: 100%; object-fit: contain;">
        </div>

        <h3 style="font-weight: 800; font-size: 1.3rem; color: #1C1C1E; margin-bottom: 6px;">Install App</h3>
        <p style="font-size: 0.86rem; color: #8E8E93; line-height: 1.4; margin-bottom: 20px;">
          Install Varsaathi on your phone home screen for instant push notifications, full screen view, and super fast dating experience!
        </p>

        ${actionContent}
      </div>
    `;

    document.body.appendChild(modal);

    setTimeout(() => {
      modal.style.opacity = '1';
      modal.style.pointerEvents = 'auto';
      const card = document.getElementById('pwaModalCard');
      if (card) card.style.transform = 'translateY(0)';
    }, 100);

    const installBtn = document.getElementById('pwaInstallBtn');
    if (installBtn) {
      installBtn.addEventListener('click', async () => {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          const { outcome } = await deferredPrompt.userChoice;
          console.log('[PWA Engine] Install prompt outcome:', outcome);
          deferredPrompt = null;
        } else {
          alert('To install, tap the 3 dots menu in your browser and select "Add to Home screen".');
        }
        closeModal();
      });
    }

    const dismissBtn = document.getElementById('pwaDismissBtn');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', () => {
        localStorage.setItem(DISMISS_KEY, Date.now().toString());
        closeModal();
      });
    }

    const iosDoneBtn = document.getElementById('pwaIosDoneBtn');
    if (iosDoneBtn) {
      iosDoneBtn.addEventListener('click', () => {
        localStorage.setItem(DISMISS_KEY, Date.now().toString());
        closeModal();
      });
    }

    function closeModal() {
      const card = document.getElementById('pwaModalCard');
      if (card) card.style.transform = 'translateY(100%)';
      modal.style.opacity = '0';
      modal.style.pointerEvents = 'none';
      setTimeout(() => modal.remove(), 350);
    }
  }

  // Listen for Chrome/Android PWA install prompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    console.log('[PWA Engine] beforeinstallprompt event captured');

    if (!isDismissedRecently()) {
      setTimeout(() => renderPwaModal(false), 2000);
    }
  });

  // Handle iOS Safari Installation Guide trigger
  document.addEventListener('DOMContentLoaded', () => {
    if (isIOS && !isDismissedRecently()) {
      setTimeout(() => renderPwaModal(true), 3500);
    }
  });

  // Global function to trigger PWA install modal from any button
  window.triggerPwaInstallModal = function () {
    if (isIOS) {
      renderPwaModal(true);
    } else {
      renderPwaModal(false);
    }
  };
})();
