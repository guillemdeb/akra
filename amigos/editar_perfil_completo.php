<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$error = '';
$success = '';

// Obtenir dades actuals
$sql = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $edad = (int)($_POST['edad'] ?? 0);
    $genero = $_POST['genero'] ?? '';
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $mostrar_telefono = isset($_POST['mostrar_telefono']) ? 1 : 0;
    $mostrar_email = isset($_POST['mostrar_email']) ? 1 : 0;
    
    // Validacions
    if (empty($nombre) || strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } elseif ($edad < 18 || $edad > 120) {
        $error = "La edad debe estar entre 18 y 120 años";
    } elseif (empty($ubicacion)) {
        $error = "La ubicación es obligatoria";
    } else {
        // Processar pujada d'imatge
        $foto_actual = $usuario['foto'];
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['foto']['type'];
            $file_size = $_FILES['foto']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error = "Formato de imagen no válido. Usa JPG, PNG, GIF o WebP";
            } elseif ($file_size > $max_size) {
                $error = "La imagen es demasiado grande. Máximo 5MB";
            } else {
                // Generar nom únic
                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nuevo_nombre = 'perfil_' . $usuario_id . '_' . time() . '.' . $extension;
                $ruta_destino = 'uploads/' . $nuevo_nombre;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                    // Eliminar foto anterior (si no és default)
                    if ($foto_actual && $foto_actual !== 'default.png' && file_exists('uploads/' . $foto_actual)) {
                        unlink('uploads/' . $foto_actual);
                    }
                    $foto_actual = $nuevo_nombre;
                } else {
                    $error = "Error al subir la imagen";
                }
            }
        }
        
        if (empty($error)) {
            try {
                $sql = "UPDATE usuarios SET 
                        nombre = :nombre,
                        edad = :edad,
                        genero = :genero,
                        ubicacion = :ubicacion,
                        telefono = :telefono,
                        descripcion = :descripcion,
                        mostrar_telefono = :mostrar_telefono,
                        mostrar_email = :mostrar_email,
                        foto = :foto
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nombre' => $nombre,
                    'edad' => $edad,
                    'genero' => $genero,
                    'ubicacion' => $ubicacion,
                    'telefono' => $telefono,
                    'descripcion' => $descripcion,
                    'mostrar_telefono' => $mostrar_telefono,
                    'mostrar_email' => $mostrar_email,
                    'foto' => $foto_actual,
                    'id' => $usuario_id
                ]);
                
                $_SESSION['usuario_nombre'] = $nombre;
                $success = "✅ Perfil actualizado correctamente";
                
                // Recarregar dades
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = "Error al actualizar el perfil: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-error: #E74C3C;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); line-height: 1.6; }
        header { background: var(--color-principal); color: white; padding: 20px; text-align: center; }
        header h1 { font-size: 1.8rem; }
        .back-link { color: white; text-decoration: none; display: inline-block; margin-bottom: 10px; }
        .back-link:hover { text-decoration: underline; }
        main { max-width: 700px; margin: 30px auto; padding: 0 15px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #ffe6e6; color: var(--color-error); border-left: 4px solid var(--color-error); }
        .alert-success { background: #e6ffe6; color: #27ae60; border-left: 4px solid var(--color-success); }
        .photo-section { text-align: center; margin-bottom: 30px; }
        .current-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--color-principal); margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 1.05rem; }
        label .required { color: var(--color-error); }
        input[type="text"], input[type="number"], input[type="tel"], select, textarea {
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: Arial, sans-serif;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--color-principal); box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1); }
        textarea { resize: vertical; min-height: 100px; }
        .file-input-wrapper { position: relative; display: inline-block; width: 100%; }
        input[type="file"] { width: 100%; padding: 12px; border: 2px dashed #ddd; border-radius: 8px; cursor: pointer; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .checkbox-group label { margin: 0; font-weight: normal; cursor: pointer; }
        .char-count { text-align: right; font-size: 0.9rem; color: #888; margin-top: 5px; }
        .buttons { display: flex; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s; }
        .btn-primary { background: var(--color-principal); color: white; }
        .btn-primary:hover { background: #357ABD; transform: translateY(-2px); }
        .btn-secondary { background: #888; color: white; }
        .btn-secondary:hover { background: #666; }
        @media (max-width: 600px) {
            .buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver al perfil</a>
        <h1><i class="fas fa-user-edit"></i> Editar Mi Perfil</h1>
    </header>

    <main>
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- FOTO DE PERFIL -->
                <div class="photo-section">
                    <img src="uploads/<?php echo htmlspecialchars($usuario['foto'] ?: 'default.png'); ?>" 
                         alt="Foto actual" 
                         class="current-photo"
                         id="preview-photo">
                    <div class="form-group">
                        <label><i class="fas fa-camera"></i> Cambiar foto de perfil</label>
                        <input type="file" name="foto" accept="image/*" onchange="previewImage(this)">
                        <small style="color: #888;">Formatos: JPG, PNG, GIF, WebP. Máximo 5MB</small>
                    </div>
                </div>

                <!-- INFORMACIÓN BÁSICA -->
                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user"></i> Nombre completo <span class="required">*</span></label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required minlength="3" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="edad"><i class="fas fa-birthday-cake"></i> Edad <span class="required">*</span></label>
                    <input type="number" id="edad" name="edad" value="<?php echo $usuario['edad']; ?>" required min="18" max="120">
                </div>

                <div class="form-group">
                    <label for="genero"><i class="fas fa-venus-mars"></i> Género</label>
                    <select id="genero" name="genero">
                        <option value="Hombre" <?php if($usuario['genero']=='Hombre') echo 'selected'; ?>>Hombre</option>
                        <option value="Mujer" <?php if($usuario['genero']=='Mujer') echo 'selected'; ?>>Mujer</option>
                        <option value="Otro" <?php if($usuario['genero']=='Otro') echo 'selected'; ?>>Otro</option>
                        <option value="Prefiero no decirlo" <?php if($usuario['genero']=='Prefiero no decirlo') echo 'selected'; ?>>Prefiero no decirlo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación <span class="required">*</span></label>
                    <input type="text" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($usuario['ubicacion']); ?>" required placeholder="Ej: Alicante, Valencia...">
                </div>

                <div class="form-group">
                    <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" placeholder="Ej: 666 123 456">
                </div>

                <!-- SOBRE MI -->
                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-align-left"></i> Sobre mí</label>
                    <textarea id="descripcion" name="descripcion" maxlength="500" oninput="updateCharCount()"><?php echo htmlspecialchars($usuario['descripcion']); ?></textarea>
                    <div class="char-count"><span id="char-count"><?php echo strlen($usuario['descripcion']); ?></span>/500 caracteres</div>
                </div>

                <!-- PRIVACIDAD -->
                <fieldset style="border: 2px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <legend style="font-weight: bold; color: var(--color-principal); padding: 0 10px;"><i class="fas fa-lock"></i> Configuración de privacidad</legend>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="mostrar_telefono" name="mostrar_telefono" <?php if($usuario['mostrar_telefono']) echo 'checked'; ?>>
                        <label for="mostrar_telefono">Mostrar mi teléfono a mis amigos</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="mostrar_email" name="mostrar_email" <?php if($usuario['mostrar_email']) echo 'checked'; ?>>
                        <label for="mostrar_email">Mostrar mi email a mis amigos</label>
                    </div>
                </fieldset>

                <div class="buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-photo').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updateCharCount() {
            const textarea = document.getElementById('descripcion');
            const count = document.getElementById('char-count');
            count.textContent = textarea.value.length;
        }
    </script>
</body>
</html>
