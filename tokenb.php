<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCM: Obtener y Guardar Token</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        #log { margin-top: 20px; padding: 10px; border: 1px solid #ccc; min-height: 50px; text-align: left; background-color: #eee; border-radius: 4px; }
        h1 { color: #333; }
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
        button:hover:not(:disabled) { background-color: #45a049; }
        button:disabled { background-color: #aaa; cursor: not-allowed; }
        .log-error { color: red !important; font-weight: bold; }
        .log-success { color: darkgreen; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Firebase Cloud Messaging (FCM) 🔑</h1>
        <p>Haz clic para obtener tu token y guardarlo automáticamente en la base de datos.</p>
        <button id="getTokenButton">Obtener y Guardar Token</button>
        <div id="log">
            <h3>Registro de Acciones:</h3>
        </div>
    </div>
    
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
        import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging.js";

        const firebaseConfig = {
            apiKey: "AIzaSyBulpMP6bbJ66DjiHTPkYTDFnOGLofxwrE",
            authDomain: "majo-19e66.firebaseapp.com",
            projectId: "majo-19e66",
            storageBucket: "majo-19e66.firebasestorage.app",
            messagingSenderId: "335384882055",
            appId: "1:335384882055:web:12a545b31ea22c91f60122",
            measurementId: "G-T1PEZLGN7N"
        };
        const VAPID_KEY = 'BIahtRqHD6ZgpS3-ErAQzKrzM2CspHmv_--NskdHZV3nRFqyk-gFhPfne_43q60tvYmpQX6W8JRg3QCm_Q4_hik';

        const app = initializeApp(firebaseConfig);
        const messaging = getMessaging(app);

        const logElement = document.getElementById('log');
        const button = document.getElementById('getTokenButton');

        function appendLog(message, isError = false) {
            console.log(message);
            const p = document.createElement('p');
            p.textContent = (isError ? '❌ ' : '✅ ') + message;
            p.className = isError ? 'log-error' : 'log-success';
            logElement.appendChild(p);
            logElement.scrollTop = logElement.scrollHeight;
        }

        // ----------------------------------------------------------------------
        // GENERAR FINGERPRINT DEL NAVEGADOR
        // ----------------------------------------------------------------------
        async function generateFingerprint() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = "top";
            ctx.font = "14px 'Arial'";
            ctx.fillStyle = "#f60";
            ctx.fillRect(125,1,62,20);
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
            const hashHex = hashArray.map(b => b.toString(16).padStart(2,'0')).join('');
            return hashHex;
        }

        // ----------------------------------------------------------------------
        // OBTENER TOKEN Y ENVIAR AL SERVIDOR
        // ----------------------------------------------------------------------
        async function requestAndSendToken() {
            button.disabled = true;
            appendLog('Iniciando proceso: Registrando Service Worker...');

            try {
                const registration = await navigator.serviceWorker.register("sw.js");
                appendLog('Service Worker registrado correctamente.');

                const currentToken = await getToken(messaging, {
                    serviceWorkerRegistration: registration,
                    vapidKey: VAPID_KEY
                });

                if (currentToken) {
                    appendLog("Token obtenido: " + currentToken);

                    const deviceFingerprint = await generateFingerprint();
                    appendLog("Fingerprint generado: " + deviceFingerprint);

                    await sendTokenToServer(currentToken, deviceFingerprint);
                    appendLog("🎉 Proceso completado: Token y fingerprint enviados correctamente.");
                } else {
                    appendLog('No se pudo obtener el token. Habilita las notificaciones en tu navegador.', true);
                }

            } catch (err) {
                appendLog(`Error en el proceso: ${err.message}`, true);
            } finally {
                button.disabled = false;
            }
        }

        async function sendTokenToServer(token, fingerprint) {
            appendLog(`Enviando token y fingerprint a /admin/token.php...`);
            
            const response = await fetch('token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    fcm_token: token,
                    device_id: fingerprint
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Fallo del servidor (${response.status}): ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            appendLog(`Respuesta del servidor PHP: ${result.message}`);
        }

        button.addEventListener('click', requestAndSendToken);
    </script>
</body>
</html>
