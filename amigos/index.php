<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: timeline.php");
    exit();
}
$page_title = 'Iniciar sessió';
?>
<?php require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body class="auth-page">
<?php ra_splash_body(); ?>

<div class="auth-wrap">
    <div class="auth-logo-bar">
        <div class="auth-logo">Red<span>Amigos</span></div>
        <div class="auth-tagline">Connectem persones, creem somriures</div>
    </div>

    <div class="auth-card">
        <h2 class="auth-title">Benvingut/da! 👋</h2>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Has tancat la sessió correctament.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="login-form">
            <div class="form-group">
                <label class="form-label" for="email">Correu electrònic</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="el.teu@email.com" required autocomplete="username">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Contrasenya</label>
                <div class="input-icon-wrap" style="position:relative;">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="La teva contrasenya" required autocomplete="current-password"
                           style="padding-right:48px;">
                    <button type="button" onclick="togglePwd()" style="
                        position:absolute;right:14px;top:50%;transform:translateY(-50%);
                        background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;" id="eye-btn">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" id="login-btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; display:flex; flex-direction:column; gap:10px;">
            <a href="recuperar_password.php" style="color:var(--text-muted); font-size:0.9rem;">
                <i class="fas fa-key"></i> Has oblidat la contrasenya?
            </a>
            <a href="register.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i> Crea un compte nou
            </a>
        </div>

        <!-- Botó instal·lar PWA (ocult fins que el navegador dispari l'event) -->
        <button id="btn-install" style="
            display:none; margin-top:16px; width:100%; background:var(--accent);
            color:white; border:none; padding:12px 20px; border-radius:10px;
            font-family:var(--font); font-size:0.95rem; font-weight:700;
            cursor:pointer; align-items:center; justify-content:center; gap:8px;">
            <i class="fas fa-download"></i> Instal·lar RedAmigos
        </button>

        <p style="text-align:center; margin-top:28px; color:var(--text-muted); font-size:0.88rem; border-top:1px solid var(--border); padding-top:18px;">
            <i class="fas fa-question-circle"></i> Necessites ajuda?<br>
            Truca'ns al <a href="tel:900123456"><strong>900 123 456</strong></a>
        </p>
    </div>
</div>

<script>
function togglePwd() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
// Loading feedback en submit
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Entrant...';
    btn.disabled = true;
});
</script>
</body>
</html>
