<?php
// biodata.php - Traditional Professional Matrimonial Biodata Viewer & PDF Generator
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/flags.php';
require_login();

$target_id = (int)($_GET['id'] ?? 0);
$current_user_id = get_current_user_id();

if ($target_id <= 0) {
    $target_id = $current_user_id;
}

$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$u_stmt->execute([':id' => $target_id]);
$target_user = $u_stmt->fetch();

if (!$target_user) {
    header("Location: saathi.php");
    exit;
}

$target_saathi = get_saathi_profile($target_id);
$age = calculate_age($target_user['birthdate'] ?? '2000-01-01');
$placeholder_img = 'assets/images/no_image_placeholder.png';
$avatar = !empty($target_user['avatar_url']) ? $target_user['avatar_url'] : $placeholder_img;

$clean_filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $target_user['full_name']) . '_Matrimonial_Biodata.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($target_user['full_name']) ?> - Matrimonial Biodata</title>
  <!-- Google Fonts for Traditional Executive Biodata -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- html2pdf.js CDN for Client-Side High-Res PDF Export -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #F3F4F6;
      color: #1F2937;
      padding: 20px 10px;
    }
    .action-top-bar {
      max-width: 760px;
      margin: 0 auto 20px auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }
    .btn-action {
      background: #E91E63;
      color: #FFF;
      border: none;
      padding: 10px 20px;
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.9rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      box-shadow: 0 4px 12px rgba(233,30,99,0.3);
    }
    .btn-action.btn-outline {
      background: #FFF;
      color: #1F2937;
      border: 1.5px solid #D1D5DB;
      box-shadow: none;
    }
    
    /* PDF Printable Outer Buffer Container */
    .biodata-wrapper {
      background: #FFFFFF;
      max-width: 760px;
      margin: 0 auto;
      padding: 16px;
      box-sizing: border-radius: 18px;
    }

    /* Traditional Printable Biodata Card */
    .biodata-card {
      background: #FFFFFF;
      width: 100%;
      max-width: 728px;
      margin: 0 auto;
      border-radius: 14px;
      border: 2px solid #E91E63;
      padding: 26px 28px;
      box-shadow: 0 4px 20px rgba(233,30,99,0.06);
      position: relative;
      box-sizing: border-box;
    }
    
    /* Decorative Traditional Header Frame */
    .biodata-header {
      text-align: center;
      border-bottom: 2px dashed #E91E63;
      padding-bottom: 18px;
      margin-bottom: 20px;
    }
    .biodata-sacred {
      font-family: 'Cinzel', serif;
      font-weight: 700;
      font-size: 0.95rem;
      color: #E91E63;
      letter-spacing: 2px;
      margin-bottom: 4px;
    }
    .biodata-main-title {
      font-family: 'Cinzel', serif;
      font-size: 1.7rem;
      font-weight: 800;
      color: #880E4F;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .biodata-sub-tag {
      font-size: 0.74rem;
      font-weight: 700;
      color: #6B7280;
      margin-top: 4px;
    }

    /* Candidate Top Hero Section */
    .hero-flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      background: #FDF4F6;
      border-radius: 14px;
      padding: 18px 20px;
      margin-bottom: 20px;
      border: 1px solid #FCE4EC;
    }
    .hero-info h2 {
      font-size: 1.5rem;
      font-weight: 800;
      color: #880E4F;
    }
    .hero-info p {
      font-size: 0.9rem;
      color: #4B5563;
      margin-top: 4px;
      font-weight: 600;
    }
    .hero-avatar-frame {
      width: 110px;
      height: 110px;
      border-radius: 12px;
      overflow: hidden;
      border: 3px solid #E91E63;
      box-shadow: 0 4px 12px rgba(233,30,99,0.2);
      flex-shrink: 0;
    }
    .hero-avatar-frame img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Biodata Section Card */
    .biodata-section {
      margin-bottom: 18px;
    }
    .section-header-title {
      font-family: 'Cinzel', serif;
      font-size: 0.95rem;
      font-weight: 700;
      color: #880E4F;
      border-bottom: 1.5px solid #E91E63;
      padding-bottom: 4px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Table Grid Layout */
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
    }
    .info-item {
      display: flex;
      font-size: 0.84rem;
      line-height: 1.4;
    }
    .info-label {
      width: 44%;
      color: #6B7280;
      font-weight: 600;
      flex-shrink: 0;
    }
    .info-val {
      width: 56%;
      color: #111827;
      font-weight: 700;
      word-break: break-word;
    }

    /* Footer */
    .biodata-footer {
      text-align: center;
      margin-top: 24px;
      padding-top: 12px;
      border-top: 1px solid #E5E7EB;
      font-size: 0.72rem;
      color: #9CA3AF;
      font-weight: 600;
    }

    @media print {
      body {
        background: #FFF !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .action-top-bar {
        display: none !important;
      }
      .biodata-wrapper {
        padding: 0 !important;
        max-width: 100% !important;
      }
      .biodata-card {
        box-shadow: none !important;
        border: 2px solid #E91E63 !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 20px 24px !important;
        box-sizing: border-box !important;
      }
    }
  </style>
