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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = $_POST['tipo'] ?? 'quedada';
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $fecha_evento = $_POST['fecha_evento'] ?? '';
    $hora_evento = $_POST['hora_evento'] ?? '';
    $plazas_max = !empty($_POST['plazas_max']) ? (int)$_POST['plazas_max'] : NULL;
    $visibilidad = $_POST['visibilidad'] ?? 'amigos';
    
    // Validacions
    if (empty($titulo) || strlen($titulo) < 5) {
        $error = "El título debe tener al menos 5 caracteres";
    } elseif (empty($descripcion) || strlen($descripcion) < 20) {
        $error = "La descripción debe tener al menos 20 caracteres";
    } elseif (empty($fecha_evento) || empty($hora_evento)) {
        $error = "Debes especificar fecha y hora del evento";
    } elseif (empty($ubicacion)) {
        $error = "La ubicación es obligatoria";
    } else {
        // Combinar fecha i hora
        $fecha_hora_completa = $fecha_evento . ' ' . $hora_evento . ':00';
        $fecha_evento_dt = new DateTime($fecha_hora_completa);
        $ahora = new DateTime();
        
        if ($fecha_evento_dt <= $ahora) {
            $error = "El evento debe ser en una fecha futura";
        } elseif ($plazas_max && $plazas_max < 2) {
            $error = "Debe haber al menos 2 plazas disponibles";
        } else {
            try {
                $sql = "INSERT INTO eventos (creador_id, titulo, descripcion, tipo, ubicacion, fecha_evento, plazas_max, visibilidad) 
                        VALUES (:creador_id, :titulo, :descripcion, :tipo, :ubicacion, :fecha_evento, :plazas_max, :visibilidad)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'creador_id' => $usuario_id,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'tipo' => $tipo,
                    'ubicacion' => $ubicacion,
                    'fecha_evento' => $fecha_hora_completa,
                    'plazas_max' => $plazas_max,
                    'visibilidad' => $visibilidad
                ]);
                
                $evento_id = $pdo->lastInsertId();
                
                // Apuntar automàticament el creador
                $sql = "INSERT INTO evento_participantes (evento_id, usuario_id, estado) VALUES (:evento_id, :usuario_id, 'confirmado')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);
                
                // Crear notificació per als amics (si visibilitat = amics)
                if ($visibilidad === 'amigos') {
                    $sql_amigos = "SELECT amigo_id FROM amistades WHERE usuario_id = :usuario_id AND estado = 'aceptada'
                                   UNION
                                   SELECT usuario_id FROM amistades WHERE amigo_id = :usuario_id2 AND estado = 'aceptada'";
                    $stmt_amigos = $pdo->prepare($sql_amigos);
                    $stmt_amigos->execute(['usuario_id' => $usuario_id, 'usuario_id2' => $usuario_id]);
                    $amigos = $stmt_amigos->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($amigos as $amigo_id) {
                        $sql_notif = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                                      VALUES (:usuario_id, 'evento', :contenido, :enlace)";
                        $stmt_notif = $pdo->prepare($sql_notif);
                        $stmt_notif->execute([
                            'usuario_id' => $amigo_id,
                            'contenido' => $_SESSION['usuario_nombre'] . ' ha creado un nuevo evento: ' . $titulo,
                            'enlace' => 'ver_evento.php?id=' . $evento_id
                        ]);
                    }
                }
                
                header("Location: ver_evento.php?id=" . $evento_id);
                exit();
                
            } catch (PDOException $e) {
                $error = "Error al crear el evento: " . $e->getMessage();
            }
        }
    }
}

