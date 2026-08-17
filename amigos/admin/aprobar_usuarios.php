<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";
require_once "../includes/email_helper.php";

$usuario_id = $_SESSION['usuario_id'];

// Verificar que l'usuari és admin
$sql = "SELECT email FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Lista blanca d'admins (emails)
$admins_emails = ['admin@redamigos.com', 'maria@ejemplo.com']; // CANVIA AIXÒ!

if (!$usuario || !in_array($usuario['email'], $admins_emails)) {
    header("Location: ../dashboard.php");
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

// Processar accions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $usuario_id_pendiente = (int)($_POST['usuario_id'] ?? 0);
    
    if ($accion === 'aprobar' && $usuario_id_pendiente > 0) {
        try {
            // Obtenir dades de l'usuari per l'email
            $s = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
            $s->execute(['id' => $usuario_id_pendiente]);
            $u_aprovat = $s->fetch(PDO::FETCH_ASSOC);
            
            $sql = "UPDATE usuarios SET 
                    aprobado = TRUE, 
                    fecha_aprobacion = NOW(), 
                    aprobado_por = :admin_id 
                    WHERE id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'admin_id' => $usuario_id,
                'usuario_id' => $usuario_id_pendiente
            ]);
            
            // Notificació interna
            $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                    VALUES (:usuario_id, 'sistema', '🎉 ¡Bienvenido/a! Tu cuenta ha sido aprobada. Ya puedes empezar.', 'dashboard.php')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['usuario_id' => $usuario_id_pendiente]);
            
            // ── Generar codi personal PWA i enviar email complet ──
            require_once "../includes/pwa_codigos.php";
            $codi_pwa = pwa_assignar_codi($usuario_id_pendiente);
            
            if ($u_aprovat) {
                // Email únic amb benvinguda + codi d'instal·lació
                pwa_email_codi($u_aprovat['email'], $u_aprovat['nombre'], $codi_pwa);
            }
            
            $mensaje = "✅ Aprovat · Codi PWA: <strong>{$codi_pwa}</strong> · Email enviat.";
            $tipo_mensaje = "success";
            
        } catch (PDOException $e) {
            $mensaje = "Error al aprobar usuario";
            $tipo_mensaje = "error";
        }
        
    } elseif ($accion === 'rechazar' && $usuario_id_pendiente > 0) {
        $motivo = trim($_POST['motivo'] ?? 'No especificado');
        
        try {
            // Dades de l'usuari
            $s = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
            $s->execute(['id' => $usuario_id_pendiente]);
            $u_rebutjat = $s->fetch(PDO::FETCH_ASSOC);
            
            $sql = "UPDATE usuarios SET 
                    activo = FALSE,
                    motivo_rechazo = :motivo
                    WHERE id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'motivo' => $motivo,
                'usuario_id' => $usuario_id_pendiente
            ]);
            
            // Notificació interna
            $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                    VALUES (:usuario_id, 'sistema', :contenido, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuario_id_pendiente,
                'contenido' => 'La teva sol·licitud de registre ha estat revisada. Motiu: ' . $motivo
            ]);
            
            // ── Email de rebuig ──
            if ($u_rebutjat) {
                ra_email_rebuig($u_rebutjat['email'], $u_rebutjat['nombre'], $motivo);
            }
            
            $mensaje = "Usuari rebutjat i notificat per email.";
            $tipo_mensaje = "success";
            
        } catch (PDOException $e) {
            $mensaje = "Error al rechazar usuario";
            $tipo_mensaje = "error";
        }
    }
}

// Obtenir usuaris pendents
$sql = "SELECT id, nombre, email, edad, genero, ubicacion, telefono, descripcion, fecha_registro
        FROM usuarios 
        WHERE aprobado = FALSE AND activo = TRUE
        ORDER BY fecha_registro ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadístiques
