<?php
// matches.php - Connections, Requests & Messages (Matching UI Mockup Image 1)
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'matches';
$current_user_id = get_current_user_id();

// Fetch all matches and connection requests for current user
$matches_stmt = $pdo->prepare("
    SELECT m.id AS match_id, m.status AS match_status, m.requested_by, m.matched_at,
           u.id AS user_id, u.full_name, u.avatar_url, u.photos, u.location_city, u.occupation, u.birthdate, u.last_seen, u.interests,
           sp.height_cm, sp.highest_qualification, sp.occupation_type, sp.marital_status AS saathi_marital_status, sp.diet, sp.religion, sp.caste_community, sp.degree,
           (SELECT message_text FROM messages WHERE match_id = m.id ORDER BY id DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM messages WHERE match_id = m.id ORDER BY id DESC LIMIT 1) AS last_message_time,
           (SELECT COUNT(*) FROM messages WHERE match_id = m.id AND receiver_id = :u1 AND is_read = 0) AS unread_count
    FROM matches m
    JOIN users u ON (u.id = IF(m.user1_id = :u2, m.user2_id, m.user1_id))
    LEFT JOIN saathi_profiles sp ON sp.user_id = u.id
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

// Separate into Accepted Chats, Incoming Requests, and Outgoing Pending
$chats_matches = array_values(array_filter($all_matches, function($m) {
    return $m['match_status'] === 'accepted' || empty($m['match_status']);
}));

$incoming_requests = array_values(array_filter($all_matches, function($m) use ($current_user_id) {
    return $m['match_status'] === 'pending' && (int)$m['requested_by'] !== $current_user_id;
}));

$outgoing_pending = array_values(array_filter($all_matches, function($m) use ($current_user_id) {
    return $m['match_status'] === 'pending' && (int)$m['requested_by'] === $current_user_id;
}));

function get_relative_time($datetime) {
    if (empty($datetime)) return 'New';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) {
        $mins = max(1, floor($diff / 60));
        return "{$mins} min";
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? "1 hour" : "{$hours} hours";
    }
    $days = floor($diff / 86400);
    return $days == 1 ? "1 day" : "{$days} days";
}

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<header class="app-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; min-height: 56px;">
  <!-- Left: Logo -->
  <div class="header-logo-left" style="display: flex; align-items: center; flex: 1;">
    <img src="assets/images/varsaathi_logo.png" alt="VARSAATHI" style="height: 38px; max-width: 155px; object-fit: contain; display: block;">
  </div>

  <!-- Center: For Chourasiyas -->
  <div class="header-center-title" style="flex: 2; text-align: center; display: flex; align-items: center; justify-content: center;">
    <span style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">For Chourasiyas</span>
  </div>

  <!-- Right Actions: Filter, Menu -->
  <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; flex: 1; gap: 8px;">
    <button class="icon-btn" id="filterBtn" title="Filter Matches" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E; display: flex; align-items: center; justify-content: center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1C1C1E" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" y1="21" x2="4" y2="14"></line>
        <line x1="4" y1="10" x2="4" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12" y2="3"></line>
        <line x1="20" y1="21" x2="20" y2="16"></line>
        <line x1="20" y1="12" x2="20" y2="3"></line>
        <line x1="1" y1="14" x2="7" y2="14"></line>
        <line x1="9" y1="8" x2="15" y2="8"></line>
        <line x1="17" y1="16" x2="23" y2="16"></line>
      </svg>
    </button>
    <button class="icon-btn" id="openSidebarBtn" title="Menu" style="width: 36px; height: 36px; background: #F8F8FA; border: 1px solid #E5E5EA; color: #1C1C1E;">
      <i class="fa-solid fa-bars" style="font-size: 0.95rem;"></i>
    </button>
  </div>
</header>

