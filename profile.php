<?php
// profile.php
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'profile';
$current_user_id = get_current_user_id();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$stmt->execute([':u' => $current_user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: splash.php");
    exit;
}

$age = calculate_age($user['birthdate'] ?? '2000-01-01');
$interests = json_decode($user['interests'] ?? '[]', true) ?: [];
$placeholder_img = 'assets/images/no_image_placeholder.png';
$avatar = !empty($user['avatar_url']) ? $user['avatar_url'] : $placeholder_img;
$photos = json_decode($user['photos'] ?? '[]', true) ?: [$avatar];
$is_private = (bool)($user['is_private'] ?? 0);

// Count Statistics for Dashboard
$m_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE (user1_id = :u1 OR user2_id = :u2) AND status = 'accepted'");
$m_count_stmt->execute([':u1' => $current_user_id, ':u2' => $current_user_id]);
$total_matches = (int)$m_count_stmt->fetchColumn();

$likes_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM swipes WHERE target_id = :u AND swipe_type IN ('like', 'superlike')");
$likes_count_stmt->execute([':u' => $current_user_id]);
$total_likes = (int)$likes_count_stmt->fetchColumn();

$slikes_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM swipes WHERE target_id = :u AND swipe_type = 'superlike'");
$slikes_count_stmt->execute([':u' => $current_user_id]);
$total_superlikes = (int)$slikes_count_stmt->fetchColumn();

$spark_score = 100 + ($total_likes * 5) + ($total_superlikes * 15) + ($total_matches * 10);

// Calculate Detailed Profile Completion & Missing Tasks
$completion_score = 0;
$missing_tasks = [];

$has_custom_avatar = (!empty($user['avatar_url']) && strpos($user['avatar_url'], 'default_avatar') === false && strpos($user['avatar_url'], 'no_image_placeholder') === false);
if ($has_custom_avatar) {
    $completion_score += 20;
} else {
    $missing_tasks[] = ['label' => 'Upload Profile Picture', 'points' => 20, 'action' => "document.getElementById('avatarFileInput').click()"];
}

if (count($photos) >= 2) {
    $completion_score += 20;
} else {
    $missing_tasks[] = ['label' => 'Add 2+ Gallery Photos', 'points' => 20, 'action' => "document.querySelector('input[name=\"photos[]\"]').click()"];
}

if (!empty(trim($user['bio'] ?? ''))) {
    $completion_score += 20;
} else {
    $missing_tasks[] = ['label' => 'Write an About Me Bio', 'points' => 20, 'action' => 'openEditProfileModal()'];
}

if (!empty(trim($user['occupation'] ?? ''))) {
    $completion_score += 15;
} else {
    $missing_tasks[] = ['label' => 'Add Profession / Job', 'points' => 15, 'action' => 'openEditProfileModal()'];
}

if (!empty(trim($user['location_city'] ?? '')) && $user['location_city'] !== 'No Location') {
    $completion_score += 10;
} else {
    $missing_tasks[] = ['label' => 'Set Location City', 'points' => 10, 'action' => 'fetchRealLocation()'];
}

if (count($interests) >= 3) {
    $completion_score += 15;
} else {
    $missing_tasks[] = ['label' => 'Select 3+ Passions', 'points' => 15, 'action' => 'openEditProfileModal()'];
}

$city_display = (!empty(trim($user['location_city'] ?? '')) && $user['location_city'] !== 'No Location') ? $user['location_city'] : 'No Location';
$profile_share_url = "https://programmingashram.store/profile-view.php?id=" . $current_user_id;

$css_version = filemtime(__DIR__ . '/assets/css/style.css');
require_once __DIR__ . '/includes/header.php';
?>

<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title">
    <i class="fa-solid fa-user-gear"></i>
    My Profile
  </div>

  <div class="header-actions">
    <button class="icon-btn" onclick="openShareModal()" title="Share Profile" style="color: var(--ios-pink);">
      <i class="fa-solid fa-share-nodes"></i>
    </button>
    <a href="logout.php" class="icon-btn" title="Logout" style="color:#FF3B30;"><i class="fa-solid fa-right-from-bracket"></i></a>
  </div>
</header>

<main class="app-body" style="padding: 14px;">

  <!-- Modern Dating Profile Header Card -->
  <div style="background: #FFF; border-radius: var(--radius-card); padding: 20px 16px 18px 16px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid var(--border-subtle);">
    
    <!-- Hidden Avatar Upload Form -->
    <form id="avatarUploadForm" action="api/upload_photos.php" method="POST" enctype="multipart/form-data" style="display: none;">
      <input type="hidden" name="action" value="upload_avatar">
      <input type="file" id="avatarFileInput" name="avatar_file" accept="image/*" onchange="document.getElementById('avatarUploadForm').submit()">
    </form>

    <!-- Profile Avatar Ring with VIP Gold Crown & Change Camera Badge -->
    <div style="position: relative; display: inline-block; cursor: pointer;" title="Tap to change profile picture">
      <div style="position: relative; padding: 3px; border-radius: 50%; background: linear-gradient(135deg, #FF2D55, #FF9500); box-shadow: 0 6px 20px rgba(255,45,85,0.25);">
        <img src="<?= htmlspecialchars($avatar) ?>" onclick="document.getElementById('avatarFileInput').click()" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #FFF; display: block;" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
      </div>
      
      <!-- Change Photo Camera Badge -->
      <div onclick="document.getElementById('avatarFileInput').click()" style="position: absolute; bottom: 2px; right: 2px; width: 32px; height: 32px; border-radius: 50%; background: var(--ios-gradient); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; border: 2px solid #FFF; box-shadow: 0 3px 10px rgba(195,31,58,0.35);" title="Change Profile Picture">
        <i class="fa-solid fa-camera"></i>
      </div>
    </div>

    <!-- Name & Verified / VIP Badges -->
    <div style="margin-top: 8px;">
      <h2 style="font-weight: 800; font-size: 1.3rem; color: var(--ios-text); display: inline-flex; align-items: center; gap: 6px; margin: 0;">
        <?= htmlspecialchars($user['full_name']) ?>, <?= $age ?>
        <i class="fa-solid fa-circle-check" style="color: #007AFF; font-size: 1.05rem;" title="Verified Candidate"></i>
      </h2>
    </div>

    <!-- VIP Member Badge Pill -->
    <div style="margin-top: 4px; margin-bottom: 8px;">
      <span style="background: #FFFBF0; color: #996500; font-size: 0.7rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; border: 1px solid #FFE58F; display: inline-flex; align-items: center; gap: 4px;">
        <i class="fa-solid fa-crown" style="color: #FFB300;"></i> VARSAATHI VIP MEMBER
      </span>
    </div>

    <p style="color: var(--ios-muted); font-size: 0.82rem; font-weight: 600; margin-bottom: 14px;">
      <i class="fa-solid fa-briefcase" style="color: var(--ios-purple); margin-right: 4px;"></i> <?= htmlspecialchars($user['occupation'] ?: 'Member') ?> • <i class="fa-solid fa-location-dot" style="color: var(--ios-pink); margin-right: 4px;"></i> <span id="userCityLabel"><?= htmlspecialchars($city_display) ?></span>
    </p>

    <!-- Clean Action Buttons Bar -->
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
      <button type="button" onclick="openEditProfileModal()" style="height: 38px; padding: 0 18px; font-size: 0.82rem; font-weight: 700; border-radius: 20px; background: var(--ios-gradient); color: #FFF; border: none; box-shadow: 0 4px 12px rgba(195,31,58,0.22); display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer;">
        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
      </button>

      <button type="button" id="fetchLocBtn" onclick="fetchRealLocation()" style="height: 38px; padding: 0 16px; font-size: 0.82rem; font-weight: 700; border-radius: 20px; background: #FFF; color: var(--ios-text); border: 1.5px solid #E5E5EA; display: inline-flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <i class="fa-solid fa-location-crosshairs" style="color: var(--ios-pink);"></i> Set Location
      </button>
    </div>
  </div>

  <!-- Sleek Statistics Bar (Monochrome Neutral Icons) -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 14px 10px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle); display: flex; justify-content: space-around; text-align: center; align-items: center;">
    <div style="flex: 1;">
      <div style="font-weight: 900; font-size: 1.25rem; color: var(--ios-text); line-height: 1;"><?= $total_likes ?></div>
      <div style="font-size: 0.68rem; color: var(--ios-muted); font-weight: 700; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 4px;">
        <i class="fa-solid fa-heart" style="color: #2C2C2E;"></i> Likes
      </div>
    </div>
    
    <div style="width: 1px; height: 28px; background: #F0F0F2;"></div>

    <div style="flex: 1;">
      <div style="font-weight: 900; font-size: 1.25rem; color: var(--ios-text); line-height: 1;"><?= $total_superlikes ?></div>
      <div style="font-size: 0.68rem; color: var(--ios-muted); font-weight: 700; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 4px;">
        <i class="fa-solid fa-star" style="color: #2C2C2E;"></i> Superlikes
      </div>
    </div>

    <div style="width: 1px; height: 28px; background: #F0F0F2;"></div>

    <div style="flex: 1;">
      <div style="font-weight: 900; font-size: 1.25rem; color: var(--ios-text); line-height: 1;"><?= $total_matches ?></div>
      <div style="font-size: 0.68rem; color: var(--ios-muted); font-weight: 700; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 4px;">
        <i class="fa-solid fa-comments" style="color: #2C2C2E;"></i> Matches
      </div>
    </div>

    <div style="width: 1px; height: 28px; background: #F0F0F2;"></div>

    <div style="flex: 1;">
      <div style="font-weight: 900; font-size: 1.25rem; color: var(--ios-text); line-height: 1;"><?= $spark_score ?></div>
      <div style="font-size: 0.68rem; color: var(--ios-muted); font-weight: 700; margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 4px;">
        <i class="fa-solid fa-fire" style="color: #2C2C2E;"></i> Spark
      </div>
    </div>
  </div>

  <!-- Interactive Profile Privacy Toggle Switch Card -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 14px 16px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between;">
    <div style="flex: 1; padding-right: 12px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-user-lock" style="color: #2C2C2E; font-size: 0.95rem;"></i>
        <span style="font-weight: 800; font-size: 0.88rem; color: var(--ios-text);" id="privacyStatusLabel"><?= $is_private ? 'Private Profile' : 'Public Profile' ?></span>
      </div>
      <div style="font-size: 0.72rem; color: var(--ios-muted); font-weight: 600; margin-top: 2px;" id="privacyStatusDesc">
        <?= $is_private ? 'Only main photo visible. Gallery photos locked.' : 'Everyone can view your full photo gallery.' ?>
      </div>
    </div>

    <label class="ios-switch">
      <input type="checkbox" id="privacyToggleBtn" <?= $is_private ? 'checked' : '' ?> onchange="togglePrivacyMode(this.checked)">
      <span class="ios-slider"></span>
    </label>
  </div>

  <!-- Custom SVG Radial Progress Component -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle); display: flex; align-items: center; gap: 16px;">
    <!-- SVG Radial Progress Ring -->
    <div style="position: relative; width: 68px; height: 68px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
      <svg width="68" height="68" viewBox="0 0 36 36" style="transform: rotate(-90deg); overflow: visible;">
        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#F2F2F7" stroke-width="3.2"/>
        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#progressGradient)" stroke-width="3.2" stroke-dasharray="<?= min(100, $completion_score) ?>, 100" stroke-linecap="round"/>
        <defs>
          <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#FF2D55" />
            <stop offset="100%" stop-color="#E024BE" />
          </linearGradient>
        </defs>
      </svg>
      <div style="position: absolute; font-weight: 900; font-size: 0.9rem; color: var(--ios-text); font-family: 'Outfit', sans-serif;">
        <?= $completion_score ?>%
      </div>
    </div>

    <div style="flex: 1;">
      <div style="font-size: 0.85rem; font-weight: 800; color: var(--ios-text); margin-bottom: 2px;">
        Profile Completion
      </div>
      <div style="font-size: 0.74rem; color: var(--ios-muted); font-weight: 600; margin-bottom: 8px;">
        <?= ($completion_score >= 100) ? '🎉 Your profile is 100% complete & VIP optimized!' : 'Complete missing details to get 5x more matches.' ?>
      </div>

      <?php if (!empty($missing_tasks)): ?>
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
          <?php foreach ($missing_tasks as $task): ?>
            <button type="button" onclick="<?= $task['action'] ?>" style="background: #FFF0F3; color: var(--ios-pink); border: 1px solid rgba(255,45,85,0.25); border-radius: 14px; padding: 4px 10px; font-size: 0.7rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
              <i class="fa-solid fa-plus" style="font-size: 0.6rem;"></i> <?= htmlspecialchars($task['label']) ?> (+<?= $task['points'] ?>%)
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Multi-Photo Gallery & Upload Section -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
      <h4 style="font-weight: 800; font-size: 0.88rem; color: var(--ios-text); margin: 0;">
        <i class="fa-solid fa-images" style="color: var(--ios-pink); margin-right: 6px;"></i> My Profile Photos (<span id="photoCount"><?= count($photos) ?></span>/6)
      </h4>
    </div>

    <!-- Multi Photo Grid -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px;">
      <?php 
      for ($i = 0; $i < 6; $i++): 
        $hasPhoto = isset($photos[$i]);
        $photoUrl = $hasPhoto ? $photos[$i] : null;
        $isPrimary = ($photoUrl === $avatar);
      ?>
        <div style="position: relative; height: 96px; border-radius: 14px; overflow: hidden; background: #F4F4F6; border: 1px dashed #E4E4E7; display: flex; flex-direction: column; align-items: center; justify-content: center;">
          <?php if ($hasPhoto): ?>
            <img src="<?= htmlspecialchars($photoUrl) ?>" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;" onclick="openPhotoViewer('<?= htmlspecialchars($photoUrl) ?>')" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
            
            <!-- Delete Button -->
            <form action="api/upload_photos.php" method="POST" style="position: absolute; top: 4px; right: 4px;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="photo_url" value="<?= htmlspecialchars($photoUrl) ?>">
              <button type="submit" style="width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,0.65); color: #FFF; border: none; font-size: 0.62rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </form>

            <!-- Set Primary Cover Badge -->
            <?php if ($isPrimary): ?>
              <span style="position: absolute; bottom: 4px; left: 4px; background: var(--ios-pink); color: #FFF; font-size: 0.6rem; font-weight: 700; padding: 1px 5px; border-radius: 6px;">
                <i class="fa-solid fa-star" style="font-size: 0.55rem;"></i> Main
              </span>
            <?php else: ?>
              <form action="api/upload_photos.php" method="POST" style="position: absolute; bottom: 4px; left: 4px;">
                <input type="hidden" name="action" value="set_primary">
                <input type="hidden" name="photo_url" value="<?= htmlspecialchars($photoUrl) ?>">
                <button type="submit" style="background: rgba(0,0,0,0.55); color: #FFF; font-size: 0.6rem; font-weight: 600; padding: 1px 5px; border-radius: 6px; border: none; cursor: pointer;">
                  Set Main
                </button>
              </form>
            <?php endif; ?>

          <?php else: ?>
            <!-- Empty Upload Slot -->
            <form action="api/upload_photos.php" method="POST" enctype="multipart/form-data" style="width:100%; height:100%;">
              <input type="hidden" name="action" value="upload">
              <label style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; color: var(--ios-pink);">
                <i class="fa-solid fa-plus" style="font-size: 1.1rem; margin-bottom: 2px;"></i>
                <span style="font-size: 0.64rem; font-weight: 700;">Add</span>
                <input type="file" name="photos[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
              </label>
            </form>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>

    <!-- Multi-Upload Form Button -->
    <form action="api/upload_photos.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload">
      <label class="btn-block" style="cursor: pointer; height: 38px; font-size: 0.8rem; font-weight: 700; border-radius: 20px; border: 1px solid #E5E5EA; background: #F9F9FB; color: var(--ios-text); display: flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-cloud-arrow-up" style="margin-right: 6px; color: var(--ios-pink);"></i> Upload Multiple Pictures
        <input type="file" name="photos[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
      </label>
    </form>
  </div>

  <!-- About Me Card -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
      <h4 style="font-weight: 800; font-size: 0.9rem; color: var(--ios-text); margin: 0;">
        <i class="fa-solid fa-quote-left" style="color: var(--ios-pink); margin-right: 6px;"></i> About Me
      </h4>
      <button onclick="openEditProfileModal()" style="border: none; background: rgba(255,45,85,0.08); color: var(--ios-pink); font-size: 0.74rem; font-weight: 700; padding: 4px 10px; border-radius: 12px; cursor: pointer;">
        <i class="fa-solid fa-pen" style="margin-right: 4px;"></i> Edit
      </button>
    </div>
    <p style="color: var(--ios-text); font-size: 0.86rem; line-height: 1.5; margin: 0; font-weight: 400;">
      <?= htmlspecialchars($user['bio'] ?: 'No bio added yet. Click edit to share your bio!') ?>
    </p>
  </div>

  <!-- My Passions & Interests -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.03); border: 1px solid var(--border-subtle);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
      <h4 style="font-weight: 800; font-size: 0.9rem; color: var(--ios-text); margin: 0;">
        <i class="fa-solid fa-heart" style="color: var(--ios-pink); margin-right: 6px;"></i> Passions & Hobbies
      </h4>
      <button onclick="openEditProfileModal()" style="border: none; background: rgba(255,45,85,0.08); color: var(--ios-pink); font-size: 0.74rem; font-weight: 700; padding: 4px 10px; border-radius: 12px; cursor: pointer;">
        <i class="fa-solid fa-plus" style="margin-right: 4px;"></i> Edit
      </button>
    </div>
    <div class="card-tags">
      <?php if (!empty($interests)): ?>
        <?php foreach ($interests as $tag): ?>
          <span class="tag-chip" style="background: rgba(255,45,85,0.08); color: var(--ios-pink); border-color: rgba(255,45,85,0.2); font-weight: 600;"><i class="fa-solid fa-tag" style="margin-right: 4px;"></i> <?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color: var(--ios-muted); font-size: 0.8rem; font-style: italic; margin: 0;">No interests added yet. Click "+ Edit" to add your hobbies!</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Account Options Menu -->
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 6px 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); margin-bottom: 24px;">
    <a href="#" onclick="openEditProfileModal(); return false;" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 8px; text-decoration: none; color: var(--ios-text); font-weight: 700; border-bottom: 1px solid var(--border-subtle);">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-pen-to-square" style="color: var(--ios-pink);"></i>
        <span>Edit Profile & Passions</span>
      </div>
      <i class="fa-solid fa-chevron-right" style="color: var(--ios-muted); font-size: 0.8rem;"></i>
    </a>

    <a href="#" onclick="openShareModal(); return false;" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 8px; text-decoration: none; color: var(--ios-text); font-weight: 700; border-bottom: 1px solid var(--border-subtle);">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-share-nodes" style="color: #007AFF;"></i>
        <span>Share Profile Link</span>
      </div>
      <i class="fa-solid fa-chevron-right" style="color: var(--ios-muted); font-size: 0.8rem;"></i>
    </a>

    <a href="logout.php" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 8px; text-decoration: none; color: var(--ios-text); font-weight: 700;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-right-from-bracket" style="color: var(--ios-purple);"></i>
        <span>Sign Out</span>
      </div>
      <i class="fa-solid fa-chevron-right" style="color: var(--ios-muted); font-size: 0.8rem;"></i>
    </a>
  </div>
