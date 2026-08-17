<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sense connexió - RedAmigos</title>
    <link rel="manifest" href="/amigos/manifest.json">
    <meta name="theme-color" content="#4A90E2">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Nunito', Arial, sans-serif;
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .wifi-icon {
            width: 80px; height: 80px;
            background: #f0f2f5;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 24px;
        }
        h1 { color: #1C1E21; font-size: 1.5rem; margin-bottom: 10px; }
        p  { color: #65676B; line-height: 1.6; margin-bottom: 24px; }
        .logo { font-size: 1.1rem; font-weight: 800; color: #4A90E2; margin-bottom: 32px; }
        .logo span { color: #7ED321; }
        .btn {
            background: #4A90E2; color: white; border: none;
            padding: 14px 32px; border-radius: 10px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            width: 100%; transition: all 0.2s;
        }
        .btn:hover { background: #357ABD; transform: translateY(-1px); }
        .cached-pages { margin-top: 24px; text-align: left; }
        .cached-pages h4 { font-size: 0.85rem; color: #888; margin-bottom: 12px; text-transform: uppercase; }
        .page-link { display: flex; align-items: center; gap: 10px; padding: 10px; background: #f8f9fa;
                     border-radius: 8px; margin-bottom: 6px; color: #4A90E2; text-decoration: none;
                     font-weight: 600; font-size: 0.92rem; }
        .page-link:hover { background: #EBF4FF; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Red<span>Amigos</span></div>
        <div class="wifi-icon">📡</div>
        <h1>Sense connexió</h1>
        <p>No podem connectar ara mateix. Comprova la teva connexió a internet i torna-ho a intentar.</p>
        <button class="btn" onclick="tryReconnect()">
            🔄 Reintentar connexió
        </button>
        <div class="cached-pages" id="cached-links" style="display:none;">
            <h4>Pàgines disponibles offline</h4>
            <a href="/amigos/timeline.php" class="page-link">🏠 Inici</a>
            <a href="/amigos/mensajes.php" class="page-link">💬 Missatges</a>
            <a href="/amigos/feed.php" class="page-link">👥 Amics</a>
        </div>
    </div>
    <script>
        document.getElementById('cached-links').style.display = 'block';
        function tryReconnect() {
            const btn = document.querySelector('.btn');
            btn.textContent = '⏳ Connectant...';
            btn.disabled = true;
            fetch('/amigos/index.php', { method: 'HEAD', cache: 'no-store' })
                .then(() => { window.location.href = '/amigos/timeline.php'; })
                .catch(() => {
                    btn.textContent = '🔄 Reintentar connexió';
                    btn.disabled = false;
                    alert('Encara sense connexió. Intenta-ho més tard.');
                });
        }
    </script>
</body>
</html>
