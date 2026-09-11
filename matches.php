<?php
// matches.php
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'matches';
$current_user_id = get_current_user_id();

// Fetch all matches for current user (pending & accepted)
$matches_stmt = $pdo->prepare("
    SELECT m.id AS match_id, m.status AS match_status, m.requested_by, m.matched_at,
           u.id AS user_id, u.full_name, u.avatar_url, u.location_city, u.occupation, u.last_seen,
           (SELECT message_text FROM messages WHERE match_id = m.id ORDER BY id DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM messages WHERE match_id = m.id ORDER BY id DESC LIMIT 1) AS last_message_time,
           (SELECT COUNT(*) FROM messages WHERE match_id = m.id AND receiver_id = :u1 AND is_read = 0) AS unread_count
    FROM matches m
    JOIN users u ON (u.id = IF(m.user1_id = :u2, m.user2_id, m.user1_id))
    WHERE (m.user1_id = :u3 OR m.user2_id = :u4) AND (m.status != 'rejected' OR m.status IS NULL)
    ORDER BY COALESCE(last_message_time, m.matched_at) DESC
");
$matches_stmt->execute([
    ':u1' => $current_user_id,
    ':u2' => $current_user_id,
    ':u3' => $current_user_id,
    ':u4' => $current_user_id
]);
$all_matches = $matches_stmt->fetchAll();

// Separate pending requests from accepted matches
$pending_requests = array_filter($all_matches, function($m) use ($current_user_id) {
    return $m['match_status'] === 'pending' && (int)$m['requested_by'] !== $current_user_id;
});

$sent_pending = array_filter($all_matches, function($m) use ($current_user_id) {
    return $m['match_status'] === 'pending' && (int)$m['requested_by'] === $current_user_id;
});

$accepted_matches = array_filter($all_matches, function($m) {
    return $m['match_status'] === 'accepted' || empty($m['match_status']);
});

$css_version = filemtime(__DIR__ . '/assets/css/style.css');
require_once __DIR__ . '/includes/header.php';
?>

<!-- iOS Header with Sidebar Trigger -->
<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title">
    <i class="fa-solid fa-comments"></i>
    Messages & Matches
  </div>

  <div class="header-actions">
    <a href="discover.php" class="icon-btn" title="Discover Matches">
      <i class="fa-solid fa-compass"></i>
    </a>
  </div>
</header>

<main class="app-body" style="padding-bottom: 20px;">
  <?php if (empty($all_matches)): ?>
    <!-- Empty Matches State -->
    <div style="text-align: center; padding: 60px 20px;" class="no-matches-box">
      <div style="width: 80px; height: 80px; border-radius: 50%; background: #FDF0F6; color: var(--ios-pink); display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 16px; box-shadow: var(--ios-glow);">
        <i class="fa-solid fa-heart-crack"></i>
      </div>
      <h3 style="font-weight: 800; font-size: 1.3rem; margin-bottom: 6px; color: var(--ios-text);">No Matches Yet</h3>
      <p style="color: var(--ios-muted); font-size: 0.86rem; line-height: 1.4; max-width: 280px; margin: 0 auto 20px auto;">
        Swipe right on cards deck or radar scanner to discover matches and connect!
      </p>
      <a href="index.php" class="btn-block btn-primary" style="max-width: 240px; margin: 0 auto; height: 48px; border-radius: 24px; font-size: 0.95rem;">
        <i class="fa-solid fa-layer-group" style="margin-right: 6px;"></i> Explore Cards Feed
      </a>
    </div>

  <?php else: ?>

    <!-- Pending Match Requests Section (Action Required) -->
    <?php if (!empty($pending_requests)): ?>
      <div class="section-label" style="display: flex; justify-content: space-between; align-items: center; color: var(--ios-pink);">
        <span><i class="fa-solid fa-bell" style="margin-right: 6px;"></i> Match Requests Received</span>
        <span class="sidebar-badge" style="background: #FFF0F5; color: var(--ios-pink); font-size: 0.72rem; font-weight: 800; padding: 2px 10px; border-radius: 12px;"><?= count($pending_requests) ?> New</span>
      </div>

      <div class="chat-list" style="margin-bottom: 24px;">
        <?php foreach ($pending_requests as $req): 
          $placeholder_img = 'assets/images/no_image_placeholder.png';
          $req_avatar = !empty($req['avatar_url']) ? $req['avatar_url'] : $placeholder_img;
        ?>
          <div class="chat-row" 
               data-match-id="<?= $req['match_id'] ?>" 
               data-partner-id="<?= $req['user_id'] ?>" 
               data-full-name="<?= htmlspecialchars($req['full_name'], ENT_QUOTES) ?>" 
               data-avatar-url="<?= htmlspecialchars($req_avatar, ENT_QUOTES) ?>"
               style="background: #FFF9FC; border: 1.5px solid #FFE4EE; border-radius: 16px; margin-bottom: 10px; padding: 12px 14px;">
            <a href="profile-view.php?id=<?= $req['user_id'] ?>" class="chat-avatar-wrapper" style="text-decoration: none;">
              <img src="<?= htmlspecialchars($req_avatar) ?>" class="chat-avatar" alt="<?= htmlspecialchars($req['full_name']) ?>" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
            </a>

            <div class="chat-meta" style="flex: 1;">
              <span class="chat-name" style="font-weight: 800; color: var(--ios-text); font-size: 0.95rem;"><?= htmlspecialchars($req['full_name']) ?></span>
              <span style="font-size: 0.76rem; color: var(--ios-pink); font-weight: 700;">Sent you a match request!</span>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
              <button type="button" onclick="respondMatchRequest(<?= $req['match_id'] ?>, 'accept', event)" style="background: var(--ios-pink); color: #FFF; border: none; padding: 8px 14px; border-radius: 20px; font-weight: 800; font-size: 0.82rem; cursor: pointer; box-shadow: 0 4px 10px rgba(195,31,58,0.25);">
                <i class="fa-solid fa-check" style="margin-right: 4px;"></i> Accept
              </button>
              <button type="button" onclick="respondMatchRequest(<?= $req['match_id'] ?>, 'decline', event)" style="background: #E5E5EA; color: #3A3A3C; border: none; padding: 8px 12px; border-radius: 20px; font-weight: 700; font-size: 0.82rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>


    <!-- New Accepted Matches Story Bar -->
    <div class="section-label" style="display: flex; justify-content: space-between; align-items: center;">
      <span>Matches</span>
      <span class="sidebar-badge" style="background: rgba(255,45,85,0.1); color: var(--ios-pink); font-size: 0.72rem; font-weight: 800; padding: 2px 10px; border-radius: 12px;"><?= count($accepted_matches) ?> Active</span>
    </div>

    <div class="matches-stories">
      <?php foreach ($accepted_matches as $nm): 
        $placeholder_img = 'assets/images/no_image_placeholder.png';
        $nm_avatar = !empty($nm['avatar_url']) ? $nm['avatar_url'] : $placeholder_img;
      ?>
        <a href="chat.php?match_id=<?= $nm['match_id'] ?>" 
           class="story-item" 
           data-match-id="<?= $nm['match_id'] ?>" 
           data-partner-id="<?= $nm['user_id'] ?>" 
           data-full-name="<?= htmlspecialchars($nm['full_name'], ENT_QUOTES) ?>" 
           data-avatar-url="<?= htmlspecialchars($nm_avatar, ENT_QUOTES) ?>"
           title="Message <?= htmlspecialchars($nm['full_name']) ?>">
          <div class="story-ring">
            <img src="<?= htmlspecialchars($nm_avatar) ?>" class="story-img" alt="<?= htmlspecialchars($nm['full_name']) ?>" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
          </div>
          <span class="story-name"><?= htmlspecialchars(explode(' ', $nm['full_name'])[0]) ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Active Conversations List -->
    <div class="section-label">Active Conversations</div>
    <div class="chat-list">
      <?php foreach ($all_matches as $chat): 
        $placeholder_img = 'assets/images/no_image_placeholder.png';
        $chat_avatar = !empty($chat['avatar_url']) ? $chat['avatar_url'] : $placeholder_img;
        $has_msg = !empty($chat['last_message']);
        $raw_last_msg = $chat['last_message'] ?? '';
        $is_pending = ($chat['match_status'] === 'pending');
        $is_sender = ($is_pending && (int)$chat['requested_by'] === $current_user_id);
        $is_receiver = ($is_pending && (int)$chat['requested_by'] !== $current_user_id);

        if ($is_receiver) {
            $last_msg = '👉 Match Request Received! Tap Accept to start chatting.';
        } elseif ($is_sender) {
            $last_msg = '⏳ Waiting for match request to be accepted...';
        } elseif (strpos($raw_last_msg, 'call-log-card') !== false) {
            if (strpos($raw_last_msg, 'missed') !== false) {
                $last_msg = '📞 Missed call';
            } elseif (strpos($raw_last_msg, 'Video') !== false || strpos($raw_last_msg, 'fa-video') !== false) {
                $last_msg = '📹 Video call';
            } else {
                $last_msg = '📞 Voice call';
            }
        } else {
            $last_msg = $has_msg ? strip_tags($raw_last_msg) : 'Match Created! Tap to send a message 👋';
        }
        
        $time_display = 'Just now';
        if ($chat['last_message_time']) {
            $diff = time() - strtotime($chat['last_message_time']);
            if ($diff < 60) {
                $time_display = 'Just now';
            } elseif ($diff < 3600) {
                $time_display = max(1, floor($diff / 60)) . 'm';
            } elseif ($diff < 86400) {
                $time_display = floor($diff / 3600) . 'h';
            } else {
                $time_display = date('M d', strtotime($chat['last_message_time']));
            }
        }
      ?>
        <a href="chat.php?match_id=<?= $chat['match_id'] ?>" 
           class="chat-row" 
           data-match-id="<?= $chat['match_id'] ?>" 
           data-partner-id="<?= $chat['user_id'] ?>" 
           data-full-name="<?= htmlspecialchars($chat['full_name'], ENT_QUOTES) ?>" 
           data-avatar-url="<?= htmlspecialchars($chat_avatar, ENT_QUOTES) ?>"
           style="<?= $is_pending ? 'opacity:0.9; background:#FAFAFC;' : '' ?>">
          <div class="chat-avatar-wrapper">
            <img src="<?= htmlspecialchars($chat_avatar) ?>" class="chat-avatar" alt="<?= htmlspecialchars($chat['full_name']) ?>" onerror="this.onerror=null; this.src='<?= $placeholder_img ?>';">
            <?php if (!$is_pending): ?>
              <div class="online-dot"></div>
            <?php endif; ?>
          </div>

          <div class="chat-meta">
            <span class="chat-name"><?= htmlspecialchars($chat['full_name']) ?></span>
            <span class="chat-last-msg <?= ($is_pending || !$has_msg || $chat['unread_count'] > 0) ? 'unread' : '' ?>" style="<?= ($is_pending || !$has_msg) ? 'color: var(--ios-pink); font-weight: 700;' : '' ?>">
              <?= htmlspecialchars($last_msg) ?>
            </span>
          </div>

          <div class="chat-right-col">
            <span class="chat-time"><?= $time_display ?></span>
            <div style="display: flex; align-items: center; gap: 4px;">
              <?php if ($chat['unread_count'] > 0): ?>
                <span class="badge" style="position:relative; top:0; right:0; padding:2px 6px; font-size:0.68rem;"><?= $chat['unread_count'] ?></span>
              <?php endif; ?>

              <?php if ($is_receiver): ?>
                <span class="btn-msg-pill" style="background:#FFF0F5; color:var(--ios-pink);">
                  Respond
                </span>
              <?php elseif ($is_sender): ?>
                <span class="btn-msg-pill" style="background:#F2F2F7; color:#8E8E93;">
                  Pending
                </span>
              <?php else: ?>
                <span class="btn-msg-pill">
                  <i class="fa-solid fa-paper-plane" style="font-size: 0.68rem;"></i> Message
                </span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</main>

<!-- iOS Bottom Sheet Action Modal -->
<div id="chatBottomSheetOverlay" class="ios-sheet-overlay" onclick="closeChatBottomSheet(event)">
  <div class="ios-sheet-container" onclick="event.stopPropagation()">
    <div class="sheet-drag-handle"></div>

    <div class="sheet-user-header">
      <img id="sheetAvatar" src="" class="sheet-user-avatar" alt="User">
      <div style="flex:1;">
        <h4 id="sheetUserName" class="sheet-user-title">User Name</h4>
        <span class="sheet-user-sub">Chat & Profile Options</span>
      </div>
      <button type="button" onclick="closeChatBottomSheet()" style="background:none; border:none; font-size:1.2rem; color:#8E8E93; cursor:pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="sheet-options-list">
      <button type="button" class="sheet-option-btn danger" onclick="executeSheetAction('delete')">
        <i class="fa-solid fa-trash-can"></i>
        <span>Delete Chat</span>
      </button>

      <button type="button" class="sheet-option-btn danger" onclick="executeSheetAction('block')">
        <i class="fa-solid fa-user-slash"></i>
        <span>Block User</span>
      </button>

      <button type="button" class="sheet-option-btn warning" onclick="executeSheetAction('report')">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Report Profile / Chat</span>
      </button>

      <button type="button" class="sheet-option-btn cancel" onclick="closeChatBottomSheet()">
        <span>Cancel</span>
      </button>
    </div>
  </div>
</div>

<script>
let activeSheetData = null;
let longPressTimer = null;

function respondMatchRequest(matchId, action, event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  
  fetch('api/match_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ match_id: matchId, action: action })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.error || 'Could not process match request.');
    }
  })
  .catch(err => {
    alert('Network error. Please try again.');
  });
}