</main>

<!-- Share Profile Bottom Sheet Modal -->
<div id="shareProfileModal" class="ios-sheet-overlay" onclick="closeShareModal(event)">
  <div class="ios-sheet-container" onclick="event.stopPropagation()">
    <div class="sheet-drag-handle"></div>

    <div class="sheet-user-header">
      <img src="<?= htmlspecialchars($avatar) ?>" class="sheet-user-avatar" alt="Avatar" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
      <div style="flex:1;">
        <h4 class="sheet-user-title">Share <?= htmlspecialchars($user['full_name']) ?>'s Profile</h4>
        <span class="sheet-user-sub">Share your unique Varsaathi link</span>
      </div>
      <button type="button" onclick="closeShareModal()" style="background:none; border:none; font-size:1.2rem; color:#8E8E93; cursor:pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Direct Profile Link Box -->
    <div style="background: #F2F2F7; border-radius: 14px; padding: 10px 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
      <input type="text" id="shareLinkInput" readonly value="<?= $profile_share_url ?>" style="flex: 1; border: none; background: transparent; font-size: 0.82rem; font-weight: 700; color: #1C1C1E; outline: none;">
      <button onclick="copyShareLink()" style="background: var(--ios-pink); color: #FFF; border: none; padding: 6px 14px; border-radius: 12px; font-weight: 800; font-size: 0.78rem; cursor: pointer;">
        Copy
      </button>
    </div>

    <div class="sheet-options-list">
      <button type="button" class="sheet-option-btn" onclick="triggerWebShare()" style="color: #007AFF; background: #EEF7FF;">
        <i class="fa-solid fa-share-nodes" style="color: #007AFF;"></i>
        <span>Share via App / Apps Menu</span>
      </button>

      <a href="https://api.whatsapp.com/send?text=Check%20out%20my%20profile%20on%20VARSAATHI!%20<?= urlencode($profile_share_url) ?>" target="_blank" class="sheet-option-btn" style="color: #25D366; background: #E8F9EE; text-decoration: none;">
        <i class="fa-brands fa-whatsapp" style="color: #25D366; font-size: 1.2rem;"></i>
        <span>Share to WhatsApp</span>
      </a>

      <button type="button" class="sheet-option-btn cancel" onclick="closeShareModal()">
        <span>Done</span>
      </button>
    </div>
  </div>
