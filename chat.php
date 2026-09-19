<?php
// chat.php
require_once __DIR__ . '/config/db.php';
require_login();

$current_user_id = get_current_user_id();
$match_id = (int)($_GET['match_id'] ?? 0);
$target_user_id = (int)($_GET['target_id'] ?? ($_GET['user_id'] ?? ($_GET['partner_id'] ?? ($_GET['id'] ?? 0))));

// If match_id is not provided but target_user_id is provided, find or create match
if (!$match_id && $target_user_id && $target_user_id !== $current_user_id) {
    $find_stmt = $pdo->prepare("
        SELECT id FROM matches 
        WHERE (user1_id = :u1 AND user2_id = :u2) OR (user1_id = :u2_2 AND user2_id = :u1_2)
        LIMIT 1
    ");
    $find_stmt->execute([
        ':u1' => $current_user_id, ':u2' => $target_user_id,
        ':u1_2' => $current_user_id, ':u2_2' => $target_user_id
    ]);
    $existing_match = $find_stmt->fetch();
    if ($existing_match) {
        $match_id = (int)$existing_match['id'];
    } else {
        $ins = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, status, requested_by, matched_at) VALUES (:u1, :u2, 'pending', :req, NOW())");
        $ins->execute([
            ':u1' => $current_user_id,
            ':u2' => $target_user_id,
            ':req' => $current_user_id
        ]);
        $match_id = (int)$pdo->lastInsertId();
    }
}

if (!$match_id) {
    header("Location: matches.php");
    exit;
}

