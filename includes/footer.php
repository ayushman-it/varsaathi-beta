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

    </div>
  </div>

  <script>
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
  </script>
</body>
</html>