</div>

<!-- Fullscreen Profile Photo Lightbox Modal -->
<div class="modal-overlay" id="profilePhotoViewerModal" style="z-index: 999999; background: rgba(0,0,0,0.92); backdrop-filter: blur(15px);">
  <div style="position: relative; width: 92%; max-width: 440px; text-align: center;">
    <button onclick="closePhotoViewer()" style="position: absolute; top: -50px; right: 0; background: rgba(255,255,255,0.2); color: #FFF; border: none; width: 38px; height: 38px; border-radius: 50%; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <img id="fullPhotoViewerImg" src="" style="width: 100%; max-height: 75vh; object-fit: contain; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); display: block; margin: 0 auto;" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
    <div style="margin-top: 14px; color: #FFF; font-weight: 700; font-size: 0.9rem;">
      <?= htmlspecialchars($user['full_name']) ?>
    </div>
  </div>
</div>

<!-- Edit Profile & Interests Modal -->
<div class="modal-overlay" id="editProfileModal">
  <div class="modal-drawer" style="max-height: 85vh; overflow-y: auto; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
      <h3 style="font-weight: 800; font-size: 1.2rem; color: var(--ios-text); margin: 0;">
        <i class="fa-solid fa-pen-to-square" style="color: var(--ios-pink); margin-right: 6px;"></i> Edit Profile & Passions
      </h3>
      <button type="button" onclick="closeEditProfileModal()" style="border: none; background: #F2F2F7; width: 32px; height: 32px; border-radius: 50%; color: var(--ios-muted); font-size: 0.9rem; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form id="editProfileForm">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" id="editFullName" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Date of Birth (Calculated Age: <?= $age ?> yrs)</label>
        <input type="date" id="editBirthdate" name="birthdate" class="form-control" value="<?= htmlspecialchars($user['birthdate'] ?? '2000-01-01') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Occupation / Profession</label>
        <input type="text" id="editOccupation" name="occupation" class="form-control" value="<?= htmlspecialchars($user['occupation'] ?? '') ?>" placeholder="e.g. Software Engineer">
      </div>

      <div class="form-group">
        <label class="form-label">City Location</label>
        <input type="text" id="editLocationCity" name="location_city" class="form-control" value="<?= htmlspecialchars(($user['location_city'] !== 'No Location') ? ($user['location_city'] ?? '') : '') ?>" placeholder="e.g. Mumbai">
      </div>

      <div class="form-group">
        <label class="form-label">Bio / About Me</label>
        <textarea id="editBio" name="bio" class="form-control" rows="3" placeholder="Share your bio..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Passions & Hobbies (Comma Separated)</label>
        <input type="text" id="editInterests" name="interests" class="form-control" value="<?= htmlspecialchars(implode(', ', $interests)) ?>" placeholder="e.g. Coffee, Travel, Fitness, Music">
      </div>

      <button type="submit" class="btn-block btn-primary" style="height: 48px; border-radius: 24px; font-weight: 800; font-size: 0.95rem;">
        Save Profile Changes
      </button>
    </form>
  </div>
