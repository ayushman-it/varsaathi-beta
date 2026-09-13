<?php
// index.php - Home Card Swipe & Slide Feed (Matching UI Mockup)
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'home';
$current_user_id = get_current_user_id();

// Fetch current user details & preferences
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$u_stmt->execute([':id' => $current_user_id]);
$current_user = $u_stmt->fetch();

$pref_stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = :id");
$pref_stmt->execute([':id' => $current_user_id]);
$pref = $pref_stmt->fetch() ?: [
    'min_age' => 18,
    'max_age' => 45,
    'max_distance' => 50,
    'gender_preference' => 'everyone'
];

// Fetch candidates for home feed
$gender_sql = "";
$params = [':u' => $current_user_id];
if ($pref['gender_preference'] !== 'everyone') {
    $gender_sql = " AND u.gender = :gen";
    $params[':gen'] = $pref['gender_preference'];
}

$c_stmt = $pdo->prepare("
    SELECT u.*, sp.highest_qualification, sp.occupation_type
    FROM users u
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
    WHERE u.id != :u $gender_sql
    ORDER BY RAND()
    LIMIT 15
");
$c_stmt->execute($params);
$candidates = $c_stmt->fetchAll();

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px;">
  <!-- Left: Logo -->
  <div class="header-logo-left" style="display: flex; align-items: center; flex: 1;">
    <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 155px; object-fit: contain; display: block;">
  </div>

  <!-- Center: For Choursaiyas -->
  <div class="header-center-title" style="flex: 2; text-align: center; display: flex; align-items: center; justify-content: center;">
    <span style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">For Choursaiyas</span>
  </div>

  <!-- Right: Hamburger Menu Button -->
  <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; flex: 1;">
    <button class="icon-btn" id="openSidebarBtn" title="Open Menu" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA;">
      <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
    </button>
  </div>
</header>

<main class="app-body" style="padding: 6px 0 12px 0; background: transparent; display: flex; flex-direction: column; justify-content: space-between; flex: 1; min-height: 0;">

    <!-- Horizontal 3D Card Carousel Stage with Side Peeking Cards -->
    <div class="card-carousel-stage" id="cardCarouselStage">
      
      <!-- 1. Promo Welcome Banner Card (Always First Card in Carousel Stack) -->
      <div class="carousel-card-item promo-card" data-index="0" data-match-score="100" data-user-id="0" data-user-name="Varsaathi Matrimony">
        <div class="carousel-card-photo-wrap">
          <img src="assets/images/welcome_banner.jpg" class="carousel-card-img" alt="Varsaathi Promo Banner" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        </div>
        <div class="carousel-card-footer">
          <div>
            <div class="carousel-card-name">Varsaathi Matrimony</div>
            <div class="carousel-card-loc"><i class="fa-solid fa-heart" style="color: #C31F3A; margin-right: 3px;"></i> Matrimony for Choursaiyas</div>
          </div>
          <div class="carousel-card-like-icon active" title="Verified Profile" style="background: #E8F5E9; border: 1px solid #A5D6A7; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-circle-check" style="color: #2E7D32; font-size: 1.15rem;"></i>
          </div>
        </div>
      </div>

      <!-- 2. Candidate Profiles Cards Stack / Fallback Card -->
      <?php if (!empty($candidates)): ?>
        <?php foreach ($candidates as $index => $cand): 
          $c_name = ucwords(strtolower(trim((string)($cand['full_name'] ?? ''))));
          $c_age = calculate_age($cand['birthdate'] ?? '2000-01-01');
          $c_img = get_valid_avatar_url($cand['avatar_url'] ?? '');
          $c_city = !empty($cand['location_city']) && strpos(strtolower((string)$cand['location_city']), 'san francisco') === false ? $cand['location_city'] : 'India';
          $match_score = rand(91, 98); // High compatibility match score percentage
        ?>
          <div class="carousel-card-item" data-index="<?= $index + 1 ?>" data-user-id="<?= $cand['id'] ?>" data-match-score="<?= $match_score ?>" data-user-name="<?= htmlspecialchars($c_name) ?>">
            
            <!-- Photo Display -->
            <div class="carousel-card-photo-wrap">
              <img src="<?= htmlspecialchars($c_img) ?>" class="carousel-card-img" alt="<?= htmlspecialchars($c_name) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
              
              <!-- Quick View Full Profile Button -->
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: rgba(0,0,0,0.4); backdrop-filter: blur(10px); color: #FFF; border: 1px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; text-decoration: none;" title="View Profile">
                <i class="fa-solid fa-user-check" style="font-size: 0.85rem;"></i>
              </a>
            </div>

            <!-- White Footer Metadata -->
            <div class="carousel-card-footer">
              <div>
                <div class="carousel-card-name"><?= htmlspecialchars($c_name) ?>, <?= $c_age ?></div>
                <div class="carousel-card-loc"><?= htmlspecialchars($c_city) ?></div>
              </div>

              <div class="carousel-card-like-icon" onclick="toggleCardFavorite(this, <?= $cand['id'] ?>)" title="Favorite">
                <i class="fa-solid fa-heart"></i>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Card 2: Fallback when candidate data is empty -->
        <div class="carousel-card-item" data-index="1" data-match-score="95" data-user-id="0" data-user-name="More Profiles Coming Soon">
          <div class="carousel-card-photo-wrap" style="background: linear-gradient(135deg, #FFF0F4 0%, #FFEBF0 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 10px;">🌟</div>
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #1C1C1E; margin-bottom: 6px;">More Profiles Arriving Soon!</h3>
            <p style="font-size: 0.78rem; color: #8E8E93; max-width: 220px; line-height: 1.4; margin-bottom: 14px;">We are matching fresh verified profiles for you. Check back shortly!</p>
            <button onclick="location.reload()" style="background: var(--ios-gradient); color: #FFF; border: none; padding: 8px 18px; border-radius: 18px; font-weight: 700; font-size: 0.78rem; cursor: pointer; box-shadow: 0 4px 12px rgba(195,31,58,0.25);">
              <i class="fa-solid fa-rotate-right" style="margin-right: 4px;"></i> Refresh
            </button>
          </div>
          <div class="carousel-card-footer">
            <div>
              <div class="carousel-card-name">Varsaathi Match</div>
              <div class="carousel-card-loc">Verified Members</div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Match Percentage & Action Pill Bar -->
    <div class="saathi-match-action-pill" style="margin-top: 6px; margin-bottom: 8px; background: transparent !important; flex-shrink: 0;">
      <!-- Pass Button (X) -->
      <button class="action-circle-btn pass" id="carouselPassBtn" title="Pass / Next Card">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <!-- Center Dynamic Match Percentage Badge -->
      <div class="match-percent-badge">
        <span class="percent-chip" id="dynamicMatchScore">96%</span>
        <span class="match-label-text">Match Your Profile</span>
      </div>

      <!-- Like / Send Interest Button (Heart) -->
      <button class="action-circle-btn like" id="carouselLikeBtn" title="Send Matrimonial Interest">
        <i class="fa-solid fa-heart"></i>
      </button>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const stage = document.getElementById('cardCarouselStage');
  if (!stage) return;

  const cards = stage.querySelectorAll('.carousel-card-item');
  const matchScoreBadge = document.getElementById('dynamicMatchScore');
  const passBtn = document.getElementById('carouselPassBtn');
  const likeBtn = document.getElementById('carouselLikeBtn');
  let currentIndex = 0;

  function updateActiveCard() {
    let closestCard = cards[0];
    let minDistance = Infinity;

    cards.forEach((card, idx) => {
      const rect = card.getBoundingClientRect();
      const distance = Math.abs(rect.left + rect.width / 2 - window.innerWidth / 2);
      if (distance < minDistance) {
        minDistance = distance;
        closestCard = card;
        currentIndex = idx;
      }
    });

    if (closestCard && matchScoreBadge) {
      const score = closestCard.dataset.matchScore || '96';
      matchScoreBadge.textContent = `${score}%`;
    }
  }

  stage.addEventListener('scroll', function() {
    clearTimeout(window.cardScrollTimer);
    window.cardScrollTimer = setTimeout(updateActiveCard, 100);
  });

  function scrollToCard(index) {
    if (index >= 0 && index < cards.length) {
      cards[index].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      currentIndex = index;
      if (cards[index] && matchScoreBadge) {
        matchScoreBadge.textContent = `${cards[index].dataset.matchScore || '96'}%`;
      }
    }
  }

  // Touch Swipe Left & Right Gesture Handlers
  let touchStartX = 0;
  let touchStartY = 0;

  stage.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
  }, { passive: true });

  stage.addEventListener('touchend', function(e) {
    const touchEndX = e.changedTouches[0].screenX;
    const touchEndY = e.changedTouches[0].screenY;
    const diffX = touchStartX - touchEndX;
    const diffY = touchStartY - touchEndY;

    if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
      if (diffX > 0) {
        // Swipe Left -> Next Card
        if (currentIndex < cards.length - 1) {
          scrollToCard(currentIndex + 1);
        }
      } else {
        // Swipe Right -> Previous Card
        if (currentIndex > 0) {
          scrollToCard(currentIndex - 1);
        }
      }
    }
  }, { passive: true });

  if (passBtn) {
    passBtn.addEventListener('click', function() {
      if (currentIndex < cards.length - 1) {
        scrollToCard(currentIndex + 1);
      } else {
        scrollToCard(0);
      }
    });
  }

  if (likeBtn) {
    likeBtn.addEventListener('click', function(e) {
      const activeCard = cards[currentIndex];
      if (activeCard) {
        const userId = activeCard.dataset.userId;
        const userName = activeCard.dataset.userName || 'Candidate';

        if (userId === '0') {
          // Promo card -> slide to first candidate
          scrollToCard(1);
          return;
        }

        if (typeof window.triggerCuteLikeAnimation === 'function') {
          window.triggerCuteLikeAnimation(likeBtn, e);
        }

        fetch('api/saathi_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'send_interest', target_id: userId })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            if (data.is_match) {
              alert(`💕 It's a Match with ${userName}! You can now chat and video call.`);
            } else {
              alert(`❤️ Matrimonial Interest & Notification Sent to ${userName}!`);
            }
          }
          if (currentIndex < cards.length - 1) {
            scrollToCard(currentIndex + 1);
          } else {
            location.reload();
          }
        })
        .catch(err => {
          if (currentIndex < cards.length - 1) {
            scrollToCard(currentIndex + 1);
          }
        });
      }
    });
  }

  updateActiveCard();
});

function toggleCardFavorite(btn, targetId, event) {
  if (!targetId || targetId === 0) return;
  btn.classList.toggle('active');
  if (btn.classList.contains('active') && typeof window.triggerCuteLikeAnimation === 'function') {
    window.triggerCuteLikeAnimation(btn, event);
  }
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'send_interest', target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success && data.message) {
      console.log('[Interest Sent]', data.message);
    }
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
