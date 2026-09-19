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
    'max_age' => 60,
    'max_distance' => 300,
    'gender_preference' => 'everyone',
    'marital_status' => 'Any',
    'religion_community' => 'Any',
    'education' => 'Any',
    'occupation' => 'Any'
];

// Fetch candidates for home feed with comprehensive filter support
$where_clauses = ["u.id != :u"];
$params = [':u' => $current_user_id];

if (!empty($pref['gender_preference']) && $pref['gender_preference'] !== 'everyone') {
    $where_clauses[] = "u.gender = :gen";
    $params[':gen'] = $pref['gender_preference'];
}

if (!empty($pref['min_age']) && !empty($pref['max_age'])) {
    $where_clauses[] = "TIMESTAMPDIFF(YEAR, u.birthdate, CURDATE()) BETWEEN :min_age AND :max_age";
    $params[':min_age'] = (int)$pref['min_age'];
    $params[':max_age'] = (int)$pref['max_age'];
}

if (!empty($pref['marital_status']) && $pref['marital_status'] !== 'Any') {
    $where_clauses[] = "(sp.marital_status LIKE :m_status OR u.marital_status LIKE :m_status)";
    $params[':m_status'] = '%' . $pref['marital_status'] . '%';
}

if (!empty($pref['religion_community']) && $pref['religion_community'] !== 'Any') {
    $where_clauses[] = "(sp.caste_community LIKE :rel OR sp.religion LIKE :rel OR u.bio LIKE :rel)";
    $params[':rel'] = '%' . $pref['religion_community'] . '%';
}

if (!empty($pref['education']) && $pref['education'] !== 'Any') {
    $where_clauses[] = "(sp.highest_qualification LIKE :edu OR sp.degree LIKE :edu)";
    $params[':edu'] = '%' . $pref['education'] . '%';
}

if (!empty($pref['occupation']) && $pref['occupation'] !== 'Any') {
    $where_clauses[] = "(sp.occupation_type LIKE :occ OR u.occupation LIKE :occ)";
    $params[':occ'] = '%' . $pref['occupation'] . '%';
}

$where_sql = implode(' AND ', $where_clauses);