</div>

<script>
function togglePrivacyMode(isPrivate) {
  fetch('api/toggle_privacy.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ is_private: isPrivate ? 1 : 0 })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const label = document.getElementById('privacyStatusLabel');
      const desc = document.getElementById('privacyStatusDesc');
      if (data.is_private) {
        label.textContent = 'Private Profile';
        desc.textContent = 'Only main photo visible. Gallery photos locked.';
      } else {
        label.textContent = 'Public Profile';
        desc.textContent = 'Everyone can view your full photo gallery.';
      }
    } else {
      alert(data.error || 'Failed to update privacy settings');
    }
  })
  .catch(() => alert('Network error updating privacy settings'));
}

function openShareModal() {
  document.getElementById('shareProfileModal').classList.add('active');
}

function closeShareModal(e) {
  document.getElementById('shareProfileModal').classList.remove('active');
}

function copyShareLink() {
  const linkInput = document.getElementById('shareLinkInput');
  linkInput.select();
  linkInput.setSelectionRange(0, 99999);
  try {
    navigator.clipboard.writeText(linkInput.value);
    alert('Profile link copied to clipboard!');
  } catch(e) {
    alert('Link selected. Copy now!');
  }
}

function triggerWebShare() {
  if (navigator.share) {
    navigator.share({
      title: "Check out <?= htmlspecialchars($user['full_name']) ?>'s profile on VARSAATHI!",
      text: "Connect with <?= htmlspecialchars($user['full_name']) ?> on VARSAATHI Dating App!",
      url: "<?= $profile_share_url ?>"
    }).catch(() => {});
  } else {
    copyShareLink();
  }
}

