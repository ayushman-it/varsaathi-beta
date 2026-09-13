<?php
// splash.php - Upgraded Match & Matrimonial Splash Engine
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
  <meta name="theme-color" content="#C31F3A">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>VARSAATHI - Find Your Right Life Partner</title>
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
  <style>
    .splash-wrapper {
      height: 100%;
      width: 100%;
      background: linear-gradient(135deg, #E02847 0%, #C31F3A 45%, #A8162E 80%, #7E0D1F 100%);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 50px 24px 36px 24px;
      color: #FFFFFF;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    /* Floating 3D Hearts Background Effect (Matching Mockup) */
    .floating-heart {
      position: absolute;
      color: rgba(255, 255, 255, 0.25);
      filter: drop-shadow(0 4px 12px rgba(0,0,0,0.15));
      animation: floatHeart 5s infinite ease-in-out;
      pointer-events: none;
      user-select: none;
    }
    .fh-1 { top: 8%; left: 8%; font-size: 2rem; animation-delay: 0s; }
    .fh-2 { top: 18%; right: 10%; font-size: 3.2rem; animation-delay: 1.2s; opacity: 0.35; }
    .fh-3 { top: 44%; left: 6%; font-size: 1.8rem; animation-delay: 2.5s; }
    .fh-4 { bottom: 26%; right: 8%; font-size: 2.6rem; animation-delay: 1.8s; }
    .fh-5 { bottom: 18%; left: 12%; font-size: 1.5rem; animation-delay: 3.5s; }

    @keyframes floatHeart {
      0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
      50% { transform: translateY(-15px) rotate(8deg) scale(1.08); }
    }

    .brand-emblem-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      padding: 14px 28px;
      border-radius: 24px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.22);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
    }

    .brand-emblem-card img {
      height: 48px;
      object-fit: contain;
    }

    .splash-tagline {
      font-size: 0.85rem;
      letter-spacing: 2px;
      font-weight: 700;
      text-transform: uppercase;
      opacity: 0.95;
      color: rgba(255, 255, 255, 0.9);
      margin-top: 4px;
    }

    .splash-subtitle {
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.85);
      margin-top: 8px;
      font-weight: 500;
      max-width: 270px;
      line-height: 1.4;
    }

    .dots-indicator {
      display: flex;
      gap: 6px;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
    }

    .dots-indicator .dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.4);
      transition: all 0.3s ease;
    }

    .dots-indicator .dot.active {
      width: 24px;
      border-radius: 10px;
      background: #FFFFFF;
    }

    .btn-splash-white {
      background: #FFFFFF;
      color: #C31F3A;
      height: 52px;
      font-size: 1rem;
      font-weight: 800;
      border-radius: 26px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 320px;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: transform 0.15s ease;
    }

    .btn-splash-white:active {
      transform: scale(0.97);
    }

    .btn-splash-outline {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.35);
      color: #FFFFFF;
      height: 48px;
      font-size: 0.95rem;
      font-weight: 700;
      border-radius: 24px;
      width: 100%;
      max-width: 320px;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 10px;
      transition: background 0.15s ease;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="splash-wrapper">

      <!-- Floating 3D Hearts Background (Matching Mockup Image) -->
      <div class="floating-heart fh-1"><i class="fa-solid fa-heart"></i></div>
      <div class="floating-heart fh-2"><i class="fa-solid fa-heart"></i></div>
      <div class="floating-heart fh-3"><i class="fa-solid fa-heart"></i></div>
      <div class="floating-heart fh-4"><i class="fa-solid fa-heart"></i></div>
      <div class="floating-heart fh-5"><i class="fa-solid fa-heart"></i></div>

      <!-- Top Section -->
      <div style="margin-top: 30px;">
        <!-- Varsaathi White Emblem Card -->
        <div class="brand-emblem-card" style="flex-direction: column; padding: 12px 24px;">
          <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI">
          <span style="font-size: 0.68rem; font-weight: 700; color: #C31F3A; letter-spacing: 0.5px; margin-top: 4px;">For Choursaiyas</span>
        </div>
        <div class="splash-tagline">RIGHT LIFE PARTNER</div>
        <div class="splash-subtitle">Find exactly the right partner for you! Anytime, Anywhere.</div>
      </div>

      <!-- Center Illustration / Icon Motif -->
      <div style="position: relative; margin: 20px 0;">
        <div style="width: 140px; height: 140px; border-radius: 50%; background: rgba(255, 255, 255, 0.12); display: flex; align-items: center; justify-content: center; margin: 0 auto; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.25); box-shadow: 0 14px 35px rgba(0,0,0,0.18);">
          <i class="fa-solid fa-heart-circle-bolt" style="font-size: 3.6rem; color: #FFFFFF; opacity: 0.95;"></i>
        </div>
      </div>

      <!-- Bottom Controls -->
      <div style="width: 100%; display: flex; flex-direction: column; align-items: center; z-index: 10;">
        
        <!-- Pagination Indicator Dots -->
        <div class="dots-indicator">
          <span class="dot active"></span>
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </div>

        <!-- Action Buttons (Matching Mockup Image) -->
        <a href="onboarding-slider.php" class="btn-splash-white">
          Let’s Get Started! <i class="fa-solid fa-arrow-right" style="font-size: 0.9rem;"></i>
        </a>

        <a href="login.php" class="btn-splash-outline">
          Sign In
        </a>

      </div>

    </div>
  </div>
</body>
</html>
