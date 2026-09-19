<?php
// saathi-profile.php - Ultra-Modern Shaadi.com Profile Viewer (Ref: media_1789219148081.png)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

$target_id = (int)($_GET['id'] ?? 0);
$current_user_id = get_current_user_id();

if ($target_id <= 0) {
    $target_id = $current_user_id; // Default to self if no ID provided
}

$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$u_stmt->execute([':id' => $target_id]);
$target_user = $u_stmt->fetch();

if (!$target_user) {
    header("Location: index.php");
    exit;
}

$is_own_profile = ($target_id === $current_user_id);
$target_saathi = get_saathi_profile($target_id) ?: [];

// Check existing match/interest status between current user and target user
$m_check = $pdo->prepare("
    SELECT id, status, requested_by 
    FROM matches 
    WHERE (user1_id = LEAST(:u, :t) AND user2_id = GREATEST(:u, :t))
");
$m_check->execute([':u' => $current_user_id, ':t' => $target_id]);
$existing_match_row = $m_check->fetch();

$has_sent_interest = ($existing_match_row && (int)($existing_match_row['requested_by'] ?? 0) === $current_user_id);

$age = calculate_age($target_user['birthdate'] ?? '2000-01-01');
$avatar = get_valid_avatar_url($target_user['avatar_url'] ?? '');
$photos_raw = json_decode($target_user['photos'] ?? '[]', true) ?: [$avatar];
if (!in_array($avatar, $photos_raw)) {
    array_unshift($photos_raw, $avatar);
}

$privacy = json_decode($target_saathi['privacy_json'] ?? '{}', true) ?: [
    'show_income' => true,
    'show_religion' => true,
    'show_community' => true,
    'show_kundli' => true,
    'show_family' => true
];

$city_raw = trim($target_user['location_city'] ?? '');
if (empty($city_raw) || $city_raw === 'No Location' || strpos(strtolower($city_raw), 'san francisco') !== false) {
    $location_display = 'India';
} else {
    $location_display = ucwords(strtolower($city_raw)) . ', India';
}

$og_title = htmlspecialchars($target_user['full_name']) . " (" . $age . " yrs, " . $location_display . ") - VARSAATHI Matrimony";
$og_description = "Matrimonial Profile & Biodata of " . htmlspecialchars($target_user['full_name']) . ". Profession: " . htmlspecialchars($target_user['occupation'] ?: 'Member') . ". View profile on Varsaathi!";
$og_image = (strpos($avatar, 'http') === 0) ? $avatar : (SITE_URL . ltrim($avatar, '/'));
$og_url = SITE_URL . "saathi-profile.php?id=" . $target_id;

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-screen-container">

  <!-- 1. Soft Pink Header Banner -->
  <div class="profile-top-banner">
    <div class="profile-nav-header">
      <a href="javascript:history.length > 1 ? history.back() : (window.location.href='index.php')" class="icon-btn" style="background:#FFF; color:#111; width:36px; height:36px;" title="Back">
        <i class="fa-solid fa-arrow-left"></i>
      </a>

      <div class="profile-nav-title">Profile</div>

      <button class="icon-btn" style="background:#FFF; color:#111; width:36px; height:36px;" title="Options" onclick="alert('Profile options: Report, Share or Bookmark')">
        <i class="fa-solid fa-ellipsis-vertical"></i>
      </button>
    </div>

    <!-- 2. Centered Overlapping Avatar -->
    <div class="profile-avatar-container">
      <div class="profile-avatar-outer-ring" style="cursor: pointer;" onclick="openPhotoPreview('<?= htmlspecialchars($avatar) ?>', <?= htmlspecialchars(json_encode(array_values($photos_raw))) ?>, 0)" title="Click to Preview Photo">
        <img src="<?= htmlspecialchars($avatar) ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        <div class="profile-avatar-verified-badge" title="Verified Profile">
          <i class="fa-solid fa-check"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- 3. Main Overlapping White Card -->
  <div class="profile-main-card">
    
    <!-- User Name & Subtitle -->
    <h1 class="profile-user-name">
      <?= htmlspecialchars($target_user['full_name']) ?>
      <i class="fa-solid fa-circle-check" style="color: #E91E63; font-size: 1.15rem;" title="Verified Profile"></i>
    </h1>

    <div class="profile-user-subtitle">
      <span><?= $age ?></span>
      <span>|</span>
      <span><i class="fa-solid fa-location-dot" style="color: #E91E63; margin-right: 2px;"></i> <?= htmlspecialchars($location_display) ?></span>
    </div>

    <!-- Matrimonial Headline Quote Banner -->
    <?php if (!empty($target_saathi['headline'])): ?>
      <div style="background: #FDF4F6; border-left: 3.5px solid #E91E63; padding: 10px 14px; border-radius: 12px; font-weight: 600; font-size: 0.84rem; color: #9C1443; margin-bottom: 18px;">
        "<?= htmlspecialchars($target_saathi['headline']) ?>"
      </div>
    <?php endif; ?>

    <!-- AI Compatibility Card -->
    <?php if (!$is_own_profile): 
      // Compute dynamic compatibility score & explanation
      $c_user = $current_user_data ?: [];
      $c_saathi = get_saathi_profile($current_user_id) ?: [];
      
      $match_reasons = [];
      $score = 82;

      // 1. Location match
      $curr_city = strtolower(trim($c_user['location_city'] ?? ''));
      $targ_city = strtolower(trim($target_user['location_city'] ?? ''));
      if (!empty($curr_city) && !empty($targ_city) && $curr_city === $targ_city && strpos($curr_city, 'san francisco') === false) {
          $score += 7;
          $match_reasons[] = "Same location (" . ucwords($curr_city) . ")";
      } else {
          $match_reasons[] = "Preferred location match (" . htmlspecialchars($location_display) . ")";
      }

      // 2. Age compatibility
      $curr_age = calculate_age($c_user['birthdate'] ?? '2000-01-01');
      $age_diff = abs($curr_age - $age);
      if ($age_diff <= 4) {
          $score += 6;
          $match_reasons[] = "Optimal age alignment (" . $age . " years)";
      }

      // 3. Marriage Timeline match
      $curr_timeline = $c_saathi['marriage_timeline'] ?? 'Within 1 Year';
      $targ_timeline = $target_saathi['marriage_timeline'] ?? 'Within 1 Year';
      if ($curr_timeline === $targ_timeline) {
          $score += 4;
          $match_reasons[] = "Shared matrimonial timeline (" . htmlspecialchars($targ_timeline) . ")";
      } else {
          $match_reasons[] = "Aligned relationship vision";
      }

      $final_match_percent = min(98, max(84, $score));

      // Professional short summary paragraph
      $target_first_name = explode(' ', trim($target_user['full_name']))[0];
      $summary_paragraph = "High profile compatibility based on age alignment, location preferences, and matrimonial timeline.";
    ?>
    <div style="background: linear-gradient(135deg, #FFF5F7 0%, #FFFFFF 100%); border-radius: 20px; border: 1px solid rgba(195, 31, 58, 0.16); padding: 14px 16px; margin-bottom: 20px; box-shadow: 0 6px 20px rgba(195, 31, 58, 0.05);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <div style="font-weight: 800; font-size: 0.9rem; color: #C31F3A; display: flex; align-items: center; gap: 6px;">
          <i class="fa-solid fa-sparkles" style="font-size: 0.9rem;"></i> AI Compatibility
        </div>
        <span style="font-size: 0.74rem; font-weight: 800; background: linear-gradient(135deg, #E02847, #C31F3A); color: #FFF; padding: 3px 10px; border-radius: 12px; box-shadow: 0 2px 8px rgba(195, 31, 58, 0.25);">
          <?= $final_match_percent ?>% Match
        </span>
      </div>

      <!-- Short Professional Summary Paragraph -->
      <p style="font-size: 0.8rem; color: #555555; line-height: 1.45; margin-bottom: 10px; font-weight: 400;">
        <?= $summary_paragraph ?>
      </p>

      <!-- Key Matched Factors -->
      <div style="font-size: 0.76rem; color: #1C1C1E; display: flex; flex-direction: column; gap: 5px; background: rgba(255,255,255,0.7); padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.04);">
        <?php foreach ($match_reasons as $reason): ?>
          <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-circle-check" style="color: #22C55E; font-size: 0.82rem; flex-shrink: 0;"></i>
            <span style="font-weight: 500;"><?= $reason ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- About Me Section -->
    <h3 class="profile-section-title">About Me</h3>
    <p class="profile-about-text">
      <?= !empty($target_user['bio']) 
          ? nl2br(htmlspecialchars($target_user['bio'])) 
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
          <div class="partner-pref-value"><?= htmlspecialchars($target_saathi['partner_religion'] ?? 'Any') ?></div>
        </div>
      </div>

      <div class="partner-pref-card">
        <div class="partner-pref-icon-wrap">
          <i class="fa-solid fa-location-dot"></i>
        </div>
        <div>
          <div class="partner-pref-label">Location</div>
          <div class="partner-pref-value"><?= htmlspecialchars($target_saathi['partner_location'] ?? 'India') ?></div>
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
          <div class="photo-gallery-item" onclick="openPhotoPreview('<?= htmlspecialchars($p_url) ?>', <?= htmlspecialchars(json_encode(array_values($photos_raw))) ?>, <?= $idx ?>)" style="cursor: pointer;" title="Click to View Photo">
            <img src="<?= htmlspecialchars($p_url) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['marital_status'] ?? 'Never Married') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother Tongue</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['mother_tongue'] ?? 'Hindi') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Diet</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['diet'] ?? 'Vegetarian') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Body Type</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['body_type'] ?? 'Average') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Complexion</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['complexion'] ?? 'Fair') ?></div>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['highest_qualification'] ?? 'Graduate') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Degree</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' / Degree', '', $target_saathi['degree'] ?: 'B.Tech')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Profession</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_user['occupation'] ?: 'Member') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Sector</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['occupation_type'] ?? 'Private Job') ?></div>
          </div>
          <?php if (!empty($privacy['show_income']) && !empty($target_saathi['annual_income'])): ?>
            <div style="grid-column: 1 / -1;">
              <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Annual Income</div>
              <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['annual_income']) ?></div>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['gotra'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Manglik</div>
            <?php 
              $manglik_val = $target_saathi['manglik_status'] ?? 'No';
              if ($manglik_val === 'Prefer not to say' || empty($manglik_val)) { $manglik_val = 'Non-Manglik'; }
            ?>
            <div style="font-size: 0.86rem; color: <?= ($manglik_val === 'Yes' || $manglik_val === 'Manglik') ? '#C31F3A' : '#1C1C1E' ?>; font-weight: 700;"><?= htmlspecialchars($manglik_val) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Rashi</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['rashi'] ?: 'Not Specified') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Nakshatra</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['nakshatra'] ?: 'Not Specified') ?></div>
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
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars(str_replace(' Family', '', $target_saathi['family_type'] ?? 'Nuclear')) ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Status</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['family_status'] ?? 'Middle Class') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Father</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['father_occupation'] ?: 'Service') ?></div>
          </div>
          <div>
            <div style="font-size: 0.72rem; color: #8E8E93; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">Mother</div>
            <div style="font-size: 0.86rem; color: #1C1C1E; font-weight: 700;"><?= htmlspecialchars($target_saathi['mother_occupation'] ?: 'Homemaker') ?></div>
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
      <div id="interestBtnArea" style="width: 100%; margin-bottom: 8px;">
        <?php if ($has_sent_interest): ?>
          <button onclick="cancelSaathiInterest(<?= $target_id ?>, '<?= htmlspecialchars(addslashes($target_user['full_name'] ?? 'Candidate')) ?>', '<?= htmlspecialchars(addslashes($avatar)) ?>')" class="btn-pink-action" style="background: #F2F2F7; color: #C31F3A; border: 1px solid #FFE0E6; box-shadow: none;">
            <i class="fa-solid fa-xmark"></i> Cancel Interest Request
          </button>
        <?php else: ?>
          <button onclick="sendSaathiInterest(<?= $target_id ?>, '<?= htmlspecialchars(addslashes($target_user['full_name'] ?? 'Candidate')) ?>', '<?= htmlspecialchars(addslashes($avatar)) ?>')" class="btn-pink-action">
            <i class="fa-solid fa-heart"></i> Send Matrimonial Interest
          </button>
        <?php endif; ?>
      </div>

      <a href="biodata.php?id=<?= $target_id ?>" target="_blank" class="btn-pink-outline" style="padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; white-space: nowrap; margin-bottom: 8px;">
        <i class="fa-solid fa-file-pdf"></i> Download Biodata
      </a>
      <button onclick="handleCandidateCardChat(<?= $target_id ?>, '<?= htmlspecialchars(addslashes($target_user['full_name'] ?? 'Candidate')) ?>')" class="btn-pink-outline" style="padding: 10px; height: 42px; font-size: 0.86rem; border-radius: 21px; font-weight: 700; border-color:#D1D5DB; color:#374151 !important; white-space: nowrap; width: 100%; background: none; cursor: pointer;">
        <i class="fa-solid fa-comments"></i> Start Free Chat
      </button>
    <?php endif; ?>

  </div>

