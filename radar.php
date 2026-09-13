<?php
// radar.php - Radar Location Scanner
require_once __DIR__ . '/config/db.php';
require_login();

$active_tab = 'radar';
$current_user_id = get_current_user_id();

$u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$u_stmt->execute([':id' => $current_user_id]);
$current_user = $u_stmt->fetch();

// Fetch nearby candidates (up to 25 users)
$nearby_stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id != :u
    ORDER BY distance_km ASC
    LIMIT 25
");
$nearby_stmt->execute([':u' => $current_user_id]);
$nearby_users = $nearby_stmt->fetchAll();

$css_version = time();
require_once __DIR__ . '/includes/header.php';
?>

<!-- iOS Header -->
<header class="app-header">
  <button class="icon-btn" id="openSidebarBtn" title="Open Menu">
    <i class="fa-solid fa-bars-staggered"></i>
  </button>

  <div class="header-title">
    <i class="fa-solid fa-satellite-dish" style="color: var(--ios-pink);"></i>
    Radar Scanner
  </div>

  <div class="header-actions">
    <span style="font-size: 0.76rem; font-weight:800; background:rgba(195,31,58,0.1); color:var(--ios-pink); padding:4px 12px; border-radius:20px;">
      <?= count($nearby_users) ?> Nearby
    </span>
  </div>
</header>

<main class="app-body" style="position: relative; overflow: hidden; touch-action: none; background: #F8F9FA;">
  <!-- Snap Map Interactive Pan & Zoom Viewport -->
  <div class="snap-map-viewport" id="snapViewport" style="background: radial-gradient(circle at center, #FFFFFF 0%, #FFF0F3 60%, #FCE4EC 100%);">
    
    <div class="snap-map-stage" id="snapStage" style="width: 1200px; height: 1200px; position: absolute; left: 50%; top: 50%; margin-left: -600px; margin-top: -600px;">
      
      <!-- Radar Target Concentric Pulse Rings -->
      <div class="map-radar-ring r1"></div>
      <div class="map-radar-ring r2"></div>
      <div class="map-radar-ring r3"></div>
      <div class="map-radar-ring r4"></div>

      <!-- Center Current User Node (You) -->
      <div class="snap-node center-user-node" style="left: 600px; top: 600px; z-index: 10;">
        <div class="snap-pin-avatar-wrapper" style="border-color: #E91E63;">
          <img src="<?= htmlspecialchars($current_user['avatar_url']) ?>" class="snap-pin-avatar" alt="You" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        </div>
        <div class="snap-pin-badge center-badge" style="background: #E91E63;">
          <span>You</span>
        </div>
      </div>

      <!-- Spaced-Out Nearby User Map Pins (No Overlapping!) -->
      <?php
      $total_nodes = count($nearby_users);
      // Pre-calculated orbital ring distances & angle steps for wide spacing
      $rings_config = [
          ['radius' => 170, 'count' => 5,  'angleOffset' => 0],
          ['radius' => 320, 'count' => 9,  'angleOffset' => 0.4],
          ['radius' => 470, 'count' => 11, 'angleOffset' => 0.8],
      ];

      $node_idx = 0;
      foreach ($rings_config as $ring):
          $r = $ring['radius'];
          $count_in_ring = $ring['count'];
          $offset = $ring['angleOffset'];

          for ($i = 0; $i < $count_in_ring && $node_idx < $total_nodes; $i++, $node_idx++):
              $user = $nearby_users[$node_idx];
              $angle = ($i / $count_in_ring) * 2 * M_PI + $offset;
              $posX = 600 + cos($angle) * $r;
              $posY = 600 + sin($angle) * $r;

              $age = calculate_age($user['birthdate']);
              $first_name = explode(' ', trim($user['full_name']))[0];
              $userInterests = json_encode(json_decode($user['interests'] ?? '[]', true) ?: []);
              $userOccupation = $user['occupation'] ?: 'Candidate';
              $city_label = !empty($user['location_city']) && strpos(strtolower($user['location_city']), 'san francisco') === false ? $user['location_city'] : 'Nearby';

              // Calculate exact GPS distance or dynamic radar ring spread
              $calc_dist = null;
              if (!empty($current_user['latitude']) && !empty($current_user['longitude']) && !empty($user['latitude']) && !empty($user['longitude'])) {
                  $calc_dist = calculate_distance_km($current_user['latitude'], $current_user['longitude'], $user['latitude'], $user['longitude']);
              }
              if ($calc_dist === null) {
                  $base_r_dist = ($r === 170) ? 0.8 : (($r === 320) ? 2.4 : 5.2);
                  $calc_dist = round($base_r_dist + ($i * 0.4), 1);
              }
      ?>
        <div class="snap-node"
             style="left: <?= number_format($posX, 1) ?>px; top: <?= number_format($posY, 1) ?>px; z-index: 5;"
             onclick="openRadarUserModal('<?= htmlspecialchars(addslashes($user['full_name'])) ?>', '<?= $age ?>', '<?= htmlspecialchars(addslashes($userOccupation)) ?>', '<?= htmlspecialchars(addslashes($city_label)) ?>', '<?= $calc_dist ?>', '<?= htmlspecialchars($user['avatar_url']) ?>', '<?= $user['id'] ?>', '<?= htmlspecialchars(addslashes($userInterests)) ?>')">
          <div class="snap-pin-avatar-wrapper">
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" class="snap-pin-avatar" alt="<?= htmlspecialchars($user['full_name']) ?>" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
          </div>
          <div class="snap-pin-stem"></div>
          <div class="snap-pin-badge">
            <span><?= htmlspecialchars($first_name) ?>, <?= $age ?></span>
            <span class="snap-pin-dist">• <?= $calc_dist ?> km</span>
          </div>
        </div>
      <?php 
          endfor;
      endforeach; 
      ?>
    </div>
  </div>

  <!-- Snap Map Floating Action Controls -->
  <div class="snap-controls-overlay">
    <div class="snap-hint-pill">
      <i class="fa-solid fa-hand-pointer" style="color: var(--ios-pink);"></i> Drag radar to explore members
    </div>

    <div class="snap-zoom-toolbar">
      <button class="snap-control-btn" id="zoomInBtn" title="Zoom In"><i class="fa-solid fa-plus"></i></button>
      <button class="snap-control-btn" id="zoomOutBtn" title="Zoom Out"><i class="fa-solid fa-minus"></i></button>
      <button class="snap-control-btn primary-btn" id="centerMapBtn" title="Recenter Radar"><i class="fa-solid fa-location-crosshairs"></i> Center</button>
    </div>
  </div>
