<?php
/**
 * INSTALAR.PHP — Porta d'entrada a la PWA
 * L'usuari ha d'introduir el seu codi personal per desbloquear
 * la pantalla d'instal·lació i accedir a l'app.
 */
session_start();
require_once "config.php";
require_once "includes/pwa_codigos.php";

// Si ja té sessió activa → anar directament
if (isset($_SESSION['usuario_id'])) {
    header("Location: timeline.php");
    exit();
}

$fase   = 'entrada';   // 'entrada' | 'desbloquejat'
$usuari = null;
$codi   = strtoupper(trim($_GET['c'] ?? $_POST['codi'] ?? ''));
$error  = '';

// ── Validar codi (POST o GET amb codi preomplert) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['c'])) {
    if (!empty($codi)) {
        $resultat = pwa_validar_codi($codi);
        if ($resultat['valid']) {
            $fase   = 'desbloquejat';
            $usuari = $resultat;
            // Guardar en sessió que el codi és vàlid (per al login posterior)
            $_SESSION['pwa_codi_validat']  = $codi;
            $_SESSION['pwa_usuario_id']    = $usuari['usuario_id'];
            // Registrar la instal·lació
            pwa_registrar_instalacio($usuari['usuario_id'], $codi);
        } else {
            $error = $resultat['error'];
        }
    }
}

// Si venim amb sessió de codi ja validat
if (isset($_SESSION['pwa_codi_validat']) && $fase !== 'desbloquejat') {
    $resultat = pwa_validar_codi($_SESSION['pwa_codi_validat']);
    if ($resultat['valid']) {
        $fase   = 'desbloquejat';
        $usuari = $resultat;
        $codi   = $_SESSION['pwa_codi_validat'];
    }
}

