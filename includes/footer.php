<?php
// includes/footer.php
$footer_user_id = get_current_user_id();
?>
  </div> <!-- /.app-container -->

  <script>
    window.CURRENT_USER_ID = <?= (int)$footer_user_id ?>;
  </script>
  <!-- App Script Dependencies -->
  <script src="assets/js/app.js"></script>
  <!-- Firebase SDKs & FCM Notification Engine -->
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js"></script>
  <script src="firebase-config.js"></script>
  <script src="assets/js/notifications.js"></script>
  <!-- PeerJS WebRTC Calling Engine -->
  <script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>
  <script src="assets/js/calls.js"></script>
  <!-- Varsaathi PWA Installation Engine -->
  <script src="assets/js/pwa-install.js"></script>
  <!-- Varsaathi VIP Premium Bottom Sheet Engine -->
  <script src="assets/js/premium.js"></script>
  <!-- Global Bottom Sheet Filter Drawer Modal -->
  <div class="modal-overlay filter-drawer-overlay" id="filterDrawerModal">
    <div class="filter-drawer-sheet">
      <div class="filter-drawer-handle"></div>
      
      <!-- Modal Header -->
      <div class="filter-drawer-header">
        <span class="filter-drawer-title" style="font-family: system-ui, -apple-system, 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 300; color: #1C1C1E; letter-spacing: -0.4px; white-space: nowrap;">Filter Preferences</span>
        <div style="display: flex; align-items: center; gap: 8px;">
          <button class="filter-drawer-reset-btn" onclick="resetGlobalFilters()" type="button">Reset All</button>
          <button class="filter-drawer-close-btn" id="closeFilterBtn" type="button" title="Close Filters">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="filter-drawer-body">
        
        <!-- 1. Looking For / Gender Category (Var / Vadhu / Everyone) -->
        <div>
          <div class="filter-group-title">Looking For / किसे खोज रहे हैं</div>
          <div class="filter-chip-group" id="filterGenderChips">
            <button type="button" class="filter-chip-btn active" data-gender="everyone" onclick="selectFilterChip(this, 'gender')">Everyone ✨</button>
            <button type="button" class="filter-chip-btn" data-gender="female" onclick="selectFilterChip(this, 'gender')">Vadhu 👰 (Bride)</button>
            <button type="button" class="filter-chip-btn" data-gender="male" onclick="selectFilterChip(this, 'gender')">Var 🤵 (Groom)</button>
          </div>
        </div>

        <!-- 2. Age Range Filter -->
        <div>
          <div class="filter-range-label-wrap">
            <span class="filter-group-title" style="margin-bottom: 0;">Age Range / आयु सीमा</span>
            <span class="filter-range-value" id="ageVal">18 - 45 Yrs</span>
          </div>
          <div style="display: flex; gap: 10px; align-items: center; margin-top: 6px;">
            <input type="range" class="filter-range-slider" id="ageMin" min="18" max="60" value="18">
            <span style="font-size: 0.8rem; color: #8E8E93;">to</span>
            <input type="range" class="filter-range-slider" id="ageMax" min="18" max="60" value="45">
          </div>
        </div>

        <!-- 3. Distance Range Filter -->
        <div>
          <div class="filter-range-label-wrap">
            <span class="filter-group-title" style="margin-bottom: 0;">Max Distance / अधिकतम दूरी</span>
            <span class="filter-range-value" id="distanceVal">50 km</span>
          </div>
          <input type="range" class="filter-range-slider" id="distanceRange" min="5" max="300" step="5" value="50" style="margin-top: 6px;">
        </div>

        <!-- 4. Marital Status Filter -->
        <div>
          <div class="filter-group-title">Marital Status / वैवाहिक स्थिति</div>
          <div class="filter-chip-group" id="filterMaritalChips">
            <button type="button" class="filter-chip-btn active" data-val="Any" onclick="selectFilterChip(this, 'marital')">Any</button>
            <button type="button" class="filter-chip-btn" data-val="Never Married" onclick="selectFilterChip(this, 'marital')">Never Married</button>
            <button type="button" class="filter-chip-btn" data-val="Divorced" onclick="selectFilterChip(this, 'marital')">Divorced</button>
            <button type="button" class="filter-chip-btn" data-val="Widowed" onclick="selectFilterChip(this, 'marital')">Widowed</button>
          </div>
        </div>

        <!-- 5. Religion & Community Filter -->
        <div>
          <div class="filter-group-title">Community / धर्म एवं समुदाय</div>
          <div class="filter-chip-group" id="filterCommunityChips">
            <button type="button" class="filter-chip-btn active" data-val="Any" onclick="selectFilterChip(this, 'community')">Any</button>
            <button type="button" class="filter-chip-btn" data-val="Chourasiya" onclick="selectFilterChip(this, 'community')">Chourasiya Samaj ❤️</button>
            <button type="button" class="filter-chip-btn" data-val="Hindu" onclick="selectFilterChip(this, 'community')">Hindu</button>
            <button type="button" class="filter-chip-btn" data-val="Jain" onclick="selectFilterChip(this, 'community')">Jain</button>
          </div>
        </div>

        <!-- 6. Education Qualification Filter -->
        <div>
          <div class="filter-group-title">Education / योग्यता</div>
          <div class="filter-chip-group" id="filterEduChips">
            <button type="button" class="filter-chip-btn active" data-val="Any" onclick="selectFilterChip(this, 'education')">Any</button>
            <button type="button" class="filter-chip-btn" data-val="Bachelor" onclick="selectFilterChip(this, 'education')">Bachelor Degree</button>
            <button type="button" class="filter-chip-btn" data-val="Master" onclick="selectFilterChip(this, 'education')">Master / Post Grad</button>
            <button type="button" class="filter-chip-btn" data-val="Engineer" onclick="selectFilterChip(this, 'education')">B.Tech / Engineering</button>
            <button type="button" class="filter-chip-btn" data-val="Doctor" onclick="selectFilterChip(this, 'education')">MBBS / Medical</button>
          </div>
        </div>

        <!-- 7. Profession Sector Filter -->
        <div>
          <div class="filter-group-title">Profession Sector / व्यवसाय क्षेत्र</div>
          <div class="filter-chip-group" id="filterOccChips">
            <button type="button" class="filter-chip-btn active" data-val="Any" onclick="selectFilterChip(this, 'occupation')">Any</button>
            <button type="button" class="filter-chip-btn" data-val="Private" onclick="selectFilterChip(this, 'occupation')">Private Sector</button>
            <button type="button" class="filter-chip-btn" data-val="Government" onclick="selectFilterChip(this, 'occupation')">Government / PSU</button>
            <button type="button" class="filter-chip-btn" data-val="Business" onclick="selectFilterChip(this, 'occupation')">Business / Self Employed</button>
          </div>
        </div>

        <!-- 8. Hobbies & Interests Multi-select Filter -->
        <div>
          <div class="filter-group-title">Interests & Hobbies / रुचि व शौक</div>
          <div class="filter-chip-group" id="filterHobbyChips">
            <button type="button" class="filter-chip-btn" data-val="Travel" onclick="toggleMultiFilterChip(this)">Travel ✈️</button>
            <button type="button" class="filter-chip-btn" data-val="Music" onclick="toggleMultiFilterChip(this)">Music 🎵</button>
            <button type="button" class="filter-chip-btn" data-val="Fitness" onclick="toggleMultiFilterChip(this)">Fitness 🏋️</button>
            <button type="button" class="filter-chip-btn" data-val="Cooking" onclick="toggleMultiFilterChip(this)">Cooking 🍳</button>
            <button type="button" class="filter-chip-btn" data-val="Reading" onclick="toggleMultiFilterChip(this)">Reading 📚</button>
            <button type="button" class="filter-chip-btn" data-val="Movies" onclick="toggleMultiFilterChip(this)">Movies 🎬</button>
            <button type="button" class="filter-chip-btn" data-val="Photography" onclick="toggleMultiFilterChip(this)">Photography 📷</button>
            <button type="button" class="filter-chip-btn" data-val="Gaming" onclick="toggleMultiFilterChip(this)">Gaming 🎮</button>
          </div>
        </div>

      </div>

      <!-- Modal Footer Action Button -->
      <div class="filter-drawer-footer">
        <button class="filter-apply-btn" id="applyFiltersBtn" type="button" onclick="handleApplyFilters()">
          Apply Filters ✨
        </button>
      </div>
    </div>
  </div>

  <!-- Global Fullscreen Photo Lightbox Preview Modal -->
  <div class="modal-overlay" id="globalPhotoPreviewModal" style="z-index: 9999999; background: rgba(0,0,0,0.94); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); display: none; align-items: center; justify-content: center; position: fixed; inset: 0;">
    <div style="position: relative; width: 94%; max-width: 500px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
      
      <!-- Top Bar: Counter & Close Button -->
      <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 0 4px;">
        <span id="globalPhotoCounter" style="color: #FFFFFF; font-size: 0.88rem; font-weight: 700; background: rgba(255,255,255,0.18); padding: 4px 12px; border-radius: 14px; backdrop-filter: blur(10px);">1 / 1</span>
        <button onclick="closePhotoPreview()" style="background: rgba(255,255,255,0.22); color: #FFFFFF; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);" title="Close Preview">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Main Image Display Container -->
      <div style="position: relative; width: 100%; display: flex; align-items: center; justify-content: center;">
        <img id="globalPhotoPreviewImg" src="" style="width: 100%; max-height: 78vh; object-fit: contain; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.8); display: block;" onerror="this.onerror=null; this.src='assets/images/no_image_placeholder.png';">
        
        <!-- Prev & Next Arrows -->
        <button id="globalPhotoPrevBtn" onclick="navigatePhotoPreview(-1)" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.5); color: #FFF; border: 1px solid rgba(255,255,255,0.3); font-size: 1rem; display: none; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(10px); z-index: 10;">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <button id="globalPhotoNextBtn" onclick="navigatePhotoPreview(1)" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.5); color: #FFF; border: 1px solid rgba(255,255,255,0.3); font-size: 1rem; display: none; align-items: center; justify-content: center; cursor: pointer; backdrop-filter: blur(10px); z-index: 10;">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>

  <!-- Global Candidate Chat Request & Pending Notice Modal -->
  <div class="modal-overlay" id="globalChatNoticeModal" style="z-index: 999999; background: rgba(0,0,0,0.5); backdrop-filter: blur(6px); display: none; align-items: center; justify-content: center; position: fixed; inset: 0;">
    <div style="width: 90%; max-width: 380px; background: #FFFFFF; border-radius: 24px; padding: 24px 20px; text-align: center; box-shadow: 0 16px 40px rgba(0,0,0,0.2); position: relative;">
      <div style="width: 60px; height: 60px; border-radius: 50%; background: #E8F2FF; color: #007AFF; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto;">
        <i class="fa-solid fa-comment-dots"></i>
      </div>
      <h3 style="font-size: 1.15rem; font-weight: 700; color: #1C1C1E; margin-bottom: 8px;" id="chatNoticeTitle">Chat Request Sent 💬</h3>
      <p style="font-size: 0.85rem; color: #636366; line-height: 1.5; margin-bottom: 20px;" id="chatNoticeBody">
        Matrimonial interest & chat request sent! As soon as the candidate accepts your request, chat and calling will open automatically.
      </p>
      <div style="display: flex; gap: 10px;">
        <a href="matches.php" style="flex: 1; padding: 12px; border-radius: 16px; background: #F2F2F7; color: #1C1C1E; font-weight: 600; font-size: 0.88rem; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
          View Requests
        </a>
        <button type="button" onclick="closeGlobalChatNoticeModal()" style="flex: 1; padding: 12px; border-radius: 16px; background: #1C1C1E; color: #FFFFFF; font-weight: 600; font-size: 0.88rem; border: none; cursor: pointer;">
          Got It 👍
        </button>
      </div>
    </div>
  </div>

  <script>
  window.handleCandidateCardChat = async function(targetId, targetName = 'Candidate') {
    if (!targetId || targetId <= 0) return;
    
    try {
      const res = await fetch('api/check_chat_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ target_id: targetId })
      });
      const data = await res.json();
      
      if (data.can_chat && data.chat_url) {
        window.location.href = data.chat_url;
        return;
      }
      
      // Show pending modal notice
      const modal = document.getElementById('globalChatNoticeModal');
      const title = document.getElementById('chatNoticeTitle');
      const body = document.getElementById('chatNoticeBody');
      
      if (title) title.textContent = data.status === 'accepted' ? 'Connecting Chat... 💬' : 'Chat Request Sent 💬';
      if (body) body.textContent = data.message || `Matrimonial chat request sent to ${targetName}! Once accepted, chat will open automatically.`;
      if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
      }
    } catch (err) {
      console.error('Chat check error:', err);
      window.location.href = 'matches.php';
    }
  };

  window.closeGlobalChatNoticeModal = function() {
    const modal = document.getElementById('globalChatNoticeModal');
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  };
  let globalPreviewPhotos = [];
  let globalPreviewIndex = 0;

  window.openPhotoPreview = function(url, photosArr = [], initialIdx = 0) {
    const modal = document.getElementById('globalPhotoPreviewModal');
    const img = document.getElementById('globalPhotoPreviewImg');
    if (!modal || !img) return;

    if (Array.isArray(photosArr) && photosArr.length > 0) {
      globalPreviewPhotos = photosArr;
      globalPreviewIndex = initialIdx >= 0 && initialIdx < photosArr.length ? initialIdx : 0;
    } else {
      globalPreviewPhotos = [url];
      globalPreviewIndex = 0;
    }

    updateGlobalPhotoDisplay();
    modal.style.display = 'flex';
    modal.classList.add('active');
  };

  function updateGlobalPhotoDisplay() {
    const img = document.getElementById('globalPhotoPreviewImg');
    const counter = document.getElementById('globalPhotoCounter');
    const prevBtn = document.getElementById('globalPhotoPrevBtn');
    const nextBtn = document.getElementById('globalPhotoNextBtn');

    if (!globalPreviewPhotos || globalPreviewPhotos.length === 0) return;

    const currentUrl = globalPreviewPhotos[globalPreviewIndex];
    if (img) img.src = currentUrl;

    if (counter) {
      counter.textContent = `${globalPreviewIndex + 1} / ${globalPreviewPhotos.length}`;
    }

    if (globalPreviewPhotos.length > 1) {
      if (prevBtn) prevBtn.style.display = 'flex';
      if (nextBtn) nextBtn.style.display = 'flex';
    } else {
      if (prevBtn) prevBtn.style.display = 'none';
      if (nextBtn) nextBtn.style.display = 'none';
    }
  }

  window.navigatePhotoPreview = function(direction) {
    if (!globalPreviewPhotos || globalPreviewPhotos.length <= 1) return;
    globalPreviewIndex = (globalPreviewIndex + direction + globalPreviewPhotos.length) % globalPreviewPhotos.length;
    updateGlobalPhotoDisplay();
  };

  window.closePhotoPreview = function() {
    const modal = document.getElementById('globalPhotoPreviewModal');
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  };

  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('globalPhotoPreviewModal');
    if (modal && modal.style.display === 'flex') {
      if (e.key === 'Escape') closePhotoPreview();
      if (e.key === 'ArrowLeft') navigatePhotoPreview(-1);
      if (e.key === 'ArrowRight') navigatePhotoPreview(1);
    }
  });

  // Filter Drawer Helper Functions
  window.selectFilterChip = function(btn, groupName) {
    const container = btn.closest('.filter-chip-group');
    if (!container) return;
    container.querySelectorAll('.filter-chip-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  };

  window.toggleMultiFilterChip = function(btn) {
    btn.classList.toggle('active');
  };

  window.resetGlobalFilters = function() {
    document.querySelectorAll('.filter-chip-group').forEach(group => {
      group.querySelectorAll('.filter-chip-btn').forEach((btn, idx) => {
        if (idx === 0 && group.id !== 'filterHobbyChips') {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });
    });
    const ageMin = document.getElementById('ageMin');
    const ageMax = document.getElementById('ageMax');
    const ageVal = document.getElementById('ageVal');
    if (ageMin && ageMax) {
      ageMin.value = 18;
      ageMax.value = 45;
      if (ageVal) ageVal.textContent = '18 - 45 Yrs';
    }
    const dist = document.getElementById('distanceRange');
    const distVal = document.getElementById('distanceVal');
    if (dist) {
      dist.value = 50;
      if (distVal) distVal.textContent = '50 km';
    }
  };

  window.handleApplyFilters = async function() {
    const applyBtn = document.getElementById('applyFiltersBtn');
    if (applyBtn) {
      applyBtn.disabled = true;
      applyBtn.textContent = 'Saving Preferences...';
    }

    const genderActive = document.querySelector('#filterGenderChips .filter-chip-btn.active');
    const gender_preference = genderActive ? genderActive.getAttribute('data-gender') : 'everyone';

    const ageMin = document.getElementById('ageMin')?.value || 18;
    const ageMax = document.getElementById('ageMax')?.value || 45;
    const max_distance = document.getElementById('distanceRange')?.value || 50;

    const maritalActive = document.querySelector('#filterMaritalChips .filter-chip-btn.active');
    const marital_status = maritalActive ? maritalActive.getAttribute('data-val') : 'Any';

    const commActive = document.querySelector('#filterCommunityChips .filter-chip-btn.active');
    const religion_community = commActive ? commActive.getAttribute('data-val') : 'Any';

    const eduActive = document.querySelector('#filterEduChips .filter-chip-btn.active');
    const education = eduActive ? eduActive.getAttribute('data-val') : 'Any';

    const occActive = document.querySelector('#filterOccChips .filter-chip-btn.active');
    const occupation = occActive ? occActive.getAttribute('data-val') : 'Any';

    const hobbies = [];
    document.querySelectorAll('#filterHobbyChips .filter-chip-btn.active').forEach(b => {
      hobbies.push(b.getAttribute('data-val'));
    });

    try {
      const res = await fetch('api/filter.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          ajax: 1,
          gender_preference,
          min_age: ageMin,
          max_age: ageMax,
          max_distance,
          marital_status,
          religion_community,
          education,
          occupation,
          interests: hobbies
        })
      });
      const data = await res.json();
      if (data.success) {
        const modal = document.getElementById('filterDrawerModal');
        if (modal) modal.classList.remove('active');
        window.location.reload();
      } else {
        alert(data.message || 'Error updating filters');
      }
    } catch (err) {
      console.error('Filter update error:', err);
      window.location.reload();
    } finally {
      if (applyBtn) {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply Filters ✨';
      }
    }
  };
  </script>
</body>
</html>