<main class="app-body" style="padding: 12px 16px 90px 16px; background: #FAF9FC;">

  <!-- Connections Segmented Pill Tabs Bar (Matching Image 1) -->
  <div style="background: #EFEFF4; padding: 4px; border-radius: 26px; display: flex; align-items: center; gap: 4px; margin-bottom: 14px;">
    <button class="conn-tab-btn active" onclick="switchConnTab('chats')" id="tabBtnChats" style="flex: 1; height: 36px; border: none; border-radius: 20px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: #FFFFFF; color: #1C1C1E; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
      Chats (<?= count($chats_matches) ?>)
    </button>
    <button class="conn-tab-btn" onclick="switchConnTab('requests')" id="tabBtnRequests" style="flex: 1; height: 36px; border: none; border-radius: 20px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: transparent; color: #8E8E93; position: relative;">
      Requests <?= count($incoming_requests) > 0 ? '<span style="background: #C31F3A; color: #FFF; font-size: 0.68rem; padding: 2px 6px; border-radius: 10px; margin-left: 4px;">' . count($incoming_requests) . '</span>' : '' ?>
    </button>
    <button class="conn-tab-btn" onclick="switchConnTab('pending')" id="tabBtnPending" style="flex: 1; height: 36px; border: none; border-radius: 20px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: transparent; color: #8E8E93;">
      Pending (<?= count($outgoing_pending) ?>)
    </button>
  </div>

  <!-- Search Input -->
  <div class="search-bar-pill" style="margin-bottom: 14px;">
    <input type="text" class="search-input-pill" placeholder="Search Connections" id="msgSearchInput" style="font-weight: 400; font-size: 0.88rem;">
    <button class="search-btn-circle" title="Search">
      <i class="fa-solid fa-magnifying-glass" style="font-size: 0.9rem;"></i>
    </button>
  </div>

  <!-- SECTION 1: ACTIVE CHATS TAB -->
  <div id="chatsTabSection">
    <div class="messages-container-card" id="conversationsCardContainer">
      <?php if (empty($chats_matches)): ?>
        <div style="text-align: center; padding: 40px 20px;">
          <div style="font-size: 2.8rem; margin-bottom: 10px;">💬</div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: #1C1C1E;">No Active Chats Yet</h3>
          <p style="color: #8E8E93; font-size: 0.82rem; max-width: 250px; margin: 0 auto 16px auto;">Accept a match request or send interest on Discover cards to start chatting!</p>
          <a href="index.php" class="btn-block btn-primary" style="max-width: 200px; margin: 0 auto; height: 42px; border-radius: 21px; font-weight: 700;">
            Explore Cards
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($chats_matches as $m): 
          $partner_name = ucwords(strtolower(trim((string)($m['full_name'] ?? ''))));
          $partner_avatar = get_valid_avatar_url($m['avatar_url'] ?? '');
          
          $raw_snippet = (string)($m['last_message'] ?? '');
          $clean_snippet = trim(strip_tags(preg_replace('/<!--(.*?)-->/s', '', $raw_snippet)));
          if (empty($clean_snippet)) {
              if (strpos($raw_snippet, 'call-log') !== false || strpos($raw_snippet, 'call_id') !== false) {
                  $clean_snippet = '📞 Video/Audio Call';
              } else {
                  $clean_snippet = 'Matched! Say Hello 👋';
              }
          }

          $rel_time = get_relative_time($m['last_message_time'] ?? ($m['matched_at'] ?? ''));
          $unread = (int)($m['unread_count'] ?? 0);
          $last_seen_time = !empty($m['last_seen']) ? strtotime($m['last_seen']) : 0;
          $has_story_ring = ($unread > 0 || (time() - $last_seen_time) < 3600);
        ?>
          <a href="chat.php?match_id=<?= $m['match_id'] ?>" class="message-item-row">
            <div class="message-avatar-wrap" style="<?= $has_story_ring ? 'padding: 2px; background: linear-gradient(135deg, #C31F3A, #FF2D55); border-radius: 50%;' : '' ?>">
              <img src="<?= htmlspecialchars($partner_avatar) ?>" class="message-avatar-img" alt="<?= htmlspecialchars($partner_name) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
            </div>

            <div class="message-body-meta">
              <div class="message-author-name"><?= htmlspecialchars($partner_name) ?></div>
              <div class="message-snippet-text" style="<?= $unread > 0 ? 'font-weight: 600; color: #1C1C1E;' : '' ?>">
                <?= htmlspecialchars($clean_snippet) ?>
              </div>
            </div>

            <div class="message-right-info">
              <div class="message-time-text"><?= htmlspecialchars($rel_time) ?></div>
              <?php if ($unread > 0): ?>
                <div class="message-unread-badge"><?= $unread ?></div>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- SECTION 2: INCOMING REQUESTS TAB (MATCHING IMAGE 1 MOCKUP) -->
  <div id="requestsTabSection" style="display: none;">
    <?php if (empty($incoming_requests)): ?>
      <div style="background: #FFFFFF; border-radius: 24px; padding: 40px 20px; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.04);">
        <div style="font-size: 2.8rem; margin-bottom: 10px;">💌</div>
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: #1C1C1E;">No Pending Requests</h3>
        <p style="color: #8E8E93; font-size: 0.82rem; max-width: 250px; margin: 0 auto 16px auto;">When members send you matrimonial interest, their requests will appear here.</p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 14px;">
        <?php foreach ($incoming_requests as $req): 
          $req_name = ucwords(strtolower(trim((string)($req['full_name'] ?? ''))));
          $req_avatar = get_valid_avatar_url($req['avatar_url'] ?? '');
          $req_age = calculate_age($req['birthdate'] ?? '2000-01-01');
          $req_ht = !empty($req['height_cm']) ? round($req['height_cm'] / 30.48, 1) . "′" : "5′ 5″";
          $req_occ = !empty($req['occupation']) ? $req['occupation'] : ($req['occupation_type'] ?? 'Member');
          $req_city = !empty($req['location_city']) && strpos(strtolower((string)$req['location_city']), 'san francisco') === false ? $req['location_city'] . ', India' : 'India';
          
          $req_traits = get_candidate_traits($req);
          $photos_raw = json_decode((string)($req['photos'] ?? '[]'), true) ?: [];
          if (!in_array($req_avatar, $photos_raw)) { array_unshift($photos_raw, $req_avatar); }
        ?>
          <div class="req-card" style="background: #FFFFFF; border-radius: 22px; padding: 14px 16px; border: 1px solid #F0F0F5; box-shadow: 0 6px 20px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
              
              <!-- Left Candidate Info -->
              <a href="saathi-profile.php?id=<?= $req['user_id'] ?>" style="text-decoration: none; color: inherit; flex: 1;">
                <div>
                  <h4 style="font-size: 0.96rem; font-weight: 600; color: #1C1C1E; margin-bottom: 2px; line-height: 1.25;"><?= htmlspecialchars($req_name) ?></h4>
                  <div style="font-size: 0.75rem; color: #8E8E93; font-weight: 400; margin-bottom: 8px;"><?= htmlspecialchars($req_occ) ?></div>
                  
                  <div style="display: flex; gap: 14px; margin-bottom: 8px;">
                    <div>
                      <div style="font-size: 0.65rem; color: #8E8E93; text-transform: uppercase; font-weight: 500;">Age</div>
                      <div style="font-size: 0.84rem; color: #C31F3A; font-weight: 600;"><?= $req_age ?> Years</div>
                    </div>
                    <div style="border-left: 1px solid #E5E5EA; padding-left: 14px;">
                      <div style="font-size: 0.65rem; color: #8E8E93; text-transform: uppercase; font-weight: 500;">Height</div>
                      <div style="font-size: 0.84rem; color: #C31F3A; font-weight: 600;"><?= $req_ht ?></div>
                    </div>
                  </div>

                  <div style="font-size: 0.74rem; color: #636366; font-weight: 400; display: flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-location-dot" style="color: #8E8E93; font-size: 0.7rem;"></i> <?= htmlspecialchars($req_city) ?>
                  </div>
                </div>
              </a>

              <!-- Right Photo Carousel Horizontal Strip -->
              <div class="photo-carousel-wrap" style="display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; width: 140px; flex-shrink: 0; padding-bottom: 2px;">
                <?php foreach ($photos_raw as $p_img): ?>
                  <img src="<?= htmlspecialchars(get_valid_avatar_url($p_img)) ?>" style="width: 65px; height: 98px; border-radius: 14px; object-fit: cover; scroll-snap-align: start; flex-shrink: 0;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
                <?php endforeach; ?>
              </div>

            </div>

            <!-- Dynamic Trait Pills Row -->
            <div style="display: flex; gap: 6px; margin-bottom: 10px; overflow-x: auto; scrollbar-width: none; align-items: center;">
              <?php foreach ($req_traits as $r_trait): ?>
                <span style="background: #F2F2F7; color: #636366; font-size: 0.7rem; font-weight: 500; padding: 4px 10px; border-radius: 10px; white-space: nowrap; flex-shrink: 0;"><?= htmlspecialchars($r_trait) ?></span>
              <?php endforeach; ?>
            </div>

            <!-- Action Buttons: REJECT & ACCEPT -->
            <div style="display: flex; gap: 10px;">
              <button onclick="handleRequestAction(<?= $req['match_id'] ?>, 'reject_request')" style="flex: 1; height: 38px; border-radius: 19px; border: none; background: #FFF0F4; color: #C31F3A; font-weight: 700; font-size: 0.78rem; cursor: pointer;">
                DECLINE
              </button>
              <button onclick="handleRequestAction(<?= $req['match_id'] ?>, 'accept_request')" style="flex: 1; height: 38px; border-radius: 19px; border: none; background: linear-gradient(135deg, #E02847, #C31F3A); color: #FFFFFF; font-weight: 700; font-size: 0.78rem; cursor: pointer; box-shadow: 0 4px 14px rgba(195,31,58,0.25);">
                ACCEPT
              </button>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- SECTION 3: OUTGOING PENDING TAB -->
  <div id="pendingTabSection" style="display: none;">
    <?php if (empty($outgoing_pending)): ?>
      <div style="background: #FFFFFF; border-radius: 24px; padding: 40px 20px; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,0.04);">
        <div style="font-size: 2.8rem; margin-bottom: 10px;">⏳</div>
        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 6px; color: #1C1C1E;">No Sent Pending Requests</h3>
        <p style="color: #8E8E93; font-size: 0.82rem; max-width: 250px; margin: 0 auto 16px auto;">Matrimonial interests you send to other members will be tracked here until accepted.</p>
      </div>
    <?php else: ?>
      <div class="messages-container-card">
        <?php foreach ($outgoing_pending as $pend): 
          $p_name = ucwords(strtolower(trim($pend['full_name'])));
          $p_avatar = get_valid_avatar_url($pend['avatar_url'] ?? '');
        ?>
          <a href="chat.php?match_id=<?= $pend['match_id'] ?>" class="message-item-row">
            <div class="message-avatar-wrap">
              <img src="<?= htmlspecialchars($p_avatar) ?>" class="message-avatar-img" alt="<?= htmlspecialchars($p_name) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
            </div>
            <div class="message-body-meta">
              <div class="message-author-name"><?= htmlspecialchars($p_name) ?></div>
              <div class="message-snippet-text">Matrimonial interest sent • Awaiting response</div>
            </div>
            <div class="message-right-info">
              <span style="font-size: 0.72rem; font-weight: 700; color: #8E8E93; background: #F2F2F7; padding: 4px 10px; border-radius: 12px;">Pending</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<script>
