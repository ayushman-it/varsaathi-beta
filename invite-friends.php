<?php
// invite-friends.php - App Sharing & Referral Center
require_once __DIR__ . '/config/db.php';
$active_tab = 'profile';

$current_user_id = get_current_user_id();
$app_share_url = SITE_URL;
$app_banner_url = SITE_URL . "assets/images/welcome_banner.jpg";
$app_share_title = "VARSAATHI - Match, Chat & Matrimony";
$app_share_text = "✨ Join VARSAATHI Matrimony & Dating App! ✨\n\nFind your verified life partner with 100% Privacy Controls, AI Matchmaking, and Free Voice/Video Calling! 💕\n\n👉 Join now: " . $app_share_url;

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header">
  <a href="javascript:history.back()" class="icon-btn" title="Back"><i class="fa-solid fa-chevron-left"></i></a>
  <div class="header-title" style="font-size: 1.15rem; font-weight: 700;">Share Varsaathi App</div>
  <button class="icon-btn" id="openSidebarBtn" title="Menu"><i class="fa-solid fa-bars"></i></button>
</header>

<main class="app-body" style="padding: 16px; padding-bottom: 90px; text-align: center; background: #F4F5F7;">
  
  <div style="background: #FFF; border-radius: 24px; padding: 20px; box-shadow: 0 4px 18px rgba(0,0,0,0.04); margin-bottom: 16px; border: 1px solid #E2E8F0;">
    
    <!-- App Promo Banner Preview -->
    <div style="position: relative; border-radius: 18px; overflow: hidden; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
      <img src="assets/images/welcome_banner.jpg" alt="Varsaathi Banner" style="width: 100%; height: 160px; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
      <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%); display: flex; align-items: flex-end; padding: 14px;">
        <div style="color: #FFF; text-align: left;">
          <div style="font-weight: 800; font-size: 1.1rem; line-height: 1.2;">VARSAATHI Matrimony</div>
          <div style="font-size: 0.76rem; opacity: 0.9;">Verified Profiles • AI Matching • Free Calling</div>
        </div>
      </div>
    </div>

    <h2 style="font-weight: 800; font-size: 1.2rem; margin-bottom: 6px; color: #1C1C1E;">Invite Friends & Family!</h2>
    <p style="font-size: 0.82rem; color: #64748B; margin-bottom: 18px; line-height: 1.5; padding: 0 6px;">
      Share Varsaathi with single friends and family looking for meaningful matrimonial matches.
    </p>

    <!-- App Share Link Box -->
    <div class="form-group" style="margin-bottom: 18px;">
      <label class="form-label" style="text-align: left; display: block; font-size: 0.74rem; font-weight: 700; color: #8E8E93; margin-bottom: 4px;">APP SHARE LINK</label>
      <div style="display: flex; gap: 8px; background: #F1F5F9; border-radius: 14px; padding: 8px 12px; align-items: center;">
        <input type="text" id="appLinkInput" class="form-control" value="<?= htmlspecialchars($app_share_url) ?>" readonly style="border: none; background: transparent; font-weight: 700; font-size: 0.84rem; padding: 0; color: #1E293B;">
        <button class="icon-btn primary-btn" style="width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;" onclick="copyAppLink()" title="Copy Link">
          <i class="fa-solid fa-copy"></i>
        </button>
      </div>
    </div>

    <!-- Action Share Buttons Row -->
    <div style="display: flex; flex-direction: column; gap: 10px;">
      <button class="btn-block" style="background: var(--ios-gradient); color: #FFF; height: 46px; border-radius: 23px; font-weight: 800; font-size: 0.9rem; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(195,31,58,0.3);" onclick="triggerAppShare()">
        <i class="fa-solid fa-share-nodes" style="margin-right: 6px;"></i> Share via App / Apps Menu
      </button>

      <div style="display: flex; gap: 10px;">
        <a href="https://api.whatsapp.com/send?text=<?= urlencode($app_share_text) ?>" target="_blank" class="btn-block" style="background: #25D366; color: #FFF; flex: 1; height: 44px; border-radius: 22px; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
          <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i> WhatsApp
        </a>
        <a href="https://t.me/share/url?url=<?= urlencode($app_share_url) ?>&text=<?= urlencode($app_share_text) ?>" target="_blank" class="btn-block" style="background: #0088CC; color: #FFF; flex: 1; height: 44px; border-radius: 22px; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
          <i class="fa-brands fa-telegram" style="font-size: 1.1rem;"></i> Telegram
        </a>
      </div>
    </div>

  </div>

</main>

<script>
function copyAppLink() {
  const input = document.getElementById('appLinkInput');
  input.select();
  input.setSelectionRange(0, 99999);
  try {
    navigator.clipboard.writeText(input.value);
    alert('Varsaathi app link copied to clipboard!');
  } catch(e) {
    alert('App link selected. Copy now!');
  }
}

async function triggerAppShare() {
  const shareTitle = "<?= addslashes($app_share_title) ?>";
  const shareText = "<?= str_replace(["\r", "\n"], ['\r', '\n'], addslashes($app_share_text)) ?>";
  const shareUrl = "<?= $app_share_url ?>";
  const bannerUrl = "<?= $app_banner_url ?>";

  if (!navigator.share) {
    copyAppLink();
    return;
  }

  try {
    const response = await fetch(bannerUrl);
    if (response.ok) {
      const blob = await response.blob();
      const file = new File([blob], 'varsaathi_banner.jpg', { type: blob.type || 'image/jpeg' });

      if (navigator.canShare && navigator.canShare({ files: [file] })) {
        await navigator.share({
          files: [file],
          title: shareTitle,
          text: shareText,
          url: shareUrl
        });
        return;
      }
    }
  } catch(e) {
    console.warn("File share fallback triggered:", e);
  }

  try {
    await navigator.share({
      title: shareTitle,
      text: shareText,
      url: shareUrl
    });
  } catch(err) {
    // User cancelled or share failed
  }
}
</script>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
