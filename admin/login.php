<?php
// admin/login.php
require_once 'includes/core.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($password, $username)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Contrasenya incorrecta';
    sleep(1); // Protecció brute force
}
$has_users = !empty(getUsers());
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · AKRA Tech Studio</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #0f0f0f; --navy2: #1a1a1a; --gold: #d4d4d8; --gold-l: #e4e4e7;
            --white: #fff; --off: #f7f6f3; --muted: #8892a4; --border: rgba(0,0,0,0.08);
            --f-display: 'Syne', sans-serif; --f-body: 'DM Sans', sans-serif;
        }
        body {
            font-family: var(--f-body);
            background: var(--navy);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        .bg-orb {
            position: fixed; border-radius: 50%; filter: blur(100px); pointer-events: none;
        }
        .bg-orb-1 { width: 400px; height: 400px; background: rgba(201,168,76,0.06); top: -10%; right: 0; }
        .bg-orb-2 { width: 350px; height: 350px; background: rgba(59,130,246,0.04); bottom: 0; left: -5%; }

        .login-card {
            position: relative; z-index: 1;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 48px;
            width: 100%; max-width: 420px;
            box-shadow: 0 40px 80px rgba(0,0,0,0.3);
        }
        .login-logo {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 32px;
        }
        .login-logo svg { color: var(--gold); }
        .login-logo-text {
            font-family: var(--f-display); font-weight: 800;
            font-size: 1.2rem; color: white; line-height: 1.1;
        }
        .login-logo-text span { display: block; font-size: 0.6rem; color: var(--gold); letter-spacing: 0.12em; text-transform: uppercase; font-weight: 600; }
        h1 { font-family: var(--f-display); font-size: 1.5rem; font-weight: 800; color: white; margin-bottom: 6px; }
        .subtitle { color: rgba(255,255,255,0.45); font-size: 0.9rem; margin-bottom: 36px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: rgba(255,255,255,0.55); letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 8px; }
        input[type="password"] {
            width: 100%; padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 12px; font-family: var(--f-body); font-size: 1rem;
            color: white; outline: none; transition: all 0.2s;
        }
        input[type="password"]:focus { border-color: var(--gold); background: rgba(255,255,255,0.08); }
        input[type="password"]::placeholder { color: rgba(255,255,255,0.2); }
        
        .btn-login {
            width: 100%; padding: 15px;
            background: var(--gold); color: #0f0f0f;
            border: none; border-radius: 12px;
            font-family: var(--f-display); font-size: 1rem; font-weight: 800;
            cursor: pointer; transition: all 0.2s; letter-spacing: 0.02em;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover { background: var(--gold-l); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        
        .error-msg {
            background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5; padding: 12px 16px; border-radius: 10px;
            font-size: 0.88rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
        }
        .version { text-align: center; margin-top: 24px; font-size: 0.75rem; color: rgba(255,255,255,0.15); }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <div class="login-card">
        <div class="login-logo">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                <rect width="36" height="36" rx="8" fill="currentColor" fill-opacity="0.15"/>
                <path d="M8 28L18 8L28 28" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.5 22H24.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <div class="login-logo-text">
                AKRA Tech Studio
                <span>Panel d'administració</span>
            </div>
        </div>

        <h1>Accés restringit</h1>
        <p class="subtitle">Introdueix la contrasenya per accedir al panel.</p>

        <?php if ($error): ?>
        <div class="error-msg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($has_users): ?>
            <div class="form-group">
                <label for="username">Usuari</label>
                <input type="text" id="username" name="username" placeholder="el.teu.usuari" autofocus required>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="password">Contrasenya</label>
                <input type="password" id="password" name="password" placeholder="••••••••••••" <?= $has_users ? '' : 'autofocus' ?> required>
            </div>
            <button type="submit" class="btn-login">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Entrar al panel
            </button>
        </form>
        <p class="version">AKRA Admin v<?= ADMIN_VERSION ?></p>
    </div>
</body>
</html>