</head>
<body>

  <!-- Top Action Bar (Download & Print Buttons) -->
  <div class="action-top-bar">
    <a href="saathi-profile.php?id=<?= $target_id ?>" class="btn-action btn-outline">
      <i class="fa-solid fa-arrow-left"></i> Back to Profile
    </a>

    <div style="display: flex; gap: 10px;">
      <button onclick="printBiodata()" class="btn-action btn-outline">
        <i class="fa-solid fa-print"></i> Print / Save PDF
      </button>

      <button id="downloadPdfBtn" onclick="generatePdf()" class="btn-action">
        <i class="fa-solid fa-file-pdf"></i> Download Biodata PDF
      </button>
    </div>
  </div>

  <!-- Outer Buffer Wrapper Container (Prevents PDF Edge Clipping) -->
  <div class="biodata-wrapper" id="biodataWrapper">
    <!-- Printable Matrimonial Biodata Document -->
    <div class="biodata-card" id="biodataDocument">
      
      <!-- Sacred Header -->
      <div class="biodata-header">
        <div class="biodata-sacred">|| SHREE GANESHAY NAMAH ||</div>
        <div class="biodata-main-title">MATRIMONIAL BIODATA</div>
        <div class="biodata-sub-tag">Verified VARSAATHI Candidate Profile (ID: VS-<?= 1000 + $target_id ?>)</div>
      </div>

      <!-- Candidate Hero Section -->
      <div class="hero-flex">
        <div class="hero-info">
          <h2><?= htmlspecialchars($target_user['full_name']) ?></h2>
          <p><?= $age ?> Yrs • <?= (int)($target_saathi['height_cm'] ?? 165) ?> cm • <?= htmlspecialchars($target_saathi['marital_status'] ?? 'Never Married') ?></p>
          <p style="font-size:0.86rem; color:#880E4F; font-weight:700; margin-top:6px;">
            <i class="fa-solid fa-briefcase"></i> <?= htmlspecialchars($target_user['occupation'] ?: 'Member') ?>
          </p>
          <p style="font-size:0.84rem; color:#6B7280; margin-top:2px;">
            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($target_user['location_city'] ?: 'India') ?>, <?= htmlspecialchars($target_saathi['state'] ?? 'CG') ?>
          </p>
        </div>

        <div class="hero-avatar-frame">
          <img src="<?= htmlspecialchars($avatar) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        </div>
      </div>

      <!-- 1. Personal & Physical Details -->
      <div class="biodata-section">
        <div class="section-header-title">
          <i class="fa-solid fa-user"></i> Personal & Physical Details
        </div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Full Name:</span><span class="info-val"><?= htmlspecialchars($target_user['full_name']) ?></span></div>
          <div class="info-item"><span class="info-label">Date of Birth:</span><span class="info-val"><?= date('d M Y', strtotime($target_user['birthdate'] ?? '2000-01-01')) ?> (Age: <?= $age ?>)</span></div>
          <div class="info-item"><span class="info-label">Gender:</span><span class="info-val"><?= ucfirst(htmlspecialchars($target_user['gender'])) ?></span></div>
          <div class="info-item"><span class="info-label">Marital Status:</span><span class="info-val"><?= htmlspecialchars($target_saathi['marital_status'] ?? 'Never Married') ?></span></div>
          <div class="info-item"><span class="info-label">Height:</span><span class="info-val"><?= (int)($target_saathi['height_cm'] ?? 165) ?> cm</span></div>
          <div class="info-item"><span class="info-label">Weight:</span><span class="info-val"><?= !empty($target_saathi['weight_kg']) ? (int)$target_saathi['weight_kg'] . ' kg' : 'Not Specified' ?></span></div>
          <div class="info-item"><span class="info-label">Complexion:</span><span class="info-val"><?= htmlspecialchars($target_saathi['complexion'] ?? 'Fair') ?></span></div>
          <div class="info-item"><span class="info-label">Body Type:</span><span class="info-val"><?= htmlspecialchars($target_saathi['body_type'] ?? 'Average') ?></span></div>
          <div class="info-item"><span class="info-label">Mother Tongue:</span><span class="info-val"><?= htmlspecialchars($target_saathi['mother_tongue'] ?? 'Hindi') ?></span></div>
          <div class="info-item"><span class="info-label">Languages Spoken:</span><span class="info-val"><?= htmlspecialchars($target_saathi['languages_spoken'] ?? 'Hindi, English') ?></span></div>
        </div>
      </div>

      <!-- 2. Astrology & Kundli Details -->
      <div class="biodata-section">
        <div class="section-header-title">
          <i class="fa-solid fa-om"></i> Religion, Caste & Kundli Details
        </div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Religion:</span><span class="info-val"><?= htmlspecialchars($target_saathi['religion'] ?? 'Hindu') ?></span></div>
          <div class="info-item"><span class="info-label">Caste / Community:</span><span class="info-val"><?= htmlspecialchars($target_saathi['caste_community'] ?? 'Not Specified') ?></span></div>
          <div class="info-item"><span class="info-label">Sub-Caste:</span><span class="info-val"><?= htmlspecialchars($target_saathi['sub_caste'] ?? 'Not Specified') ?></span></div>
          <div class="info-item"><span class="info-label">Gotra:</span><span class="info-val"><?= htmlspecialchars($target_saathi['gotra'] ?? 'Not Specified') ?></span></div>
          <div class="info-item"><span class="info-label">Manglik Status:</span><span class="info-val" style="color:#C31F3A;"><?= htmlspecialchars($target_saathi['manglik_status'] ?? 'No') ?></span></div>
          <div class="info-item"><span class="info-label">Rashi (Moon Sign):</span><span class="info-val"><?= htmlspecialchars($target_saathi['rashi'] ?? 'Not Specified') ?></span></div>
          <div class="info-item"><span class="info-label">Nakshatra:</span><span class="info-val"><?= htmlspecialchars($target_saathi['nakshatra'] ?? 'Not Specified') ?></span></div>
          <div class="info-item"><span class="info-label">Time & Place of Birth:</span><span class="info-val"><?= htmlspecialchars($target_saathi['birth_time'] ?: 'N/A') ?>, <?= htmlspecialchars($target_saathi['birth_place'] ?: $target_user['location_city']) ?></span></div>
        </div>
      </div>

      <!-- 3. Education & Career Details -->
      <div class="biodata-section">
        <div class="section-header-title">
          <i class="fa-solid fa-graduation-cap"></i> Education & Career Details
        </div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Highest Education:</span><span class="info-val"><?= htmlspecialchars($target_saathi['highest_qualification'] ?? 'Graduate') ?></span></div>
          <div class="info-item"><span class="info-label">Degree / Field:</span><span class="info-val"><?= htmlspecialchars($target_saathi['degree'] ?: 'B.Tech / Degree') ?></span></div>
          <div class="info-item"><span class="info-label">College / Institute:</span><span class="info-val"><?= htmlspecialchars($target_saathi['college'] ?: 'University') ?></span></div>
          <div class="info-item"><span class="info-label">Occupation / Job:</span><span class="info-val"><?= htmlspecialchars($target_user['occupation'] ?: 'Professional Member') ?></span></div>
          <div class="info-item"><span class="info-label">Employment Sector:</span><span class="info-val"><?= htmlspecialchars($target_saathi['occupation_type'] ?? 'Private Job') ?></span></div>
          <div class="info-item"><span class="info-label">Annual Income:</span><span class="info-val"><?= htmlspecialchars($target_saathi['annual_income'] ?? 'Prefer not to say') ?></span></div>
        </div>
      </div>

      <!-- 4. Family Background Details -->
      <div class="biodata-section">
        <div class="section-header-title">
          <i class="fa-solid fa-people-roof"></i> Family Background
        </div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Father's Occupation:</span><span class="info-val"><?= htmlspecialchars($target_saathi['father_occupation'] ?: 'Service / Business') ?></span></div>
          <div class="info-item"><span class="info-label">Mother's Occupation:</span><span class="info-val"><?= htmlspecialchars($target_saathi['mother_occupation'] ?: 'Homemaker') ?></span></div>
          <div class="info-item"><span class="info-label">Family Type:</span><span class="info-val"><?= htmlspecialchars($target_saathi['family_type'] ?? 'Nuclear') ?></span></div>
          <div class="info-item"><span class="info-label">Family Status:</span><span class="info-val"><?= htmlspecialchars($target_saathi['family_status'] ?? 'Middle Class') ?></span></div>
          <div class="info-item"><span class="info-label">Native Location:</span><span class="info-val"><?= htmlspecialchars($target_saathi['family_location'] ?: $target_user['location_city']) ?></span></div>
          <div class="info-item"><span class="info-label">Siblings:</span><span class="info-val"><?= (int)($target_saathi['brothers_count'] ?? 0) ?> Brother(s), <?= (int)($target_saathi['sisters_count'] ?? 0) ?> Sister(s)</span></div>
        </div>
      </div>

      <!-- 5. Lifestyle & Expectations -->
      <div class="biodata-section">
        <div class="section-header-title">
          <i class="fa-solid fa-heart"></i> Lifestyle & Partner Preferences
        </div>
        <div class="info-grid">
          <div class="info-item"><span class="info-label">Dietary Habits:</span><span class="info-val"><?= htmlspecialchars($target_saathi['diet'] ?? 'Vegetarian') ?></span></div>
          <div class="info-item"><span class="info-label">Smoking / Drinking:</span><span class="info-val"><?= htmlspecialchars($target_saathi['smoking'] ?? 'No') ?> / <?= htmlspecialchars($target_saathi['drinking'] ?? 'No') ?></span></div>
          <div class="info-item"><span class="info-label">Partner Age Range:</span><span class="info-val"><?= (int)($target_saathi['partner_min_age'] ?? 18) ?> - <?= (int)($target_saathi['partner_max_age'] ?? 45) ?> Years</span></div>
          <div class="info-item"><span class="info-label">Marriage Timeline:</span><span class="info-val"><?= htmlspecialchars($target_saathi['marriage_timeline'] ?? 'Within 1 Year') ?></span></div>
        </div>
      </div>

      <!-- Footer Timestamp -->
      <div class="biodata-footer">
        Generated via VARSAATHI Matrimonial Platform • Confidential Biodata Document • Date: <?= date('d M Y') ?>
      </div>

    </div>
  </div>

  <script>
    function printBiodata() {
      window.print();
    }

    function generatePdf() {
      const element = document.getElementById('biodataWrapper');
      const btn = document.getElementById('downloadPdfBtn');

      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating PDF...';

      const opt = {
        margin:       [6, 6, 6, 6],
        filename:     '<?= $clean_filename ?>',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false, scrollX: 0, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      html2pdf().set(opt).from(element).save().then(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Download Biodata PDF';
      }).catch(err => {
        console.error('PDF Generation Error:', err);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Download Biodata PDF';
        alert('Downloading via Print PDF fallback...');
        window.print();
      });
    }
  </script>
</body>
</html>
