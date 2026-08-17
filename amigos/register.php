<?php
session_start();
require "config.php";

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $edad = (int)($_POST['edad'] ?? 0);
    $genero = $_POST['genero'] ?? 'Prefiero no decirlo';
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $terminos = isset($_POST['terminos']);
    
    // Validacions
    if (empty($nombre) || strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email no válido";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "La contraseña debe contener al menos una mayúscula";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "La contraseña debe contener al menos un número";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } elseif ($edad < 18 || $edad > 120) {
        $error = "Debes tener entre 18 y 120 años";
    } elseif (empty($ubicacion)) {
        $error = "La ubicación es obligatoria";
    } elseif (!$terminos) {
        $error = "Debes aceptar los términos y condiciones";
    } else {
        // Comprovar si l'email ja existeix
        $sql = "SELECT id FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            $error = "Este email ya está registrado";
        } else {
            // Registrar usuari
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO usuarios (nombre, email, password, edad, genero, ubicacion, aprobado) 
                        VALUES (:nombre, :email, :password, :edad, :genero, :ubicacion, FALSE)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => $password_hash,
                    'edad' => $edad,
                    'genero' => $genero,
                    'ubicacion' => $ubicacion
                ]);
                
                // ── Notificar administradors ──
                $nuevo_usuario_id = $pdo->lastInsertId();
                require_once "includes/email_helper.php";
                
                // Emails admins (BD + llista fixa)
                $admin_emails_fixos = ['admin@redamigos.com']; // Canvia per emails reals
                $sql_admins = "SELECT id, email FROM usuarios WHERE email IN ('admin@redamigos.com') AND activo = 1";
                $stmt_admins = $pdo->prepare($sql_admins);
                $stmt_admins->execute();
                $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($admins as $admin) {
                    // Notificació interna
                    $sql_notif = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                                  VALUES (:admin_id, 'sistema', :contenido, :enlace)";
                    $stmt_notif = $pdo->prepare($sql_notif);
                    $stmt_notif->execute([
                        'admin_id' => $admin['id'],
                        'contenido' => '🆕 Nou usuari pendent: ' . $nombre . ' (' . $email . ')',
                        'enlace' => 'admin/aprobar_usuarios.php'
                    ]);
                    // Email a l'admin
                    ra_email_admin_nou_usuari($admin['email'], $nombre, $email, $nuevo_usuario_id);
                }
                
                // Per si no hi ha admins a BD, enviar email als fixos igualment
                foreach ($admin_emails_fixos as $admin_mail) {
                    $ja_notificat = array_filter($admins, fn($a) => $a['email'] === $admin_mail);
                    if (empty($ja_notificat)) {
                        ra_email_admin_nou_usuari($admin_mail, $nombre, $email, $nuevo_usuario_id);
                    }
                }
                
                // Guardar dades temporals per a la pàgina d'espera
                $_SESSION['nombre_registro'] = $nombre;
                $_SESSION['email_registro']  = $email;
                
                // Redirigir a pàgina d'espera
                header("Location: pendiente_aprobacion.php");
                exit();
            } catch (PDOException $e) {
                $error = "Error al registrar: " . $e->getMessage();
            }
        }
    }
}
?>
<?php $page_title = 'Registre'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body class="auth-page">
<?php ra_splash_body(); ?>
    <div class="container">
        <?php if ($success): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <h2 style="color: var(--color-success); margin-bottom: 15px;">¡Registro completado!</h2>
                <p style="margin-bottom: 15px; color: #555; line-height: 1.6;">
                    Tu cuenta ha sido creada exitosamente.<br>
                    <strong>Está pendiente de aprobación por nuestro equipo.</strong>
                </p>
                <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid var(--color-principal);">
                    <h4 style="color: var(--color-principal); margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> Próximos pasos:
                    </h4>
                    <ul style="text-align: left; color: #555; line-height: 1.8;">
                        <li>Revisaremos tu solicitud en las próximas 24-48 horas</li>
                        <li>Te enviaremos un email cuando tu cuenta sea aprobada</li>
                        <li>Podrás iniciar sesión una vez aprobada</li>
                    </ul>
                </div>
                <p style="color: #888; font-size: 0.95rem;">
                    Si tienes alguna pregunta, contacta con nosotros en <strong>soporte@redamigos.com</strong>
                </p>
            </div>
        <?php else: ?>
            <h2>Crear una cuenta</h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre completo <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" required minlength="3" maxlength="100" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="edad"><i class="fas fa-birthday-cake"></i> Edad <span class="required">*</span></label>
                    <input type="number" id="edad" name="edad" required min="18" max="120" value="<?php echo $_POST['edad'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="genero"><i class="fas fa-venus-mars"></i> Género</label>
                    <select id="genero" name="genero">
                        <option value="Hombre">Hombre</option>
                        <option value="Mujer">Mujer</option>
                        <option value="Otro">Otro</option>
                        <option value="Prefiero no decirlo" selected>Prefiero no decirlo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación <span class="required">*</span></label>
                    <input type="text" id="ubicacion" name="ubicacion" required placeholder="Ej: Alicante, Valencia..." value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8" oninput="validatePassword()">
                    <div id="password-requirements" class="password-requirements">
                        <div id="req-length"><i class="fas fa-circle"></i> Mínimo 8 caracteres</div>
                        <div id="req-uppercase"><i class="fas fa-circle"></i> Al menos una mayúscula</div>
                        <div id="req-number"><i class="fas fa-circle"></i> Al menos un número</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar contraseña <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terminos" name="terminos" required>
                        <label for="terminos">
                            Acepto los <a href="#" style="color: var(--color-principal);">términos y condiciones</a> y la <a href="#" style="color: var(--color-principal);">política de privacidad</a>
                        </label>
                    </div>
                </div>

                <button type="submit">
                    <i class="fas fa-user-plus"></i> Crear cuenta
                </button>
            </form>

            <div class="links">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Ya tengo cuenta, iniciar sesión</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqNumber = document.getElementById('req-number');
            
            if (password.length >= 8) {
                reqLength.className = 'valid';
                reqLength.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqLength.className = 'invalid';
                reqLength.querySelector('i').className = 'fas fa-circle';
            }
            
            if (/[A-Z]/.test(password)) {
                reqUppercase.className = 'valid';
                reqUppercase.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqUppercase.className = 'invalid';
                reqUppercase.querySelector('i').className = 'fas fa-circle';
            }
            
            if (/[0-9]/.test(password)) {
                reqNumber.className = 'valid';
                reqNumber.querySelector('i').className = 'fas fa-check-circle';
            } else {
                reqNumber.className = 'invalid';
                reqNumber.querySelector('i').className = 'fas fa-circle';
            }
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
