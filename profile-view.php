<?php
// profile-view.php - Auto-Forward to Unified Saathi Profile Viewer
require_once __DIR__ . '/config/db.php';
require_login();

$target_id = (int)($_GET['id'] ?? 0);
if ($target_id > 0) {
    header("Location: saathi-profile.php?id=" . $target_id);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>

    $age = calculate_age($target_user['birthdate'] ?? '2000-01-01');
    $interests_decoded = json_decode((string)($target_user['interests'] ?? '[]'), true);
    $interests = is_array($interests_decoded) ? $interests_decoded : [];

    $placeholder_img = 'assets/images/no_image_placeholder.png';
    $avatar = get_valid_avatar_url($target_user['avatar_url'] ?? '');
    
    $photos_decoded = json_decode((string)($target_user['photos'] ?? '[]'), true);
    $photos = is_array($photos_decoded) ? $photos_decoded : [];
    $photos = array_values(array_filter($photos, function($p) { return !empty($p); }));

    if (empty($photos)) {
        $photos = [$avatar];
    }

    $is_private = (bool)($target_user['is_private'] ?? 0);
    $is_own_profile = ($target_id === $current_user_id);

    if ($is_private && !$is_own_profile) {
        $visible_photos = [$avatar];
        $photos_locked = true;
    } else {
        $visible_photos = $photos;
        $photos_locked = false;
    }

    $has_multiple_photos = count($visible_photos) > 1;

    // Get candidate online status
    $partner_status = get_user_online_status($target_user['last_seen'] ?? null);
    if (!is_array($partner_status)) {
        $partner_status = ['status' => 'offline', 'label' => 'Offline', 'color' => '#8E8E93'];
    }

    $css_version = time();
    require_once __DIR__ . '/includes/header.php';
} catch (Throwable $t_err) {
    $css_version = time();
    require_once __DIR__ . '/includes/header.php';
    echo '<div style="padding:40px 20px; text-align:center;"><h3 style="color:#C31F3A;">Profile Error</h3><p style="color:#666; margin:10px 0 20px 0;">' . htmlspecialchars($t_err->getMessage()) . '</p><a href="index.php" style="background:#1C1C1E; color:#FFF; padding:10px 20px; border-radius:20px; text-decoration:none; font-weight:bold; font-size:0.88rem;">Return to Home Deck</a></div>';
    require_once __DIR__ . '/includes/navbar.php';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title"><?= htmlspecialchars($target_user['full_name']) ?></h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<main class="app-body" style="padding: 14px; overflow-y: auto;">

  <!-- Cover Photo / Interactive Story-Style Photo Carousel -->
  <div style="position: relative; width: 100%; height: 380px; min-height: 380px; flex-shrink: 0; border-radius: var(--radius-card); overflow: hidden; box-shadow: 0 12px 32px rgba(0,0,0,0.15); margin-bottom: 14px; user-select: none; background: #1C1C1E;">
    
    <!-- Main Carousel Image -->
    <img id="mainCarouselImg" src="<?= htmlspecialchars($visible_photos[0]) ?>" alt="<?= htmlspecialchars($target_user['full_name']) ?>" onclick="openPhotoPreview(this.src, <?= htmlspecialchars(json_encode(array_values($visible_photos))) ?>, currentPhotoIndex)" style="width: 100%; height: 100%; min-height: 100%; object-fit: cover; display: block; transition: opacity 0.2s ease; cursor: pointer;" title="Click to View Full Photo" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
    
    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0) 40%, rgba(0,0,0,0.85) 100%); pointer-events: none;"></div>

    <?php if ($has_multiple_photos): ?>
      <!-- Top Instagram-Story Progress Bars -->
      <div style="position: absolute; top: 10px; left: 12px; right: 12px; display: flex; gap: 4px; z-index: 10;">
        <?php foreach ($visible_photos as $idx => $p): ?>
          <div class="carousel-bar <?= ($idx === 0) ? 'active' : '' ?>" id="pBar_<?= $idx ?>" style="flex: 1; height: 3px; background: rgba(255,255,255,0.4); border-radius: 2px; transition: background 0.2s ease;"></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Status & Distance Badges -->
    <div style="position: absolute; top: 18px; right: 14px; display: flex; align-items: center; gap: 6px; z-index: 10;">
      <div style="background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 4px 12px; border-radius: 20px; color: #FFF; font-size: 0.74rem; font-weight: 800; border: 1px solid rgba(255,255,255,0.25); display: flex; align-items: center; gap: 6px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: <?= $partner_status['color'] ?>;"></span>
        <span><?= htmlspecialchars($partner_status['label']) ?></span>
      </div>
      <div style="background: rgba(0,0,0,0.55); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 4px 10px; border-radius: 20px; color: #FFF; font-size: 0.74rem; font-weight: 800; border: 1px solid rgba(255,255,255,0.25);">
        <i class="fa-solid fa-location-dot" style="color: var(--ios-pink);"></i> <?= $target_user['distance_km'] ?? 3 ?> km
      </div>
    </div>

    <?php if ($has_multiple_photos): ?>
      <button onclick="prevPhoto()" style="position: absolute; top: 40%; left: 8px; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.4); color: #FFF; border: 1px solid rgba(255,255,255,0.3); font-size: 0.9rem; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10;" title="Previous Photo">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <button onclick="nextPhoto()" style="position: absolute; top: 40%; right: 8px; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.4); color: #FFF; border: 1px solid rgba(255,255,255,0.3); font-size: 0.9rem; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10;" title="Next Photo">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    <?php endif; ?>

    <!-- Name & Info Overlay -->
    <div style="position: absolute; bottom: 16px; left: 16px; right: 16px; color: #FFF; z-index: 10;">
      <h2 style="font-weight: 900; font-size: 1.7rem; line-height: 1.1; font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 6px;">
        <?= htmlspecialchars($target_user['full_name']) ?>, <?= $age ?>
        <i class="fa-solid fa-circle-check" style="color: #007AFF; font-size: 1.2rem;" title="Verified Profile"></i>
      </h2>
      <p style="font-size: 0.88rem; opacity: 0.95; margin-top: 4px; font-weight: 600;">
        <i class="fa-solid fa-briefcase" style="margin-right: 4px;"></i> <?= htmlspecialchars($target_user['occupation'] ?: 'Professional Member') ?> • <i class="fa-solid fa-city" style="margin-right: 4px;"></i> <?= htmlspecialchars(($target_user['location_city'] && $target_user['location_city'] !== 'San Francisco') ? $target_user['location_city'] : 'No Location') ?>
      </p>
    </div>
  </div>

  <?php if ($has_multiple_photos): ?>
    <!-- Photo Thumbnails Gallery Strip -->
    <div style="display: flex; gap: 10px; margin-bottom: 16px; overflow-x: auto; padding: 2px 0;">
      <?php foreach ($visible_photos as $idx => $p): ?>
        <img src="<?= htmlspecialchars($p) ?>" id="thumb_<?= $idx ?>" class="carousel-thumb <?= ($idx === 0) ? 'active' : '' ?>" style="width: 68px; height: 68px; border-radius: 14px; object-fit: cover; cursor: pointer; border: 2.5px solid <?= ($idx === 0) ? 'var(--ios-pink)' : 'transparent' ?>; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s ease;" onclick="showPhoto(<?= $idx ?>)" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Private Profile Locked Gallery & Ask for Photos Card -->
  <?php if ($photos_locked): ?>
    <div style="background: linear-gradient(135deg, #FFF0F3, #FFF5F8); border-radius: var(--radius-ios); padding: 18px 16px; text-align: center; margin-bottom: 14px; border: 1.5px dashed rgba(255,45,85,0.3); box-shadow: 0 4px 16px rgba(255,45,85,0.06);">
      <div style="font-size: 2rem; margin-bottom: 4px;">🔒</div>
      <h4 style="font-weight: 800; font-size: 0.95rem; color: var(--ios-text); margin-bottom: 2px;">Private Photo Gallery</h4>
      <p style="font-size: 0.78rem; color: var(--ios-muted); font-weight: 600; margin-bottom: 12px; line-height: 1.4;">
        <?= htmlspecialchars($target_user['full_name']) ?>'s profile photo gallery is private. Tap below to send a photo access request!
      </p>
      <button type="button" onclick="requestPrivatePhotos(<?= $target_id ?>)" id="askPhotosBtn" style="padding: 10px 22px; border-radius: 22px; font-weight: 800; font-size: 0.84rem; background: #FFF; color: var(--ios-pink); border: 1.5px solid var(--ios-pink); cursor: pointer; box-shadow: 0 4px 12px rgba(195,31,58,0.12); display: inline-flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-camera" style="font-size: 0.95rem;"></i> Ask for Photos
      </button>
    </div>
  <?php endif; ?>

  <!-- Basic Info & Profession -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 16px; margin-bottom: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(175,82,222,0.1); color: var(--ios-purple); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
        <i class="fa-solid fa-briefcase"></i>
      </div>
      <div>
        <div style="font-size: 0.76rem; color: var(--ios-muted); font-weight: 800; text-transform: uppercase;">Profession / Occupation</div>
        <div style="font-size: 0.95rem; font-weight: 800; color: var(--ios-text);"><?= htmlspecialchars($target_user['occupation'] ?: 'Professional Member') ?></div>
      </div>
    </div>

    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,45,85,0.1); color: var(--ios-pink); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
        <i class="fa-solid fa-house"></i>
      </div>
      <div>
        <div style="font-size: 0.76rem; color: var(--ios-muted); font-weight: 800; text-transform: uppercase;">Location / Lives in</div>
        <div style="font-size: 0.95rem; font-weight: 800; color: var(--ios-text);"><?= htmlspecialchars(($target_user['location_city'] && $target_user['location_city'] !== 'San Francisco') ? $target_user['location_city'] : 'No Location') ?></div>
      </div>
    </div>
  </div>

  <!-- About Me Section -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; margin-bottom: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
    <h4 style="font-weight: 800; font-size: 0.95rem; margin-bottom: 8px; color: var(--ios-text);"><i class="fa-solid fa-quote-left" style="color: var(--ios-pink); margin-right: 6px;"></i> About Me</h4>
    <p style="color: var(--ios-text); font-size: 0.88rem; line-height: 1.5; margin: 0;">
      <?= htmlspecialchars($target_user['bio'] ?: 'Looking for real connections & fun conversations nearby!') ?>
    </p>
  </div>

  <!-- Interests Section -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
    <h4 style="font-weight: 800; font-size: 0.95rem; margin-bottom: 10px; color: var(--ios-text);"><i class="fa-solid fa-heart" style="color: var(--ios-pink); margin-right: 6px;"></i> Passions & Interests</h4>
    <div class="card-tags">
      <?php if (!empty($interests)): ?>
        <?php foreach ($interests as $tag): ?>
          <span class="tag-chip" style="background: rgba(255,45,85,0.08); color: var(--ios-pink); border-color: rgba(255,45,85,0.2);"><i class="fa-solid fa-tag" style="margin-right: 4px;"></i> <?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      <?php else: ?>
        <span class="tag-chip" style="background: #F4F4F5; color: #71717A;"><i class="fa-solid fa-mug-hot"></i> Coffee</span>
        <span class="tag-chip" style="background: #F4F4F5; color: #71717A;"><i class="fa-solid fa-plane"></i> Travel</span>
        <span class="tag-chip" style="background: #F4F4F5; color: #71717A;"><i class="fa-solid fa-music"></i> Music</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Action Controls Floating Bar -->
  <div style="display: flex; align-items: center; justify-content: space-evenly; margin-bottom: 20px;">
    <button class="ctrl-btn large btn-dislike" onclick="actionSwipe('dislike')" title="Dislike">
      <i class="fa-solid fa-xmark"></i>
    </button>
    
    <button class="ctrl-btn small" style="background: #FFF; color: #FFCC00; font-size: 1.2rem; border: 1.5px solid #FFEAA7;" onclick="actionSwipe('superlike')" title="Superlike">
      <i class="fa-solid fa-star"></i>
    </button>

    <button class="ctrl-btn large btn-like" onclick="actionSwipe('like')" title="Like">
      <i class="fa-solid fa-heart"></i>
    </button>
  </div>
