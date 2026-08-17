<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require "config.php";

$error = '';
$max_intentos = 5;
$tiempo_bloqueo = 300; // 5 minutos

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos";
    } else {
        // Rate limiting per IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql = "SELECT intentos_fallidos, ultimo_intento 
                FROM intentos_login 
                WHERE ip = :ip";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['ip' => $ip]);
        $intento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $bloquejat = false;
        if ($intento) {
            $temps_transcorregut = time() - strtotime($intento['ultimo_intento']);
            
            if ($intento['intentos_fallidos'] >= $max_intentos && $temps_transcorregut < $tiempo_bloqueo) {
                $temps_restant = ceil(($tiempo_bloqueo - $temps_transcorregut) / 60);
                $error = "Demasiados intentos fallidos. Espera {$temps_restant} minutos.";
                $bloquejat = true;
            } elseif ($temps_transcorregut >= $tiempo_bloqueo) {
                $sql = "UPDATE intentos_login SET intentos_fallidos = 0 WHERE ip = :ip";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['ip' => $ip]);
            }
        }
        
        if (!$bloquejat) {
            $sql = "SELECT id, nombre, email, password, activo, aprobado 
                    FROM usuarios 
                    WHERE email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":email" => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                if (!$usuario['aprobado']) {
                    $error = "Tu cuenta está pendiente de aprobación. Te avisaremos por email cuando sea aprobada.";
                } elseif (!$usuario['activo']) {
                    $error = "Tu cuenta está desactivada. Contacta con soporte.";
                } else {
                    // Login correcte
                    // Reset intentos
                    $sql = "DELETE FROM intentos_login WHERE ip = :ip";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['ip' => $ip]);
                    
                    session_regenerate_id(true);
                    
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['ultimo_acceso'] = time();
                    
                    $sql = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['id' => $usuario['id']]);
                    
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Email o contraseña incorrectos";
                
                if ($intento) {
                    $sql = "UPDATE intentos_login 
                            SET intentos_fallidos = intentos_fallidos + 1, 
                                ultimo_intento = NOW() 
                            WHERE ip = :ip";
                } else {
                    $sql = "INSERT INTO intentos_login (ip, intentos_fallidos, ultimo_intento) 
                            VALUES (:ip, 1, NOW())";
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['ip' => $ip]);
            }
        }
    }
}
?>
?>
<?php
$page_title = 'Iniciar sessió';
require_once "includes/pwa_head.php";
?>
<html lang="ca">
<body class="auth-page">
<?php ra_splash_body(); ?>

<div class="auth-wrap">
    <div class="auth-logo-bar">
        <div class="auth-logo">Red<span>Amigos</span></div>
        <div class="auth-tagline">Connectem persones, creem somriures</div>
    </div>
    <div class="auth-card">
        <h2 class="auth-title">Iniciar sessió</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form">
            <div class="form-group">
                <label class="form-label" for="email">Correu electrònic</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="el.teu@email.com" required autocomplete="username"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
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
                        background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg" id="login-btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;display:flex;flex-direction:column;gap:10px;">
            <a href="recuperar_password.php" style="color:var(--text-muted);font-size:0.9rem;">
                <i class="fas fa-key"></i> Has oblidat la contrasenya?
            </a>
            <a href="register.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i> Crea un compte nou
            </a>
        </div>

        <button id="btn-install" style="
            display:none;margin-top:16px;width:100%;background:var(--accent);
            color:white;border:none;padding:12px 20px;border-radius:10px;
            font-family:var(--font);font-size:0.95rem;font-weight:700;
            cursor:pointer;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-download"></i> Instal·lar RedAmigos
        </button>

        <p style="text-align:center;margin-top:24px;color:var(--text-muted);font-size:0.88rem;border-top:1px solid var(--border);padding-top:16px;">
            <i class="fas fa-question-circle"></i> Necessites ajuda?<br>
            Truca'ns al <a href="tel:900123456"><strong>900 123 456</strong></a>
        </p>
    </div>
</div>
<script>
function togglePwd() {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> Entrant...';
    btn.disabled = true;
});
</script>
</body></html>
