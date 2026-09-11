/**
 * VARSAATHI Dating App - Pitch Black Anti-Screenshot & DRM Security Engine
 * Features:
 * - 100% Pitch-Black Screen Overlay (#000000) on screenshot attempt, 3-finger gesture, or app-switch
 * - Blocks PrintScreen, Ctrl+S, Cmd+S, Ctrl+P, F12, DevTools, and Right-Click context menus
 * - Disables iOS Safari & Android Chrome long-press photo download menus
 * - AUTO-DISABLED during file input picker so photo uploading never blocks screen!
 */

(function() {
  'use strict';

  // 1. Create Pure Pitch-Black Security Overlay (#000000)
  const blackoutOverlay = document.createElement('div');
  blackoutOverlay.id = 'privacyBlackoutOverlay';
  blackoutOverlay.style.cssText = `
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    background: #000000 !important;
    color: #FFFFFF;
    z-index: 9999999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.08s ease-in-out;
    font-family: -apple-system, BlinkMacSystemFont, 'Plus Jakarta Sans', sans-serif;
  `;
  blackoutOverlay.innerHTML = `
    <div style="font-size: 3.5rem; margin-bottom: 14px; filter: drop-shadow(0 0 16px rgba(195,31,58,0.8));">🔒</div>
    <h3 style="font-weight: 800; font-size: 1.3rem; margin-bottom: 8px; color: #FFFFFF; letter-spacing: -0.5px;">Screenshot Blocked</h3>
    <p style="font-size: 0.85rem; color: #8E8E93; max-width: 260px; line-height: 1.45; font-weight: 600;">VARSAATHI enforces strict privacy protection. Screenshots & screen recordings are blacked out.</p>
  `;
  document.body.appendChild(blackoutOverlay);

  let blackoutTimer = null;
  let isFilePickerActive = false;

  // Track File Picker Activation so screen NEVER turns black during photo uploads
  document.addEventListener('click', (e) => {
    if (e.target && (e.target.tagName === 'INPUT' && e.target.type === 'file' || e.target.closest('input[type="file"]') || e.target.closest('label[for]'))) {
      isFilePickerActive = true;
      if (blackoutOverlay) {
        blackoutOverlay.style.opacity = '0';
        blackoutOverlay.style.pointerEvents = 'none';
      }
      setTimeout(() => { isFilePickerActive = false; }, 20000);
    }
  }, true);

  window.addEventListener('focus', () => {
    isFilePickerActive = false;
    if (blackoutOverlay) {
      blackoutOverlay.style.opacity = '0';
      blackoutOverlay.style.pointerEvents = 'none';
    }
  });

  // Trigger Instant Pure Pitch-Black Screen (#000000)
  function flashBlackout(durationMs = 1200) {
    if (isFilePickerActive) return;

    blackoutOverlay.style.opacity = '1';
    blackoutOverlay.style.pointerEvents = 'auto';

    if (blackoutTimer) clearTimeout(blackoutTimer);
    blackoutTimer = setTimeout(() => {
      if (!document.hidden && document.hasFocus()) {
        blackoutOverlay.style.opacity = '0';
        blackoutOverlay.style.pointerEvents = 'none';
      }
    }, durationMs);
  }

  // Show security toast alert
  let toastTimer = null;
  function showPrivacyToast(message) {
    let toast = document.getElementById('privacyToastAlert');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'privacyToastAlert';
      toast.style.cssText = `
        position: fixed;
        top: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(-20px);
        background: #1C1C1E;
        color: #FFFFFF;
        padding: 10px 20px;
        border-radius: 26px;
        font-size: 0.82rem;
        font-weight: 800;
        z-index: 99999999;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.25s ease;
        pointer-events: none;
        white-space: nowrap;
      `;
      document.body.appendChild(toast);
    }
    toast.innerHTML = `<span style="font-size: 1.1rem; color: #C31F3A;">🔒</span> <span>${message}</span>`;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';

    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(-50%) translateY(-20px)';
    }, 2800);
  }

  // 2. Blackout Screen on Window Blur / App-Switch (Only when file picker is NOT active)
  window.addEventListener('blur', () => {
    if (!isFilePickerActive) flashBlackout(1500);
  });
  window.addEventListener('pagehide', () => {
    if (!isFilePickerActive) flashBlackout(1500);
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && !isFilePickerActive) {
      blackoutOverlay.style.opacity = '1';
      blackoutOverlay.style.pointerEvents = 'auto';
    } else {
      setTimeout(() => {
        blackoutOverlay.style.opacity = '0';
        blackoutOverlay.style.pointerEvents = 'none';
      }, 300);
    }
  });

  // 3. Image Protection & Touch Gesture Handling
  document.addEventListener('touchstart', (e) => {
    // Detect 2-finger or 3-finger swipe gesture (Common screenshot gesture)
    if (e.touches && e.touches.length >= 2 && !isFilePickerActive) {
      flashBlackout(1500);
      showPrivacyToast('Screenshots disabled on VARSAATHI');
    }

    // Disable long-press image download menu on iOS / Android
    if (e.target.tagName === 'IMG' || e.target.closest('img')) {
      e.target.style.webkitTouchCallout = 'none';
      e.target.style.webkitUserSelect = 'none';
      e.target.style.userSelect = 'none';
    }
  }, { passive: true });

  // 4. Disable Right-Click Context Menu & Photo Saving on images
  document.addEventListener('contextmenu', (e) => {
    if (e.target.tagName === 'IMG' || e.target.closest('img')) {
      e.preventDefault();
      showPrivacyToast('Photo saving disabled');
      return false;
    }
  });

  // 5. Disable Image Drag-and-Drop
  document.addEventListener('dragstart', (e) => {
    if (e.target.tagName === 'IMG' || e.target.closest('img')) {
      e.preventDefault();
      return false;
    }
  });

  // 6. Keyboard Shortcut Interception (PrintScreen, Ctrl+S, Cmd+S, Ctrl+P, F12, DevTools)
  window.addEventListener('keydown', (e) => {
    if (e.key === 'PrintScreen' || e.keyCode === 44) {
      e.preventDefault();
      flashBlackout(1800);
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText('');
        }
      } catch(err) {}
      showPrivacyToast('Screenshots are blacked out on VARSAATHI');
      return false;
    }

    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P')) {
      e.preventDefault();
      showPrivacyToast('Saving webpage or photos is disabled');
      return false;
    }

    if (e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c'))) {
      e.preventDefault();
      showPrivacyToast('Developer tools disabled for security');
      return false;
    }
  });

  console.log('[VARSAATHI Security Engine] Pitch-Black Anti-Screenshot System Active 🔒');
})();
