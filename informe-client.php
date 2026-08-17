<?php
// /informe-client.php — Accés privat del client a la seua auditoria.
// Cal el token de l'enllaç I les credencials (usuari + contrasenya).
require_once __DIR__ . '/admin/includes/core.php';

$token = sanitize($_GET['a'] ?? '');
$audit = $token ? getAuditByToken($token) : null;

// Tancar sessió d'esta auditoria
if (isset($_GET['logout']) && $token) {
    unset($_SESSION['audit_auth'][$token]);
    header('Location: informe-client.php?a=' . urlencode($token));
    exit;
}

$error = '';
$lang = $audit['lang'] ?? 'ca';
if ($audit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Petit fre anti-força bruta
    $_SESSION['audit_attempts'][$token] = ($_SESSION['audit_attempts'][$token] ?? 0) + 1;
    if ($_SESSION['audit_attempts'][$token] > 8) {
        usleep(700000);
    }
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (verifyAuditAccess($audit, $username, $password)) {
        $_SESSION['audit_auth'][$token] = true;
        $_SESSION['audit_attempts'][$token] = 0;
        header('Location: informe-client.php?a=' . urlencode($token));
        exit;
    }
    $error = auditT('login_error', $lang);
}

$authenticated = $audit && !empty($_SESSION['audit_auth'][$token]) && !empty($audit['access_enabled']);

// Acceptació digital d'una proposta des del portal del client
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept_proposal') {
    $prop = getProposal($_POST['proposal_id'] ?? '');
    if ($prop && $prop['client_id'] === $audit['client_id'] && !in_array($prop['status'], ['aceptada', 'rechazada'])) {
        $prop['status']       = 'aceptada';
        $prop['accepted_at']  = date('Y-m-d H:i:s');
        $prop['accepted_ip']  = $_SERVER['REMOTE_ADDR'] ?? '';
        saveProposal($prop);
        advanceClientStage($prop['client_id'], 'guanyat');
    }
    header('Location: informe-client.php?a=' . urlencode($token)); exit;
}

if ($audit && $authenticated) {
    $client = getClient($audit['client_id']);
    if ($client) {
        $proposals = array_values(array_filter(getProposals(), fn($p) => $p['audit_id'] === $audit['id']));
        $view = 'client';
        include __DIR__ . '/admin/includes/audit-report-render.php';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accés privat · Informe d'auditoria · AKRA Tech Studio</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--ink:#0F172A;--ink2:#1E293B;--accent:#2563EB;--paper:#F8FAFC;--border:#E2E8F0;--muted:#64748B}
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;background:var(--ink);background-image:radial-gradient(circle at 20% 20%, rgba(37,99,235,.25), transparent 45%);padding:20px}
.box{background:#fff;border-radius:20px;padding:44px 40px;max-width:380px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35)}
.brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:var(--ink);text-align:center;margin-bottom:4px}
.brand small{display:block;font-family:'DM Sans',sans-serif;font-weight:500;font-size:.68rem;color:var(--accent);letter-spacing:.08em;text-transform:uppercase;margin-top:4px}
h1{font-family:'Syne',sans-serif;font-size:1.05rem;text-align:center;color:var(--ink);margin:22px 0 6px}
p.sub{text-align:center;color:var(--muted);font-size:.82rem;margin-bottom:26px;line-height:1.5}
.form-group{margin-bottom:14px}
label{display:block;font-size:.76rem;font-weight:600;color:var(--ink2);margin-bottom:5px}
input{width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:9px;font-size:.9rem;font-family:'DM Sans',sans-serif}
input:focus{outline:none;border-color:var(--accent)}
button{width:100%;padding:12px;border:none;border-radius:9px;background:var(--accent);color:#fff;font-weight:700;font-size:.88rem;cursor:pointer;margin-top:8px;font-family:'DM Sans',sans-serif}
button:hover{background:#1d4ed8}
.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.8rem;padding:10px 14px;border-radius:8px;margin-bottom:16px;text-align:center}
.notice{background:var(--paper);border:1px solid var(--border);color:var(--muted);font-size:.82rem;padding:16px;border-radius:10px;text-align:center;line-height:1.6}
.lock{text-align:center;font-size:2rem;margin-bottom:4px}
</style>
</head>
<body>
<div class="box">
    <div class="brand">AKRA<br>TECH STUDIO<small><?= htmlspecialchars(auditT('tagline', $lang)) ?></small></div>

    <?php if (!$audit): ?>
        <div class="lock">🔒</div>
        <h1><?= htmlspecialchars(auditT('link_invalid_h', $lang)) ?></h1>
        <div class="notice"><?= htmlspecialchars(auditT('link_invalid_p', $lang)) ?></div>
    <?php elseif (empty($audit['access_enabled'])): ?>
        <div class="lock">🔒</div>
        <h1><?= htmlspecialchars(auditT('access_off_h', $lang)) ?></h1>
        <div class="notice"><?= htmlspecialchars(auditT('access_off_p', $lang)) ?></div>
    <?php else: ?>
        <h1><?= htmlspecialchars(auditT('login_h1', $lang)) ?></h1>
        <p class="sub"><?= htmlspecialchars(auditT('login_sub', $lang)) ?></p>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label><?= htmlspecialchars(auditT('login_user', $lang)) ?></label>
                <input type="text" name="username" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label><?= htmlspecialchars(auditT('login_pass', $lang)) ?></label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit"><?= htmlspecialchars(auditT('login_btn', $lang)) ?></button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
