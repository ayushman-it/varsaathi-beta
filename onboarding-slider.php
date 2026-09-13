<?php
// onboarding-slider.php
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
  <title>Welcome - VARSAATHI Onboarding</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
  <style>
    .slider-container {
      display: flex;
      flex-direction: column;
      height: 100%;
      background: #FFF;
      justify-content: space-between;
    }
    .slider-hero {
      width: 100%;
      height: 48vh;
      background: #FDF0F6;
      border-radius: 0 0 50% 50% / 0 0 40px 40px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .slider-hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .slider-body {
      padding: 24px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      justify-content: center;
    }
    .slider-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--ios-text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      line-height: 1.3;
      margin-bottom: 12px;
    }
    .slider-desc {
      font-size: 0.85rem;
      color: #71717A;
      line-height: 1.5;
      max-width: 320px;
      margin-bottom: 24px;
    }
    .slider-dots {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-bottom: 24px;
    }
    .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      border: 1.5px solid #D4D4D8;
      background: transparent;
      transition: all 0.3s ease;
    }
    .dot.active {
      width: 28px;
      border-radius: 12px;
      background: var(--ios-pink);
      border-color: var(--ios-pink);
    }
    .slider-controls {
      display: flex;
      gap: 12px;
      padding: 0 24px 32px 24px;
      width: 100%;
    }
    .btn-skip {
      flex: 1;
      height: 48px;
      border-radius: 24px;
      background: #FDF0F6;
      color: var(--ios-pink);
      border: none;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .btn-next {
      flex: 1;
      height: 48px;
      border-radius: 24px;
      background: var(--ios-gradient);
      color: #FFF;
      border: none;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(255, 45, 85, 0.35);
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="slider-container">

      <!-- Top Image Hero (Curved bottom curve) -->
      <div class="slider-hero">
        <img id="slideImg" src="https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?auto=format&fit=crop&w=800&q=80" alt="Varsaathi Love">
      </div>

      <!-- Slide Content Text -->
      <div class="slider-body">
        <h2 class="slider-title" id="slideTitle">Welcome to Varsaathi- Where Love Meets!</h2>
        <p class="slider-desc" id="slideDesc">
          Discover genuine connections with people around you who share your passion, values, and energy.
        </p>

        <!-- Pagination Dots (Matching Image 2) -->
        <div class="slider-dots">
          <span class="dot active" id="dot0"></span>
          <span class="dot" id="dot1"></span>
          <span class="dot" id="dot2"></span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="slider-controls">
        <a href="auth-choice.php" class="btn-skip">Skip</a>
        <button type="button" class="btn-next" onclick="nextSlide()">Next</button>
      </div>

    </div>
  </div>

  <script>
    const slides = [
      {
        img: 'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?auto=format&fit=crop&w=800&q=80',
        title: 'Welcome - Right Life Partner Match',
        desc: 'For suggesting genuine life partner matches anytime, anywhere with verified profiles.'
      },
      {
        img: 'https://images.unsplash.com/photo-1522673607200-164d1b6ce486?auto=format&fit=crop&w=800&q=80',
        title: 'Find Exactly the Right Partner for You!',
        desc: 'Filter by education, occupation, diet, and family preferences to find your perfect soulmate.'
      },
      {
        img: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80',
        title: 'Congrats! Start Your Journey Together',
        desc: 'Connect in real-time with voice & video calls, instant messaging, and complete privacy.'
      }
    ];

    let currentIndex = 0;

    function nextSlide() {
      currentIndex++;
      if (currentIndex >= slides.length) {
        window.location.href = 'auth-choice.php';
        return;
      }
      updateSlide();
    }

    function updateSlide() {
      document.getElementById('slideImg').src = slides[currentIndex].img;
      document.getElementById('slideTitle').textContent = slides[currentIndex].title;
      document.getElementById('slideDesc').textContent = slides[currentIndex].desc;

      for (let i = 0; i < 3; i++) {
        document.getElementById(`dot${i}`).className = (i === currentIndex) ? 'dot active' : 'dot';
      }
    }
  </script>
</body>
</html>