function openPhotoViewer(url) {
  document.getElementById('fullPhotoViewerImg').src = url;
  document.getElementById('profilePhotoViewerModal').classList.add('active');
}

function closePhotoViewer() {
  document.getElementById('profilePhotoViewerModal').classList.remove('active');
}

function openEditProfileModal() {
  document.getElementById('editProfileModal').classList.add('active');
}

function closeEditProfileModal() {
  document.getElementById('editProfileModal').classList.remove('active');
}

document.getElementById('editProfileForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('api/update_profile.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.error || 'Could not update profile');
    }
  })
  .catch(err => {
    alert('Error updating profile');
  });
});

function fetchRealLocation() {
  const btn = document.getElementById('fetchLocBtn');
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser");
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      fetch('api/update_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ latitude: lat, longitude: lng })
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-location-crosshairs" style="color: var(--ios-pink);"></i> Set Location';
        if (data.success) {
          if (data.city) {
            document.getElementById('userCityLabel').textContent = data.city;
          }
          alert(`Location updated successfully! (${data.city || 'Coordinates saved'})`);
        } else {
          alert(data.error || 'Failed to update location');
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-location-crosshairs" style="color: var(--ios-pink);"></i> Set Location';
        alert('Network error updating location');
      });
    },
    (error) => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-location-crosshairs" style="color: var(--ios-pink);"></i> Set Location';
      alert("Location permission denied or unavailable.");
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