// Fetch Match & Recipient Info
$stmt = $pdo->prepare("
    SELECT m.id AS match_id, m.status AS match_status, m.requested_by,
           u.id AS partner_id, u.full_name, u.avatar_url, u.location_city, u.last_seen
    FROM matches m
    JOIN users u ON (u.id = IF(m.user1_id = :u1, m.user2_id, m.user1_id))
    WHERE m.id = :match_id AND (m.user1_id = :u2 OR m.user2_id = :u3)
");
$stmt->execute([
    ':u1' => $current_user_id,
    ':u2' => $current_user_id,
    ':u3' => $current_user_id,
    ':match_id' => $match_id
]);
$partner = $stmt->fetch();

if (!$partner) {
    header("Location: matches.php");
    exit;
}

$is_pending = ($partner['match_status'] === 'pending');
$is_receiver = ($is_pending && (int)$partner['requested_by'] !== $current_user_id);
$is_sender = ($is_pending && (int)$partner['requested_by'] === $current_user_id);

$partner_status = get_user_online_status($partner['last_seen'] ?? null);

$css_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#FFFFFF">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Chat with <?= htmlspecialchars($partner['full_name']) ?> - VARSAATHI</title>
  <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- PeerJS WebRTC Library -->
  <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_version ?>">
</head>
<body>
  <div class="app-container">

    <div class="chat-container" id="chatContainer">
      <!-- Varsaathi Chat Screen Top Header -->
      <header class="app-header">
        <a href="matches.php" class="icon-btn" style="width:34px; height:34px;"><i class="fa-solid fa-chevron-left" style="font-size:0.85rem;"></i></a>

        <a href="saathi-profile.php?id=<?= $partner['partner_id'] ?>" style="display:flex; align-items:center; gap:10px; flex:1; margin-left:6px; text-decoration:none;">
          <div class="chat-avatar-wrapper">
            <img src="<?= htmlspecialchars($partner['avatar_url']) ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';">
            <?php if (!$is_pending): ?>
              <div class="online-dot" style="width:10px; height:10px; background: <?= $partner_status['color'] ?>;" title="<?= htmlspecialchars($partner_status['label']) ?>"></div>
            <?php endif; ?>
          </div>
          <div>
            <h4 style="font-weight:800; font-size:0.9rem; line-height:1.1; color:var(--ios-text);"><?= htmlspecialchars($partner['full_name']) ?></h4>
            <span style="font-size:0.68rem; color:<?= $is_pending ? '#8E8E93' : $partner_status['color'] ?>; font-weight:700;"><?= $is_pending ? 'Pending Match Request' : htmlspecialchars($partner_status['label']) ?></span>
          </div>
        </a>

        <?php if (!$is_pending): ?>
          <div class="header-actions" style="gap:5px;">
            <!-- iOS Theme Selector Toggle Button -->
            <button class="icon-btn" onclick="toggleThemeMenu()" style="width:34px; height:34px; color:var(--ios-purple);" title="Change Chat Theme">
              <i class="fa-solid fa-palette" style="font-size:0.85rem;"></i>
            </button>

            <!-- Call Buttons -->
            <button class="icon-btn" id="audioCallBtn" style="width:34px; height:34px;" title="Start Voice Call">
              <i class="fa-solid fa-phone" style="font-size:0.82rem; color:var(--ios-pink);"></i>
            </button>
            <button class="icon-btn" id="videoCallBtn" style="width:34px; height:34px;" title="Start Video Call">
              <i class="fa-solid fa-video" style="font-size:0.82rem; color:var(--ios-purple);"></i>
            </button>
          </div>
        <?php endif; ?>
      </header>

      <!-- Theme Dropdown Drawer -->
      <div id="themeDropdown" class="theme-dropdown-menu" style="display:none; position:absolute; top:48px; right:14px; background:#FFF; border-radius:18px; padding:10px; box-shadow:0 10px 30px rgba(0,0,0,0.15); z-index:9999; width:180px; border:1px solid #E5E5EA;">
        <div style="font-size:0.72rem; font-weight:800; color:#8E8E93; text-transform:uppercase; margin-bottom:6px; padding:0 6px;">iOS Chat Themes</div>
        <button onclick="setChatTheme('theme-ios-crimson')" class="theme-menu-opt active" style="width:100%; text-align:left; border:none; background:none; padding:8px 10px; border-radius:10px; font-weight:700; font-size:0.82rem; cursor:pointer; color:#C31F3A; display:flex; align-items:center; gap:8px;">
          <span style="width:12px; height:12px; border-radius:50%; background:#C31F3A;"></span> Crimson iOS
        </button>
        <button onclick="setChatTheme('theme-ios-blue')" class="theme-menu-opt" style="width:100%; text-align:left; border:none; background:none; padding:8px 10px; border-radius:10px; font-weight:700; font-size:0.82rem; cursor:pointer; color:#007AFF; display:flex; align-items:center; gap:8px;">
          <span style="width:12px; height:12px; border-radius:50%; background:#007AFF;"></span> iMessage Blue
        </button>
        <button onclick="setChatTheme('theme-ios-dark')" class="theme-menu-opt" style="width:100%; text-align:left; border:none; background:none; padding:8px 10px; border-radius:10px; font-weight:700; font-size:0.82rem; cursor:pointer; color:#1C1C1E; display:flex; align-items:center; gap:8px;">
          <span style="width:12px; height:12px; border-radius:50%; background:#1C1C1E;"></span> Dark Velvet
        </button>
      </div>

      <!-- Message History Container -->
      <div class="chat-messages" id="chatMessages">
        <!-- Live loaded messages via chat.js -->
      </div>

      <!-- Slide to Reply Quoted Context Bar -->
      <div id="replyPreviewBar" style="display:none; background:#F2F2F7; border-left:4px solid var(--ios-pink); padding:8px 12px; margin:0 12px 6px 12px; border-radius:12px; position:relative; font-size:0.82rem;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="font-weight:800; color:var(--ios-pink);" id="replySenderName">Replying to User</span>
          <button type="button" onclick="cancelReplyMode()" style="background:none; border:none; color:#8E8E93; font-size:0.9rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="replyTextSnippet" style="color:#3A3A3C; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;">Quoted message...</div>
        <input type="hidden" id="replyQuoteInput" value="">
      </div>

      <!-- Varsaathi Chat Input Footer Bar / Pending Action Banner -->
      <?php if ($is_pending): ?>
        <?php if ($is_receiver): ?>
          <div style="background:#FFF0F5; border-top:1.5px solid #FFE4EE; padding:16px 20px; text-align:center; box-shadow:0 -4px 12px rgba(0,0,0,0.03);">
            <p style="font-weight:800; font-size:0.9rem; color:var(--ios-text); margin-bottom:12px;">
              💕 <?= htmlspecialchars($partner['full_name']) ?> sent you a match request!
            </p>
            <div style="display:flex; gap:12px; justify-content:center;">
              <button onclick="respondInChat(<?= $partner['match_id'] ?>, 'accept')" class="btn-primary" style="padding:10px 24px; border-radius:22px; font-weight:800; font-size:0.9rem; border:none; color:#FFF; cursor:pointer; background:var(--ios-pink); box-shadow:0 4px 12px rgba(195,31,58,0.25);">
                <i class="fa-solid fa-check" style="margin-right:4px;"></i> Accept Match
              </button>
              <button onclick="respondInChat(<?= $partner['match_id'] ?>, 'decline')" style="padding:10px 18px; border-radius:22px; font-weight:700; font-size:0.9rem; border:none; background:#E5E5EA; color:#3A3A3C; cursor:pointer;">
                Decline
              </button>
            </div>
          </div>
        <?php else: ?>
          <div style="background:#F9F9FB; border-top:1px solid #E5E5EA; padding:16px 20px; text-align:center; color:#71717A; font-weight:600; font-size:0.86rem;">
            ⏳ Match request pending. Messaging and calls will unlock as soon as <?= htmlspecialchars($partner['full_name']) ?> accepts your request.
          </div>
        <?php endif; ?>
      <?php else: ?>
        <form id="chatForm" 
              data-match-id="<?= $partner['match_id'] ?>" 
              data-user-id="<?= $current_user_id ?>"
              data-partner-id="<?= $partner['partner_id'] ?>"
              data-partner-name="<?= htmlspecialchars($partner['full_name']) ?>"
              data-partner-avatar="<?= htmlspecialchars($partner['avatar_url']) ?>"
              class="chat-input-bar">
          <input type="file" id="mediaUploadInput" accept="image/*,video/*" style="display:none;">

          <button type="button" class="icon-btn" onclick="document.getElementById('mediaUploadInput').click()" style="width:36px; height:36px; border:none; background:#F2F2F7; color:var(--ios-muted);" title="Share Photo/Video">
            <i class="fa-solid fa-paperclip"></i>
          </button>

          <button type="button" class="icon-btn" onclick="document.getElementById('mediaUploadInput').click()" style="width:36px; height:36px; border:none; background:#F2F2F7; color:var(--ios-muted);" title="Share Media">
            <i class="fa-solid fa-camera"></i>
          </button>

          <input type="text" id="chatInput" class="chat-input-field" placeholder="Write a message..." autocomplete="off">

          <button type="submit" class="icon-btn primary-btn" style="width:38px; height:38px;">
            <i class="fa-solid fa-paper-plane" style="font-size:0.9rem;"></i>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script src="assets/js/chat.js?v=<?= $css_version ?>"></script>
  <script src="assets/js/calls.js?v=<?= $css_version ?>"></script>
  <script src="assets/js/privacy-protection.js?v=<?= $css_version ?>"></script>
  <script>
  function toggleThemeMenu() {
    const menu = document.getElementById('themeDropdown');
    menu.style.display = (menu.style.display === 'none') ? 'block' : 'none';
  }

  function setChatTheme(themeClass) {
    const container = document.getElementById('chatContainer');
    container.classList.remove('theme-ios-crimson', 'theme-ios-blue', 'theme-ios-dark');
    container.classList.add(themeClass);
    localStorage.setItem('varsaathi_chat_theme', themeClass);
    document.getElementById('themeDropdown').style.display = 'none';
  }

  // Restore active theme
  const savedTheme = localStorage.getItem('varsaathi_chat_theme') || 'theme-ios-crimson';
  document.getElementById('chatContainer').classList.add(savedTheme);

  function respondInChat(matchId, action) {
    fetch('api/match_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, action: action })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        if (action === 'accept') {
          window.location.reload();
        } else {
          window.location.href = 'matches.php';
        }
      } else {
        alert(data.error || 'Could not process match request.');
      }
    })
    .catch(err => alert('Network error. Please try again.'));
  }
  </script>
</body>
</html>
