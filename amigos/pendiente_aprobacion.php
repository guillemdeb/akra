<?php
/**
 * PENDIENTE DE APROBACIÓN
 * Pàgina que veu l'usuari just després de registrar-se
 * (xarxa tancada - cal aprovació admin)
 */
session_start();

// Si ja està logat, redirigir
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Obtenir el nom si el tenim en sessió temporal
$nombre_registre = $_SESSION['nombre_registro'] ?? 'Amic/a';
$email_registre  = $_SESSION['email_registro']  ?? '';

// Netejar les dades temporals
unset($_SESSION['nombre_registro'], $_SESSION['email_registro']);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre pendent - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
<div class="pending-wrap">
    <div class="pending-card fade-in">

        <!-- Icona animada -->
        <div class="pending-icon">
            <i class="fas fa-hourglass-half"></i>
        </div>

        <!-- Títol -->
        <h1>Gràcies, <?= htmlspecialchars($nombre_registre) ?>!</h1>
        <p style="font-size:1.05rem; color: #4A90E2; font-weight:700; margin-bottom:6px;">
            El teu compte s'ha creat correctament.
        </p>
        <p>
            RedAmigos és una xarxa privada. El nostre equip revisarà la teva sol·licitud
            i t'enviarà una resposta per correu electrònic en les pròximes 24–48 hores.
        </p>

        <?php if ($email_registre): ?>
        <div class="alert alert-info" style="margin-top:16px;">
            <i class="fas fa-envelope"></i>
            <span>T'enviarem la confirmació a <strong><?= htmlspecialchars($email_registre) ?></strong></span>
        </div>
        <?php endif; ?>

        <!-- Passos del procés -->
        <div class="steps">
            <div class="step">
                <div class="step-num" style="background:#27AE60;">✓</div>
                <span><strong>Registre completat</strong> — La teva sol·licitud ha estat rebuda</span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <span><strong>Revisió de l'administrador</strong> — Verificarem la teva informació</span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <span><strong>Confirmació per email</strong> — Et notificarem per correu</span>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <span><strong>Accés complet</strong> — Podràs entrar i conèixer gent!</span>
            </div>
        </div>

        <p style="font-size:0.88rem; color: var(--text-muted); margin-bottom: 24px;">
            Si tens alguna pregunta, contacta'ns al
            <strong><a href="tel:900123456">900 123 456</a></strong> o envia'ns un email.
        </p>

        <a href="index.php" class="btn btn-secondary btn-full">
            <i class="fas fa-arrow-left"></i> Tornar a l'inici
        </a>
    </div>
</div>
</body>
</html>
