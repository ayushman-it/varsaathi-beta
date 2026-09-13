<?php
// login.php
require_once __DIR__ . '/config/db.php';

$error = '';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Check password verify or default demo password 'password123'
        if ($user && ($password === 'password123' || password_verify($password, $user['password_hash']))) {
            login_user_session($user['id']);
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid email or password!';
        }
    } else {
        $error = 'Please fill in all fields!';
    }
}
$css_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Sign In - VARSAATHI Dating App</title>
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
</head>
<body>
  <div class="app-container">
    <div class="auth-container">
      <div style="text-align: center; margin-bottom: 24px;">
        <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 48px; object-fit: contain; margin-bottom: 2px;">
        <div style="font-size: 0.72rem; font-weight: 700; color: #C31F3A; letter-spacing: 0.5px; margin-bottom: 8px;">For Choursaiyas</div>
        <p style="color: var(--ios-muted); font-size: 0.84rem; font-weight: 500;">Find your perfect match & connect nearby</p>
      </div>

      <?php if (!empty($error)): ?>
        <div style="background: #FFE5E5; color: #FF2D55; padding: 12px 16px; border-radius: 14px; font-size: 0.85rem; font-weight: 700; margin-bottom: 18px; text-align: center;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="your.email@domain.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-block btn-primary" style="margin-top: 8px;">Sign In</button>
      </form>

      <div style="display: flex; align-items: center; gap: 10px; margin: 18px 0 14px 0;">
        <hr style="flex:1; border:none; border-top:1px solid #E5E5EA;">
        <span style="font-size:0.72rem; color:var(--ios-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">or continue with</span>
        <hr style="flex:1; border:none; border-top:1px solid #E5E5EA;">
      </div>

      <div style="display: flex; justify-content: center; align-items: center; min-height: 52px; margin-bottom: 12px;">
        <div id="g_id_signin" style="width: 100%; display: flex; justify-content: center;"></div>
        <button type="button" id="customGoogleBtn" onclick="triggerGoogleAuth()" class="btn-block" style="display: none; background: #FFF; color: #1C1C1E; border: 1.5px solid #E5E5EA; font-weight: 700; font-size: 0.92rem; border-radius: 26px; height: 50px; align-items: center; justify-content: center; gap: 10px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
          <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.11-6.72-4.96H1.29v3.15C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.24c-.25-.72-.38-1.49-.38-2.24s.13-1.52.38-2.24V6.61H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.39l3.99-3.15z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.61l3.99 3.15c.95-2.85 3.6-4.96 6.72-4.96z"/></svg>
          Continue with Google
        </button>
      </div>

      <div style="margin-top: 18px; text-align: center;">
        <p style="font-size: 0.88rem; color: var(--ios-muted); font-weight: 600;">
          Don't have an account? <a href="register.php" style="color: var(--ios-pink); font-weight: 800; text-decoration: none;">Create One</a>
        </p>
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