</main>

<!-- Radar User Details Drawer -->
<div class="modal-overlay" id="radarUserDrawer">
  <div class="modal-drawer" style="text-align: center; padding: 18px 20px 24px 20px;">
    <!-- Top Drag Handle -->
    <div style="width: 36px; height: 4px; border-radius: 2px; background: #E5E5EA; margin: 0 auto 14px auto;"></div>

    <!-- Candidate Avatar -->
    <div style="position: relative; display: inline-block; margin-bottom: 10px;">
      <img id="radarModalAvatar" src="" style="width: 76px; height: 76px; border-radius: 50%; object-fit: cover; border: 3px solid #FFF; box-shadow: 0 6px 18px rgba(233,30,99,0.25);" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
    </div>

    <h3 id="radarModalName" style="font-weight: 800; font-size: 1.15rem; margin-bottom: 4px; color: var(--ios-text);">User Name</h3>
    
    <div style="font-size: 0.78rem; font-weight: 600; color: var(--ios-muted); margin-bottom: 10px;">
      <i class="fa-solid fa-briefcase" style="color: #E91E63; margin-right: 3px;"></i> <span id="radarModalOccupation">Member</span> • <i class="fa-solid fa-location-dot" style="color: #E91E63; margin-right: 3px;"></i> <span id="radarModalLoc">City • 5 km away</span>
    </div>

    <!-- Interest Tags Preview -->
    <div id="radarModalTags" style="display: flex; justify-content: center; flex-wrap: wrap; gap: 5px; margin-bottom: 16px;"></div>

    <!-- Action Toolbar Buttons -->
    <div style="display: flex; align-items: center; gap: 10px;">
      <a id="radarModalViewProfileBtn" href="#" class="btn-block btn-primary" style="flex: 1; height: 42px; font-size: 0.86rem; font-weight: 700; border-radius: 21px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #E91E63, #D81B60);">
        <i class="fa-solid fa-user" style="margin-right: 6px;"></i> View Profile
      </a>

      <button id="radarModalLikeBtn" title="Send Interest" style="width: 44px; height: 42px; border-radius: 21px; background: linear-gradient(135deg, #E91E63, #D81B60); color: #FFF; border: none; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(233,30,99,0.3); flex-shrink: 0;">
        <i class="fa-solid fa-heart"></i>
      </button>

      <button onclick="document.getElementById('radarUserDrawer').classList.remove('active')" title="Close" style="width: 44px; height: 42px; border-radius: 21px; background: #F2F2F7; color: var(--ios-muted); border: 1px solid #E5E5EA; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  </div>
</div>

