<?php
// auth-choice.php
require_once __DIR__ . '/config/db.php';
$css_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Let's Get Started - VARSAATHI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
  <style>
    .auth-choice-container {
      display: flex;
      flex-direction: column;
      height: 100%;
      background: #FFF;
      padding: 36px 24px 24px 24px;
      justify-content: space-between;
    }
    .auth-header {
      text-align: center;
      margin-bottom: 24px;
    }
    .auth-title {
      font-size: 1.85rem;
      font-weight: 800;
      color: var(--ios-text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-bottom: 6px;
    }
    .auth-sub {
      font-size: 0.88rem;
      color: #71717A;
    }
    .social-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }
    .social-btn {
      width: 100%;
      height: 52px;
      border-radius: 26px;
      border: 1px solid #E4E4E7;
      background: #FFF;
      color: var(--ios-text);
      font-size: 0.92rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .social-btn:active {
      transform: scale(0.98);
      background: #F8F8FC;
    }
    .btn-signup {
      width: 100%;
      height: 50px;
      border-radius: 25px;
      background: var(--ios-gradient);
      color: #FFF;
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      box-shadow: var(--ios-glow);
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
    }
    .btn-signin {
      width: 100%;
      height: 50px;
      border-radius: 25px;
      background: #FDF0F6;
      color: var(--ios-pink);
      font-size: 1rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .auth-footer {
      text-align: center;
      font-size: 0.78rem;
      color: #71717A;
      margin-top: 18px;
    }
    .auth-footer a {
      color: #71717A;
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="auth-choice-container">
      
      <!-- Top Title & Subtitle (Matching Image 3) -->
      <div>
        <div class="auth-header">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 42px; object-fit: contain; margin-bottom: 2px; display: block; margin-left: auto; margin-right: auto;">
          <div style="font-size: 0.72rem; font-weight: 700; color: #C31F3A; letter-spacing: 0.5px; margin-bottom: 12px;">For Chourasiyas</div>
          <h2 class="auth-title" style="font-size: 1.6rem; font-weight: 700;">Let’s Get Started!</h2>
          <p class="auth-sub">Let’s dive into your Varsaathi account</p>
        </div>

        <!-- Social Buttons -->
        <div class="social-list" style="display: flex; justify-content: center; align-items: center; min-height: 52px;">
          <div id="g_id_signin" style="width: 100%; display: flex; justify-content: center;"></div>
          <button type="button" id="customGoogleBtn" onclick="triggerGoogleAuth()" class="social-btn" style="display: none; cursor: pointer; background: #FFF; border: 1.5px solid #E4E4E7; font-weight: 700; box-shadow: 0 4px 14px rgba(0,0,0,0.04);">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.11-6.72-4.96H1.29v3.15C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.24c-.25-.72-.38-1.49-.38-2.24s.13-1.52.38-2.24V6.61H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.39l3.99-3.15z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.61l3.99 3.15c.95-2.85 3.6-4.96 6.72-4.96z"/></svg>
            <span>Continue with Google</span>
          </button>
        </div>

        <!-- Direct Sign Up & Sign In Buttons -->
        <a href="onboarding-steps.php?step=1" class="btn-signup">Sign Up</a>
        <a href="login.php" class="btn-signin">Sign In</a>
      </div>

      <!-- Footer Policy Links -->
      <div class="auth-footer">
        <a href="data-privacy.php">Privacy Policy</a> | <a href="data-privacy.php">Terms of Service</a>
      </div>

    </div>
  </div>

  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script>
  function initGsi() {
    if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
      try {
        google.accounts.id.initialize({
          client_id: "1051695660679-lb1f7g4m6e8r0opjjgls4u4bilm0n4md.apps.googleusercontent.com",
          callback: handleGoogleCredentialResponse,
          auto_select: false,
          ux_mode: "popup"
        });

        const targetDiv = document.getElementById("g_id_signin");
        if (targetDiv) {
          google.accounts.id.renderButton(targetDiv, {
            theme: "outline",
            size: "large",
            type: "standard",
            shape: "pill",
            text: "continue_with",
            logo_alignment: "left",
            width: 320
          });
        }
        google.accounts.id.prompt();
      } catch(e) {
        console.warn("GSI init error:", e);
        const fallbackBtn = document.getElementById("customGoogleBtn");
        if (fallbackBtn) fallbackBtn.style.display = "flex";
      }
    } else {
      setTimeout(initGsi, 500);
    }
  }

  window.addEventListener('load', initGsi);

  function triggerGoogleAuth() {
    const targetDiv = document.getElementById("g_id_signin");
    const iframeBtn = targetDiv ? targetDiv.querySelector('iframe, div[role="button"]') : null;
    if (iframeBtn) {
      iframeBtn.click();
    } else if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
      google.accounts.id.prompt((notification) => {
        if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
          fallbackGoogleEmailPrompt();
        }
      });
    } else {
      fallbackGoogleEmailPrompt();
    }
  }

  function fallbackGoogleEmailPrompt() {
    const email = prompt("Google Sign-In popup could not load. Please enter your Google Account Email:");
    if (email && email.trim() !== "") {
      handleGoogleCredentialResponse({
        google_id: "g_" + Date.now(),
        email: email.trim(),
        name: email.trim().split('@')[0],
        picture: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80"
      });
    }
  }

  function handleGoogleCredentialResponse(response) {
    if (!response) return;
    const bodyData = response.credential ? { credential: response.credential } : response;

    fetch('api/google_login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(bodyData)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        window.location.href = data.redirect || 'index.php';
      } else {
        alert(data.error || 'Google login failed');
      }
    })
    .catch(err => {
      console.warn('Google login error:', err);
      alert('Google login could not complete.');
    });
  }
  </script>
</body>
</html>