function openChatBottomSheet(data) {
  activeSheetData = data;
  document.getElementById('sheetAvatar').src = data.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80';
  document.getElementById('sheetUserName').textContent = data.full_name || 'User';
  document.getElementById('chatBottomSheetOverlay').classList.add('active');
}

function closeChatBottomSheet(e) {
  document.getElementById('chatBottomSheetOverlay').classList.remove('active');
}

function executeSheetAction(action) {
  if (!activeSheetData) return;
  const matchId = activeSheetData.match_id;
  const partnerId = activeSheetData.partner_id;

  let confirmMsg = 'Delete this chat conversation?';
  if (action === 'block') confirmMsg = 'Block this user? They will no longer be able to message or match with you.';
  if (action === 'report') confirmMsg = 'Report this user for inappropriate behavior?';

  if (!confirm(confirmMsg)) return;

  fetch('api/manage_chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      match_id: matchId,
      partner_id: partnerId,
      action: action
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('chatBottomSheetOverlay').classList.remove('active');
      window.location.reload();
    } else {
      alert(data.error || 'Action failed');
    }
  })
  .catch(err => alert('Network error. Please try again.'));
}

// Attach Touch & Hold (Long Press) Event Listeners to all chat rows & story items
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.chat-row, .story-item').forEach(item => {
    let touchMoved = false;
    let isLongPressed = false;

    const startPress = (e) => {
      touchMoved = false;
      isLongPressed = false;
      clearTimeout(longPressTimer);
      
      longPressTimer = setTimeout(() => {
        if (!touchMoved) {
          isLongPressed = true;
          if ('vibrate' in navigator) {
            try { navigator.vibrate(40); } catch(err) {}
          }
          openChatBottomSheet({
            match_id: item.dataset.matchId,
            partner_id: item.dataset.partnerId,
            full_name: item.dataset.fullName,
            avatar_url: item.dataset.avatarUrl
          });
        }
      }, 500); // 500ms long press duration
    };

    const cancelPress = () => {
      clearTimeout(longPressTimer);
    };

    // Mobile Touch Events
    item.addEventListener('touchstart', startPress, { passive: true });
    item.addEventListener('touchmove', () => { touchMoved = true; clearTimeout(longPressTimer); }, { passive: true });
    item.addEventListener('touchend', cancelPress, { passive: true });
    item.addEventListener('touchcancel', cancelPress, { passive: true });

    // Desktop Mouse Long Press Fallback
    item.addEventListener('mousedown', startPress);
    item.addEventListener('mouseup', cancelPress);
    item.addEventListener('mouseleave', cancelPress);

    // Prevent link navigation if long-press was triggered
    item.addEventListener('click', (e) => {
      if (isLongPressed) {
        e.preventDefault();
        e.stopPropagation();
        isLongPressed = false;
        return false;
      }
    });

    // Right click / Context menu
    item.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openChatBottomSheet({
        match_id: item.dataset.matchId,
        partner_id: item.dataset.partnerId,
        full_name: item.dataset.fullName,
        avatar_url: item.dataset.avatarUrl
      });
    });
  });
});
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
