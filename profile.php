<?php
// profile.php - Single Unified Matrimonial Profile (Ref: media_1789219148081.png)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

try {
    $active_tab = 'profile';
    $current_user_id = get_current_user_id();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
    $stmt->execute([':u' => $current_user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: splash.php");
        exit;
    }

    $saathi = get_saathi_profile($current_user_id) ?: [];
    $age = calculate_age((string)($user['birthdate'] ?? '2000-01-01'));
    $placeholder_img = 'assets/images/no_image_placeholder.png';
    $avatar = get_valid_avatar_url((string)($user['avatar_url'] ?? ''));
    $photos = json_decode((string)($user['photos'] ?? '[]'), true);
    if (!is_array($photos)) {
        $photos = [];
    }
    if (empty($photos) && !empty($avatar) && $avatar !== $placeholder_img) {
        $photos = [$avatar];
    }

    $has_photo = (!empty($avatar) && $avatar !== $placeholder_img);
    $completion_pct = calculate_saathi_completion($saathi, $user);

    $is_private = (bool)($user['is_private'] ?? 0);
    $city_name = trim((string)($user['location_city'] ?? ''));
    $city_display = (!empty($city_name) && $city_name !== 'No Location') ? $city_name . ', India' : 'Location Not Set';
    $profile_share_url = SITE_URL . "saathi-profile.php?id=" . $current_user_id;
    $share_title = "Matrimonial Profile - " . (string)($user['full_name'] ?? '');

    // Fetch sample candidates for AI recommendations
    require_once __DIR__ . '/config/groq.php';
    $rec_stmt = $pdo->prepare("
        SELECT u.*, sp.highest_qualification, sp.occupation_type, sp.height_cm, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree
        FROM users u
        LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
        WHERE u.id != :u
        LIMIT 6
    ");
    $rec_stmt->execute([':u' => $current_user_id]);
    $rec_candidates = $rec_stmt->fetchAll();
    $ai_recommendations = rank_candidates_with_groq($user, $saathi, $rec_candidates);

    $css_version = time();
    require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-screen-container" style="width: 100%; max-width: 580px; margin: 0 auto; padding-bottom: 90px;">

  <!-- Header -->
  <header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px; background: #FFFFFF; border-bottom: 1px solid #E5E5EA;">
    <div class="header-title" style="font-size: 1.6rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.5px; font-family: system-ui, -apple-system, sans-serif;">
      Profile
    </div>

    <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
      <button class="icon-btn" id="filterBtn" title="Filter Matches" style="width: 36px; height: 36px; background: #FFFFFF; border: 1px solid #E5E5EA; color: #1C1C1E; display: flex; align-items: center; justify-content: center;">
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
    <div class="profile-avatar-container" onclick="document.getElementById('avatarFileInput').click()" title="Tap to change profile picture" style="cursor:pointer; margin-top: 10px; margin-bottom: 14px;">
      <div class="profile-avatar-outer-ring">
        <img src="<?= htmlspecialchars($avatar) ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
        <div class="profile-avatar-verified-badge" title="Change Profile Picture">
          <i class="fa-solid fa-camera"></i>
        </div>
      </div>
    </div>

  <!-- 3. Main Overlapping White Card Container -->
  <div class="profile-main-card" style="margin-left: 12px; margin-right: 12px;">
    
    <!-- User Name & Subtitle -->
    <h1 class="profile-user-name" style="font-family: system-ui, -apple-system, sans-serif; font-size: 1.4rem;">
      <?= htmlspecialchars($user['full_name']) ?>
      <i class="fa-solid fa-circle-check" style="color: #FF2D55; font-size: 1.15rem;" title="Verified Profile"></i>
    </h1>

    <div class="profile-user-subtitle" style="margin-bottom: 16px;">
      <span><?= $age ?> Yrs</span>
      <span>|</span>
      <span><i class="fa-solid fa-location-dot" style="color: #FF2D55; margin-right: 2px;"></i> <span id="userCityLabel"><?= htmlspecialchars($city_display) ?></span></span>
    </div>

    <!-- Upload Photo Callout Prompt if photo missing -->
    <?php if (!$has_photo): ?>
      <div style="background: linear-gradient(135deg, #FFF0F4 0%, #FFEBF0 100%); border: 1px solid #FFD6E0; border-radius: 18px; padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="width: 38px; height: 38px; border-radius: 50%; background: #FF2D55; color: #FFF; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fa-solid fa-camera"></i>
          </div>
          <div>
            <div style="font-size: 0.88rem; font-weight: 700; color: #1C1C1E;">Add Profile Photo</div>
            <div style="font-size: 0.76rem; color: #636366;">Profiles with photos get 10x more interest!</div>
          </div>
        </div>
        <button type="button" onclick="document.getElementById('avatarFileInput').click()" style="background: #FF2D55; color: #FFF; border: none; padding: 7px 14px; border-radius: 14px; font-weight: 600; font-size: 0.78rem; cursor: pointer; white-space: nowrap;">
          Upload
        </button>
      </div>
    <?php endif; ?>

    <!-- Profile Completion Progress Section -->
    <div style="background: #F8F8FA; border: 1px solid #EBEBEF; border-radius: 18px; padding: 14px 16px; margin-bottom: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
        <span style="font-size: 0.84rem; font-weight: 700; color: #1C1C1E; font-family: system-ui, -apple-system, sans-serif;">Profile Completion</span>
        <span style="font-size: 0.84rem; font-weight: 800; color: #C31F3A;"><?= $completion_pct ?>%</span>
      </div>
      <div style="width: 100%; height: 7px; background: #E5E5EA; border-radius: 4px; overflow: hidden; margin-bottom: 8px;">
        <div style="width: <?= $completion_pct ?>%; height: 100%; background: linear-gradient(90deg, #1C1C1E 0%, #C31F3A 100%); border-radius: 4px; transition: width 0.4s ease;"></div>
      </div>
      <?php if ($completion_pct < 100): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
          <span style="font-size: 0.76rem; color: #8E8E93; flex: 1; min-width: 150px;">Complete your biodata for better matching</span>
          <a href="saathi-edit.php" style="font-size: 0.76rem; font-weight: 700; color: #C31F3A; text-decoration: none; white-space: nowrap; flex-shrink: 0;">Update Profile &rarr;</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Main Full-Width Edit Profile Action Button -->
    <div style="display: flex; gap: 10px; margin-bottom: 16px;">
      <a href="saathi-edit.php" class="btn-pink-action" style="flex: 1; margin: 0; padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 16px; font-weight: 600; display: flex; align-items: center; justify-content: center; background: #1C1C1E; color: #FFF; text-decoration: none;">
        <i class="fa-solid fa-pen-to-square" style="margin-right: 6px;"></i> Edit Details
      </a>
      <a href="biodata.php?id=<?= $current_user_id ?>" target="_blank" class="btn-pink-outline" style="flex: 1; margin: 0; padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 16px; font-weight: 600; white-space: nowrap; display: flex; align-items: center; justify-content: center; border: 1px solid #E5E5EA; color: #1C1C1E; text-decoration: none;">
        <i class="fa-solid fa-file-pdf" style="margin-right: 6px; color: #C31F3A;"></i> Download Biodata
      </a>
    </div>

    <!-- Matrimonial Headline Banner -->
    <?php if (!empty($saathi['headline'])): ?>
      <div style="background: #F8F8FA; border-left: 3px solid #C31F3A; border-top: 1px solid #F0F0F5; border-right: 1px solid #F0F0F5; border-bottom: 1px solid #F0F0F5; padding: 9px 12px; border-radius: 10px; font-weight: 500; font-size: 0.82rem; color: #1C1C1E; margin-bottom: 16px;">
        "<?= htmlspecialchars($saathi['headline']) ?>"
      </div>
    <?php endif; ?>

    <!-- About Me Section -->
    <h3 class="profile-section-title" style="font-family: system-ui, -apple-system, sans-serif;">About Me</h3>
    <div class="profile-about-text" style="font-size: 0.88rem; color: #3A3A3C; line-height: 1.5; margin-bottom: 20px;">
      <?php if (!empty($user['bio'])): 
        $bio_text = trim((string)$user['bio']);
        $is_long_bio = (mb_strlen($bio_text) > 130 || substr_count($bio_text, "\n") >= 2);
      ?>
        <?php if ($is_long_bio): ?>
          <div id="bioTextShortUser">
            <?= nl2br(htmlspecialchars(mb_strimwidth($bio_text, 0, 130, '...'))) ?>
            <button type="button" onclick="toggleBioUser(true)" style="background: none; border: none; padding: 0; color: #C31F3A; font-weight: 700; font-size: 0.8rem; cursor: pointer; margin-left: 4px; display: inline-flex; align-items: center; gap: 2px;">
              Read More <i class="fa-solid fa-chevron-down" style="font-size: 0.68rem;"></i>
            </button>
          </div>
          <div id="bioTextFullUser" style="display: none;">
            <?= nl2br(htmlspecialchars($bio_text)) ?>
            <button type="button" onclick="toggleBioUser(false)" style="background: none; border: none; padding: 0; color: #C31F3A; font-weight: 700; font-size: 0.8rem; cursor: pointer; margin-left: 4px; margin-top: 4px; display: inline-flex; align-items: center; gap: 2px;">
              Read Less <i class="fa-solid fa-chevron-up" style="font-size: 0.68rem;"></i>
            </button>
          </div>
        <?php else: ?>
          <?= nl2br(htmlspecialchars($bio_text)) ?>
        <?php endif; ?>
      <?php else: ?>
        <span style="color: #8E8E93; font-style: italic;">No bio added yet. Tell potential life partners about yourself, your career, and family values.</span>
        <div style="margin-top: 8px;">
          <a href="saathi-edit.php?step=2" style="font-size: 0.78rem; font-weight: 700; color: #C31F3A; text-decoration: none;">+ Add Bio / About Me</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Partner Preference Section -->
    <h3 class="profile-section-title" style="font-family: system-ui, -apple-system, sans-serif;">Partner Preference</h3>
    <div class="partner-pref-grid" style="margin-bottom: 20px;">
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
          <div class="partner-pref-value"><?= htmlspecialchars($saathi['partner_location'] ?? 'Any') ?></div>
        </div>
      </div>
    </div>

    <!-- Photos Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; margin-bottom: 10px;">
      <h3 class="profile-section-title" style="margin: 0; font-family: system-ui, -apple-system, sans-serif;">Photos</h3>
      <form action="api/upload_photos.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <label style="cursor: pointer; background: #F8F8FA; color: #1C1C1E; border: 1px solid #E5E5EA; border-radius: 14px; padding: 4px 12px; font-size: 0.76rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
          <i class="fa-solid fa-plus" style="color: #FF2D55;"></i> Add Photo
          <input type="file" name="photos[]" multiple accept="image/*" style="display: none;" onchange="this.form.submit()">
        </label>
      </form>
    </div>

    <div class="photo-gallery-grid" style="margin-bottom: 24px;">
      <?php if (!empty($photos)): ?>
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
      <?php else: ?>
        <div style="grid-column: 1 / -1; background: #F8F8FA; border: 1px dashed #E5E5EA; border-radius: 16px; padding: 20px; text-align: center; color: #8E8E93; font-size: 0.82rem;">
          No gallery photos uploaded yet. Click "+ Add Photo" to showcase your pictures!
        </div>
      <?php endif; ?>
    </div>

    <!-- Key Specifications Breakdown -->
    <h3 class="profile-section-title" style="margin-top:20px; font-family: system-ui, -apple-system, sans-serif;">Key Specifications</h3>
    <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 12px;">
      
      <!-- Basic & Physical -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);">
        <div style="font-weight: 700; font-size: 0.82rem; color: #1C1C1E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-user" style="font-size: 0.88rem; color: #FF2D55;"></i> Basic & Physical Details
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Height</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= !empty($saathi['height_cm']) ? (int)$saathi['height_cm'] . ' cm' : 'Not Specified' ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['marital_status'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother Tongue</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['mother_tongue'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Diet</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['diet'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Body Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['body_type'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Complexion</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['complexion'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

      <!-- Education & Career -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);">
        <div style="font-weight: 700; font-size: 0.82rem; color: #1C1C1E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-graduation-cap" style="font-size: 0.88rem; color: #FF2D55;"></i> Education & Career
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Qualification</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['highest_qualification'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Degree</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['degree'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Profession</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($user['occupation'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Sector</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['occupation_type'] ?: 'Not Specified') ?></div>
          </div>
          <div style="grid-column: 1 / -1;">
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Annual Income</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['annual_income'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

      <!-- Astrology & Kundli -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);">
        <div style="font-weight: 700; font-size: 0.82rem; color: #1C1C1E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-moon" style="font-size: 0.88rem; color: #FF2D55;"></i> Astrology & Kundli
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Gotra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['gotra'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Manglik</div>
            <?php 
              $m_status = $saathi['manglik_status'] ?? '';
              if (empty($m_status)) { $m_status = 'Not Specified'; }
            ?>
            <div style="font-size: 0.86rem; color: <?= ($m_status === 'Yes' || $m_status === 'Manglik') ? '#FF2D55' : '#1C1C1E' ?>; font-weight: 600;"><?= htmlspecialchars($m_status) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Rashi</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['rashi'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nakshatra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['nakshatra'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

      <!-- Family Background -->
      <div style="background: #FFFFFF; border-radius: 18px; padding: 16px; border: 1px solid #F0F0F5; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);">
        <div style="font-weight: 700; font-size: 0.82rem; color: #1C1C1E; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
          <i class="fa-solid fa-house-chimney-window" style="font-size: 0.88rem; color: #FF2D55;"></i> Family & Values
        </div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px 14px;">
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Family Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['family_type'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['family_status'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Father</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['father_occupation'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 600;"><?= htmlspecialchars($saathi['mother_occupation'] ?: 'Not Specified') ?></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Account & Privacy Controls -->
    <h3 class="profile-section-title" style="margin-top:24px; font-family: system-ui, -apple-system, sans-serif;">Account Settings</h3>

    <?php 
    $user_comm = strtolower(trim(($saathi['caste_community'] ?? '') . ' ' . ($saathi['religion'] ?? '')));
    $is_chourasiya_user = (strpos($user_comm, 'chourasiya') !== false || strpos($user_comm, 'chaurasia') !== false);
    $auto_enable_chourasiya = isset($_GET['join_chourasiya']) && $_GET['join_chourasiya'] == '1';
    if ($auto_enable_chourasiya && !$is_chourasiya_user) {
        $is_chourasiya_user = true;
        $upd_c = $pdo->prepare("UPDATE saathi_profiles SET caste_community = 'Chourasiya' WHERE user_id = :u");
        $upd_c->execute([':u' => $current_user_id]);
    }
    ?>

    <!-- Community Chourasiya Member Toggle Switch -->
    <div style="background: linear-gradient(135deg, #FFF0F4 0%, #FFF5F7 100%); border: 1px solid #FFE0E6; border-radius: 16px; padding: 14px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;" id="chourasiyaToggleCard">
      <div>
        <div style="font-weight: 700; font-size: 0.88rem; color: #1C1C1E; display: flex; align-items: center; gap: 6px;">
          <span>👑 I'm a Chourasiya Member</span>
        </div>
        <div style="font-size: 0.72rem; color: #636366; margin-top: 2px;" id="chourasiyaStatusDesc">
          <?= $is_chourasiya_user ? 'Active! Your profile appears under Chourasiya community tabs.' : 'Enable to list your profile under Chourasiya Samaj tabs.' ?>
        </div>
      </div>
      <label class="ios-switch">
        <input type="checkbox" id="chourasiyaToggleBtn" <?= $is_chourasiya_user ? 'checked' : '' ?> onchange="toggleChourasiyaMode(this.checked)">
        <span class="ios-slider"></span>
      </label>
    </div>

    <div style="background: #F8F8FA; border: 1px solid #E5E5EA; border-radius: 16px; padding: 14px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
      <div>
        <div style="font-weight: 700; font-size: 0.88rem; color: #1C1C1E;" id="privacyStatusLabel"><?= $is_private ? 'Private Profile' : 'Public Profile' ?></div>
        <div style="font-size: 0.72rem; color: #636366; margin-top: 2px;" id="privacyStatusDesc">
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
      <button type="button" id="fetchLocBtn" onclick="fetchRealLocation()" class="btn-pink-outline" style="flex: 1; margin: 0; padding: 10px; border-radius: 14px; font-weight: 600; font-size: 0.82rem; border: 1px solid #E5E5EA; color: #1C1C1E;">
        <i class="fa-solid fa-location-crosshairs" style="color: #FF2D55;"></i> Set Location
      </button>

      <button type="button" onclick="openShareModal()" class="btn-pink-outline" style="flex: 1; margin: 0; padding: 10px; border-radius: 14px; font-weight: 600; font-size: 0.82rem; border: 1px solid #E5E5EA; color: #1C1C1E;">
        <i class="fa-solid fa-share-nodes" style="color: #FF2D55;"></i> Share Profile
      </button>
    </div>

    <!-- Professional AI Recommendations Cards Section -->
    <h3 class="profile-section-title" style="margin-top:24px; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: space-between;">
      <span>AI Recommendations</span>
      <span style="font-size: 0.72rem; font-weight: 700; color: #C31F3A; background: #FFF0F4; padding: 3px 10px; border-radius: 12px; border: 1px solid #FFE0E6;">95%+ Match</span>
    </h3>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
      <?php if (!empty($ai_recommendations)): ?>
        <?php foreach ($ai_recommendations as $rec): 
          $rec_cand = $rec['candidate'] ?? null;
          if (!$rec_cand) continue;
          $r_name = ucwords(strtolower(trim((string)$rec_cand['full_name'])));
          $r_age = calculate_age((string)($rec_cand['birthdate'] ?? '2000-01-01'));
          $r_avatar = get_valid_avatar_url((string)($rec_cand['avatar_url'] ?? ''));
          $r_city = !empty($rec_cand['location_city']) && strpos(strtolower($rec_cand['location_city']), 'san francisco') === false ? $rec_cand['location_city'] : 'India';
          $r_score = (int)($rec['compatibilityScore'] ?? 95);
        ?>
          <div style="background: #FFFFFF; border: 1px solid #EBEBEF; border-radius: 18px; padding: 12px 14px; box-shadow: 0 4px 14px rgba(0,0,0,0.03); display: flex; gap: 12px; align-items: center;">
            <img src="<?= htmlspecialchars($r_avatar) ?>" style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; flex-shrink: 0;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.svg';">
            
            <div style="flex: 1; min-width: 0;">
              <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 3px;">
                <h4 style="font-size: 0.92rem; font-weight: 700; color: #1C1C1E; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin: 0;"><?= htmlspecialchars($r_name) ?></h4>
                <span style="background: #FFF0F4; color: #C31F3A; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; border: 1px solid #FFE0E6; flex-shrink: 0;"><?= $r_score ?>% Match</span>
              </div>
              
              <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 500; margin-bottom: 8px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; display: flex; align-items: center; gap: 4px;">
                <i class="fa-solid fa-location-dot" style="color: #C31F3A; font-size: 0.68rem;"></i> <?= htmlspecialchars($r_city) ?> &bull; <?= $r_age ?> Yrs
              </div>

              <div style="display: flex; gap: 8px;">
                <a href="saathi-profile.php?id=<?= $rec_cand['id'] ?>" style="padding: 6px 14px; border-radius: 14px; background: #F2F2F7; color: #1C1C1E; font-weight: 700; font-size: 0.74rem; text-decoration: none;">
                  View Profile
                </a>
                <button type="button" onclick="handleCandidateCardChat(<?= $rec_cand['id'] ?>, '<?= htmlspecialchars(addslashes($r_name)) ?>')" style="padding: 6px 14px; border-radius: 14px; background: #FFF0F4; color: #C31F3A; border: 1px solid #FFE0E6; font-weight: 700; font-size: 0.74rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                  <i class="fa-solid fa-comment-dots" style="color: #C31F3A; font-size: 0.75rem;"></i> Start Chat
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="background: #F8F8FA; border-radius: 16px; padding: 16px; text-align: center; color: #8E8E93; font-size: 0.8rem;">
          AI Recommendations loading fresh candidates...
        </div>
      <?php endif; ?>
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
function toggleBioUser(expand) {
  const shortEl = document.getElementById('bioTextShortUser');
  const fullEl = document.getElementById('bioTextFullUser');
  if (shortEl && fullEl) {
    shortEl.style.display = expand ? 'none' : 'block';
    fullEl.style.display = expand ? 'block' : 'none';
  }
}

function toggleChourasiyaMode(isChourasiya) {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'toggle_chourasiya', is_chourasiya: isChourasiya })
  })
  .then(res => res.json())
  .then(data => {
    const desc = document.getElementById('chourasiyaStatusDesc');
    if (data.success) {
      if (desc) {
        desc.textContent = data.is_chourasiya 
          ? 'Active! Your profile appears under Chourasiya community tabs.' 
          : 'Enable to list your profile under Chourasiya Samaj tabs.';
      }
      alert(data.message || 'Community preference updated!');
    } else {
      alert(data.error || 'Failed to update community preference');
    }
  })
  .catch(() => alert('Network error updating community preference'));
}

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
} catch (Throwable $e) {
    error_log("profile.php Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "<div style='padding:40px 20px; text-align:center; font-family:sans-serif;'>
            <h2 style='color:#FF2D55;'>Profile Temporarily Unavailable</h2>
            <p style='color:#666;'>We encountered an unexpected error loading your profile. Please try refreshing.</p>
            <p style='font-size:0.8rem; color:#aaa;'>" . htmlspecialchars($e->getMessage()) . "</p>
            <a href='home.php' style='display:inline-block; margin-top:15px; padding:10px 20px; background:#FF2D55; color:#fff; text-decoration:none; border-radius:12px;'>Return Home</a>
          </div>";
}
?>
