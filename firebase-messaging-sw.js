importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

// Initialize Firebase inside Service Worker with real Varsaathi project credentials
firebase.initializeApp({
  apiKey: "AIzaSyC0isEo51BqZXQCi5Mt4ILagjb8pnuFaAk",
  authDomain: "varsaathi.firebaseapp.com",
  projectId: "varsaathi",
  storageBucket: "varsaathi.firebasestorage.app",
  messagingSenderId: "1027738439255",
  appId: "1:1027738439255:web:5ce5aef3ad8caf82579d12",
  measurementId: "G-5JDP0CJ0WN"
});

const messaging = firebase.messaging();

// Handle Background Push Notifications
messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message: ', payload);
  
  const isCall = payload.data?.type === 'call';
  const notificationTitle = payload.notification?.title || payload.data?.title || (isCall ? 'Incoming Call 📞' : 'VARSAATHI Alert');
  
  const notificationOptions = {
    body: payload.notification?.body || payload.data?.body || 'You have an incoming call on Varsaathi.',
    icon: payload.notification?.icon || 'assets/images/favicon.png',
    badge: 'assets/images/favicon.png',
    tag: isCall ? 'incoming_call' : undefined,
    renotify: true,
    requireInteraction: isCall, // Keep banner on lock screen / system notification tray until user responds
    sound: 'default',
    vibrate: isCall ? [600, 300, 600, 300, 600, 300, 600, 300, 600] : [200, 100, 200],
    data: payload.data || {},
    actions: isCall ? [
      { action: 'answer', title: '📞 Answer' },
      { action: 'decline', title: '✖ Decline' }
    ] : [
      { action: 'open', title: 'Open Varsaathi' }
    ]
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click in OS tray
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const data = event.notification.data || {};
  let targetUrl = data.url || 'matches.php';

  if (data.type === 'chat' && data.match_id) {
    targetUrl = `chat.php?match_id=${data.match_id}`;
  } else if (data.type === 'call') {
    if (event.action === 'decline') {
      return; // Close notification on decline
    }
    targetUrl = `chat.php?match_id=${data.match_id || ''}&auto_answer=1`;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
