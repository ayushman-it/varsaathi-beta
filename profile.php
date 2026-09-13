<?php
// profile.php - Single Unified Matrimonial Profile (Ref: media_1789219148081.png)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
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

$saathi = get_saathi_profile($current_user_id);
$age = calculate_age($user['birthdate'] ?? '2000-01-01');
$placeholder_img = 'assets/images/no_image_placeholder.png';
$avatar = !empty($user['avatar_url']) ? $user['avatar_url'] : $placeholder_img;
$photos = json_decode($user['photos'] ?? '[]', true) ?: [$avatar];
if (!in_array($avatar, $photos)) {
    array_unshift($photos, $avatar);
}

$is_private = (bool)($user['is_private'] ?? 0);
$city_display = (!empty(trim($user['location_city'] ?? '')) && $user['location_city'] !== 'No Location') ? $user['location_city'] . ', India' : 'India';
$profile_share_url = SITE_URL . "saathi-profile.php?id=" . $current_user_id;

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-screen-container">

  <!-- Header -->
  <header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px; background: transparent; border: none;">
    <!-- Left: Logo -->
    <div class="header-logo-left" style="display: flex; align-items: center; flex: 1;">
      <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 155px; object-fit: contain; display: block;">
    </div>

    <!-- Center: For Chourasiyas -->
    <div class="header-center-title" style="flex: 2; text-align: center; display: flex; align-items: center; justify-content: center;">
      <span style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">For Chourasiyas</span>
    </div>

    <!-- Right Actions: Filter, Menu -->
    <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; flex: 1; gap: 8px;">
      <button class="icon-btn" id="filterBtn" title="Filter Matches" style="width: 36px; height: 36px; background: #FFFFFF; border: 1px solid #E5E5EA; color: #1C1C1E;">
        <i class="fa-solid fa-sliders" style="font-size: 0.95rem; font-weight: 300;"></i>
      </button>
      <button class="icon-btn" id="openSidebarBtn" title="Menu" style="width: 36px; height: 36px; background: #FFFFFF; border: 1px solid #E5E5EA; color: #1C1C1E;">
        <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
      </button>
    </div>
  </header>

    <!-- Hidden Avatar Upload Form -->
    <form id="avatarUploadForm" action="api/upload_photos.php" method="POST" enctype="multipart/form-data" style="display: none;">
      <input type="hidden" name="action" value="upload_avatar">
      <input type="file" id="avatarFileInput" name="avatar_file" accept="image/*" onchange="document.getElementById('avatarUploadForm').submit()">
    </form>

    <!-- 2. Centered Overlapping Avatar Ring -->
    <div class="profile-avatar-container" onclick="document.getElementById('avatarFileInput').click()" title="Tap to change profile picture" style="cursor:pointer;">
      <div class="profile-avatar-outer-ring">
        <img src="<?= htmlspecialchars($avatar) ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
        <div class="profile-avatar-verified-badge" title="Change Profile Picture">
          <i class="fa-solid fa-camera"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Main Overlapping White Card Container -->
  <div class="profile-main-card">
    
    <!-- User Name & Subtitle -->
    <h1 class="profile-user-name">
      <?= htmlspecialchars($user['full_name']) ?>
      <i class="fa-solid fa-circle-check" style="color: #E91E63; font-size: 1.15rem;" title="Verified Profile"></i>
    </h1>

    <div class="profile-user-subtitle">
      <span><?= $age ?></span>
      <span>|</span>
      <span><i class="fa-solid fa-location-dot" style="color: #E91E63; margin-right: 2px;"></i> <span id="userCityLabel"><?= htmlspecialchars($city_display) ?></span></span>
    </div>

    <!-- Main Full-Width Edit Profile Action Button -->
    <a href="saathi-edit.php" class="btn-pink-action" style="margin-top: 0; margin-bottom: 10px;">
      <i class="fa-solid fa-pen-to-square"></i> Edit Profile
    </a>

    <!-- Download Matrimonial Biodata PDF Button -->
    <a href="biodata.php?id=<?= $current_user_id ?>" target="_blank" class="btn-pink-outline" style="margin-top: 0; margin-bottom: 18px; padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; white-space: nowrap;">
      <i class="fa-solid fa-file-pdf"></i> Download Biodata
    </a>

    <!-- Matrimonial Headline Banner -->
    <?php if (!empty($saathi['headline'])): ?>
      <div style="background: #FDF4F6; border-left: 3.5px solid #E91E63; padding: 10px 14px; border-radius: 12px; font-weight: 600; font-size: 0.84rem; color: #9C1443; margin-bottom: 18px;">
        "<?= htmlspecialchars($saathi['headline']) ?>"
      </div>
    <?php endif; ?>

    <!-- About Me Section -->
    <h3 class="profile-section-title">About Me</h3>
    <p class="profile-about-text">
      <?= !empty($user['bio']) 
          ? nl2br(htmlspecialchars($user['bio'])) 
          : "Looking for a life partner who values family, growth and happiness. Believer in simple living and meaningful bonds." ?>
    </p>

    <!-- Partner Preference Section -->
    <h3 class="profile-section-title">Partner Preference</h3>
    <div class="partner-pref-grid">
      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-user-group"></i>
        </div>
        <div>
          <div class="partner-pref-label">Age</div>
          <div class="partner-pref-value"><?= (int)($saathi['partner_min_age'] ?? 18) ?> - <?= (int)($saathi['partner_max_age'] ?? 45) ?> yrs</div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-ruler-vertical"></i>
        </div>
        <div>
          <div class="partner-pref-label">Height</div>
          <div class="partner-pref-value"><?= (int)($saathi['partner_min_height'] ?? 140) ?>-<?= (int)($saathi['partner_max_height'] ?? 210) ?> cm</div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-om"></i>
        </div>
        <div>
          <div class="partner-pref-label">Religion</div>
          <div class="partner-pref-value"><?= htmlspecialchars($saathi['partner_religion'] ?? 'Any') ?></div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-location-dot"></i>
        </div>
        <div>
          <div class="partner-pref-label">Location</div>
          <div class="partner-pref-value"><?= htmlspecialchars($saathi['partner_location'] ?? 'India') ?></div>
        </div>
      </div>
    </div>

    <!-- Photos Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 22px; margin-bottom: 10px;">
      <h3 class="profile-section-title" style="margin: 0;">Photos</h3>
      <form action="api/upload_photos.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <label style="cursor: pointer; background: #FDF4F6; color: #E91E63; border: 1px solid #FCE4EC; border-radius: 14px; padding: 4px 12px; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
          <i class="fa-solid fa-plus"></i> Add Photo
          <input type="file" name="photos[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
        </label>
      </form>
    </div>

    <div class="photo-gallery-grid">
      <?php 
        $display_photos = array_slice($photos, 0, 4);
        $extra_count = count($photos) - 4;
        foreach ($display_photos as $idx => $p_url): 
      ?>
        <div class="photo-gallery-item" onclick="openPhotoPreview('<?= htmlspecialchars($p_url) ?>', <?= htmlspecialchars(json_encode(array_values($photos))) ?>, <?= $idx ?>)" style="cursor:pointer;" title="Click to View Full Photo">
          <img src="<?= htmlspecialchars($p_url) ?>" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
          <?php if ($idx === 3 && $extra_count > 0): ?>
            <div class="photo-gallery-overlay">+<?= $extra_count ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Key Specifications Breakdown -->
    <h3 class="profile-section-title" style="margin-top:28px;">Key Specifications</h3>
    <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 12px;">
      
      <!-- Basic & Physical -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);">
        <div style="font-weight: 800; font-size: 0.82rem; color: #C31F3A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-user" style="font-size: 0.88rem;"></i> Basic & Physical Details
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Height</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= (int)($saathi['height_cm'] ?? 165) ?> cm</div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['marital_status'] ?? 'Never Married') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother Tongue</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['mother_tongue'] ?? 'Hindi') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Diet</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['diet'] ?? 'Vegetarian') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Body Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['body_type'] ?? 'Average') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Complexion</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['complexion'] ?? 'Fair') ?></div>
          </div>
        </div>
      </div>

      <!-- Education & Career -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);">
        <div style="font-weight: 800; font-size: 0.82rem; color: #C31F3A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-graduation-cap" style="font-size: 0.88rem;"></i> Education & Career
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Qualification</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['highest_qualification'] ?? 'Graduate') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Degree</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' / Degree', '', $saathi['degree'] ?: 'Not Specified')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Profession</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($user['occupation'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Sector</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['occupation_type'] ?? 'Not Specified') ?></div>
          </div>
          <div style="grid-column: 1 / -1;">
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Annual Income</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['annual_income'] ?? 'Prefer not to say') ?></div>
          </div>
        </div>
      </div>

      <!-- Astrology & Kundli -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);">
        <div style="font-weight: 800; font-size: 0.82rem; color: #C31F3A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-moon" style="font-size: 0.88rem;"></i> Astrology & Kundli
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Gotra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['gotra'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Manglik</div>
            <?php 
              $m_status = $saathi['manglik_status'] ?? 'No';
              if ($m_status === 'Prefer not to say' || empty($m_status)) { $m_status = 'Non-Manglik'; }
            ?>
            <div style="font-size: 0.86rem; color: <?= ($m_status === 'Yes' || $m_status === 'Manglik') ? '#C31F3A' : '#1C1C1E' ?>; font-weight: 700;"><?= htmlspecialchars($m_status) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Rashi</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['rashi'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nakshatra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['nakshatra'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

      <!-- Family Background -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);">
        <div style="font-weight: 800; font-size: 0.82rem; color: #C31F3A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-house-chimney-window" style="font-size: 0.88rem;"></i> Family & Values
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Family Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' Family', '', $saathi['family_type'] ?? 'Nuclear')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['family_status'] ?? 'Middle Class') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Father</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['father_occupation'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($saathi['mother_occupation'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Account & Privacy Controls -->
    <h3 class="profile-section-title" style="margin-top:28px;">Account Settings</h3>
    <div style="background: #FDF4F6; border-radius: 16px; padding: 14px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
      <div>
        <div style="font-weight: 700; font-size: 0.88rem; color: #1F2937;" id="privacyStatusLabel"><?= $is_private ? 'Private Profile' : 'Public Profile' ?></div>
        <div style="font-size: 0.72rem; color: #6B7280; margin-top: 2px;" id="privacyStatusDesc">
          <?= $is_private ? 'Only main photo visible to non-matches.' : 'Everyone can view your profile photos.' ?>
        </div>
      </div>
      <label class="ios-switch">
        <input type="checkbox" id="privacyToggleBtn" <?= $is_private ? 'checked' : '' ?> onchange="togglePrivacyMode(this.checked)">
        <span class="ios-slider"></span>
      </label>
    </div>

    <!-- Action Buttons Row -->
    <div style="display: flex; gap: 10px; margin-top: 16px;">
      <button type="button" id="fetchLocBtn" onclick="fetchRealLocation()" class="btn-pink-outline" style="flex: 1; margin: 0; padding: 10px;">
        <i class="fa-solid fa-location-crosshairs"></i> Set Location
      </button>

      <button type="button" onclick="openShareModal()" class="btn-pink-outline" style="flex: 1; margin: 0; padding: 10px;">
        <i class="fa-solid fa-share-nodes"></i> Share Profile
      </button>
    </div>

  </div>

</div>

<?php
$biodata_share_url = SITE_URL . "biodata.php?id=" . $current_user_id;
$avatar_full_url = (strpos($avatar, 'http') === 0) ? $avatar : (SITE_URL . ltrim($avatar, '/'));
$rich_share_text = "🌸 Matrimonial Profile & Biodata of " . $user['full_name'] . " (" . $age . " yrs, " . $city_display . ")\n"
    . "💼 Profession: " . ($user['occupation'] ?: 'Not Specified') . "\n"
    . "📑 Download Biodata PDF: " . $biodata_share_url . "\n"
    . "🔗 View Profile: " . $profile_share_url;
?>

<!-- Share Profile & Biodata Bottom Sheet Modal -->
<div id="shareProfileModal" class="ios-sheet-overlay" onclick="closeShareModal(event)">
  <div class="ios-sheet-container" onclick="event.stopPropagation()">
    <div class="sheet-drag-handle"></div>

    <div class="sheet-user-header">
      <img src="<?= htmlspecialchars($avatar) ?>" class="sheet-user-avatar" alt="Avatar" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
      <div style="flex:1;">
        <h4 class="sheet-user-title">Share <?= htmlspecialchars($user['full_name']) ?>'s Profile</h4>
        <span class="sheet-user-sub">Share Profile & Biodata PDF with photo</span>
      </div>
      <button type="button" onclick="closeShareModal()" style="background:none; border:none; font-size:1.2rem; color:#8E8E93; cursor:pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- 1. Direct Profile Link Box -->
    <div style="margin-bottom: 12px;">
      <div style="font-size: 0.74rem; font-weight: 700; color: #8E8E93; margin-bottom: 4px;">PROFILE LINK</div>
      <div style="background: #F3F4F6; border-radius: 14px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
        <input type="text" id="shareLinkInput" readonly value="<?= $profile_share_url ?>" style="flex: 1; border: none; background: transparent; font-size: 0.82rem; font-weight: 600; color: #111827; outline: none;">
        <button onclick="copyShareLink('shareLinkInput', 'Profile link')" style="background: #E91E63; color: #FFF; border: none; padding: 6px 14px; border-radius: 12px; font-weight: 700; font-size: 0.78rem; cursor: pointer;">
          Copy
        </button>
      </div>
    </div>

    <!-- 2. Matrimonial Biodata PDF Link Box -->
    <div style="margin-bottom: 16px;">
      <div style="font-size: 0.74rem; font-weight: 700; color: #8E8E93; margin-bottom: 4px;">BIODATA PDF LINK</div>
      <div style="background: #F3F4F6; border-radius: 14px; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
        <input type="text" id="shareBiodataInput" readonly value="<?= $biodata_share_url ?>" style="flex: 1; border: none; background: transparent; font-size: 0.82rem; font-weight: 600; color: #111827; outline: none;">
        <button onclick="copyShareLink('shareBiodataInput', 'Biodata PDF link')" style="background: #C31F3A; color: #FFF; border: none; padding: 6px 14px; border-radius: 12px; font-weight: 700; font-size: 0.78rem; cursor: pointer;">
          Copy
        </button>
      </div>
    </div>

    <div class="sheet-options-list">
      <button type="button" class="sheet-option-btn" onclick="triggerWebShare()" style="color: #E91E63; background: #FDF4F6;">
        <i class="fa-solid fa-share-nodes" style="color: #E91E63;"></i>
        <span>Share via App / Native Options</span>
      </button>

      <a href="https://api.whatsapp.com/send?text=<?= urlencode($rich_share_text) ?>" target="_blank" class="sheet-option-btn" style="color: #25D366; background: #E8F9EE; text-decoration: none;">
        <i class="fa-brands fa-whatsapp" style="color: #25D366; font-size: 1.2rem;"></i>
        <span>Share to WhatsApp (Profile & Biodata)</span>
      </a>

      <a href="https://t.me/share/url?url=<?= urlencode($profile_share_url) ?>&text=<?= urlencode($rich_share_text) ?>" target="_blank" class="sheet-option-btn" style="color: #0088CC; background: #E6F4FB; text-decoration: none;">
        <i class="fa-brands fa-telegram" style="color: #0088CC; font-size: 1.2rem;"></i>
        <span>Share to Telegram</span>
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
        desc.textContent = 'Only main photo visible to non-matches.';
      } else {
        label.textContent = 'Public Profile';
        desc.textContent = 'Everyone can view your profile photos.';
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

function copyShareLink(inputId = 'shareLinkInput', label = 'Profile link') {
  const linkInput = document.getElementById(inputId);
  if (!linkInput) return;
  linkInput.select();
  linkInput.setSelectionRange(0, 99999);
  try {
    navigator.clipboard.writeText(linkInput.value);
    alert(`${label} copied to clipboard!`);
  } catch(e) {
    alert(`${label} selected. Copy now!`);
  }
}

function triggerWebShare() {
  const shareText = "<?= str_replace(["\r", "\n"], ['\r', '\n'], addslashes($rich_share_text)) ?>";
  if (navigator.share) {
    navigator.share({
      title: "<?= addslashes($share_title) ?>",
      text: shareText,
      url: "<?= $profile_share_url ?>"
    }).catch(() => {});
  } else {
    copyShareLink('shareLinkInput', 'Profile link');
  }
}

function openPhotoViewer(url) {
  document.getElementById('fullPhotoViewerImg').src = url;
  document.getElementById('profilePhotoViewerModal').classList.add('active');
}

function closePhotoViewer() {
  document.getElementById('profilePhotoViewerModal').classList.remove('active');
}

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
        btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Set Location';
        if (data.success) {
          if (data.city) {
            document.getElementById('userCityLabel').textContent = data.city + ', India';
          }
          alert(`Location updated successfully! (${data.city || 'Coordinates saved'})`);
        } else {
          alert(data.error || 'Failed to update location');
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Set Location';
        alert('Network error updating location');
      });
    },
    (error) => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Set Location';
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