</main>

<!-- Match Success Celebration Modal -->
<div class="match-modal" id="viewMatchModal">
  <div style="font-size: 3.5rem; margin-bottom: 8px;">🎉</div>
  <h2 class="match-title">It's a Match!</h2>
  <p class="match-subtitle">You and <?= htmlspecialchars($target_user['full_name']) ?> liked each other!</p>
  
  <div class="match-avatars">
    <img id="matchMyAvatar" src="<?= htmlspecialchars($avatar) ?>" class="avatar-bubble" alt="You" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
    <div class="match-heart-icon"><i class="fa-solid fa-heart"></i></div>
    <img src="<?= htmlspecialchars($visible_photos[0]) ?>" class="avatar-bubble" alt="Match" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
  </div>

  <div style="width: 100%; max-width: 300px;">
    <a id="matchChatBtn" href="matches.php" class="btn-block btn-light" style="margin-bottom: 10px; text-decoration: none; font-weight: 800;">
      <i class="fa-solid fa-paper-plane" style="margin-right: 6px;"></i> Send Message Now
    </a>
    <button onclick="document.getElementById('viewMatchModal').classList.remove('active')" class="btn-block" style="background: rgba(255,255,255,0.22); color: #FFF; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(10px); font-weight: 700; cursor: pointer;">
      Keep Browsing
    </button>
  </div>