// Data mínima (demà)
$fecha_minima = date('Y-m-d', strtotime('+1 day'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Evento - RedAmigos</title>
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
        
        header { background: var(--color-principal); color: white; padding: 15px 20px; }
        header a { color: white; text-decoration: none; }
        header a:hover { text-decoration: underline; }
        
        main { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .container { background: white; padding: 35px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 30px; font-size: 1.8rem; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #ffe6e6; color: var(--color-error); border-left: 4px solid var(--color-error); }
        
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; font-size: 1.05rem; }
        .required { color: var(--color-error); }
        
        input[type="text"], input[type="date"], input[type="time"], input[type="number"], select, textarea {
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: Arial, sans-serif;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--color-principal); box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1); }
        textarea { resize: vertical; min-height: 120px; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .char-count { text-align: right; font-size: 0.9rem; color: #888; margin-top: 5px; }
        
        .tipo-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .tipo-option { display: flex; flex-direction: column; align-items: center; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .tipo-option:hover { border-color: var(--color-principal); background: #f0f8ff; }
        .tipo-option input[type="radio"] { display: none; }
        .tipo-option input[type="radio"]:checked + label { color: var(--color-principal); font-weight: bold; }
        .tipo-option i { font-size: 2rem; margin-bottom: 10px; color: var(--color-principal); }
        .tipo-option label { cursor: pointer; text-align: center; }
        
        .info-box { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--color-principal); }
        .info-box h4 { color: var(--color-principal); margin-bottom: 8px; }
        .info-box ul { margin-left: 20px; }
        .info-box li { margin: 5px 0; color: #555; }
        
        .buttons { display: flex; gap: 15px; margin-top: 30px; }
        .btn { flex: 1; padding: 15px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s; }
        .btn-primary { background: var(--color-success); color: white; }
        .btn-primary:hover { background: #6DB91D; transform: translateY(-2px); }
        .btn-secondary { background: #888; color: white; }
        .btn-secondary:hover { background: #666; }
        
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .tipo-options { grid-template-columns: 1fr 1fr; }
            .buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header>
        <a href="eventos.php"><i class="fas fa-arrow-left"></i> Volver a Eventos</a>
        <h1 style="margin-top: 10px;"><i class="fas fa-calendar-plus"></i> Crear Nuevo Evento</h1>
    </header>

    <main>
        <div class="container">
            <h2>Organiza una Quedada</h2>

            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Consejos para crear un buen evento:</h4>
                <ul>
                    <li>Sé claro y específico con el título y descripción</li>
                    <li>Indica claramente el punto de encuentro</li>
                    <li>Establece un límite de plazas si es necesario</li>
                    <li>Elige la visibilidad adecuada para tu evento</li>
                </ul>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="titulo"><i class="fas fa-heading"></i> Título del evento <span class="required">*</span></label>
                    <input type="text" id="titulo" name="titulo" required minlength="5" maxlength="200" placeholder="Ej: Paseo por el parque del Oeste" value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Tipo de evento <span class="required">*</span></label>
                    <div class="tipo-options">
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="quedada" id="tipo_quedada" <?php echo (!isset($_POST['tipo']) || $_POST['tipo'] === 'quedada') ? 'checked' : ''; ?>>
                            <i class="fas fa-users"></i>
                            <label for="tipo_quedada">Quedada</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="cafe" id="tipo_cafe" <?php echo ($_POST['tipo'] ?? '') === 'cafe' ? 'checked' : ''; ?>>
                            <i class="fas fa-coffee"></i>
                            <label for="tipo_cafe">Café</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="paseo" id="tipo_paseo" <?php echo ($_POST['tipo'] ?? '') === 'paseo' ? 'checked' : ''; ?>>
                            <i class="fas fa-walking"></i>
                            <label for="tipo_paseo">Paseo</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="actividad" id="tipo_actividad" <?php echo ($_POST['tipo'] ?? '') === 'actividad' ? 'checked' : ''; ?>>
                            <i class="fas fa-running"></i>
                            <label for="tipo_actividad">Actividad</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="excursion" id="tipo_excursion" <?php echo ($_POST['tipo'] ?? '') === 'excursion' ? 'checked' : ''; ?>>
                            <i class="fas fa-hiking"></i>
                            <label for="tipo_excursion">Excursión</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" name="tipo" value="otro" id="tipo_otro" <?php echo ($_POST['tipo'] ?? '') === 'otro' ? 'checked' : ''; ?>>
                            <i class="fas fa-star"></i>
                            <label for="tipo_otro">Otro</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion"><i class="fas fa-align-left"></i> Descripción <span class="required">*</span></label>
                    <textarea id="descripcion" name="descripcion" required minlength="20" maxlength="1000" oninput="updateCharCount()" placeholder="Describe el evento: qué haremos, qué llevar, punto de encuentro..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    <div class="char-count"><span id="char-count"><?php echo strlen($_POST['descripcion'] ?? ''); ?></span>/1000 caracteres</div>
                </div>

                <div class="form-group">
                    <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación / Punto de encuentro <span class="required">*</span></label>
                    <input type="text" id="ubicacion" name="ubicacion" required maxlength="200" placeholder="Ej: Cafetería Central, Calle Mayor 15, Alicante" value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_evento"><i class="fas fa-calendar"></i> Fecha <span class="required">*</span></label>
                        <input type="date" id="fecha_evento" name="fecha_evento" required min="<?php echo $fecha_minima; ?>" value="<?php echo htmlspecialchars($_POST['fecha_evento'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="hora_evento"><i class="fas fa-clock"></i> Hora <span class="required">*</span></label>
                        <input type="time" id="hora_evento" name="hora_evento" required value="<?php echo htmlspecialchars($_POST['hora_evento'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="plazas_max"><i class="fas fa-user-friends"></i> Plazas máximas (opcional)</label>
                        <input type="number" id="plazas_max" name="plazas_max" min="2" max="100" placeholder="Dejar vacío = sin límite" value="<?php echo htmlspecialchars($_POST['plazas_max'] ?? ''); ?>">
                        <small style="color: #888;">Si no quieres limitar, déjalo vacío</small>
                    </div>

                    <div class="form-group">
                        <label for="visibilidad"><i class="fas fa-eye"></i> Visibilidad</label>
                        <select id="visibilidad" name="visibilidad">
                            <option value="amigos" <?php echo ($_POST['visibilidad'] ?? 'amigos') === 'amigos' ? 'selected' : ''; ?>>Solo amigos</option>
                            <option value="publico" <?php echo ($_POST['visibilidad'] ?? '') === 'publico' ? 'selected' : ''; ?>>Público (todos)</option>
                        </select>
                    </div>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Crear Evento
                    </button>
                    <a href="eventos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script>
        function updateCharCount() {
            const textarea = document.getElementById('descripcion');
            const count = document.getElementById('char-count');
            count.textContent = textarea.value.length;
        }

        // Marcar tipo-option seleccionat visualment
        document.querySelectorAll('.tipo-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.tipo-option').forEach(opt => {
                    opt.style.borderColor = '#ddd';
                    opt.style.background = 'white';
                });
                this.style.borderColor = 'var(--color-principal)';
                this.style.background = '#f0f8ff';
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Marcar inicial
        document.querySelector('.tipo-option input:checked')?.closest('.tipo-option').click();
    </script>
</body>
</html>
