<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCM: Obtener y Guardar Token</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #log {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            min-height: 50px;
            text-align: left;
            background-color: #eee;
            border-radius: 4px;
        }

        h1 {
            color: #333;
        }

        button {
            padding: 12px 25px;
            font-size: 18px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        button:hover:not(:disabled) {
            background-color: #45a049;
        }

        button:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }

        .log-error {
            color: red !important;
            font-weight: bold;
        }

        .log-success {
            color: darkgreen;
        }
    </style>
</head>

<body>


    <script type="module">
        // ---------------------------------------------------------------
        // Firebase Cloud Messaging (FCM) - Obtener y Enviar Token + Fingerprint (con cache)
        // ---------------------------------------------------------------
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
        import {
            getMessaging,
            getToken
        } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging.js";

        // Configuración Firebase
        const firebaseConfig = {
            apiKey: "AIzaSyBulpMP6bbJ66DjiHTPkYTDFnOGLofxwrE",
            authDomain: "majo-19e66.firebaseapp.com",
            projectId: "majo-19e66",
            storageBucket: "majo-19e66.firebasestorage.app",
            messagingSenderId: "335384882055",
            appId: "1:335384882055:web:12a545b31ea22c91f60122",
            measurementId: "G-T1PEZLGN7N"
        };
        const VAPID_KEY = 'BANRq3owV2f4D_1iri6qQVdVn5igZ_5m2RcqH9kmH0S_67gIzUL3nasWI5cedjJPEIMIlm2egz_Cs-7lqGYXrIo';

        // Inicializar Firebase
        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        // ---------------------------------------------------------------
        // Generar Fingerprint Único (cacheado en localStorage)
        // ---------------------------------------------------------------
        async function generateFingerprint() {
            const cachedFingerprint = localStorage.getItem('deviceFingerprint');
            if (cachedFingerprint) {
                console.log('✅ Fingerprint obtenido desde cache:', cachedFingerprint);
                return cachedFingerprint;
            }

            console.log('🌀 Generando nuevo fingerprint del navegador...');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = "top";
            ctx.font = "14px Arial";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = "#069";
            ctx.fillText("fingerprint_test", 2, 2);
            const canvasData = canvas.toDataURL();

            const parts = [
                navigator.userAgent || '',
                navigator.platform || '',
                navigator.language || '',
                screen.width + 'x' + screen.height,
                screen.colorDepth || '',
                navigator.hardwareConcurrency || '',
                canvasData
            ];
            const raw = parts.join('||');

            const enc = new TextEncoder().encode(raw);
            const hashBuffer = await crypto.subtle.digest('SHA-256', enc);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

            localStorage.setItem('deviceFingerprint', hashHex);
            console.log('✅ Fingerprint generado y guardado en cache:', hashHex);
            return hashHex;
        }

        // ---------------------------------------------------------------
        // Obtener Token y Enviar al Servidor
        // ---------------------------------------------------------------
        async function requestAndSendToken() {
            console.log('🚀 Iniciando proceso FCM...');

            const fingerprint = await generateFingerprint();
            const cachedData = JSON.parse(localStorage.getItem('fcmData') || '{}');

            // Si ya hay token y pertenece al mismo fingerprint, no se genera de nuevo
            if (cachedData.token && cachedData.fingerprint === fingerprint) {
                console.log('✅ Token y fingerprint ya cacheados. No se solicitará nuevo token.');
                console.log('🔑 Token FCM (cache):', cachedData.token);
                return;
            }

            try {
                const registration = await navigator.serviceWorker.register("sw.js");
                console.log('✅ Service Worker registrado correctamente.');

                const currentToken = await getToken(messaging, {
                    serviceWorkerRegistration: registration,
                    vapidKey: VAPID_KEY
                });

                if (!currentToken) {
                    console.error('❌ No se pudo obtener el token. Habilita las notificaciones en tu navegador.');
                    return;
                }

                console.log('🔑 Token FCM obtenido:', currentToken);

                await sendTokenToServer(currentToken, fingerprint);

                // Guardar token y fingerprint en cache
                localStorage.setItem('fcmData', JSON.stringify({
                    token: currentToken,
                    fingerprint: fingerprint,
                    timestamp: Date.now()
                }));

                console.log('🎉 Token y fingerprint enviados y guardados correctamente.');

            } catch (err) {
                console.error('❌ Error en el proceso FCM:', err.message);
            }
        }

        // ---------------------------------------------------------------
        // Enviar Token y Fingerprint al Servidor PHP
        // ---------------------------------------------------------------
        async function sendTokenToServer(token, fingerprint) {
            console.log('📡 Enviando token y fingerprint a /push/token.php...');

            const response = await fetch('/push/token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    fcm_token: token,
                    device_id: fingerprint
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(`Fallo del servidor (${response.status}): ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            console.log('📬 Respuesta del servidor PHP:', result.message || JSON.stringify(result));
        }

        // ---------------------------------------------------------------
        // Ejecutar automáticamente al cargar
        // ---------------------------------------------------------------
        requestAndSendToken();
    </script>
</body>

</html>