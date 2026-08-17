<?php
session_start();
require "config.php";

$error = '';
$success = '';
$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';

// STEP 1: Sol·licitar recuperació
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, introduce un email válido";
    } else {
        // Comprovar si l'usuari existeix
        $sql = "SELECT id, nombre FROM usuarios WHERE email = :email AND activo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            // Generar token únic
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', time() + 1800); // 30 minuts
            
            // Guardar token
            $sql = "INSERT INTO password_resets (email, token, expiracion) 
                    VALUES (:email, :token, :expiracion)
                    ON DUPLICATE KEY UPDATE token = :token, expiracion = :expiracion";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email,
                'token' => $token,
                'expiracion' => $expiracion
            ]);
            
            // En producció: enviar email amb PHPMailer
            // Per ara, mostrem el link directament (NOMÉS PER DESENVOLUPAMENT)
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/recuperar_password.php?step=reset&token=" . $token;
            
            $success = "✅ Si el email existe en nuestra base de datos, recibirás un enlace de recuperación.";
            // DEV ONLY: Mostrem el link
            $success .= "<br><br><strong>SOLO DESARROLLO:</strong><br><a href='$reset_link'>$reset_link</a>";
        } else {
            // Per seguretat, mostrem el mateix missatge encara que no existeixi
            $success = "✅ Si el email existe en nuestra base de datos, recibirás un enlace de recuperación.";
        }
    }
}

// STEP 2: Restablir contrasenya
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'reset') {
    $token = $_POST['token'] ?? '';
    $nueva_password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirm_password'] ?? '';
    
    if (empty($nueva_password) || strlen($nueva_password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } elseif ($nueva_password !== $confirmar_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar token
        $sql = "SELECT email FROM password_resets 
                WHERE token = :token AND expiracion > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset) {
            // Actualitzar contrasenya
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET password = :password WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'password' => $password_hash,
                'email' => $reset['email']
            ]);
            
            // Eliminar token utilitzat
            $sql = "DELETE FROM password_resets WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $reset['email']]);
            
            $success = "✅ Contraseña actualizada correctamente. Ya puedes iniciar sesión.";
            $step = 'done';
        } else {
            $error = "El enlace de recuperación es inválido o ha expirado";
        }
    }
}

// Verificar token per mostrar formulari de reset
$token_valid = false;
if ($step === 'reset' && !empty($token)) {
    $sql = "SELECT email FROM password_resets WHERE token = :token AND expiracion > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token]);
    $token_valid = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-error: #E74C3C;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); min-height: 100vh; display: flex; flex-direction: column; }
        header { background: var(--color-principal); color: white; padding: 20px; text-align: center; }
        header h1 { font-size: 2rem; margin-bottom: 5px; }
        .container { max-width: 500px; margin: 40px auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; font-size: 1.5rem; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #ffe6e6; color: var(--color-error); border-left: 4px solid var(--color-error); }
        .alert-success { background: #e6ffe6; color: #27ae60; border-left: 4px solid var(--color-success); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 8px; font-size: 1.1rem; }
        input:focus { outline: none; border-color: var(--color-principal); box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1); }
        button { width: 100%; padding: 15px; background: var(--color-principal); color: white; border: none; border-radius: 8px; font-size: 1.2rem; font-weight: bold; cursor: pointer; }
        button:hover { background: #357ABD; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: var(--color-principal); text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .password-strength { margin-top: 10px; height: 5px; border-radius: 3px; background: #ddd; }
        .password-strength.weak { background: #E74C3C; }
        .password-strength.medium { background: #F39C12; }
        .password-strength.strong { background: #7ED321; }
        .password-tips { font-size: 0.9rem; color: #888; margin-top: 10px; }
    </style>
</head>
<body>
    <header>
        <h1>🌐 RedAmigos</h1>
        <p>Recuperar Contraseña</p>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($step === 'request'): ?>
            <!-- FORMULARI: Sol·licitar recuperació -->
            <h2>¿Olvidaste tu contraseña?</h2>
            <p style="text-align: center; color: #666; margin-bottom: 25px;">
                Introduce tu email y te enviaremos un enlace para restablecer tu contraseña.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                </div>
                <button type="submit"><i class="fas fa-paper-plane"></i> Enviar enlace de recuperación</button>
            </form>

        <?php elseif ($step === 'reset' && $token_valid): ?>
            <!-- FORMULARI: Nova contrasenya -->
            <h2>Crear nueva contraseña</h2>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Nueva contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8" oninput="checkPasswordStrength()">
                    <div id="strength-bar" class="password-strength"></div>
                    <div class="password-tips">
                        <small>
                            <i class="fas fa-info-circle"></i> Usa al menos 8 caracteres, incluyendo mayúsculas, minúsculas y números
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la contraseña" required>
                </div>

                <button type="submit"><i class="fas fa-check"></i> Cambiar contraseña</button>
            </form>

        <?php elseif ($step === 'done'): ?>
            <!-- ÉXITO -->
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--color-success); margin-bottom: 20px;"></i>
                <h2 style="color: var(--color-success);">¡Contraseña actualizada!</h2>
                <p style="margin: 20px 0;">Ya puedes iniciar sesión con tu nueva contraseña.</p>
                <a href="index.php" style="display: inline-block; padding: 15px 30px; background: var(--color-principal); color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    <i class="fas fa-sign-in-alt"></i> Ir al login
                </a>
            </div>

        <?php else: ?>
            <!-- TOKEN INVÀLID -->
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: var(--color-error); margin-bottom: 20px;"></i>
                <h2 style="color: var(--color-error);">Enlace inválido o expirado</h2>
                <p style="margin: 20px 0;">El enlace de recuperación no es válido o ha caducado.</p>
                <a href="recuperar_password.php" style="display: inline-block; padding: 15px 30px; background: var(--color-principal); color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    <i class="fas fa-redo"></i> Solicitar nuevo enlace
                </a>
            </div>
        <?php endif; ?>

        <div class="links">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al login</a>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strength-bar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength';
            if (strength <= 1) strengthBar.classList.add('weak');
            else if (strength <= 2) strengthBar.classList.add('medium');
            else strengthBar.classList.add('strong');
        }
    </script>
</body>
</html>
