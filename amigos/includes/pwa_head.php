<?php
/**
 * PWA HEAD - RedAmigos
 * Inclou totes les meta tags PWA, manifest, icones i splash screen
 * 
 * Ús: require_once "includes/pwa_head.php";
 * Variables opcionals: $page_title (string)
 */

$page_title = isset($page_title) ? htmlspecialchars($page_title) . ' · RedAmigos' : 'RedAmigos';

// Detectar el base path (per admin/ o arrel)
$is_admin   = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false);
$base_path  = '/amigos';
$assets     = $base_path . '/assets';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $page_title ?></title>

    <!-- ── PWA Manifest ── -->
    <link rel="manifest" href="<?= $base_path ?>/manifest.json">

    <!-- ── Theme colors ── -->
    <meta name="theme-color" content="#4A90E2" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#357ABD" media="(prefers-color-scheme: dark)">

    <!-- ── iOS PWA ── -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RedAmigos">
    <link rel="apple-touch-icon" href="<?= $assets ?>/img/apple-touch-icon.png">
    <!-- Splash screens iOS -->
    <link rel="apple-touch-startup-image" media="(device-width:320px) and (device-height:568px) and (-webkit-device-pixel-ratio:2)"  href="<?= $assets ?>/img/splash-640x1136.png">
    <link rel="apple-touch-startup-image" media="(device-width:375px) and (device-height:667px) and (-webkit-device-pixel-ratio:2)"  href="<?= $assets ?>/img/splash-750x1334.png">
    <link rel="apple-touch-startup-image" media="(device-width:375px) and (device-height:812px) and (-webkit-device-pixel-ratio:3)"  href="<?= $assets ?>/img/splash-1125x2436.png">
    <link rel="apple-touch-startup-image" media="(device-width:414px) and (device-height:896px) and (-webkit-device-pixel-ratio:3)"  href="<?= $assets ?>/img/splash-1242x2688.png">
    <link rel="apple-touch-startup-image" media="(device-width:414px) and (device-height:896px) and (-webkit-device-pixel-ratio:2)"  href="<?= $assets ?>/img/splash-828x1792.png">

    <!-- ── Android / Chrome ── -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="RedAmigos">

    <!-- ── Icones i favicon ── -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $assets ?>/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= $assets ?>/img/icon-192x192.png">

    <!-- ── SEO / OG bàsic (xarxa tancada = no-index) ── -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="RedAmigos - La teva xarxa social privada">

    <!-- ── Fonts i icones ── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- ── CSS principal ── -->
    <link rel="stylesheet" href="<?= $assets ?>/css/styles.css">

    <!-- ── SPLASH SCREEN ── -->
    <style>
        #ra-splash {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: linear-gradient(160deg, #4A90E2 0%, #2a6fc4 60%, #1d5ba8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        #ra-splash.hiding {
            opacity: 0;
            transform: scale(1.04);
            pointer-events: none;
        }
        .splash-logo-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px;
            animation: splash-pulse 1.8s ease-in-out infinite;
            position: relative;
        }
        .splash-logo-ring::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.2);
            animation: splash-ring 1.8s ease-in-out infinite 0.3s;
        }
        .splash-logo-inner {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: white;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .splash-logo-inner img {
            width: 52px; height: 52px;
            border-radius: 50%;
        }
        .splash-name {
            font-family: 'Nunito', Arial, sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .splash-name span { color: #7ED321; }
        .splash-tagline {
            font-family: 'Nunito', Arial, sans-serif;
            font-size: 0.95rem;
            color: rgba(255,255,255,0.75);
            margin-bottom: 48px;
        }
        .splash-loader {
            width: 160px; height: 3px;
            background: rgba(255,255,255,0.2);
            border-radius: 99px;
            overflow: hidden;
        }
        .splash-loader-bar {
            height: 100%;
            background: white;
            border-radius: 99px;
            animation: splash-load 1.4s ease-in-out forwards;
        }
        @keyframes splash-pulse {
            0%,100% { transform: scale(1); }
            50%      { transform: scale(1.06); }
        }
        @keyframes splash-ring {
            0%,100% { transform: scale(1); opacity: 0.5; }
            50%      { transform: scale(1.15); opacity: 0; }
        }
        @keyframes splash-load {
            0%   { width: 0%; }
            60%  { width: 80%; }
            100% { width: 100%; }
        }
    </style>
</head>
<?php
// Injectar la splash screen i el registre del SW just després de <body>
// (cridar ra_splash_body() en el body de cada pàgina)
function ra_splash_body(bool $show = true): void {
    global $assets, $base_path;
    if (!$show) return;
    ?>
    <!-- SPLASH SCREEN -->
    <div id="ra-splash" aria-hidden="true" role="presentation">
        <div class="splash-logo-ring">
            <div class="splash-logo-inner">
                <img src="<?= $assets ?>/img/icon-192x192.png" alt="RedAmigos">
            </div>
        </div>
        <div class="splash-name">Red<span>Amigos</span></div>
        <div class="splash-tagline">Connectem persones, creem somriures</div>
        <div class="splash-loader">
            <div class="splash-loader-bar"></div>
        </div>
    </div>

    <!-- PWA + SW Registration -->
    <script>
    (function() {
        // ── Amagar splash ──
        const splash = document.getElementById('ra-splash');
        function hideSplash() {
            if (!splash) return;
            splash.classList.add('hiding');
            setTimeout(() => splash.remove(), 550);
        }
        // Amagar en DOMContentLoaded (ràpid) o màxim 2.2s
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => setTimeout(hideSplash, 700));
        } else {
            setTimeout(hideSplash, 400);
        }
        // Fallback absolut
        setTimeout(hideSplash, 2200);

        // ── Registrar Service Worker ──
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= $base_path ?>/sw.js', {
                    scope: '<?= $base_path ?>/'
                }).then(reg => {
                    console.log('[PWA] SW registrat correctament:', reg.scope);
                    // Comprovar actualitzacions
                    reg.addEventListener('updatefound', () => {
                        const newSW = reg.installing;
                        newSW.addEventListener('statechange', () => {
                            if (newSW.state === 'installed' && navigator.serviceWorker.controller) {
                                showUpdateBanner();
                            }
                        });
                    });
                }).catch(err => {
                    console.warn('[PWA] SW no registrat:', err);
                });
            });
        }

        // ── Banner d'actualització disponible ──
        function showUpdateBanner() {
            const banner = document.createElement('div');
            banner.style.cssText = `
                position:fixed;bottom:70px;left:50%;transform:translateX(-50%);
                background:#1C1E21;color:white;padding:12px 20px;border-radius:12px;
                font-family:Nunito,Arial,sans-serif;font-size:0.9rem;font-weight:600;
                z-index:9999;display:flex;align-items:center;gap:12px;
                box-shadow:0 8px 24px rgba(0,0,0,0.3);
            `;
            banner.innerHTML = `
                <span>🔄 Nova versió disponible</span>
                <button onclick="location.reload()" style="
                    background:#4A90E2;color:white;border:none;padding:6px 14px;
                    border-radius:8px;cursor:pointer;font-weight:700;font-size:0.85rem;">
                    Actualitzar
                </button>
            `;
            document.body.appendChild(banner);
        }

        // ── Banner "Afegir a pantalla d'inici" ──
        let deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', e => {
            e.preventDefault();
            deferredPrompt = e;
            // Mostrar botó d'instal·lació si hi ha un element #btn-install
            const btnInstall = document.getElementById('btn-install');
            if (btnInstall) {
                btnInstall.style.display = 'flex';
                btnInstall.addEventListener('click', () => {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(result => {
                        if (result.outcome === 'accepted') btnInstall.remove();
                        deferredPrompt = null;
                    });
                });
            }
        });
    })();
    </script>
    <?php
}