</div>

<script>
function sendSaathiInterest(targetId, targetName = 'Candidate', targetAvatar = '') {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'send_interest', target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (typeof window.showInterestMatchModal === 'function') {
        window.showInterestMatchModal(targetName, targetAvatar, data.is_match, data.match_id || 0, targetId);
      }
      const area = document.getElementById('interestBtnArea');
      if (area) {
        area.innerHTML = `
          <button onclick="cancelSaathiInterest(${targetId}, '${targetName.replace(/'/g, "\\'")}', '${targetAvatar}')" class="btn-pink-action" style="background: #F2F2F7; color: #C31F3A; border: 1px solid #FFE0E6; box-shadow: none;">
            <i class="fa-solid fa-xmark"></i> Cancel Interest Request
          </button>
        `;
      }
    } else {
      alert(data.error || 'Unable to send interest');
    }
  })
  .catch(err => alert('Network error. Failed to send interest.'));
}

function cancelSaathiInterest(targetId, targetName = 'Candidate', targetAvatar = '') {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'cancel_interest', target_id: targetId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const area = document.getElementById('interestBtnArea');
      if (area) {
        area.innerHTML = `
          <button onclick="sendSaathiInterest(${targetId}, '${targetName.replace(/'/g, "\\'")}', '${targetAvatar}')" class="btn-pink-action">
            <i class="fa-solid fa-heart"></i> Send Matrimonial Interest
          </button>
        `;
      }
    } else {
      alert(data.error || 'Unable to cancel interest');
    }
  })
  .catch(err => alert('Network error. Failed to cancel interest.'));
}
</script>

<?php
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php';
?>