<script>
// Interactive Pan & Pinch Zoom Engine
(function() {
  const viewport = document.getElementById('snapViewport');
  const stage = document.getElementById('snapStage');
  const centerBtn = document.getElementById('centerMapBtn');
  const zoomInBtn = document.getElementById('zoomInBtn');
  const zoomOutBtn = document.getElementById('zoomOutBtn');

  if (!viewport || !stage) return;

  let isPanning = false;
  let startX = 0, startY = 0;
  let panX = 0, panY = 0;
  let targetPanX = 0, targetPanY = 0;
  let zoom = 1, targetZoom = 1;

  function onPointerDown(e) {
    if (e.target.closest('.snap-node') && !e.target.closest('.center-user-node')) {
      return;
    }
    isPanning = true;
    const p = e.touches ? e.touches[0] : e;
    startX = p.clientX - targetPanX;
    startY = p.clientY - targetPanY;
  }

  function onPointerMove(e) {
    if (!isPanning) return;
    const p = e.touches ? e.touches[0] : e;
    targetPanX = p.clientX - startX;
    targetPanY = p.clientY - startY;

    targetPanX = Math.max(-450, Math.min(450, targetPanX));
    targetPanY = Math.max(-450, Math.min(450, targetPanY));

    if (e.cancelable) e.preventDefault();
  }

  function onPointerUp() {
    isPanning = false;
  }

  function renderLoop() {
    panX += (targetPanX - panX) * 0.2;
    panY += (targetPanY - panY) * 0.2;
    zoom += (targetZoom - zoom) * 0.2;

    stage.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;

    requestAnimationFrame(renderLoop);
  }

  viewport.addEventListener('mousedown', onPointerDown);
  window.addEventListener('mousemove', onPointerMove);
  window.addEventListener('mouseup', onPointerUp);

  viewport.addEventListener('touchstart', onPointerDown, { passive: true });
  window.addEventListener('touchmove', onPointerMove, { passive: false });
  window.addEventListener('touchend', onPointerUp);

  viewport.addEventListener('wheel', (e) => {
    e.preventDefault();
    targetZoom += e.deltaY * -0.0015;
    targetZoom = Math.max(0.65, Math.min(1.8, targetZoom));
  }, { passive: false });

  if (zoomInBtn) {
    zoomInBtn.addEventListener('click', () => {
      targetZoom = Math.min(1.8, targetZoom + 0.25);
    });
  }

  if (zoomOutBtn) {
    zoomOutBtn.addEventListener('click', () => {
      targetZoom = Math.max(0.65, targetZoom - 0.25);
    });
  }

  if (centerBtn) {
    centerBtn.addEventListener('click', () => {
      targetPanX = 0;
      targetPanY = 0;
      targetZoom = 1;
    });
  }

  renderLoop();
})();

// Auto-update browser geolocation
if ("geolocation" in navigator) {
  navigator.geolocation.getCurrentPosition(function(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    fetch('api/update_location.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ latitude: lat, longitude: lng })
    })
    .then(res => res.json())
    .then(data => console.log('Location synced:', data))
    .catch(err => console.error('Location sync error:', err));
  }, function(error) {}, { enableHighAccuracy: true, timeout: 8000 });
}

function openRadarUserModal(name, age, occupation, city, distance, avatar, userId, rawInterests) {
  document.getElementById('radarModalName').textContent = `${name}, ${age}`;
  document.getElementById('radarModalOccupation').textContent = occupation || 'Member';
  document.getElementById('radarModalLoc').textContent = `${city || 'Location'} • ${distance || 1} km away`;
  document.getElementById('radarModalAvatar').src = avatar;
  document.getElementById('radarModalViewProfileBtn').href = `saathi-profile.php?id=${userId}`;

  const tagsContainer = document.getElementById('radarModalTags');
  tagsContainer.innerHTML = '';
  try {
    const interests = typeof rawInterests === 'string' ? JSON.parse(rawInterests || '[]') : (rawInterests || []);
    if (Array.isArray(interests) && interests.length > 0) {
      interests.slice(0, 3).forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag-chip';
        span.style.cssText = 'background: #FDF4F6; color: #E91E63; border: 1px solid #FCE4EC; font-weight: 600; font-size: 0.68rem; padding: 3px 10px; border-radius: 12px;';
        span.innerHTML = `<i class="fa-solid fa-tag" style="margin-right: 3px;"></i> ${tag}`;
        tagsContainer.appendChild(span);
      });
    }
  } catch(e) {}
  
  const likeBtn = document.getElementById('radarModalLikeBtn');
  likeBtn.onclick = function() {
    fetch('api/saathi_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'send_interest', target_id: userId })
    })
    .then(res => res.json())
    .then(data => {
      alert(`❤️ Matrimonial Interest Sent to ${name}!`);
      document.getElementById('radarUserDrawer').classList.remove('active');
      if (data.match_id) {
        location.href = `chat.php?match_id=${data.match_id}`;
      }
    });
  };

  document.getElementById('radarUserDrawer').classList.add('active');
}
</script>

<?php 
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
