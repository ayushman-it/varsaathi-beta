<?php
// saathi-edit.php - Shaadi.com-Style Comprehensive Matrimonial Profile Builder
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

$active_tab = 'saathi';
$current_user_id = get_current_user_id();

$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :u");
$u_stmt->execute([':u' => $current_user_id]);
$current_user = $u_stmt->fetch();

$saathi = get_saathi_profile($current_user_id);
$completion_pct = calculate_saathi_completion($saathi, $current_user);

$privacy_decoded = json_decode((string)($saathi['privacy_json'] ?? '{}'), true);
$privacy = is_array($privacy_decoded) ? $privacy_decoded : [
    'show_income' => true,
    'show_religion' => true,
    'show_community' => true,
    'show_kundli' => true,
    'show_family' => true
];

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header">
  <a href="profile.php" class="icon-btn" title="Back to Profile">
    <i class="fa-solid fa-chevron-left"></i>
  </a>

  <div class="header-title" style="display: flex; flex-direction: column; align-items: center; line-height: 1.2;">
    <span style="font-weight: 700; font-size: 1rem;">Edit Saathi Profile</span>
    <span style="font-size: 0.65rem; color: var(--ios-muted);">Shaadi.com Professional Matrimonial Standard</span>
  </div>

  <div class="header-actions">
    <span style="font-size: 0.76rem; font-weight:700; color:#FFF; background:var(--ios-gradient); padding:4px 10px; border-radius:12px;">
      <?= $completion_pct ?>%
    </span>
  </div>
</header>

<!-- Step Navigation Pills Bar (Dedicated Top Bar) -->
<div class="saathi-step-nav-bar" id="stepNavBar">
  <button class="step-pill active" id="tab1" onclick="switchStep(1)"><i class="fa-solid fa-id-card"></i> 1. Basic</button>
  <button class="step-pill" id="tab2" onclick="switchStep(2)"><i class="fa-solid fa-user-pen"></i> 2. About Me</button>
  <button class="step-pill" id="tab3" onclick="switchStep(3)"><i class="fa-solid fa-graduation-cap"></i> 3. Education</button>
  <button class="step-pill" id="tab4" onclick="switchStep(4)"><i class="fa-solid fa-om"></i> 4. Kundli</button>
  <button class="step-pill" id="tab5" onclick="switchStep(5)"><i class="fa-solid fa-house-chimney"></i> 5. Family</button>
  <button class="step-pill" id="tab6" onclick="switchStep(6)"><i class="fa-solid fa-heart"></i> 6. Expectations</button>
  <button class="step-pill" id="tab7" onclick="switchStep(7)"><i class="fa-solid fa-shield-halved"></i> 7. Privacy</button>
</div>

