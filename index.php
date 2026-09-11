<?php
// index.php
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
    'max_age' => 35,
    'max_distance' => 50,
    'gender_preference' => 'everyone'
];

// Fetch candidates not swiped yet by current user
$gender_sql = "";
$params = [
    ':current_u1' => $current_user_id,
    ':current_u2' => $current_user_id
];

if ($pref['gender_preference'] !== 'everyone') {
    $gender_sql = " AND u.gender = :gen";
    $params[':gen'] = $pref['gender_preference'];
}

$candidates_query = "
    SELECT u.*
    FROM users u
    WHERE u.id != :current_u1
      $gender_sql
      AND u.id NOT IN (
          SELECT target_id FROM swipes WHERE swiper_id = :current_u2
      )
    ORDER BY RAND()
    LIMIT 10
";

$c_stmt = $pdo->prepare($candidates_query);
$c_stmt->execute($params);
$candidates = $c_stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- iOS App Header -->
<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title" style="display: flex; align-items: center; justify-content: center;">
    <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 180px; object-fit: contain;">
  </div>

  <div class="header-actions">
    <button class="icon-btn" id="filterBtn" title="Preferences">
      <i class="fa-solid fa-sliders"></i>
    </button>
  </div>
</header>

<!-- App Body (Card Deck Feed) -->
<main class="app-body">
  <section class="swipe-section">
    <div class="card-deck" id="cardDeck">

      <!-- Animated Welcome Poster Promo Card (Top of Home Feed Deck Stack) -->
      <div class="profile-card welcome-card" id="welcomePromoCard" style="background: linear-gradient(135deg, #FF2D55, #C31F3A); border-radius: var(--radius-card); overflow: hidden; box-shadow: 0 14px 36px rgba(195,31,58,0.35);">
        <img src="assets/images/welcome_banner.jpg" alt="Match, Chat, Mingal with Varsaathi" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        <div class="card-gradient-overlay" style="background: linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,0.85) 100%);"></div>

        <!-- Stamps -->
        <div class="swipe-stamp like">LIKE</div>
        <div class="swipe-stamp dislike">NOPE</div>

        <!-- Floating Corner Play Button to Swipe Banner Card -->
        <button type="button" onclick="dismissWelcomePromoCard(this)" style="position: absolute; bottom: 16px; right: 16px; width: 44px; height: 44px; border-radius: 50%; background: #FFF; color: #C31F3A; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 6px 20px rgba(0,0,0,0.35); z-index: 20;" title="Swipe Banner Card">
          <i class="fa-solid fa-play" style="margin-left: 2px;"></i>
        </button>
      </div>

      <?php if (empty($candidates)): ?>
        <div class="no-more-cards" style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #FFF; border-radius: var(--radius-card); box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 30px 20px; text-align: center;">
          <div style="font-size: 3.2rem; margin-bottom: 12px;">🌟</div>
          <h3 style="font-weight: 800; font-size: 1.3rem; margin-bottom: 8px; color: var(--ios-text);">No More Profiles!</h3>
          <p style="color: var(--ios-muted); font-size: 0.85rem; line-height: 1.4; max-width: 260px; margin-bottom: 24px;">You've seen all available profiles nearby. Try widening your distance or age filters!</p>
          <button onclick="location.reload()" style="background: var(--ios-gradient); color: #FFF; font-weight: 800; border: none; border-radius: 26px; padding: 14px 28px; font-size: 0.95rem; box-shadow: var(--ios-glow); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-rotate-right"></i> Refresh Feed
          </button>
        </div>
      <?php else: ?>
        <?php foreach ($candidates as $cand): 
          $age = calculate_age($cand['birthdate']);
          $interests = json_decode($cand['interests'] ?? '[]', true) ?: [];
          $placeholder_img = 'assets/images/no_image_placeholder.png';
          $is_cand_private = (bool)($cand['is_private'] ?? 0);
          $avatar_img = !empty($cand['avatar_url']) ? $cand['avatar_url'] : $placeholder_img;
          $photos = json_decode($cand['photos'] ?? '[]', true) ?: [$avatar_img];
          $cover_photo = $is_cand_private ? $avatar_img : ($photos[0] ?? $avatar_img);
        ?>
          <div class="profile-card" data-user-id="<?= $cand['id'] ?>" data-user-name="<?= htmlspecialchars($cand['full_name']) ?>" data-user-avatar="<?= htmlspecialchars($avatar_img) ?>">
            <img src="<?= htmlspecialchars($cover_photo) ?>" alt="<?= htmlspecialchars($cand['full_name']) ?>" class="cover-photo" loading="lazy" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
            <div class="card-gradient-overlay"></div>

            <!-- Stamps -->
            <div class="swipe-stamp like">LIKE</div>
            <div class="swipe-stamp dislike">NOPE</div>

            <div class="card-info">
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="card-name">
                  <?= htmlspecialchars($cand['full_name']) ?>
                  <?php if ($is_cand_private): ?>
                    <span style="font-size: 0.68rem; background: rgba(0,0,0,0.6); color: #FFF; padding: 2px 8px; border-radius: 12px; font-weight: 700; margin-left: 6px; border: 1px solid rgba(255,255,255,0.3); vertical-align: middle;">
                      <i class="fa-solid fa-lock" style="color: var(--ios-pink);"></i> Private
                    </span>
                  <?php endif; ?>
                </span>
                <a href="profile-view.php?id=<?= $cand['id'] ?>" class="icon-btn" style="width: 36px; height: 36px; background: rgba(255,255,255,0.3); color: #FFF; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.4);" title="View Full Profile">
                  <i class="fa-solid fa-circle-info"></i>
                </a>
              </div>

              <div class="card-tags" style="margin-top: 6px;">
                <span class="tag-chip" style="background: rgba(255,255,255,0.28); backdrop-filter: blur(12px); border-radius: 50px;">
                  <i class="fa-solid fa-venus"></i> <?= $age ?> yr
                </span>
                <span class="tag-chip" style="background: rgba(255,255,255,0.28); backdrop-filter: blur(12px); border-radius: 50px;">
                  <i class="fa-solid fa-house"></i> <?= htmlspecialchars($cand['location_city']) ?>
                </span>
                <span class="tag-chip" style="background: rgba(255,255,255,0.28); backdrop-filter: blur(12px); border-radius: 50px;">
                  <i class="fa-solid fa-location-dot"></i> <?= $cand['distance_km'] ?> km
                </span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Floating Action Controls Bar -->
    <div class="action-controls" style="padding: 10px 18px;">
      <button class="ctrl-btn small btn-dislike" id="btnDislike" title="Dislike" style="width: 52px; height: 52px; background: #FFF; color: #333; font-size: 1.2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.08);">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <button class="ctrl-btn large btn-like" id="btnLike" title="Like Profile" style="width: 64px; height: 64px; background: var(--ios-gradient); color: #FFF; font-size: 1.7rem; box-shadow: var(--ios-glow);">
        <i class="fa-solid fa-heart"></i>
      </button>

      <button class="ctrl-btn small" id="btnSuperlike" title="Profile Details" style="width: 52px; height: 52px; background: #FFF; color: var(--ios-purple); font-size: 1.2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.08);">
        <i class="fa-solid fa-info"></i>
      </button>
    </div>
  </section>