function switchConnTab(tab) {
  document.querySelectorAll('.conn-tab-btn').forEach(btn => {
    btn.style.background = 'transparent';
    btn.style.color = '#8E8E93';
    btn.style.boxShadow = 'none';
  });

  const activeBtn = document.getElementById('tabBtn' + tab.charAt(0).toUpperCase() + tab.slice(1));
  if (activeBtn) {
    activeBtn.style.background = '#FFFFFF';
    activeBtn.style.color = '#1C1C1E';
    activeBtn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.06)';
  }

  document.getElementById('chatsTabSection').style.display = (tab === 'chats') ? 'block' : 'none';
  document.getElementById('requestsTabSection').style.display = (tab === 'requests') ? 'block' : 'none';
  document.getElementById('pendingTabSection').style.display = (tab === 'pending') ? 'block' : 'none';
}

function handleRequestAction(matchId, action) {
  fetch('api/saathi_action.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: action, match_id: matchId })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message || 'Request updated');
    location.reload();
  })
  .catch(err => {
    location.reload();
  });
}

document.getElementById('msgSearchInput')?.addEventListener('input', function(e) {
  const query = e.target.value.toLowerCase().trim();
  document.querySelectorAll('.message-item-row, .req-card').forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(query) ? 'flex' : 'none';
  });
});
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