$page_title = 'Instal·lar RedAmigos';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= $page_title ?></title>

    <link rel="manifest" href="/amigos/manifest.json">
    <meta name="theme-color" content="#0f1923">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RedAmigos">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        html { height:100%; }

        body {
            font-family: 'Nunito', Arial, sans-serif;
            min-height: 100vh;
            background: #0f1923;
            display: flex;
            align-items: stretch;
            overflow-x: hidden;
        }

        /* ════ FONS ANIMAT ════ */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0; overflow: hidden;
        }
        .bg-orb {
            position: absolute; border-radius: 50%;
            filter: blur(80px); opacity: 0.15;
        }
        .orb-1 {
            width: 600px; height: 600px;
            background: #4A90E2;
            top: -200px; left: -150px;
            animation: orb-float 12s ease-in-out infinite;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: #7ED321;
            bottom: -100px; right: -100px;
            animation: orb-float 16s ease-in-out infinite reverse;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: #4A90E2;
            top: 50%; right: 20%;
            animation: orb-float 10s ease-in-out infinite 3s;
        }
        @keyframes orb-float {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(30px,-40px) scale(1.05); }
            66%      { transform: translate(-20px,20px) scale(0.97); }
        }
        /* Grid de punts de fons */
        .bg-grid {
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* ════ CONTENIDOR PRINCIPAL ════ */
        .install-wrap {
            position: relative; z-index: 1;
            width: 100%; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 30px 20px;
        }

        .install-card {
            width: 100%; max-width: 440px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 44px 40px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5);
            animation: card-in 0.5s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }
        @keyframes card-in {
            from { opacity:0; transform: translateY(30px) scale(0.97); }
            to   { opacity:1; transform: translateY(0)    scale(1); }
        }

        /* ── Logo ── */
        .logo {
            text-align: center;
            font-size: 1.5rem; font-weight: 900;
            color: #4A90E2; margin-bottom: 6px;
        }
        .logo span { color: #7ED321; }
        .tagline {
            text-align: center;
            font-size: 0.82rem; color: rgba(255,255,255,0.4);
            margin-bottom: 32px; letter-spacing: 0.3px;
        }

        /* ── Icona central ── */
        .center-icon {
            width: 88px; height: 88px;
            margin: 0 auto 24px;
            position: relative;
        }
        .center-icon img {
            width: 100%; height: 100%;
            border-radius: 22px;
            box-shadow: 0 12px 40px rgba(74,144,226,0.4);
        }
        .center-icon .lock-badge {
            position: absolute; bottom: -6px; right: -6px;
            width: 30px; height: 30px;
            background: #1e293b; border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; color: #fbbf24;
            transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .center-icon .lock-badge.unlocked {
            background: #14532d; color: #86efac;
            border-color: #22c55e;
            transform: scale(1.2) rotate(-15deg);
        }

        /* ── Títols ── */
        .card-title {
            text-align: center; color: white;
            font-size: 1.35rem; font-weight: 800;
            margin-bottom: 8px;
        }
        .card-subtitle {
            text-align: center; color: rgba(255,255,255,0.45);
            font-size: 0.88rem; line-height: 1.5;
            margin-bottom: 28px;
        }

        /* ── Input codi ── */
        .codi-input-wrap {
            position: relative; margin-bottom: 8px;
        }
        .codi-input {
            width: 100%;
            padding: 18px 56px 18px 20px;
            font-size: 1.5rem; font-weight: 800;
            letter-spacing: 5px; text-align: center;
            text-transform: uppercase;
            background: rgba(255,255,255,0.06);
            border: 2px solid rgba(255,255,255,0.12);
            border-radius: 14px; color: white;
            font-family: 'Courier New', monospace;
            transition: all 0.25s;
        }
        .codi-input:focus {
            outline: none;
            border-color: #4A90E2;
            background: rgba(74,144,226,0.1);
            box-shadow: 0 0 0 4px rgba(74,144,226,0.15);
        }
        .codi-input::placeholder {
            color: rgba(255,255,255,0.2);
            letter-spacing: 3px;
        }
        .codi-input.valid {
            border-color: #22c55e;
            background: rgba(34,197,94,0.08);
            box-shadow: 0 0 0 4px rgba(34,197,94,0.12);
        }
        .codi-input.invalid {
            border-color: #ef4444;
            background: rgba(239,68,68,0.08);
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-8px); }
            40%      { transform: translateX(8px); }
            60%      { transform: translateX(-5px); }
            80%      { transform: translateX(5px); }
        }
        .codi-status-icon {
            position: absolute; right: 18px; top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem; transition: all 0.3s;
            color: rgba(255,255,255,0.3);
        }
        .codi-status-icon.checking { color: #4A90E2; animation: spin 0.7s linear infinite; }
        .codi-status-icon.ok       { color: #22c55e; }
        .codi-status-icon.ko       { color: #ef4444; }
        @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }

        .codi-hint {
            text-align: center; font-size: 0.75rem;
            color: rgba(255,255,255,0.25); margin-bottom: 20px;
            font-family: monospace; letter-spacing: 1px;
        }

        /* ── Alert error ── */
        .alert-error-dark {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px; color: #fca5a5;
            padding: 12px 16px; font-size: 0.88rem;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }

        /* ── Botó principal ── */
        .btn-main {
            width: 100%; padding: 16px;
            border: none; border-radius: 14px;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem; font-weight: 800;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-main:hover { transform: translateY(-2px); }
        .btn-main:active { transform: translateY(0); }
        .btn-main.primary {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            box-shadow: 0 8px 24px rgba(74,144,226,0.35);
        }
        .btn-main.primary:hover { box-shadow: 0 12px 32px rgba(74,144,226,0.5); }
        .btn-main.success {
            background: linear-gradient(135deg, #7ED321, #6BC318);
            color: white;
            box-shadow: 0 8px 24px rgba(126,211,33,0.35);
        }
        .btn-main.secondary {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-main.secondary:hover { background: rgba(255,255,255,0.1); color: white; }

        /* ── Fase desbloquejada ── */
        .welcome-banner {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 14px; padding: 18px;
            margin-bottom: 24px; text-align: center;
        }
        .welcome-name {
            font-size: 1.15rem; font-weight: 800; color: #86efac;
            margin-bottom: 4px;
        }
        .welcome-sub {
            font-size: 0.82rem; color: rgba(255,255,255,0.45);
        }

        /* ── Instruccions instal·lació ── */
        .install-steps {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; padding: 18px 20px;
            margin-bottom: 20px;
        }
        .install-steps h4 {
            color: rgba(255,255,255,0.6); font-size: 0.78rem;
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 14px;
        }
        .install-step {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .install-step:last-child { border-bottom: none; }
        .step-num {
            width: 24px; height: 24px; flex-shrink: 0;
            background: rgba(74,144,226,0.2);
            border: 1px solid rgba(74,144,226,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 800; color: #4A90E2;
        }
        .step-txt { font-size: 0.88rem; color: rgba(255,255,255,0.6); line-height: 1.4; }
        .step-txt strong { color: rgba(255,255,255,0.85); }

        /* Botó instal·lar PWA ocult fins que el navegador el deixa */
        #btn-pwa-install { display: none; }

        /* ── iOS instructions (mostrades quan no es pot instal·lar automàticament) ── */
        .ios-hint {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; padding: 14px 16px;
            font-size: 0.82rem; color: rgba(255,255,255,0.5);
            text-align: center; margin-top: 12px;
            display: none;
        }
        .ios-hint strong { color: rgba(255,255,255,0.75); }

        /* ── Peu ── */
        .card-footer {
            margin-top: 22px; text-align: center;
            font-size: 0.8rem; color: rgba(255,255,255,0.25);
        }
        .card-footer a { color: rgba(255,255,255,0.35); }
        .card-footer a:hover { color: rgba(255,255,255,0.6); }

        /* Spinner */
        .spinner-sm {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        @media (max-width: 480px) {
            .install-card { padding: 32px 24px; border-radius: 20px; }
            .codi-input { font-size: 1.2rem; letter-spacing: 3px; }
        }
    </style>
</head>
<body>

<!-- Fons animat -->
<div class="bg-scene">
    <div class="bg-grid"></div>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>
</div>

<div class="install-wrap">
<div class="install-card">

    <!-- Logo -->
    <div class="logo">Red<span>Amigos</span></div>
    <div class="tagline">La teva xarxa social privada</div>

    <!-- Icona de l'app -->
    <div class="center-icon">
        <img src="assets/img/icon-192x192.png" alt="RedAmigos">
        <div class="lock-badge <?= $fase === 'desbloquejat' ? 'unlocked' : '' ?>" id="lock-badge">
            <i class="fas <?= $fase === 'desbloquejat' ? 'fa-unlock' : 'fa-lock' ?>" id="lock-icon"></i>
        </div>
    </div>

    <?php if ($fase === 'entrada'): ?>
    <!-- ══════════════════════════
         FASE 1: Entrada del codi
    ══════════════════════════ -->

        <h1 class="card-title">Accés personal</h1>
        <p class="card-subtitle">
            Introdueix el teu codi personal per<br>
            desbloquear la instal·lació de l'app.
        </p>

        <?php if ($error): ?>
            <div class="alert-error-dark">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="form-codi" autocomplete="off">
            <div class="codi-input-wrap">
                <input
                    type="text"
                    name="codi"
                    id="codi-input"
                    class="codi-input <?= $error ? 'invalid' : '' ?>"
                    placeholder="XX-0000-XX"
                    maxlength="10"
                    required
                    autofocus
                    spellcheck="false"
                    autocorrect="off"
                    autocapitalize="characters"
                    value="<?= htmlspecialchars($codi) ?>">
                <i class="fas fa-key codi-status-icon" id="status-icon"></i>
            </div>
            <div class="codi-hint">Format: 2 lletres – 4 números – 2 lletres</div>

            <button type="submit" class="btn-main primary" id="btn-submit">
                <i class="fas fa-unlock-alt"></i>
                Validar codi
            </button>
        </form>

        <div style="margin-top:16px;">
            <a href="login.php" class="btn-main secondary">
                <i class="fas fa-sign-in-alt"></i>
                Ja tinc l'app instal·lada · Iniciar sessió
            </a>
        </div>

        <div class="card-footer">
            No tens codi? Contacta amb l'administrador<br>
            o truca al <a href="tel:900123456">900 123 456</a>
        </div>

    <?php else: ?>
    <!-- ══════════════════════════
         FASE 2: Codi vàlid → Instal·lar
    ══════════════════════════ -->

        <!-- Benvinguda personalitzada -->
        <div class="welcome-banner">
            <div class="welcome-name">
                <i class="fas fa-check-circle"></i>
                Hola, <?= htmlspecialchars($usuari['nombre']) ?>! 👋
            </div>
            <div class="welcome-sub">Codi verificat correctament</div>
        </div>

        <h1 class="card-title">Instal·la l'app</h1>
        <p class="card-subtitle">
            Afegeix RedAmigos a la teva pantalla d'inici<br>
            per accedir-hi com una app nativa.
        </p>

        <!-- Instruccions instal·lació -->
        <div class="install-steps">
            <h4><i class="fas fa-mobile-alt" style="margin-right:6px;"></i>Com instal·lar</h4>
            <div class="install-step">
                <div class="step-num">1</div>
                <div class="step-txt">
                    Prem el botó <strong>«Instal·lar RedAmigos»</strong> de sota
                </div>
            </div>
            <div class="install-step">
                <div class="step-num">2</div>
                <div class="step-txt">
                    Confirma a la finestra del navegador
                </div>
            </div>
            <div class="install-step">
                <div class="step-num">3</div>
                <div class="step-txt">
                    Obre l'app des de la <strong>pantalla d'inici</strong> i inicia sessió
                </div>
            </div>
        </div>

        <!-- Botó instal·lar PWA (s'activa via JS) -->
        <button class="btn-main success" id="btn-pwa-install">
            <i class="fas fa-download"></i>
            Instal·lar RedAmigos
        </button>

        <!-- Instruccions iOS (quan no hi ha beforeinstallprompt) -->
        <div class="ios-hint" id="ios-hint">
            <strong>Al Safari d'iPhone/iPad:</strong><br>
            Prem <strong>Compartir <i class="fas fa-share-from-square"></i></strong>
            → <strong>«Afegir a pantalla d'inici»</strong>
        </div>

        <!-- Accés directe sense instal·lar -->
        <a href="login.php" class="btn-main secondary" style="margin-top:12px;">
            <i class="fas fa-sign-in-alt"></i>
            Continuar sense instal·lar · Iniciar sessió
        </a>

        <div class="card-footer" style="margin-top:16px;">
            El teu codi: <code style="color:rgba(255,255,255,0.5);letter-spacing:2px;"><?= htmlspecialchars($codi) ?></code><br>
            <a href="instalar.php" style="margin-top:6px;display:inline-block;">Sortir</a>
        </div>

    <?php endif; ?>

</div><!-- /.install-card -->
</div><!-- /.install-wrap -->

<script>
(function() {
    // ══ Registrar SW ══
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/amigos/sw.js', { scope: '/amigos/' })
            .catch(e => console.warn('[SW]', e));
    }

    <?php if ($fase === 'entrada'): ?>
    // ══ Format automàtic del codi ══
    const input      = document.getElementById('codi-input');
    const statusIcon = document.getElementById('status-icon');
    const btnSubmit  = document.getElementById('btn-submit');
    let checkTimer   = null;

    input.addEventListener('input', function() {
        let v = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let fmt = '';
        for (let i = 0; i < Math.min(v.length, 8); i++) {
            if (i === 2) fmt += '-';
            if (i === 6) fmt += '-';
            fmt += v[i];
        }
        this.value = fmt;
        this.classList.remove('valid', 'invalid');
        statusIcon.className = 'fas fa-key codi-status-icon';
        statusIcon.style.color = '';

        if (fmt.length === 10) verifyAjax(fmt);
    });

    function verifyAjax(codi) {
        clearTimeout(checkTimer);
        statusIcon.className = 'fas fa-circle-notch codi-status-icon checking';
        checkTimer = setTimeout(async () => {
            try {
                const r = await fetch('api_verificar_pwa.php?c=' + encodeURIComponent(codi));
                const d = await r.json();
                input.classList.remove('valid', 'invalid');
                if (d.valid) {
                    input.classList.add('valid');
                    statusIcon.className = 'fas fa-check-circle codi-status-icon ok';
                    // Auto-submit si és vàlid
                    setTimeout(() => document.getElementById('form-codi').submit(), 400);
                } else {
                    input.classList.add('invalid');
                    statusIcon.className = 'fas fa-times-circle codi-status-icon ko';
                }
            } catch(e) {
                statusIcon.className = 'fas fa-key codi-status-icon';
            }
        }, 350);
    }

    // Loading en submit
    document.getElementById('form-codi').addEventListener('submit', function() {
        btnSubmit.innerHTML = '<div class="spinner-sm"></div> Verificant...';
        btnSubmit.disabled = true;
    });

    <?php else: ?>
    // ══ Fase 2: Gestionar instal·lació PWA ══
    let deferredPrompt = null;
    const btnInstall   = document.getElementById('btn-pwa-install');
    const iosHint      = document.getElementById('ios-hint');
    const lockBadge    = document.getElementById('lock-badge');
    const lockIcon     = document.getElementById('lock-icon');

    // Animar el cadenat
    lockBadge.classList.add('unlocked');
    lockIcon.className = 'fas fa-unlock';

    // Capturar event d'instal·lació (Chrome/Android)
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredPrompt = e;
        btnInstall.style.display = 'flex';
        iosHint.style.display    = 'none';
    });

    // Mostrar hint iOS si no hi ha prompt disponible (Safari)
    setTimeout(() => {
        if (!deferredPrompt) {
            const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
            if (isIos || isSafari) {
                iosHint.style.display = 'block';
            } else if (!window.matchMedia('(display-mode: standalone)').matches) {
                // En Chrome però sense prompt → mostrar instruccions manuals
                btnInstall.style.display = 'flex';
                btnInstall.innerHTML = '<i class="fas fa-bars"></i> Instal·la des del menú del navegador';
                btnInstall.onclick = () => {
                    iosHint.style.display = 'block';
                    iosHint.innerHTML = '<strong>Al Chrome:</strong><br>Prem els <strong>tres punts ⋮</strong> → <strong>«Afegir a pantalla d\'inici»</strong>';
                };
            }
        }
    }, 1500);

    btnInstall.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        btnInstall.innerHTML = '<div class="spinner-sm"></div> Instal·lant...';
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        deferredPrompt = null;
        if (outcome === 'accepted') {
            btnInstall.innerHTML = '<i class="fas fa-check-circle"></i> Instal·lat!';
            btnInstall.style.background = 'linear-gradient(135deg,#14532d,#166534)';
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        } else {
            btnInstall.innerHTML = '<i class="fas fa-download"></i> Instal·lar RedAmigos';
        }
    });

    // Si ja està instal·lat (mode standalone), redirigir directament
    if (window.matchMedia('(display-mode: standalone)').matches) {
        window.location.href = 'login.php';
    }
    <?php endif; ?>
})();
</script>
</body>
</html>
