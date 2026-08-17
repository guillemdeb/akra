<?php
session_start();

// Verificar si hay sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Datos de ejemplo (en producción vendrán de la BD)
$nombre = $_SESSION['usuario_nombre'] ?? "Usuario Ejemplo";
$email  = $_SESSION['usuario_email'] ?? "usuario@ejemplo.com";

// Si se envía el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoNombre = trim($_POST['nombre']);
    $nuevoEmail  = trim($_POST['email']);

    // Guardar temporalmente en sesión (luego en BD)
    $_SESSION['usuario_nombre'] = $nuevoNombre;
    $_SESSION['usuario_email']  = $nuevoEmail;

    // Mensaje de confirmación
    $mensaje = "✅ Perfil actualizado correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - App Social</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; }
        header { background: #4A90E2; color: white; padding: 15px; text-align: center; }
        .container { width: 100%; max-width: 500px; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; }
        button { margin-top: 15px; padding: 10px; width: 100%; background: #4A90E2; border: none; color: white; border-radius: 5px; cursor: pointer; }
        button:hover { background: #357ABD; }
        .mensaje { margin-top: 15px; color: green; text-align: center; }
        .back { margin-top: 20px; text-align: center; }
        .back a { text-decoration: none; color: #4A90E2; }
    </style>
</head>
<body>
    <header>
        <h1>Mi Perfil</h1>
    </header>

    <div class="container">
        <h2>Editar información</h2>

        <?php if (!empty($mensaje)) { echo "<p class='mensaje'>$mensaje</p>"; } ?>

        <form method="POST" action="">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>

            <label for="email">Correo electrónico:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <button type="submit">Guardar cambios</button>
        </form>

        <div class="back">
            <a href="dashboard.php">⬅ Volver al panel</a>
        </div>
    </div>
</body>
</html>
