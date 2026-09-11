<?php
// data-privacy.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Data & Privacy Policy</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); font-size: 0.85rem; line-height: 1.5; color: var(--ios-text);">
    <h3 style="font-weight: 800; font-size: 1.1rem; margin-bottom: 10px;">Varsaathi Privacy Commitments</h3>
    <p style="color: var(--ios-muted); margin-bottom: 14px;">
      At Varsaathi, we prioritize your privacy and data sovereignty. We collect minimal profile data needed to facilitate authentic dating matches.
    </p>

    <h4 style="font-weight: 700; margin-bottom: 6px;">1. Information We Collect</h4>
    <p style="color: var(--ios-muted); margin-bottom: 12px;">
      Basic account info (Name, Email, Birthdate, Gender, Orientation, Profile photos, and encrypted chat messages).
    </p>

    <h4 style="font-weight: 700; margin-bottom: 6px;">2. How Data is Used</h4>
    <p style="color: var(--ios-muted); margin-bottom: 12px;">
      Data is strictly utilized for match calculations, message routing, and anti-spam verification. We never sell your personal information.
    </p>

    <h4 style="font-weight: 700; margin-bottom: 6px;">3. Data Export & Rights</h4>
    <p style="color: var(--ios-muted); margin-bottom: 16px;">
      You have the right to request a full export of your Varsaathi profile and chat archive at any time under GDPR & CCPA regulations.
    </p>

    <button onclick="alert('Data export archive requested! A download link will be emailed to your registered address.');" class="btn-block btn-light" style="font-size: 0.88rem;">
      <i class="fa-solid fa-download"></i> Request Data Archive
    </button>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