</main>

<!-- Redesigned Filter Sheet Modal -->
<div class="modal-overlay" id="filterDrawerModal">
  <div class="modal-drawer">
    <div class="drawer-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; width: 100%;">
      <h3 class="drawer-title" style="font-weight:900; font-size:1.3rem; color:var(--ios-text); margin: 0;">Discovery Filters</h3>
      <button class="icon-btn" id="closeFilterBtn" style="width:36px; height:36px; border-radius: 50%; background: #F4F4F5; border: none; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;" title="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form action="api/filter.php" method="POST">
      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <label class="form-label" style="margin-bottom:0;">Maximum Distance</label>
          <span id="distanceVal" style="font-weight:800; color:var(--ios-pink); font-size:0.88rem; background:rgba(255,45,85,0.1); padding:2px 10px; border-radius:20px;"><?= $pref['max_distance'] ?> km</span>
        </div>
        <input type="range" name="max_distance" id="distanceRange" min="2" max="100" value="<?= $pref['max_distance'] ?>" style="width:100%; accent-color:var(--ios-pink); height:6px; cursor:pointer;">
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <label class="form-label" style="margin-bottom:0;">Age Range</label>
          <span id="ageVal" style="font-weight:800; color:var(--ios-pink); font-size:0.88rem; background:rgba(255,45,85,0.1); padding:2px 10px; border-radius:20px;"><?= $pref['min_age'] ?> - <?= $pref['max_age'] ?></span>
        </div>
        <div style="display:flex; gap:12px;">
          <input type="range" name="min_age" id="ageMin" min="18" max="60" value="<?= $pref['min_age'] ?>" style="width:50%; accent-color:var(--ios-pink); height:6px; cursor:pointer;">
          <input type="range" name="max_age" id="ageMax" min="18" max="60" value="<?= $pref['max_age'] ?>" style="width:50%; accent-color:var(--ios-pink); height:6px; cursor:pointer;">
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 22px;">
        <label class="form-label" style="margin-bottom:8px;">Show Me</label>
        <select name="gender_preference" class="form-control" style="border-radius: var(--radius-pill); font-weight:700;">
          <option value="everyone" <?= ($pref['gender_preference'] === 'everyone') ? 'selected' : '' ?>>Everyone</option>
          <option value="female" <?= ($pref['gender_preference'] === 'female') ? 'selected' : '' ?>>Women</option>
          <option value="male" <?= ($pref['gender_preference'] === 'male') ? 'selected' : '' ?>>Men</option>
        </select>
      </div>

      <button type="submit" class="btn-block btn-primary">Apply Filters</button>
    </form>
  </div>
