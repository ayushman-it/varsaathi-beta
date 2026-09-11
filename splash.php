<?php
// splash.php
require_once __DIR__ . '/config/db.php';
if (is_logged_in()) {
    header("Location: index.php");
    exit;
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
  <title>VARSAATHI - Match, Chat, Love!</title>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
</head>
<body>
  <div class="app-container">
    <div style="
      height: 100%;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.15) 0%, rgba(0, 0, 0, 0.65) 100%),
                  url('https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?auto=format&fit=crop&w=900&q=80') center/cover no-repeat;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      align-items: center;
      padding: 48px 24px;
      color: #FFF;
      text-align: center;
      position: relative;
    ">
      <!-- Varsaathi Interlocking Logo & Branding -->
      <div style="margin-bottom: 42px; display: flex; flex-direction: column; align-items: center;">
        <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 12px 26px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); margin-bottom: 16px; display: inline-flex; align-items: center; justify-content: center;">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 48px; object-fit: contain;">
        </div>
        <p style="font-size: 0.88rem; letter-spacing: 3.5px; font-weight: 700; text-transform: uppercase; opacity: 0.95; font-family: 'Plus Jakarta Sans', sans-serif;">MATCH, CHAT, LOVE!</p>
      </div>

      <!-- Let's Start Button (Matching Image 1 Pill Button) -->
      <a href="onboarding-slider.php" class="btn-block" style="
        background: var(--ios-gradient);
        color: #FFF;
        height: 52px;
        font-size: 1.05rem;
        font-weight: 800;
        border-radius: 28px;
        box-shadow: 0 8px 24px rgba(255, 45, 85, 0.4);
        max-width: 310px;
      ">Let’s Start</a>
    </div>
  </div>
</body>
</html>
