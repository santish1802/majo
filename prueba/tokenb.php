<!DOCTYPE html>
<html lang="en">
<body>

<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-app.js";
  import {
    getMessaging,
    getToken
  } from "https://www.gstatic.com/firebasejs/10.13.0/firebase-messaging.js";

  const firebaseConfig = {
    apiKey: "AIzaSyBulpMP6bbJ66DjiHTPkYTDFnOGLofxwrE",
    authDomain: "majo-19e66.firebaseapp.com",
    databaseURL: "https://majo-19e66-default-rtdb.firebaseio.com",
    projectId: "majo-19e66",
    storageBucket: "majo-19e66.firebasestorage.app",
    messagingSenderId: "335384882055",
    appId: "1:335384882055:web:12a545b31ea22c91f60122",
    measurementId: "G-T1PEZLGN7N"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const messaging = getMessaging(app);
  const VapidKey = 'BAPKITGdoR92fXL3wXfhFQLYo--Epc6jG3m3xrMKI1nZKlMyjiWx0c3_h5xYsq2kDAxnOmVw26BW4Y_TIQvWe7Y';

  // Register the service worker
  navigator.serviceWorker.register('sw.js').then((registration) => {
    getToken(messaging, {
      serviceWorkerRegistration: registration,
      vapidKey: VapidKey
    })
    .then((currentToken) => {
      if (currentToken) {
        console.log('Token retrieved: ', currentToken);
        subscribeUserToTopic(currentToken, 'testing5');
        // subscribeUserToTopic(currentToken, 'news'); // Replace 'news' with your topic name
      } else {
        console.log('No registration token available. Request permission to generate one.');
      }
    })
    .catch((err) => {
      console.log('An error occurred while retrieving token. ', err);
    });
  }).catch((err) => {
    console.log('Service worker registration failed: ', err);
  });

  // Subscribe to a topic
  async function requestNotificationPermission() {
    try {
      const permission = await Notification.requestPermission();
      if (permission === 'granted') {
        console.log('Notification permission granted.');
        return true;
      } else {
        console.log('Notification permission denied.');
        return false;
      }
    } catch (error) {
      console.error('Error requesting notification permission:', error);
      return false;
    }
  }

  async function subscribeUserToTopic(token, topic) {
    try {
      const response = await fetch('/subscribe.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token, topic })
      });
      
      const data = await response.json();
      
      if (data.success) {
        console.log('Successfully subscribed to topic:', topic);
      } else {
        console.error('Failed to subscribe to topic:', data.message);
      }
    } catch (error) {
      console.error('Error subscribing to topic', error);
    }
  }

  // Request notification permission on load
  requestNotificationPermission();

</script>

</body>
</html>