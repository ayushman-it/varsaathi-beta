<?php
// feedback.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Send Feedback</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
    <h3 style="font-weight: 800; font-size: 1rem; margin-bottom: 6px;">We’d Love Your Input!</h3>
    <p style="font-size: 0.82rem; color: var(--ios-muted); margin-bottom: 16px;">Tell us what you love or how we can make Varsaathi better for you.</p>

    <form onsubmit="event.preventDefault(); alert('Thank you for your feedback! Our team has received your message.'); window.location.href='index.php';">
      <div class="form-group">
        <label class="form-label">How would you rate Varsaathi?</label>
        <div style="display: flex; gap: 12px; font-size: 1.6rem; color: #FFCC00; justify-content: center; margin: 8px 0 16px 0;">
          <i class="fa-solid fa-star" style="cursor: pointer;"></i>
          <i class="fa-solid fa-star" style="cursor: pointer;"></i>
          <i class="fa-solid fa-star" style="cursor: pointer;"></i>
          <i class="fa-solid fa-star" style="cursor: pointer;"></i>
          <i class="fa-solid fa-star" style="cursor: pointer;"></i>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Feedback Category</label>
        <select class="form-control">
          <option>UI & Design Suggestion</option>
          <option>App Speed & Performance</option>
          <option>New Feature Request</option>
          <option>Bug Report</option>
          <option>General Comment</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Your Comments</label>
        <textarea class="form-control" style="height: 100px; padding: 12px;" placeholder="Write your thoughts here..." required></textarea>
      </div>

      <button type="submit" class="btn-block btn-primary">Submit Feedback</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
