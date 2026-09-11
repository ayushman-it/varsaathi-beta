/**
 * VARSAATHI - Firebase Configuration & SDK Initialization
 */

const firebaseConfig = {
  apiKey: "AIzaSyC0isEo51BqZXQCi5Mt4ILagjb8pnuFaAk",
  authDomain: "varsaathi.firebaseapp.com",
  projectId: "varsaathi",
  storageBucket: "varsaathi.firebasestorage.app",
  messagingSenderId: "1027738439255",
  appId: "1:1027738439255:web:5ce5aef3ad8caf82579d12",
  measurementId: "G-5JDP0CJ0WN"
};

// FCM Public VAPID Key
const FCM_VAPID_KEY = "BISYteAgrewF5bLSH4mvNC5M3NKgMdX7a_WccyQ043CW7swyPzY2T7basw8EPSIENvSh4BvfBZkywKZqqFgA_wg";

if (typeof window !== 'undefined') {
  window.firebaseConfig = firebaseConfig;
  window.FCM_VAPID_KEY = FCM_VAPID_KEY;
  if (typeof firebase !== 'undefined' && (!firebase.apps || !firebase.apps.length)) {
    firebase.initializeApp(firebaseConfig);
  }
}
