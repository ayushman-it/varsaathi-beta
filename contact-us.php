<?php
// contact-us.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Contact Us</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 16px; text-align: center;">
    <i class="fa-solid fa-headset" style="font-size: 2.5rem; color: var(--ios-pink); margin-bottom: 10px;"></i>
    <h3 style="font-weight: 800; font-size: 1.1rem; margin-bottom: 4px;">Varsaathi Support Desk</h3>
    <p style="font-size: 0.82rem; color: var(--ios-muted);">We are here to assist you 24/7 with any account or safety questions.</p>
  </div>

  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <form onsubmit="event.preventDefault(); alert('Your ticket has been logged! Support reference ID #RM-84920.'); window.location.href='index.php';">
      <div class="form-group">
        <label class="form-label">Subject</label>
        <input type="text" class="form-control" placeholder="How can we help you?" required>
      </div>

      <div class="form-group">
        <label class="form-label">Message Details</label>
        <textarea class="form-control" style="height: 100px; padding: 12px;" placeholder="Describe your issue or query in detail..." required></textarea>
      </div>

      <button type="submit" class="btn-block btn-primary">Send Message</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
