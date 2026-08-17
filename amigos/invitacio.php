<?php
/**
 * ENTRADA AMB CODI D'INVITACIÓ
 * L'usuari introdueix el seu codi únic per poder accedir al registre
 */
session_start();
require_once "config.php";
require_once "includes/codigos_helper.php";

// Si ja té sessió → dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: timeline.php");
    exit();
}

$error  = '';
$codi_preomplert = trim($_GET['codi'] ?? '');

// Processar enviament del formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codi = strtoupper(trim($_POST['codi'] ?? ''));
    $validacio = ra_validar_codigo($codi);
    
    if ($validacio['valid']) {
        // Guardar codi en sessió i redirigir al registre
        $_SESSION['codi_invitacio']       = $codi;
        $_SESSION['codi_invitacio_valid'] = true;
        header("Location: register.php");
        exit();
    } else {
        $error = $validacio['error'];
    }
}

$page_title = 'Accés amb codi d\'invitació';
require_once "includes/pwa_head.php";
?>
<html lang="ca">
<body class="auth-page">
<?php ra_splash_body(false); // No splash en aquesta pàgina ?>

<style>
/* Pantalla d'entrada única */
.invite-wrap {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(160deg, #1a2a4a 0%, #1e3a6e 40%, #4A90E2 100%);
    position: relative;
    overflow: hidden;
}
/* Decoració de fons */
.invite-wrap::before,
.invite-wrap::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    opacity: 0.06;
    background: white;
}
.invite-wrap::before {
    width: 500px; height: 500px;
    top: -150px; left: -100px;
}
.invite-wrap::after {
    width: 400px; height: 400px;
    bottom: -100px; right: -80px;
}

.invite-inner {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    position: relative;
    z-index: 1;
}

.invite-card {
    background: white;
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 420px;
    width: 100%;
    box-shadow: 0 24px 80px rgba(0,0,0,0.35);
    text-align: center;
}

.invite-logo {
    font-family: 'Nunito', Arial, sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 6px;
}
.invite-logo span { color: var(--accent); }

.invite-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, var(--primary-light), #d4e8ff);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    margin: 20px auto;
    position: relative;
}
.invite-icon::after {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    border: 2px dashed var(--primary);
    opacity: 0.4;
    animation: spin-slow 10s linear infinite;
}
@keyframes spin-slow { to { transform: rotate(360deg); } }

.invite-card h1 {
    font-size: 1.4rem;
    font-weight: 800;
    margin-bottom: 8px;
    color: var(--text);
}
.invite-card .subtitle {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 32px;
    line-height: 1.5;
}

/* Input del codi */
.code-input {
    width: 100%;
    padding: 18px 20px;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: 4px;
    text-align: center;
    text-transform: uppercase;
    border: 2.5px solid var(--border);
    border-radius: var(--radius-md);
    font-family: 'Courier New', monospace;
    color: var(--text);
    background: var(--bg);
    transition: var(--transition);
    margin-bottom: 6px;
}
.code-input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(74,144,226,0.15);
}
.code-input.valid   { border-color: var(--success); background: #f0fff4; }
.code-input.invalid { border-color: var(--danger);  background: #fff0f0; }

.code-hint {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 24px;
    font-family: monospace;
}

.divider {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0;
    color: var(--text-muted);
    font-size: 0.85rem;
}
.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

.invite-footer {
    text-align: center;
    padding: 20px;
    position: relative;
    z-index: 1;
}
.invite-footer a { color: rgba(255,255,255,0.7); font-size: 0.88rem; }
.invite-footer a:hover { color: white; }

/* Animació de verificació */
@keyframes check-in {
    from { transform: scale(0.5) rotate(-10deg); opacity: 0; }
    to   { transform: scale(1)   rotate(0deg);   opacity: 1; }
}
.check-anim { animation: check-in 0.3s ease forwards; }
</style>

<div class="invite-wrap">
    <div class="invite-inner">
        <div class="invite-card fade-in">

            <!-- Logo -->
            <div class="invite-logo">Red<span>Amigos</span></div>

            <!-- Icona -->
            <div class="invite-icon">🔑</div>

            <h1>Accés per invitació</h1>
            <p class="subtitle">
                RedAmigos és una xarxa <strong>privada i tancada</strong>.<br>
                Per registrar-te necessites un codi d'invitació personal.
            </p>

            <!-- Error -->
            <?php if ($error): ?>
                <div class="alert alert-error" style="text-align:left;">
                    <i class="fas fa-times-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulari -->
            <form method="POST" id="codi-form" autocomplete="off">
                <input
                    type="text"
                    name="codi"
                    id="codi-input"
                    class="code-input <?= $error ? 'invalid' : '' ?>"
                    placeholder="XXXX-XXXX-XXXX"
                    maxlength="14"
                    required
                    autofocus
                    spellcheck="false"
                    autocorrect="off"
                    autocapitalize="characters"
                    value="<?= htmlspecialchars($codi_preomplert) ?>">
                <div class="code-hint">Format: 4 lletres · guió · 4 lletres · guió · 4 lletres</div>

                <button type="submit" class="btn btn-primary btn-full btn-lg" id="btn-validar">
                    <i class="fas fa-unlock-alt"></i> Validar codi
                </button>
            </form>

            <div class="divider">o</div>

            <a href="login.php" class="btn btn-secondary btn-full">
                <i class="fas fa-sign-in-alt"></i> Ja tinc compte · Entrar
            </a>

            <p style="margin-top:20px; font-size:0.82rem; color:var(--text-muted);">
                <i class="fas fa-info-circle"></i>
                Necessites un codi? Contacta amb un membre de la comunitat
                o truca'ns al <a href="tel:900123456"><strong>900 123 456</strong></a>.
            </p>
        </div>
    </div>

    <div class="invite-footer">
        <a href="login.php">Ja sóc membre · Iniciar sessió</a>
    </div>
</div>

<script>
(function () {
    const input  = document.getElementById('codi-input');
    const form   = document.getElementById('codi-form');
    const btn    = document.getElementById('btn-validar');

    // ── Format automàtic XXXX-XXXX-XXXX ──
    input.addEventListener('input', function () {
        let val = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        let formatted = '';
        for (let i = 0; i < Math.min(val.length, 12); i++) {
            if (i === 4 || i === 8) formatted += '-';
            formatted += val[i];
        }
        this.value = formatted;
        this.classList.remove('valid', 'invalid');

        // Verificació visual en temps real (longitud completa)
        if (formatted.length === 14) {
            verifyCode(formatted);
        }
    });

    // Verificació AJAX en temps real
    let verifyTimer = null;
    function verifyCode(codi) {
        clearTimeout(verifyTimer);
        verifyTimer = setTimeout(async () => {
            try {
                const r = await fetch('api_verificar_codi.php?codi=' + encodeURIComponent(codi));
                const d = await r.json();
                input.classList.remove('valid', 'invalid');
                if (d.valid) {
                    input.classList.add('valid');
                    btn.innerHTML = '<i class="fas fa-check-circle check-anim"></i> Codi vàlid · Continuar';
                    btn.style.background = 'var(--success)';
                } else {
                    input.classList.add('invalid');
                    btn.innerHTML = '<i class="fas fa-unlock-alt"></i> Validar codi';
                    btn.style.background = '';
                }
            } catch (e) {}
        }, 300);
    }

    // Loading en submit
    form.addEventListener('submit', function () {
        btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Verificant...';
        btn.disabled = true;
    });

    // Focus automàtic
    if (!input.value) input.focus();
})();
</script>

</body>
</html>