</div>

<script>
const photosList = <?= json_encode(array_values($visible_photos)) ?>;
let currentPhotoIdx = 0;

function showPhoto(idx) {
  if (idx < 0 || idx >= photosList.length) return;
  currentPhotoIdx = idx;
  
  const img = document.getElementById('mainCarouselImg');
  img.style.opacity = '0.3';
  setTimeout(() => {
    img.src = photosList[currentPhotoIdx];
    img.style.opacity = '1';
  }, 100);

  photosList.forEach((_, i) => {
    const bar = document.getElementById(`pBar_${i}`);
    if (bar) {
      bar.style.background = (i <= currentPhotoIdx) ? '#FFF' : 'rgba(255,255,255,0.4)';
    }
    const thumb = document.getElementById(`thumb_${i}`);
    if (thumb) {
      thumb.style.borderColor = (i === currentPhotoIdx) ? 'var(--ios-pink)' : 'transparent';
    }
  });
}

function nextPhoto() {
  let next = (currentPhotoIdx + 1) % photosList.length;
  showPhoto(next);
}

function prevPhoto() {
  let prev = (currentPhotoIdx - 1 + photosList.length) % photosList.length;
  showPhoto(prev);
}

function actionSwipe(type) {
  fetch('api/swipe.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-parse-urlencoded' },
    body: `target_id=<?= $target_id ?>&swipe_type=${type}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.matched || data.is_match) {
      if (data.match_id) {
        document.getElementById('matchChatBtn').href = `chat.php?match_id=${data.match_id}`;
      }
      document.getElementById('viewMatchModal').classList.add('active');
    } else {
      window.location.href = 'index.php';
    }
  })
  .catch(() => {
    window.location.href = 'index.php';
  });
}

function requestPrivatePhotos(targetId) {
  const btn = document.getElementById('askPhotosBtn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Requesting...';
  }

  fetch('api/request_photos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (btn) {
        btn.style.background = '#E8F9EE';
        btn.style.color = '#1E7E44';
        btn.style.borderColor = '#34C759';
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Photo Access Requested';
      }
      alert("Photo request sent successfully! You will be notified when they grant access.");
    } else {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-camera"></i> Ask for Photos';
      }
      alert(data.error || "Could not send photo access request.");
    }
  })
  .catch(() => {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-camera"></i> Ask for Photos';
    }
    alert("Network error. Please try again.");
  });
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
