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
  <!-- Anti-Screenshot & Image Protection Security Engine -->
  <script src="assets/js/privacy-protection.js?v=<?= time() ?>"></script>
</body>
</html>
