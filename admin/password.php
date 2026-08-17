<?php
require_once 'includes/core.php';
requireLogin();

$success = $error = '';
$multi_user = !empty(getUsers());
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$multi_user) {
    $current  = $_POST['current'] ?? '';
    $new      = $_POST['new'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    
    $auth = readData('auth');
    $hash = $auth['password_hash'] ?? ADMIN_PASSWORD_HASH;
    
    if (!password_verify($current, $hash)) {
        $error = 'La contrasenya actual és incorrecta.';
    } elseif (strlen($new) < 8) {
        $error = 'La nova contrasenya ha de tenir almenys 8 caràcters.';
    } elseif ($new !== $confirm) {
        $error = 'Les contrasenyes no coincideixen.';
    } else {
        writeData('auth', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        $success = 'Contrasenya actualitzada correctament.';
    }
}
$page_title = 'Canviar contrasenya';
$page_subtitle = 'Seguretat del panel d\'administració';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contrasenya · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<div style="max-width:480px">
<?php if ($success): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> <?= $error ?></div><?php endif; ?>

<?php if ($multi_user): ?>
<div class="card"><div style="padding:20px 24px;color:#6b7280;font-size:.88rem;line-height:1.6">
    ℹ️ Ja tens usuaris individuals creats, així que la contrasenya única ha deixat de fer-se servir per entrar.
    Per canviar la teua contrasenya ara, ves a <a href="users.php">👥 Usuaris</a> i edita el teu propi usuari.
</div></div>
<?php else: ?>

<div class="card">
    <div class="card-header"><div class="card-title">🔒 Canviar contrasenya</div></div>
    <div class="card-body">
    <form method="POST" class="form-grid">
        <div class="form-group">
            <label>Contrasenya actual</label>
            <input type="password" name="current" required>
        </div>
        <div class="form-group">
            <label>Nova contrasenya (mínim 8 caràcters)</label>
            <input type="password" name="new" minlength="8" required>
        </div>
        <div class="form-group">
            <label>Confirmar nova contrasenya</label>
            <input type="password" name="confirm" required>
        </div>
        <button type="submit" class="btn btn-primary">Actualitzar contrasenya</button>
    </form>
    </div>
</div>

<div class="card" style="margin-top:16px;background:#fffbeb;border-color:#fbbf24">
    <div class="card-body" style="font-size:.85rem;color:#92400e">
        <strong>⚠️ Contrasenya inicial:</strong> <code style="background:#fef3c7;padding:2px 8px;border-radius:4px">akra2024admin</code><br>
        <span style="margin-top:6px;display:block">Canvia-la <strong>immediatament</strong> abans de pujar al servidor en producció.</span>
    </div>
</div>
<?php endif; ?>
</div>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