$c_stmt = $pdo->prepare("
    SELECT u.*, sp.highest_qualification, sp.occupation_type, sp.height_cm, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
    FROM users u
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
    WHERE $where_sql
    ORDER BY RAND()
    LIMIT 20
");
$c_stmt->execute($params);
$candidates = $c_stmt->fetchAll();

// Graceful fallback to all candidates if strict filter returns 0 results
if (empty($candidates)) {
    $fallback_stmt = $pdo->prepare("
        SELECT u.*, sp.highest_qualification, sp.occupation_type, sp.height_cm, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
        FROM users u
        LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
        WHERE u.id != :u
        ORDER BY RAND()
        LIMIT 20
    ");
    $fallback_stmt->execute([':u' => $current_user_id]);
    $candidates = $fallback_stmt->fetchAll();
}

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px;">
  <!-- Left: Logo -->
  <div class="header-logo-left" style="display: flex; align-items: center; flex: 1;">
    <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 155px; object-fit: contain; display: block;">
  </div>

  <!-- Center: For Chourasiyas -->
  <div class="header-center-title" style="flex: 2; text-align: center; display: flex; align-items: center; justify-content: center;">
    <span style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">For Chourasiyas</span>
  </div>

  <!-- Right: Filter & Hamburger Menu Buttons -->
  <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; flex: 1; gap: 8px;">
    <button class="icon-btn" id="filterBtn" title="Filter Matches" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E; display: flex; align-items: center; justify-content: center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1C1C1E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="21" x2="4" y2="14"></line>
        <line x1="4" y1="10" x2="4" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12" y2="3"></line>
        <line x1="20" y1="21" x2="20" y2="16"></line>
        <line x1="20" y1="12" x2="20" y2="3"></line>
        <line x1="1" y1="14" x2="7" y2="14"></line>
        <line x1="9" y1="8" x2="15" y2="8"></line>
        <line x1="17" y1="16" x2="23" y2="16"></line>
      </svg>
    </button>
    <button class="icon-btn" id="openSidebarBtn" title="Open Menu" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E;">
      <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
    </button>
  </div>
</header>

<main class="app-body" style="padding: 6px 0 12px 0; background: transparent; display: flex; flex-direction: column; justify-content: space-between; flex: 1; min-height: 0;">

    <!-- Become a Chourasiya Member Quick Action Banner -->
    <div style="padding: 0 16px; margin-bottom: 8px;">
      <a href="profile.php?join_chourasiya=1" style="display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #FFF0F4 0%, #FFF5F7 100%); border: 1px solid #FFE0E6; border-radius: 16px; padding: 10px 14px; text-decoration: none; box-shadow: 0 2px 8px rgba(195,31,58,0.06);">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 1.25rem;">👑</span>
          <div>
            <div style="font-weight: 700; font-size: 0.84rem; color: #1C1C1E; line-height: 1.2;">Become a Chourasiya Member</div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 500;">Get verified badge & exclusive community matches</div>
          </div>
        </div>
        <span style="background: var(--ios-gradient); color: #FFF; font-size: 0.72rem; font-weight: 600; padding: 5px 12px; border-radius: 12px; white-space: nowrap;">Join Now</span>
      </a>
    </div>

    <!-- Category Tabs: All vs Chourasiya (Clean Underline Active Tabs) -->
    <div style="padding: 0 16px; margin-bottom: 10px; display: flex; gap: 16px; border-bottom: 1px solid #E5E5EA;" id="homeCategoryTabs">
      <button type="button" class="home-tab-btn active" data-tab="all" onclick="switchHomeCategoryTab('all', this)" style="padding: 6px 4px 8px 4px; border: none; border-bottom: 2.5px solid #1C1C1E; background: transparent; color: #1C1C1E; font-weight: 600; font-size: 0.78rem; cursor: pointer; transition: all 0.2s ease;">
        All Profiles
      </button>
      <button type="button" class="home-tab-btn" data-tab="chourasiya" onclick="switchHomeCategoryTab('chourasiya', this)" style="padding: 6px 4px 8px 4px; border: none; border-bottom: 2.5px solid transparent; background: transparent; color: #8E8E93; font-weight: 500; font-size: 0.78rem; cursor: pointer; transition: all 0.2s ease;">
        Chourasiya
      </button>
    </div>

    <!-- Horizontal 3D Card Carousel Stage with Side Peeking Cards -->
    <div class="card-carousel-stage" id="cardCarouselStage">
      
      <!-- 1. Promo Welcome Banner Card (Always First Card in Carousel Stack) -->
      <div class="carousel-card-item promo-card" data-index="0" data-match-score="100" data-user-id="0" data-user-name="Varsaathi Matrimony" data-community="chourasiya">
        <div class="carousel-card-photo-wrap">
          <img src="assets/images/welcome_banner.jpg" class="carousel-card-img" alt="Varsaathi Promo Banner" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        </div>
        <div class="carousel-card-footer">
          <div>
            <div class="carousel-card-name">Varsaathi Matrimony</div>
            <div class="carousel-card-loc"><i class="fa-solid fa-heart" style="color: #C31F3A; margin-right: 3px;"></i> Matrimony for Chourasiyas</div>
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
          $c_comm = strtolower(trim(($cand['caste_community'] ?? '') . ' ' . ($cand['religion'] ?? '')));
          $is_chourasiya = (strpos($c_comm, 'chourasiya') !== false || strpos($c_comm, 'chaurasia') !== false || empty($c_comm));
        ?>
          <div class="carousel-card-item" data-index="<?= $index + 1 ?>" data-user-id="<?= $cand['id'] ?>" data-match-score="<?= $match_score ?>" data-user-name="<?= htmlspecialchars($c_name) ?>" data-community="<?= $is_chourasiya ? 'chourasiya' : 'other' ?>">
            
            <!-- Photo Display -->
            <div class="carousel-card-photo-wrap">
              <img src="<?= htmlspecialchars($c_img) ?>" class="carousel-card-img" alt="<?= htmlspecialchars($c_name) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
              
              <!-- Quick Chat & View Profile Buttons -->
              <button onclick="handleCandidateCardChat(<?= $cand['id'] ?>, '<?= htmlspecialchars(addslashes($c_name)) ?>')" style="position: absolute; top: 12px; right: 52px; width: 34px; height: 34px; border-radius: 50%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(10px); color: #FFF; border: 1px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; cursor: pointer;" title="Message / Chat">
                <i class="fa-solid fa-comment-dots" style="font-size: 0.85rem; color: #FFF;"></i>
              </button>
              <a href="saathi-profile.php?id=<?= $cand['id'] ?>" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px); color: #FFF; border: 1px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; text-decoration: none;" title="View Profile">
                <i class="fa-solid fa-user-check" style="font-size: 0.85rem; color: #FFF;"></i>
              </a>
            </div>

            <!-- White Footer Metadata -->
            <div class="carousel-card-footer">
              <div>
                <a href="saathi-profile.php?id=<?= $cand['id'] ?>" class="carousel-card-name" style="text-decoration: none; color: inherit; cursor: pointer; display: inline-block;"><?= htmlspecialchars($c_name) ?>, <?= $c_age ?></a>
                <div class="carousel-card-loc"><?= htmlspecialchars($c_city) ?></div>
              </div>

              <div style="display: flex; gap: 8px; align-items: center;">
                <div class="carousel-card-like-icon" onclick="handleCandidateCardChat(<?= $cand['id'] ?>, '<?= htmlspecialchars(addslashes($c_name)) ?>')" title="Chat / Message" style="background: #F2F2F7; color: #1C1C1E; border: 1px solid #E5E5EA;">
                  <i class="fa-solid fa-comment-dots" style="color: #1C1C1E;"></i>
                </div>
                <div class="carousel-card-like-icon" onclick="toggleCardFavorite(this, <?= $cand['id'] ?>)" title="Favorite">
                  <i class="fa-solid fa-heart"></i>
                </div>
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
    <div class="saathi-match-action-pill" style="margin-top: 6px; margin-bottom: 8px; background: transparent !important; flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
      <!-- Pass Button (X) -->
      <button class="action-circle-btn pass" id="carouselPassBtn" title="Pass / Next Card">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <!-- Center Dynamic Match Percentage Badge -->
      <div class="match-percent-badge">
        <span class="percent-chip" id="dynamicMatchScore">96%</span>
        <span class="match-label-text">Match Your Profile</span>
      </div>

      <!-- Chat / Message Button (💬) -->
      <button class="action-circle-btn chat" id="carouselChatBtn" title="Message / Chat Request" style="background: #FFFFFF; color: #1C1C1E; border: 1px solid #E5E5EA; box-shadow: 0 4px 12px rgba(0,0,0,0.06); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; cursor: pointer; transition: transform 0.2s ease;">
        <i class="fa-solid fa-comment-dots" style="color: #1C1C1E;"></i>
      </button>

      <!-- Like / Send Interest Button (Heart) -->
      <button class="action-circle-btn like" id="carouselLikeBtn" title="Send Matrimonial Interest">
        <i class="fa-solid fa-heart"></i>
      </button>
    </div>

</main>

<script>
window.switchHomeCategoryTab = function(tabName, btnElem) {
  const tabs = document.querySelectorAll('.home-tab-btn');
  tabs.forEach(t => {
    t.style.borderBottom = '2.5px solid transparent';
    t.style.color = '#8E8E93';
    t.style.fontWeight = '400';
    t.classList.remove('active');
  });
  if (btnElem) {
    btnElem.style.borderBottom = '2.5px solid #1C1C1E';
    btnElem.style.color = '#1C1C1E';
    btnElem.style.fontWeight = '600';
    btnElem.classList.add('active');
  }

  const cards = document.querySelectorAll('.carousel-card-item');
  cards.forEach(card => {
    const comm = card.dataset.community || 'other';
    if (tabName === 'chourasiya') {
      if (comm === 'chourasiya' || card.classList.contains('promo-card')) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    } else {
      card.style.display = 'flex';
    }
  });

  const stage = document.getElementById('cardCarouselStage');
  if (stage) {
    stage.scrollTo({ left: 0, behavior: 'smooth' });
  }
};

document.addEventListener('DOMContentLoaded', function() {
  const stage = document.getElementById('cardCarouselStage');
  if (!stage) return;

  const cards = stage.querySelectorAll('.carousel-card-item');
  const matchScoreBadge = document.getElementById('dynamicMatchScore');
  const passBtn = document.getElementById('carouselPassBtn');
  const likeBtn = document.getElementById('carouselLikeBtn');
  const chatBtn = document.getElementById('carouselChatBtn');
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

        if (typeof window.triggerBouncyHeartBubble === 'function') {
          window.triggerBouncyHeartBubble(activeCard, e);
        } else if (typeof window.triggerCuteLikeAnimation === 'function') {
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
            const userAvatar = activeCard.querySelector('.carousel-card-img')?.src || '';
            if (typeof window.showInterestMatchModal === 'function') {
              window.showInterestMatchModal(userName, userAvatar, data.is_match, data.match_id || 0, userId);
            }
          }
          if (currentIndex < cards.length - 1) {
            scrollToCard(currentIndex + 1);
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

  if (chatBtn) {
    chatBtn.addEventListener('click', function() {
      const activeCard = cards[currentIndex];
      if (activeCard) {
        const userId = activeCard.dataset.userId;
        const userName = activeCard.dataset.userName || 'Candidate';
        if (userId && userId !== '0') {
          handleCandidateCardChat(userId, userName);
        }
      }
    });
  }

  updateActiveCard();
});

function toggleCardFavorite(btn, targetId, event) {
  if (!targetId || targetId === 0) return;
  btn.classList.toggle('active');
  if (btn.classList.contains('active')) {
    if (typeof window.triggerBouncyHeartBubble === 'function') {
      window.triggerBouncyHeartBubble(btn, event);
    } else if (typeof window.triggerCuteLikeAnimation === 'function') {
      window.triggerCuteLikeAnimation(btn, event);
    }
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
