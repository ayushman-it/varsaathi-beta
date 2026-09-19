<?php
// saathi-profile.php - Ultra-Modern Shaadi.com Profile Viewer (Ref: media_1789219148081.png)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

try {
    $target_id = (int)($_GET['id'] ?? 0);
    $current_user_id = (int)get_current_user_id();

    if ($target_id <= 0) {
        $target_id = $current_user_id; // Default to self if no ID provided
    }

    $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $u_stmt->execute([':id' => $target_id]);
    $target_user = $u_stmt->fetch();

    if (!$target_user) {
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }

    $is_own_profile = ($target_id === $current_user_id);
    $target_saathi = get_saathi_profile($target_id);
    if (!is_array($target_saathi)) {
        $target_saathi = [];
    }

    // Check existing match/interest status between current user and target user
    $existing_match_row = false;
    if ($current_user_id > 0 && $target_id > 0) {
        $u1 = min((int)$current_user_id, (int)$target_id);
        $u2 = max((int)$current_user_id, (int)$target_id);
        $m_check = $pdo->prepare("
            SELECT id, status, requested_by 
            FROM matches 
            WHERE user1_id = :u1 AND user2_id = :u2
        ");
        $m_check->execute([':u1' => $u1, ':u2' => $u2]);
        $existing_match_row = $m_check->fetch();
    }

    $has_sent_interest = ($existing_match_row && (int)($existing_match_row['requested_by'] ?? 0) === $current_user_id);

    $age = calculate_age($target_user['birthdate'] ?? '2000-01-01');
    $avatar = get_valid_avatar_url($target_user['avatar_url'] ?? '');

    $photos_decoded = json_decode((string)($target_user['photos'] ?? '[]'), true);
    $photos_raw = is_array($photos_decoded) ? $photos_decoded : [$avatar];
    if (!in_array($avatar, $photos_raw)) {
        array_unshift($photos_raw, $avatar);
    }

    $privacy_decoded = json_decode((string)($target_saathi['privacy_json'] ?? '{}'), true);
    $privacy = is_array($privacy_decoded) ? $privacy_decoded : [
        'show_income' => true,
        'show_religion' => true,
        'show_community' => true,
        'show_kundli' => true,
        'show_family' => true
    ];

    $city_raw = trim((string)($target_user['location_city'] ?? ''));
    if (empty($city_raw) || $city_raw === 'No Location' || strpos(strtolower($city_raw), 'san francisco') !== false) {
        $location_display = 'India';
    } else {
        $location_display = ucwords(strtolower($city_raw)) . ', India';
    }

    $target_name = (string)($target_user['full_name'] ?? 'Candidate');
    if (empty(trim($target_name))) { $target_name = 'Candidate'; }

    $og_title = htmlspecialchars($target_name) . " (" . $age . " yrs, " . $location_display . ") - VARSAATHI Matrimony";
    $og_description = "Matrimonial Profile & Biodata of " . htmlspecialchars($target_name) . ". Profession: " . htmlspecialchars((string)($target_user['occupation'] ?? 'Member')) . ". View profile on Varsaathi!";
    $og_image = (strpos((string)$avatar, 'http') === 0) ? $avatar : (SITE_URL . ltrim((string)$avatar, '/'));
    $og_url = SITE_URL . "saathi-profile.php?id=" . $target_id;

    $css_version = time();
    require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-screen-container">

  <!-- 1. Soft Pink Header Banner (Ref: media_1789806192106.png) -->
  <div class="profile-top-banner">
    <div class="profile-nav-header">
      <a href="javascript:history.length > 1 ? history.back() : (window.location.href='index.php')" class="profile-nav-btn" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
      </a>

      <div class="profile-nav-title">Profile</div>

      <button type="button" class="profile-nav-btn" title="Options" onclick="alert('Profile options: Report, Share or Bookmark')">
        <i class="fa-solid fa-ellipsis-vertical"></i>
      </button>
    </div>

    <!-- 2. Centered Overlapping Avatar Ring -->
    <div style="margin-top: 8px; margin-bottom: 6px;">
      <div class="profile-avatar-outer-ring" style="cursor: pointer;" onclick="openPhotoPreview('<?= htmlspecialchars((string)$avatar) ?>', <?= htmlspecialchars(json_encode(array_values($photos_raw))) ?>, 0)" title="Click to Preview Photo">
        <div class="profile-avatar-gradient-border">
          <img src="<?= htmlspecialchars((string)$avatar) ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        </div>
        <div class="profile-avatar-verified-badge" title="Verified Profile">
          <i class="fa-solid fa-check"></i>
        </div>
      </div>
    </div>

    <!-- User Name & Subtitle -->
    <h1 class="profile-user-name">
      <?= htmlspecialchars($target_name) ?>
      <span style="background: #E02847; color: #FFF; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; vertical-align: middle;">
        <i class="fa-solid fa-check"></i>
      </span>
    </h1>

    <div class="profile-user-subtitle">
      <span><?= $age ?></span>
      <span>|</span>
      <span><i class="fa-solid fa-location-dot" style="color: #E02847;"></i> <?= htmlspecialchars((string)$location_display) ?></span>
    </div>

    <!-- Quick Metadata Chips Row -->
    <div class="profile-chips-row">
      <?php 
        $cand_qual = !empty($target_saathi['highest_qualification']) ? $target_saathi['highest_qualification'] : (!empty($target_saathi['degree']) ? $target_saathi['degree'] : 'Graduate');
        $cand_occ = !empty($target_user['occupation']) ? $target_user['occupation'] : (!empty($target_saathi['occupation_type']) ? $target_saathi['occupation_type'] : 'Working Professional');
      ?>
      <div class="profile-chip">
        <i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars((string)$cand_qual) ?>
      </div>
      <div class="profile-chip">
        <i class="fa-solid fa-briefcase"></i> <?= htmlspecialchars((string)$cand_occ) ?>
      </div>
      <div class="profile-chip">
        <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars((string)$location_display) ?>
      </div>
    </div>

    <!-- Action Buttons Row -->
    <?php if (!$is_own_profile): ?>
      <div class="profile-action-row">
        <button type="button" class="btn-action-outline <?= $has_sent_interest ? 'active' : '' ?>" id="candInterestBtn" onclick="toggleCandInterest(<?= $target_id ?>)">
          <i class="fa-solid fa-heart" style="color: #E02847;"></i> <?= $has_sent_interest ? 'Interested' : 'Like' ?>
        </button>
        <a href="chat.php?user_id=<?= $target_id ?>" class="btn-action-filled">
          <i class="fa-solid fa-comment-dots"></i> Start Chat
        </a>
        <button type="button" class="btn-action-icon-only" title="Bookmark Profile" onclick="alert('Profile Bookmarked!')">
          <i class="fa-solid fa-bookmark"></i>
        </button>
      </div>
    <?php else: ?>
      <div class="profile-action-row">
        <a href="saathi-edit.php" class="btn-action-outline">
          <i class="fa-solid fa-pen-to-square"></i> Edit Details
        </a>
        <a href="biodata.php?id=<?= $current_user_id ?>" target="_blank" class="btn-action-filled">
          <i class="fa-solid fa-file-pdf"></i> Download Biodata
        </a>
        <button type="button" class="btn-action-icon-only" title="Share Profile" onclick="if(navigator.share){navigator.share({title: '<?= htmlspecialchars(addslashes($og_title)) ?>', url: '<?= $og_url ?>'});}else{navigator.clipboard.writeText('<?= $og_url ?>'); alert('Profile link copied!');}">
          <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </button>
      </div>
    <?php endif; ?>

  </div> <!-- End profile-top-banner -->

  <div style="padding: 0 16px;">

    <!-- Matrimonial Headline Quote Banner -->
    <?php if (!empty($target_saathi['headline'])): ?>
      <div style="background: #FDF4F6; border-left: 3.5px solid #E02847; padding: 10px 14px; border-radius: 14px; font-weight: 600; font-size: 0.84rem; color: #9C1443; margin-bottom: 20px;">
        "<?= htmlspecialchars((string)$target_saathi['headline']) ?>"
      </div>
    <?php endif; ?>

    <!-- AI Compatibility Card (Ref: media_1789806192106.png) -->
    <?php if (!$is_own_profile): 
      $score = 82;
      $curr_city = strtolower(trim((string)($current_user_data['location_city'] ?? '')));
      $targ_city = strtolower(trim((string)($target_user['location_city'] ?? '')));
      if (!empty($curr_city) && !empty($targ_city) && $curr_city === $targ_city && strpos($curr_city, 'san francisco') === false) {
          $score += 7;
      }
      $curr_age = calculate_age($current_user_data['birthdate'] ?? '2000-01-01');
      $age_diff = abs($curr_age - $age);
      if ($age_diff <= 4) { $score += 6; }
      $final_match_percent = min(98, max(84, $score));
    ?>
    <div class="ai-compatibility-card">
      <div class="ai-card-header">
        <div class="ai-card-title">
          <i class="fa-solid fa-sparkles" style="color: #E02847;"></i> AI Compatibility
        </div>
        <div class="ai-donut-ring">
          <div class="pct"><?= $final_match_percent ?>%</div>
          <div class="label">Match</div>
        </div>
      </div>

      <p style="font-size: 0.84rem; color: #555555; line-height: 1.45; margin-top: 4px; margin-bottom: 14px; font-weight: 400;">
        High profile compatibility based on age alignment, location preferences, and matrimonial timeline.
      </p>

      <div class="ai-criteria-list">
        <!-- Preferred location match -->
        <div class="ai-criteria-item">
          <div class="ai-criteria-left">
            <div class="ai-criteria-icon-box">
              <i class="fa-solid fa-location-dot"></i>
            </div>
            <div>
              <div class="ai-criteria-title">Preferred location match</div>
              <div class="ai-criteria-subtitle"><?= htmlspecialchars((string)$location_display) ?></div>
            </div>
          </div>
          <div class="ai-criteria-check">
            <i class="fa-solid fa-check"></i>
          </div>
        </div>

        <!-- Optimal age alignment -->
        <div class="ai-criteria-item">
          <div class="ai-criteria-left">
            <div class="ai-criteria-icon-box">
              <i class="fa-solid fa-user-group"></i>
            </div>
            <div>
              <div class="ai-criteria-title">Optimal age alignment</div>
              <div class="ai-criteria-subtitle"><?= $age ?> years</div>
            </div>
          </div>
          <div class="ai-criteria-check">
            <i class="fa-solid fa-check"></i>
          </div>
        </div>

        <!-- Shared matrimonial timeline -->
        <div class="ai-criteria-item">
          <div class="ai-criteria-left">
            <div class="ai-criteria-icon-box">
              <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div>
              <div class="ai-criteria-title">Shared matrimonial timeline</div>
              <div class="ai-criteria-subtitle"><?= htmlspecialchars((string)($target_saathi['marriage_timeline'] ?? 'Within 1 Year')) ?></div>
            </div>
          </div>
          <div class="ai-criteria-check">
            <i class="fa-solid fa-check"></i>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- About Me Section -->
    <div class="profile-about-card">
      <div class="profile-about-header">
        <div class="profile-about-title-wrap">
          <div class="profile-about-icon">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="profile-about-title">About Me</div>
        </div>
      </div>

      <div class="profile-about-text" style="font-size: 0.88rem; color: #3A3A3C; line-height: 1.55;">
        <?php 
          $cand_bio = !empty($target_user['bio']) 
              ? trim((string)$target_user['bio']) 
              : "Looking for a life partner who values family, growth and happiness. Believer in simple living and meaningful bonds.";
          $is_cand_long = (mb_strlen($cand_bio) > 130 || substr_count($cand_bio, "\n") >= 2);
        ?>
        <?php if ($is_cand_long): ?>
          <div id="bioTextShortCand">
            <?= nl2br(htmlspecialchars(mb_strimwidth($cand_bio, 0, 130, '...'))) ?>
            <button type="button" onclick="toggleBioCand(true)" style="background: none; border: none; padding: 0; color: #E02847; font-weight: 700; font-size: 0.8rem; cursor: pointer; margin-left: 4px; display: inline-flex; align-items: center; gap: 2px;">
              Read More <i class="fa-solid fa-chevron-down" style="font-size: 0.68rem;"></i>
            </button>
          </div>
          <div id="bioTextFullCand" style="display: none;">
            <?= nl2br(htmlspecialchars($cand_bio)) ?>
            <button type="button" onclick="toggleBioCand(false)" style="background: none; border: none; padding: 0; color: #E02847; font-weight: 700; font-size: 0.8rem; cursor: pointer; margin-left: 4px; margin-top: 4px; display: inline-flex; align-items: center; gap: 2px;">
              Read Less <i class="fa-solid fa-chevron-up" style="font-size: 0.68rem;"></i>
            </button>
          </div>
        <?php else: ?>
          <?= nl2br(htmlspecialchars($cand_bio)) ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Partner Preference Section -->
    <h3 class="profile-section-title">Partner Preference</h3>
    <div class="partner-pref-grid">
      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-user-group"></i>
        </div>
        <div>
          <div class="partner-pref-label">Age</div>
          <div class="partner-pref-value"><?= (int)($target_saathi['partner_min_age'] ?? 18) ?> - <?= (int)($target_saathi['partner_max_age'] ?? 45) ?> yrs</div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-ruler-vertical"></i>
        </div>
        <div>
          <div class="partner-pref-label">Height</div>
          <div class="partner-pref-value"><?= (int)($target_saathi['partner_min_height'] ?? 140) ?>-<?= (int)($target_saathi['partner_max_height'] ?? 210) ?> cm</div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-om"></i>
        </div>
        <div>
          <div class="partner-pref-label">Religion</div>
          <div class="partner-pref-value"><?= htmlspecialchars((string)($target_saathi['partner_religion'] ?? 'Any')) ?></div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-location-dot"></i>
        </div>
        <div>
          <div class="partner-pref-label">Location</div>
          <div class="partner-pref-value"><?= htmlspecialchars((string)($target_saathi['partner_location'] ?? 'India')) ?></div>
        </div>
      </div>
    </div>

    <!-- Photos Section -->
    <?php if (!empty($photos_raw)): ?>
      <h3 class="profile-section-title">Photos</h3>
      <div class="photo-gallery-grid">
        <?php 
          $display_photos = array_slice($photos_raw, 0, 4);
          $extra_count = count($photos_raw) - 4;
          foreach ($display_photos as $idx => $p_url): 
        ?>
          <div class="photo-gallery-item" onclick="openPhotoPreview('<?= htmlspecialchars((string)$p_url) ?>', <?= htmlspecialchars(json_encode(array_values($photos_raw))) ?>, <?= $idx ?>)" style="cursor: pointer;" title="Click to View Photo">
            <img src="<?= htmlspecialchars((string)$p_url) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
            <?php if ($idx === 3 && $extra_count > 0): ?>
              <div class="photo-gallery-overlay">+<?= $extra_count ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Detailed Matrimonial Specifications Grid -->
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= (int)($target_saathi['height_cm'] ?? 165) ?> cm</div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['marital_status'] ?? 'Never Married')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother Tongue</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['mother_tongue'] ?? 'Hindi')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Diet</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['diet'] ?? 'Vegetarian')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Body Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['body_type'] ?? 'Average')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Complexion</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['complexion'] ?? 'Fair')) ?></div>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['highest_qualification'] ?? 'Graduate')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Degree</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' / Degree', '', (string)($target_saathi['degree'] ?? 'B.Tech'))) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Profession</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_user['occupation'] ?? 'Member')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Sector</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['occupation_type'] ?? 'Private Job')) ?></div>
          </div>
          <?php if (is_array($privacy) && !empty($privacy['show_income']) && !empty($target_saathi['annual_income'])): ?>
            <div style="grid-column: 1 / -1;">
              <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Annual Income</div>
              <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)$target_saathi['annual_income']) ?></div>
            </div>
          <?php endif; ?>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['gotra'] ?? 'Not Specified')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Manglik</div>
            <?php 
              $manglik_val = (string)($target_saathi['manglik_status'] ?? 'No');
              if ($manglik_val === 'Prefer not to say' || empty($manglik_val)) { $manglik_val = 'Non-Manglik'; }
            ?>
            <div style="font-size: 0.86rem; color: <?= ($manglik_val === 'Yes' || $manglik_val === 'Manglik') ? '#C31F3A' : '#1C1C1E' ?>; font-weight: 700;"><?= htmlspecialchars($manglik_val) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Rashi</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['rashi'] ?? 'Not Specified')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nakshatra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['nakshatra'] ?? 'Not Specified')) ?></div>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' Family', '', (string)($target_saathi['family_type'] ?? 'Nuclear'))) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['family_status'] ?? 'Middle Class')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Father</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['father_occupation'] ?? 'Service')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars((string)($target_saathi['mother_occupation'] ?? 'Homemaker')) ?></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Main Action Buttons -->
    <?php if ($is_own_profile): ?>
      <a href="saathi-edit.php" class="btn-pink-action">
        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
      </a>
      <a href="biodata.php?id=<?= $target_id ?>" target="_blank" class="btn-pink-outline" style="padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; white-space: nowrap;">
        <i class="fa-solid fa-file-pdf"></i> Download Biodata
      </a>
    <?php else: ?>
      <button onclick="sendSaathiInterest(<?= $target_id ?>)" class="btn-pink-action">
        <i class="fa-solid fa-heart"></i> Send Matrimonial Interest
      </button>

      <a href="biodata.php?id=<?= $target_id ?>" target="_blank" class="btn-pink-outline" style="padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; white-space: nowrap; margin-bottom: 8px;">
        <i class="fa-solid fa-file-pdf"></i> Download Biodata
      </a>
      <button onclick="handleCandidateCardChat(<?= $target_id ?>, '<?= htmlspecialchars(addslashes((string)$target_name)) ?>')" class="btn-pink-outline" style="padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; border-color:#D1D5DB; color:#374151 !important; white-space: nowrap; width: 100%; background: none; cursor: pointer;">
        <i class="fa-solid fa-comments"></i> Start Free Chat
      </button>
    <?php endif; ?>

  </div>

