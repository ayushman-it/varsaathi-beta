<?php
// faq.php
$active_tab = 'profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="app-header">
  <a href="javascript:history.back()" class="icon-btn"><i class="fa-solid fa-chevron-left"></i></a>
  <h1 class="header-title">Frequently Asked Questions</h1>
  <button class="icon-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="app-body" style="padding: 16px;">
  <div style="background: #FFF; border-radius: var(--radius-ios); padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">

    <details style="margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <summary style="font-weight: 800; font-size: 0.95rem; cursor: pointer; color: var(--ios-text);">How does Varsaathi match me with people?</summary>
      <p style="font-size: 0.84rem; color: var(--ios-muted); margin-top: 8px; line-height: 1.4;">
        Varsaathi uses proximity radar, interest tags, and orientation preferences to calculate compatibility scores and display nearby active profiles.
      </p>
    </details>

    <details style="margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <summary style="font-weight: 800; font-size: 0.95rem; cursor: pointer; color: var(--ios-text);">Is my exact location shared with others?</summary>
      <p style="font-size: 0.84rem; color: var(--ios-muted); margin-top: 8px; line-height: 1.4;">
        No! Your exact GPS coordinates are never displayed. Varsaathi only shows approximate distance radius (e.g. 3 km away).
      </p>
    </details>

    <details style="margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <summary style="font-weight: 800; font-size: 0.95rem; cursor: pointer; color: var(--ios-text);">How can I send photos or videos in chat?</summary>
      <p style="font-size: 0.84rem; color: var(--ios-muted); margin-top: 8px; line-height: 1.4;">
        Inside any active 1-on-1 chat window, tap the camera icon next to the message input box to upload photos or video clips directly.
      </p>
    </details>

    <details style="margin-bottom: 14px;">
      <summary style="font-weight: 800; font-size: 0.95rem; cursor: pointer; color: var(--ios-text);">Can I undo a accidental left swipe?</summary>
      <p style="font-size: 0.84rem; color: var(--ios-muted); margin-top: 8px; line-height: 1.4;">
        Yes! Varsaathi VIP members can use the rewind button on the main cards deck to view the previous profile again.
      </p>
    </details>

  </div>
</div>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