</div>

<!-- Match Celebration Pop-up Modal -->
<div class="match-modal" id="matchModal">
  <div style="font-size: 3.5rem; margin-bottom: 8px;">🎉</div>
  <h2 class="match-title">It's a Match!</h2>
  <p class="match-subtitle">You and <span id="matchedUserName">Someone</span> liked each other!</p>

  <div class="match-avatars">
    <img src="<?= htmlspecialchars($current_user['avatar_url']) ?>" class="avatar-bubble" alt="You" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';">
    <div class="match-heart-icon"><i class="fa-solid fa-heart"></i></div>
    <img src="" id="matchedUserAvatar" class="avatar-bubble" alt="Match" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';">
  </div>

  <div style="width: 100%; max-width: 300px;">
    <a href="chat.php" id="matchedChatBtn" class="btn-block btn-light" style="margin-bottom: 10px; text-decoration: none; font-weight: 800;">
      <i class="fa-solid fa-paper-plane" style="margin-right: 6px;"></i> Send Message Now
    </a>
    <button class="btn-block" id="closeMatchModalBtn" style="background: rgba(255,255,255,0.22); color: #FFF; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(10px); font-weight: 700; cursor: pointer;">Keep Swiping</button>
  </div>
</div>

<!-- Include Swipe Engine JS -->
<script src="assets/js/swipe.js"></script>
<script>
function dismissWelcomePromoCard(btn) {
  const card = btn.closest('.profile-card');
  if (card) {
    card.style.transition = 'all 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    card.style.transform = 'translateY(-120%) rotate(-12deg)';
    card.style.opacity = '0';
    setTimeout(() => {
      card.remove();
      const cardDeck = document.getElementById('cardDeck');
      if (cardDeck) {
        const cards = cardDeck.querySelectorAll('.profile-card');
        cards.forEach((c, idx) => {
          c.style.zIndex = cards.length - idx;
          if (idx === 0) {
            c.style.transform = 'scale(1) translateY(0px)';
          } else if (idx === 1) {
            c.style.transform = 'scale(0.95) translateY(12px)';
          }
        });
      }
    }, 400);
  }
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