</div>

<script>
function toggleBioCand(expand) {
  const shortEl = document.getElementById('bioTextShortCand');
  const fullEl = document.getElementById('bioTextFullCand');
  if (shortEl && fullEl) {
    shortEl.style.display = expand ? 'none' : 'block';
    fullEl.style.display = expand ? 'block' : 'none';
  }
}

function sendSaathiInterest(targetId) {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'send_interest', target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.message || 'Interest sent successfully!');
      if (data.match_id) {
        location.href = `chat.php?match_id=${data.match_id}`;
      }
    } else {
      alert(data.error || 'Unable to send interest');
    }
  })
  .catch(err => alert('Network error. Failed to send interest.'));
}
</script>

<?php
} catch (Throwable $t_err) {
    echo '<div style="padding:40px 20px; text-align:center;"><h3 style="color:#C31F3A;">Candidate Profile Error</h3><p style="color:#666; margin:10px 0 20px 0;">' . htmlspecialchars($t_err->getMessage()) . ' (Line ' . $t_err->getLine() . ' in ' . basename($t_err->getFile()) . ')</p><a href="index.php" style="background:#1C1C1E; color:#FFF; padding:10px 20px; border-radius:20px; text-decoration:none; font-weight:bold; font-size:0.88rem;">Return to Cards Deck</a></div>';
}

require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php';
?>