$sql_stats = "SELECT 
              (SELECT COUNT(*) FROM usuarios WHERE aprobado = FALSE AND activo = TRUE) as pendientes,
              (SELECT COUNT(*) FROM usuarios WHERE aprobado = TRUE) as aprobados,
              (SELECT COUNT(*) FROM usuarios WHERE activo = FALSE) as rechazados";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-error: #E74C3C;
            --color-warning: #F39C12;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); line-height: 1.6; }
        
        header { background: #2C3E50; color: white; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        header h1 { font-size: 1.8rem; margin-bottom: 5px; }
        header p { opacity: 0.8; }
        .header-nav { margin-top: 15px; }
        .header-nav a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.1); border-radius: 5px; margin-right: 10px; }
        .header-nav a:hover { background: rgba(255,255,255,0.2); }
        
        main { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stat-card h3 { color: #666; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .numero { font-size: 2.5rem; font-weight: bold; }
        .stat-card.pendientes .numero { color: var(--color-warning); }
        .stat-card.aprobados .numero { color: var(--color-success); }
        .stat-card.rechazados .numero { color: var(--color-error); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #e6ffe6; color: #27ae60; border-left: 4px solid var(--color-success); }
        .alert-error { background: #ffe6e6; color: var(--color-error); border-left: 4px solid var(--color-error); }
        
        .usuarios-grid { display: grid; gap: 25px; }
        
        .usuario-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .usuario-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .usuario-info h3 { font-size: 1.4rem; color: #333; margin-bottom: 5px; }
        .usuario-info .meta { color: #888; font-size: 0.9rem; }
        .usuario-badge { background: var(--color-warning); color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        
        .usuario-detalles { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .detalle { padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .detalle strong { display: block; color: #666; font-size: 0.85rem; margin-bottom: 5px; }
        .detalle span { color: #333; font-size: 1rem; }
        
        .usuario-descripcion { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .usuario-descripcion h4 { color: #333; margin-bottom: 10px; font-size: 0.95rem; }
        .usuario-descripcion p { color: #555; line-height: 1.6; }
        
        .usuario-actions { display: flex; gap: 15px; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: all 0.3s; font-size: 1rem; }
        .btn-aprobar { background: var(--color-success); color: white; flex: 1; }
        .btn-aprobar:hover { background: #6DB91D; transform: translateY(-2px); }
        .btn-rechazar { background: var(--color-error); color: white; flex: 1; }
        .btn-rechazar:hover { background: #C0392B; transform: translateY(-2px); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; }
        .modal-content h3 { margin-bottom: 20px; color: #333; }
        .modal-content textarea { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; min-height: 100px; font-family: Arial, sans-serif; margin-bottom: 20px; }
        .modal-actions { display: flex; gap: 10px; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        .empty-state i { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
        
        @media (max-width: 768px) {
            .usuario-detalles { grid-template-columns: 1fr; }
            .usuario-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-shield-alt"></i> Panel de Administración</h1>
        <p>Gestión y aprobación de nuevos usuarios</p>
        <div class="header-nav">
            <a href="../dashboard.php"><i class="fas fa-home"></i> Volver a RedAmigos</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
        </div>
    </header>

    <main>
        <div class="stats">
            <div class="stat-card pendientes">
                <h3><i class="fas fa-clock"></i> Pendientes de Aprobación</h3>
                <div class="numero"><?php echo $stats['pendientes']; ?></div>
            </div>
            <div class="stat-card aprobados">
                <h3><i class="fas fa-check-circle"></i> Usuarios Aprobados</h3>
                <div class="numero"><?php echo $stats['aprobados']; ?></div>
            </div>
            <div class="stat-card rechazados">
                <h3><i class="fas fa-times-circle"></i> Usuarios Rechazados</h3>
                <div class="numero"><?php echo $stats['rechazados']; ?></div>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pendientes)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3 style="color: var(--color-success); margin-bottom: 10px;">¡Todo al día!</h3>
                <p style="color: #888;">No hay usuarios pendientes de aprobación</p>
            </div>
        <?php else: ?>
            <div class="usuarios-grid">
                <?php foreach ($pendientes as $p): 
                    $fecha_registro = new DateTime($p['fecha_registro']);
                    $dias_pendiente = (new DateTime())->diff($fecha_registro)->days;
                ?>
                <div class="usuario-card">
                    <div class="usuario-header">
                        <div class="usuario-info">
                            <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                            <div class="meta">
                                Registrado hace <?php echo $dias_pendiente; ?> día<?php echo $dias_pendiente != 1 ? 's' : ''; ?>
                                · <?php echo $fecha_registro->format('d/m/Y H:i'); ?>
                            </div>
                        </div>
                        <span class="usuario-badge">PENDIENTE</span>
                    </div>

                    <div class="usuario-detalles">
                        <div class="detalle">
                            <strong>Email</strong>
                            <span><?php echo htmlspecialchars($p['email']); ?></span>
                        </div>
                        <div class="detalle">
                            <strong>Edad</strong>
                            <span><?php echo $p['edad']; ?> años</span>
                        </div>
                        <div class="detalle">
                            <strong>Género</strong>
                            <span><?php echo htmlspecialchars($p['genero']); ?></span>
                        </div>
                        <div class="detalle">
                            <strong>Ubicación</strong>
                            <span><?php echo htmlspecialchars($p['ubicacion']); ?></span>
                        </div>
                        <?php if ($p['telefono']): ?>
                        <div class="detalle">
                            <strong>Teléfono</strong>
                            <span><?php echo htmlspecialchars($p['telefono']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($p['descripcion']): ?>
                    <div class="usuario-descripcion">
                        <h4><i class="fas fa-align-left"></i> Descripción:</h4>
                        <p><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="usuario-actions">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="usuario_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="accion" value="aprobar">
                            <button type="submit" class="btn btn-aprobar" onclick="return confirm('¿Aprobar este usuario?')">
                                <i class="fas fa-check"></i> Aprobar Usuario
                            </button>
                        </form>
                        
                        <button class="btn btn-rechazar" onclick="mostrarModalRechazo(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre']); ?>')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Rechazo -->
    <div id="modalRechazo" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-times-circle"></i> Rechazar Usuario</h3>
            <p style="color: #666; margin-bottom: 20px;">Indica el motivo del rechazo (será notificado al usuario):</p>
            <form method="POST">
                <input type="hidden" name="usuario_id" id="rechazo_usuario_id">
                <input type="hidden" name="accion" value="rechazar">
                <textarea name="motivo" placeholder="Ejemplo: Perfil incompleto, información sospechosa, etc." required></textarea>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-rechazar">
                        <i class="fas fa-check"></i> Confirmar Rechazo
                    </button>
                    <button type="button" class="btn" style="background: #888; color: white;" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mostrarModalRechazo(usuarioId, nombre) {
            document.getElementById('rechazo_usuario_id').value = usuarioId;
            document.getElementById('modalRechazo').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('modalRechazo').classList.remove('active');
        }

        // Cerrar modal clicant fora
        document.getElementById('modalRechazo').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
    </script>
</body>
</html>
