<?php
require_once 'includes/hub-core.php';

if (hubIsLoggedIn()) { header('Location: index.php'); exit; }

$lang = $_COOKIE['akra_hub_lang'] ?? 'ca';
if (!array_key_exists($lang, getHubLangOptions())) $lang = 'ca';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hubLogin($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        // Si el client encara no té llengua guardada, adopta la triada abans d'entrar.
        $logged_client = hubCurrentClient();
        if (empty($logged_client['hub_lang'])) setClientHubLang($logged_client['id'], $lang);
        header('Location: index.php');
        exit;
    }
    $error = hubT('login_error', $lang);
}
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('login_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<div class="hub-login-wrap">
    <div class="hub-login-box">
        <div class="hub-login-lang-switch">
            <?php foreach (getHubLangOptions() as $key => $label): ?>
            <a href="set-lang.php?lang=<?= $key ?>&return_to=login.php" class="<?= $lang === $key ? 'active' : '' ?>"><?= strtoupper($key) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="hub-login-brand">
            <h1><span class="dot"></span>AKRA TECH STUDIO</h1>
            <p><?= htmlspecialchars(hubT('login_title', $lang)) ?></p>
        </div>

        <?php if (isset($_GET['disabled'])): ?>
        <div class="hub-alert hub-alert--error"><?= htmlspecialchars(hubT('login_disabled', $lang)) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="hub-alert hub-alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="hub-form-group">
                <label><?= htmlspecialchars(hubT('login_email', $lang)) ?></label>
                <input type="email" name="email" required autofocus placeholder="tu@empresa.es" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="hub-form-group">
                <label><?= htmlspecialchars(hubT('login_password', $lang)) ?></label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="hub-btn hub-btn--primary" style="width:100%;justify-content:center;padding:12px"><?= htmlspecialchars(hubT('login_submit', $lang)) ?></button>
        </form>
        <p style="text-align:center;font-size:.78rem;color:var(--h-muted);margin-top:20px">
            <?= htmlspecialchars(hubT('login_lost', $lang)) ?> <a href="mailto:hola@akratechstudio.es" style="color:var(--h-gold);font-weight:600">hola@akratechstudio.es</a>
        </p>
    </div>
</div>
</body></html>
