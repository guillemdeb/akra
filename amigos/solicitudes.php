<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];

// Procesar acciones (aceptar, rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
    
    if ($accion === 'aceptar' && $solicitud_id > 0) {
        $sql = "UPDATE amistades SET estado = 'aceptada', fecha_respuesta = NOW() 
                WHERE id = :id AND amigo_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $solicitud_id, 'usuario_id' => $usuario_id]);
        
        // Crear notificació per a qui va enviar la sol·licitud
        $sqlNotif = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                     SELECT usuario_id, 'amistad', 
                            CONCAT((SELECT nombre FROM usuarios WHERE id = :yo), ' ha aceptado tu solicitud de amistad'), 
                            'feed.php'
                     FROM amistades WHERE id = :id";
        $stmtNotif = $pdo->prepare($sqlNotif);
        $stmtNotif->execute(['yo' => $usuario_id, 'id' => $solicitud_id]);
        
        $mensaje = "✅ Solicitud aceptada";
    } 
    elseif ($accion === 'rechazar' && $solicitud_id > 0) {
        $sql = "UPDATE amistades SET estado = 'rechazada', fecha_respuesta = NOW() 
                WHERE id = :id AND amigo_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $solicitud_id, 'usuario_id' => $usuario_id]);
        
        $mensaje = "❌ Solicitud rechazada";
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: solicitudes.php" . (isset($mensaje) ? "?msg=" . urlencode($mensaje) : ""));
    exit();
}

// Obtener solicitudes pendientes
$sql = "SELECT a.id, u.id as usuario_id, u.nombre, u.edad, u.genero, u.ubicacion, u.foto, u.descripcion, a.fecha_solicitud,
               GROUP_CONCAT(DISTINCT i.icono SEPARATOR '|') AS iconos_intereses
        FROM amistades a
        INNER JOIN usuarios u ON u.id = a.usuario_id
        LEFT JOIN usuario_interes ui ON ui.usuario_id = u.id
        LEFT JOIN intereses i ON i.id = ui.interes_id
        WHERE a.amigo_id = :usuario_id AND a.estado = 'pendiente'
        GROUP BY a.id
        ORDER BY a.fecha_solicitud DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['msg'] ?? '';
?>
<?php $page_title = 'Sol·licituds'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'amigos'; require_once "includes/navbar.php"; ?>

    <main>
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if (count($solicitudes) === 0): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h2>No tienes solicitudes pendientes</h2>
                <p>Cuando alguien te envíe una solicitud de amistad, aparecerá aquí.</p>
            </div>
        <?php else: ?>
            <?php foreach ($solicitudes as $sol): ?>
                <div class="solicitud-card">
                    <img src="uploads/<?php echo htmlspecialchars($sol['foto'] ?: 'default.png'); ?>" 
                         alt="<?php echo htmlspecialchars($sol['nombre']); ?>" 
                         class="solicitud-avatar">
                    
                    <div class="solicitud-info">
                        <h3><?php echo htmlspecialchars($sol['nombre']); ?></h3>
                        <div class="solicitud-meta">
                            <?php echo $sol['edad']; ?> años · 
                            <?php echo htmlspecialchars($sol['genero']); ?> · 
                            📍 <?php echo htmlspecialchars($sol['ubicacion']); ?>
                            <br>
                            <small>Solicitud enviada: <?php echo date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])); ?></small>
                        </div>

                        <?php if (!empty($sol['descripcion'])): ?>
                            <div class="solicitud-description">
                                <?php echo htmlspecialchars(mb_substr($sol['descripcion'], 0, 150)); ?>
                                <?php if (mb_strlen($sol['descripcion']) > 150) echo '...'; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sol['iconos_intereses'])): ?>
                            <div class="solicitud-interests">
                                <?php 
                                $iconos = explode('|', $sol['iconos_intereses']);
                                $iconos = array_unique(array_slice($iconos, 0, 8));
                                foreach ($iconos as $icono): ?>
                                    <i class="<?php echo htmlspecialchars($icono); ?>"></i>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="solicitud-actions">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="solicitud_id" value="<?php echo $sol['id']; ?>">
                                <input type="hidden" name="accion" value="aceptar">
                                <button type="submit" class="btn btn-aceptar">
                                    <i class="fas fa-check"></i> Aceptar
                                </button>
                            </form>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="solicitud_id" value="<?php echo $sol['id']; ?>">
                                <input type="hidden" name="accion" value="rechazar">
                                <button type="submit" class="btn btn-rechazar">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            </form>

                            <a href="perfil_usuario.php?id=<?php echo $sol['usuario_id']; ?>" class="btn btn-ver">
                                <i class="fas fa-eye"></i> Ver perfil
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