<main class="app-body" style="flex: 1; min-height: 0; overflow-y: auto; padding: 16px; padding-bottom: 90px; background: #F4F5F7;">

  <form id="saathiForm" onsubmit="event.preventDefault();">
    
    <!-- STEP 1: Basic & Physical Details -->
    <div class="step-pane active" id="stepPane1">
      <h3 class="step-title"><i class="fa-solid fa-id-card" style="color: var(--ios-pink);"></i> Basic & Physical Details</h3>

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-cake-candles" style="color: #E91E63; margin-right: 4px;"></i> Date of Birth (DOB)</label>
        <input type="date" id="birthdate" class="form-control" value="<?= htmlspecialchars($current_user['birthdate'] ?? '2000-01-01') ?>" required>
        <small style="font-size: 0.7rem; color: #8E8E93; margin-top: 3px; display: block;">Your age is automatically calculated from your DOB.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Profile Created By</label>
        <select id="created_by" class="form-control">
          <?php 
          $cb_opts = ['Self', 'Parent', 'Sibling', 'Relative', 'Friend'];
          foreach ($cb_opts as $opt): 
          ?>
            <option value="<?= $opt ?>" <?= (($saathi['created_by'] ?? 'Self') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Matrimonial Profile Headline</label>
        <input type="text" id="headline" class="form-control" placeholder="e.g. Caring, ambitious & family-oriented professional" value="<?= htmlspecialchars($saathi['headline'] ?? '') ?>">
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Marital Status</label>
          <select id="marital_status" class="form-control">
            <?php 
            $ms_opts = ['Never Married', 'Divorced', 'Widowed', 'Separated', 'Awaiting Divorce'];
            foreach ($ms_opts as $opt): 
            ?>
              <option value="<?= $opt ?>" <?= (($saathi['marital_status'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Have Children?</label>
          <select id="have_children" class="form-control">
            <option value="No" <?= (($saathi['have_children'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
            <option value="Yes, living with me" <?= (($saathi['have_children'] ?? '') === 'Yes, living with me') ? 'selected' : '' ?>>Yes, living with me</option>
            <option value="Yes, not living with me" <?= (($saathi['have_children'] ?? '') === 'Yes, not living with me') ? 'selected' : '' ?>>Yes, not living with me</option>
          </select>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Height (in CM)</label>
          <input type="number" id="height_cm" class="form-control" placeholder="165" value="<?= (int)($saathi['height_cm'] ?? 165) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Weight (in KG)</label>
          <input type="number" id="weight_kg" class="form-control" placeholder="62" value="<?= (int)($saathi['weight_kg'] ?? '') ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Body Type</label>
          <select id="body_type" class="form-control">
            <?php foreach (['Average', 'Slim', 'Athletic', 'Heavy'] as $bt): ?>
              <option value="<?= $bt ?>" <?= (($saathi['body_type'] ?? '') === $bt) ? 'selected' : '' ?>><?= $bt ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Complexion</label>
          <select id="complexion" class="form-control">
            <?php foreach (['Fair', 'Very Fair', 'Wheatish', 'Dark'] as $cx): ?>
              <option value="<?= $cx ?>" <?= (($saathi['complexion'] ?? '') === $cx) ? 'selected' : '' ?>><?= $cx ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Mother Tongue</label>
        <input type="text" id="mother_tongue" class="form-control" placeholder="e.g. Hindi / Chhattisgarhi" value="<?= htmlspecialchars($saathi['mother_tongue'] ?? 'Hindi') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Languages Spoken</label>
        <input type="text" id="languages_spoken" class="form-control" placeholder="e.g. Hindi, English, Chhattisgarhi" value="<?= htmlspecialchars($saathi['languages_spoken'] ?? 'Hindi, English') ?>">
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">State</label>
          <input type="text" id="state" class="form-control" placeholder="Chhattisgarh" value="<?= htmlspecialchars($saathi['state'] ?? 'Chhattisgarh') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" id="country" class="form-control" placeholder="India" value="<?= htmlspecialchars($saathi['country'] ?? 'India') ?>">
        </div>
      </div>

      <button type="button" onclick="saveStep(1, 2)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 2: About Me -->
    <div class="step-pane" id="stepPane2" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-user-tag" style="color: var(--ios-purple);"></i> About Yourself & Background</h3>

      <div class="form-group">
        <label class="form-label">Detailed Bio / About Me</label>
        <textarea id="bio" class="form-control" rows="5" placeholder="Write a few lines about your personality, family background, career goals, lifestyle, and what kind of life partner you are looking for..."><?= htmlspecialchars($current_user['bio'] ?? '') ?></textarea>
      </div>

      <button type="button" onclick="saveStep(2, 3)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 3: Education & Career -->
    <div class="step-pane" id="stepPane3" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-graduation-cap" style="color: #007AFF;"></i> Education & Profession</h3>

      <div class="form-group">
        <label class="form-label">Highest Education Level</label>
        <select id="highest_qualification" class="form-control">
          <?php 
          $eq_opts = ["Master's Degree", "Bachelor's Degree", "Doctorate / Ph.D", "Diploma / ITI", "High School"];
          foreach ($eq_opts as $opt): 
          ?>
            <option value="<?= $opt ?>" <?= (($saathi['highest_qualification'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Degree Name</label>
          <input type="text" id="degree" class="form-control" placeholder="e.g. B.Tech / MBA / MBBS" value="<?= htmlspecialchars($saathi['degree'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Specialization / Branch</label>
          <input type="text" id="specialization" class="form-control" placeholder="e.g. Computer Science / Finance" value="<?= htmlspecialchars($saathi['specialization'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">College / University Name</label>
        <input type="text" id="college" class="form-control" placeholder="e.g. NIT Raipur / CSVTU" value="<?= htmlspecialchars($saathi['college'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Job Title / Designation</label>
        <input type="text" id="occupation" class="form-control" placeholder="e.g. Senior Software Engineer" value="<?= htmlspecialchars($current_user['occupation'] ?? '') ?>">
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Employment Sector</label>
          <select id="occupation_type" class="form-control">
            <?php foreach (['Private Job', 'Government Job', 'Business / Self-Employed', 'Professional', 'Not Working'] as $sec): ?>
              <option value="<?= $sec ?>" <?= (($saathi['occupation_type'] ?? '') === $sec) ? 'selected' : '' ?>><?= $sec ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Company / Org Name</label>
          <input type="text" id="company_name" class="form-control" placeholder="e.g. TCS / Gov Dept" value="<?= htmlspecialchars($saathi['company_name'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Annual Income Range</label>
        <select id="annual_income" class="form-control">
          <option value="Prefer not to say">Prefer not to say</option>
          <option value="Under ₹3 Lakhs">Under ₹3 Lakhs</option>
          <option value="₹3 - ₹5 Lakhs">₹3 - ₹5 Lakhs</option>
          <option value="₹5 - ₹10 Lakhs">₹5 - ₹10 Lakhs</option>
          <option value="₹10 - ₹15 Lakhs">₹10 - ₹15 Lakhs</option>
          <option value="₹15 - ₹25 Lakhs">₹15 - ₹25 Lakhs</option>
          <option value="₹25+ Lakhs">₹25+ Lakhs</option>
        </select>
      </div>

      <button type="button" onclick="saveStep(3, 4)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 4: Religion, Community & Detailed Kundli -->
    <div class="step-pane" id="stepPane4" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-om" style="color: #FF9500;"></i> Religion, Caste & Kundli / Astrology</h3>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Religion</label>
          <input type="text" id="religion" class="form-control" placeholder="e.g. Hindu / Muslim / Sikh" value="<?= htmlspecialchars($saathi['religion'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Community / Caste</label>
          <input type="text" id="caste_community" class="form-control" placeholder="e.g. Brahmin / Sahu / Soni" value="<?= htmlspecialchars($saathi['caste_community'] ?? '') ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Sub-Caste</label>
          <input type="text" id="sub_caste" class="form-control" placeholder="Sub-caste details" value="<?= htmlspecialchars($saathi['sub_caste'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Gotra</label>
          <input type="text" id="gotra" class="form-control" placeholder="e.g. Kashyap / Bharadwaj" value="<?= htmlspecialchars($saathi['gotra'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Manglik / Chevvai Dosham</label>
        <select id="manglik_status" class="form-control">
          <option value="Prefer not to say">Prefer not to say</option>
          <option value="No" <?= (($saathi['manglik_status'] ?? '') === 'No') ? 'selected' : '' ?>>No (Non-Manglik)</option>
          <option value="Yes" <?= (($saathi['manglik_status'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes (Manglik)</option>
          <option value="Partial Manglik" <?= (($saathi['manglik_status'] ?? '') === 'Partial Manglik') ? 'selected' : '' ?>>Partial / Anshik Manglik</option>
          <option value="Don't Know" <?= (($saathi['manglik_status'] ?? '') === "Don't Know") ? 'selected' : '' ?>>Don't Know</option>
        </select>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Rashi / Moon Sign</label>
          <input type="text" id="rashi" class="form-control" placeholder="e.g. Mesh / Tula / Kanya" value="<?= htmlspecialchars($saathi['rashi'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Nakshatra / Birth Star</label>
          <input type="text" id="nakshatra" class="form-control" placeholder="e.g. Rohini / Ashwini" value="<?= htmlspecialchars($saathi['nakshatra'] ?? '') ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Time of Birth (Kundli)</label>
          <input type="time" id="birth_time" class="form-control" value="<?= htmlspecialchars($saathi['birth_time'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Place of Birth (Kundli)</label>
          <input type="text" id="birth_place" class="form-control" placeholder="City of birth" value="<?= htmlspecialchars($saathi['birth_place'] ?? '') ?>">
        </div>
      </div>

      <button type="button" onclick="saveStep(4, 5)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 5: Family & Lifestyle -->
    <div class="step-pane" id="stepPane5" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-people-roof" style="color: #34C759;"></i> Family Background & Lifestyle</h3>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Family Type</label>
          <select id="family_type" class="form-control">
            <option value="Nuclear Family" <?= (($saathi['family_type'] ?? '') === 'Nuclear Family') ? 'selected' : '' ?>>Nuclear Family</option>
            <option value="Joint Family" <?= (($saathi['family_type'] ?? '') === 'Joint Family') ? 'selected' : '' ?>>Joint Family</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Family Status</label>
          <select id="family_status" class="form-control">
            <option value="Middle Class" <?= (($saathi['family_status'] ?? '') === 'Middle Class') ? 'selected' : '' ?>>Middle Class</option>
            <option value="Upper Middle Class" <?= (($saathi['family_status'] ?? '') === 'Upper Middle Class') ? 'selected' : '' ?>>Upper Middle Class</option>
            <option value="Rich / Affluent" <?= (($saathi['family_status'] ?? '') === 'Rich / Affluent') ? 'selected' : '' ?>>Rich / Affluent</option>
          </select>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Father's Occupation</label>
          <input type="text" id="father_occupation" class="form-control" placeholder="e.g. Retired Govt Officer / Business" value="<?= htmlspecialchars($saathi['father_occupation'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Mother's Occupation</label>
          <input type="text" id="mother_occupation" class="form-control" placeholder="e.g. Homemaker / Teacher" value="<?= htmlspecialchars($saathi['mother_occupation'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Native Place / Family Location</label>
        <input type="text" id="family_location" class="form-control" placeholder="e.g. Raipur / Bilaspur, CG" value="<?= htmlspecialchars($saathi['family_location'] ?? '') ?>">
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Brothers Count (Total / Married)</label>
          <div style="display: flex; gap: 6px;">
            <input type="number" id="brothers_count" class="form-control" placeholder="Total" value="<?= (int)($saathi['brothers_count'] ?? 0) ?>">
            <input type="number" id="brothers_married" class="form-control" placeholder="Married" value="<?= (int)($saathi['brothers_married'] ?? 0) ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Sisters Count (Total / Married)</label>
          <div style="display: flex; gap: 6px;">
            <input type="number" id="sisters_count" class="form-control" placeholder="Total" value="<?= (int)($saathi['sisters_count'] ?? 0) ?>">
            <input type="number" id="sisters_married" class="form-control" placeholder="Married" value="<?= (int)($saathi['sisters_married'] ?? 0) ?>">
          </div>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Own House?</label>
          <select id="own_house" class="form-control">
            <option value="Yes" <?= (($saathi['own_house'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
            <option value="No" <?= (($saathi['own_house'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Own Car?</label>
          <select id="own_car" class="form-control">
            <option value="Yes" <?= (($saathi['own_car'] ?? '') === 'Yes') ? 'selected' : '' ?>>Yes</option>
            <option value="No" <?= (($saathi['own_car'] ?? '') === 'No') ? 'selected' : '' ?>>No</option>
          </select>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Dietary Preference</label>
          <select id="diet" class="form-control">
            <option value="Vegetarian" <?= (($saathi['diet'] ?? '') === 'Vegetarian') ? 'selected' : '' ?>>Vegetarian</option>
            <option value="Non-Vegetarian" <?= (($saathi['diet'] ?? '') === 'Non-Vegetarian') ? 'selected' : '' ?>>Non-Vegetarian</option>
            <option value="Eggetarian" <?= (($saathi['diet'] ?? '') === 'Eggetarian') ? 'selected' : '' ?>>Eggetarian</option>
            <option value="Vegan" <?= (($saathi['diet'] ?? '') === 'Vegan') ? 'selected' : '' ?>>Vegan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Smoking</label>
          <select id="smoking" class="form-control">
            <option value="No">No</option>
            <option value="Yes">Yes</option>
            <option value="Occasionally">Occasionally</option>
          </select>
        </div>
      </div>

      <button type="button" onclick="saveStep(5, 6)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 6: Partner Preferences -->
    <div class="step-pane" id="stepPane6" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-heart-circle-bolt" style="color: var(--ios-pink);"></i> Desired Partner Expectations</h3>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Min Preferred Age</label>
          <input type="number" id="partner_min_age" class="form-control" value="<?= (int)($saathi['partner_min_age'] ?? 18) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Max Preferred Age</label>
          <input type="number" id="partner_max_age" class="form-control" value="<?= (int)($saathi['partner_max_age'] ?? 45) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Marriage Timeline</label>
        <select id="marriage_timeline" class="form-control">
          <option value="Within 6 Months">Within 6 Months</option>
          <option value="Within 1 Year" selected>Within 1 Year</option>
          <option value="2+ Years">2+ Years</option>
          <option value="Immediately">Immediately</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Preferred Religion / Caste</label>
        <input type="text" id="partner_community" class="form-control" placeholder="e.g. Any / Same Community" value="<?= htmlspecialchars($saathi['partner_community'] ?? 'Any') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Detailed Expectations & Criteria</label>
        <textarea id="partner_expectations" class="form-control" rows="4" placeholder="Describe your expectations regarding education, career, family background, values, and location..."><?= htmlspecialchars($saathi['partner_expectations'] ?? '') ?></textarea>
      </div>

      <button type="button" onclick="saveStep(6, 7)" class="btn-block btn-primary" style="margin-top: 16px; height: 44px; border-radius: 22px; font-weight: 600;">
        Save & Next <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
      </button>
    </div>

    <!-- STEP 7: Privacy & Controls -->
    <div class="step-pane" id="stepPane7" style="display: none;">
      <h3 class="step-title"><i class="fa-solid fa-shield-halved" style="color: var(--ios-pink);"></i> Privacy & Profile Controls</h3>

      <div style="background: #FFF; border-radius: 16px; padding: 14px; border: 1px solid #E5E5EA; margin-bottom: 16px;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F0F0F0;">
          <div>
            <div style="font-weight: 600; font-size: 0.88rem; color: var(--ios-text);">Show Religion & Caste</div>
            <div style="font-size: 0.72rem; color: var(--ios-muted);">Make religion and caste visible on profile</div>
          </div>
          <label class="ios-switch">
            <input type="checkbox" id="show_religion" <?= !empty($privacy['show_religion']) ? 'checked' : '' ?>>
            <span class="ios-slider"></span>
          </label>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F0F0F0;">
          <div>
            <div style="font-weight: 600; font-size: 0.88rem; color: var(--ios-text);">Show Income Range</div>
            <div style="font-size: 0.72rem; color: var(--ios-muted);">Make income range visible on profile</div>
          </div>
          <label class="ios-switch">
            <input type="checkbox" id="show_income" <?= !empty($privacy['show_income']) ? 'checked' : '' ?>>
            <span class="ios-slider"></span>
          </label>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0;">
          <div>
            <div style="font-weight: 600; font-size: 0.88rem; color: var(--ios-text);">Pause Saathi Profile</div>
            <div style="font-size: 0.72rem; color: var(--ios-muted);">Temporarily hide Saathi profile without deleting normal dating profile</div>
          </div>
          <label class="ios-switch">
            <input type="checkbox" id="is_paused" <?= !empty($saathi['is_paused']) ? 'checked' : '' ?> onchange="togglePause(this.checked)">
            <span class="ios-slider"></span>
          </label>
        </div>

      </div>

      <button type="button" onclick="savePrivacyAndFinish()" class="btn-block btn-primary" style="height: 46px; border-radius: 23px; font-weight: 700; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i> Publish & Save Saathi Profile
      </button>
    </div>

  </form>

</main>

<style>
.saathi-step-nav-bar {
  background: #FFFFFF;
  padding: 10px 14px;
  border-bottom: 1px solid #E2E8F0;
  display: flex;
  gap: 8px;
  overflow-x: auto;
  white-space: nowrap;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  flex-shrink: 0;
  z-index: 10;
}
.saathi-step-nav-bar::-webkit-scrollbar {
  display: none;
}
.step-pill {
  flex: 0 0 auto;
  height: 36px;
  padding: 0 16px;
  border-radius: 18px;
  background: #F1F5F9;
  border: 1px solid #E2E8F0;
  color: #475569;
  font-size: 0.78rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.step-pill.active {
  background: var(--ios-gradient);
  color: #FFFFFF;
  border-color: transparent;
  box-shadow: 0 4px 14px rgba(195, 31, 58, 0.3);
  transform: translateY(-1px);
}
.step-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ios-text);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.form-group {
  margin-bottom: 14px;
}
.form-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}
.form-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--ios-text);
  margin-bottom: 5px;
}
.form-control {
  width: 100%;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #D5D8DC;
  font-size: 0.85rem;
  background: #FFF;
}
</style>

<script>
function switchStep(stepNum) {
  for (let i = 1; i <= 7; i++) {
    const pane = document.getElementById(`stepPane${i}`);
    const tab = document.getElementById(`tab${i}`);
    if (pane) pane.style.display = (i === stepNum) ? 'block' : 'none';
    if (tab) {
      tab.classList.toggle('active', i === stepNum);
      if (i === stepNum) {
        tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
    }
  }
}

function saveStep(stepNum, nextStep) {
  let payload = { action: 'save_profile_step', step: stepNum };

  if (stepNum === 1) {
    payload.birthdate = document.getElementById('birthdate')?.value || '';
    payload.created_by = document.getElementById('created_by').value;
    payload.headline = document.getElementById('headline').value;
    payload.marital_status = document.getElementById('marital_status').value;
    payload.have_children = document.getElementById('have_children').value;
    payload.height_cm = document.getElementById('height_cm').value;
    payload.weight_kg = document.getElementById('weight_kg').value;
    payload.body_type = document.getElementById('body_type').value;
    payload.complexion = document.getElementById('complexion').value;
    payload.mother_tongue = document.getElementById('mother_tongue').value;
    payload.languages_spoken = document.getElementById('languages_spoken').value;
    payload.state = document.getElementById('state').value;
    payload.country = document.getElementById('country').value;
  } else if (stepNum === 2) {
    payload.bio = document.getElementById('bio').value;
  } else if (stepNum === 3) {
    payload.highest_qualification = document.getElementById('highest_qualification').value;
    payload.degree = document.getElementById('degree').value;
    payload.specialization = document.getElementById('specialization').value;
    payload.college = document.getElementById('college').value;
    payload.occupation = document.getElementById('occupation').value;
    payload.occupation_type = document.getElementById('occupation_type').value;
    payload.company_name = document.getElementById('company_name').value;
    payload.annual_income = document.getElementById('annual_income').value;
  } else if (stepNum === 4) {
    payload.religion = document.getElementById('religion').value;
    payload.caste_community = document.getElementById('caste_community').value;
    payload.sub_caste = document.getElementById('sub_caste').value;
    payload.gotra = document.getElementById('gotra').value;
    payload.manglik_status = document.getElementById('manglik_status').value;
    payload.rashi = document.getElementById('rashi').value;
    payload.nakshatra = document.getElementById('nakshatra').value;
    payload.birth_time = document.getElementById('birth_time').value;
    payload.birth_place = document.getElementById('birth_place').value;
  } else if (stepNum === 5) {
    payload.family_type = document.getElementById('family_type').value;
    payload.family_status = document.getElementById('family_status').value;
    payload.family_location = document.getElementById('family_location').value;
    payload.father_occupation = document.getElementById('father_occupation').value;
    payload.mother_occupation = document.getElementById('mother_occupation').value;
    payload.brothers_count = document.getElementById('brothers_count').value;
    payload.brothers_married = document.getElementById('brothers_married').value;
    payload.sisters_count = document.getElementById('sisters_count').value;
    payload.sisters_married = document.getElementById('sisters_married').value;
    payload.own_house = document.getElementById('own_house').value;
    payload.own_car = document.getElementById('own_car').value;
    payload.diet = document.getElementById('diet').value;
    payload.smoking = document.getElementById('smoking').value;
  } else if (stepNum === 6) {
    payload.partner_min_age = document.getElementById('partner_min_age').value;
    payload.partner_max_age = document.getElementById('partner_max_age').value;
    payload.partner_community = document.getElementById('partner_community').value;
    payload.marriage_timeline = document.getElementById('marriage_timeline').value;
    payload.partner_expectations = document.getElementById('partner_expectations').value;
  }

  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (nextStep <= 7) {
        switchStep(nextStep);
      }
    } else {
      alert(data.error || 'Failed to save step');
    }
  });
}

function savePrivacyAndFinish() {
  const privacyPayload = {
    action: 'update_privacy',
    show_religion: document.getElementById('show_religion').checked,
    show_income: document.getElementById('show_income').checked
  };

  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(privacyPayload)
  })
  .then(res => res.json())
  .then(data => {
    alert('Your Saathi Matrimonial Profile is updated!');
    location.href = 'profile.php';
  });
}

function togglePause(isPaused) {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'toggle_pause', is_paused: isPaused })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
  });
}
</script>

<?php
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php';
?>
