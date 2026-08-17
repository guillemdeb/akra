<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$filtro = $_GET['filtro'] ?? 'todos';

// Función helper para formatear fechas en español (reemplaza strftime deprecated)
function formatearFechaEspanol($fecha) {
    $formatter = new IntlDateFormatter(
        'es_ES',
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Europe/Madrid',
        IntlDateFormatter::GREGORIAN,
        "EEEE, dd 'de' MMMM 'de' yyyy"
    );
    return $formatter->format($fecha);
}

// Obtenir esdeveniments segons filtre
if ($filtro === 'mis_eventos') {
    // Esdeveniments creats per mi
    $sql = "SELECT e.*, u.nombre as creador_nombre, u.foto as creador_foto,
            COUNT(DISTINCT ep.usuario_id) as num_participantes,
            (SELECT COUNT(*) FROM evento_participantes WHERE evento_id = e.id AND usuario_id = :usuario_id) as estoy_apuntado
            FROM eventos e
            JOIN usuarios u ON e.creador_id = u.id
            LEFT JOIN evento_participantes ep ON e.id = ep.evento_id AND ep.estado = 'confirmado'
            WHERE e.creador_id = :usuario_id2 AND e.estado = 'activo'
            GROUP BY e.id
            ORDER BY e.fecha_evento ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id, 'usuario_id2' => $usuario_id]);
    
} elseif ($filtro === 'apuntado') {
    // Esdeveniments on estic apuntat
    $sql = "SELECT e.*, u.nombre as creador_nombre, u.foto as creador_foto,
            COUNT(DISTINCT ep2.usuario_id) as num_participantes,
            1 as estoy_apuntado
            FROM eventos e
            JOIN usuarios u ON e.creador_id = u.id
            JOIN evento_participantes ep ON e.id = ep.evento_id AND ep.usuario_id = :usuario_id AND ep.estado = 'confirmado'
            LEFT JOIN evento_participantes ep2 ON e.id = ep2.evento_id AND ep2.estado = 'confirmado'
            WHERE e.estado = 'activo' AND e.creador_id != :usuario_id2
            GROUP BY e.id
            ORDER BY e.fecha_evento ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id, 'usuario_id2' => $usuario_id]);
    
} else {
    // Tots els esdeveniments disponibles (amics + públics)
    $sql = "SELECT DISTINCT e.*, u.nombre as creador_nombre, u.foto as creador_foto,
            COUNT(DISTINCT ep.usuario_id) as num_participantes,
            (SELECT COUNT(*) FROM evento_participantes WHERE evento_id = e.id AND usuario_id = :usuario_id) as estoy_apuntado
            FROM eventos e
            JOIN usuarios u ON e.creador_id = u.id
            LEFT JOIN evento_participantes ep ON e.id = ep.evento_id AND ep.estado = 'confirmado'
            LEFT JOIN amistades a1 ON (a1.usuario_id = :usuario_id2 AND a1.amigo_id = e.creador_id AND a1.estado = 'aceptada')
            LEFT JOIN amistades a2 ON (a2.usuario_id = e.creador_id AND a2.amigo_id = :usuario_id3 AND a2.estado = 'aceptada')
            WHERE e.estado = 'activo' 
            AND e.fecha_evento > NOW()
            AND (e.visibilidad = 'publico' OR (e.visibilidad = 'amigos' AND (a1.id IS NOT NULL OR a2.id IS NOT NULL)) OR e.creador_id = :usuario_id4)
            GROUP BY e.id
            ORDER BY e.fecha_evento ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario_id' => $usuario_id,
        'usuario_id2' => $usuario_id,
        'usuario_id3' => $usuario_id,
        'usuario_id4' => $usuario_id
    ]);
}

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php $page_title = 'Esdeveniments'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'eventos'; require_once "includes/navbar.php"; ?>

    <main>
        <div class="page-header">
            <h2>Descubre Eventos</h2>
            <a href="crear_evento.php" class="btn-crear">
                <i class="fas fa-plus-circle"></i> Crear Evento
            </a>
        </div>

        <div class="filters">
            <div class="filters-tabs">
                <a href="eventos.php?filtro=todos" class="filter-tab <?php echo $filtro === 'todos' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> Todos
                </a>
                <a href="eventos.php?filtro=apuntado" class="filter-tab <?php echo $filtro === 'apuntado' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Mis Inscripciones
                </a>
                <a href="eventos.php?filtro=mis_eventos" class="filter-tab <?php echo $filtro === 'mis_eventos' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> Mis Eventos
                </a>
            </div>
        </div>

        <?php if (empty($eventos)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No hay eventos disponibles</h3>
                <p>Sé el primero en crear un evento y empieza a conocer gente nueva</p>
                <br>
                <a href="crear_evento.php" class="btn-crear">
                    <i class="fas fa-plus-circle"></i> Crear mi primer evento
                </a>
            </div>
        <?php else: ?>
            <div class="eventos-grid">
                <?php foreach ($eventos as $evento): 
                    $fecha_evento = new DateTime($evento['fecha_evento']);
                    $es_creador = $evento['creador_id'] == $usuario_id;
                    $estoy_apuntado = $evento['estoy_apuntado'] > 0;
                    $plazas_llenas = $evento['plazas_max'] && $evento['num_participantes'] >= $evento['plazas_max'];
                ?>
                <div class="evento-card">
                    <div class="evento-header">
                        <span class="evento-tipo tipo-<?php echo htmlspecialchars($evento['tipo']); ?>">
                            <?php 
                            $iconos_tipo = [
                                'quedada' => 'fa-users',
                                'actividad' => 'fa-running',
                                'excursion' => 'fa-hiking',
                                'cafe' => 'fa-coffee',
                                'paseo' => 'fa-walking'
                            ];
                            echo '<i class="fas ' . htmlspecialchars($iconos_tipo[$evento['tipo']] ?? 'fa-calendar') . '"></i> ';
                            echo htmlspecialchars(ucfirst($evento['tipo'])); 
                            ?>
                        </span>
                        <h3 class="evento-titulo"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                        <div class="evento-creador">
                            <img src="uploads/<?php echo htmlspecialchars($evento['creador_foto'] ?: 'default.png'); ?>" alt="Creador">
                            <span>Organiza: <?php echo htmlspecialchars($evento['creador_nombre']); ?></span>
                        </div>
                    </div>

                    <div class="evento-body">
                        <p class="evento-descripcion">
                            <?php echo htmlspecialchars(mb_substr($evento['descripcion'], 0, 150)) . (mb_strlen($evento['descripcion']) > 150 ? '...' : ''); ?>
                        </p>

                        <div class="evento-info">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo formatearFechaEspanol($fecha_evento); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $fecha_evento->format('H:i'); ?> horas</span>
                            </div>
                            <?php if (!empty($evento['ubicacion'])): ?>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($evento['ubicacion']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($evento['plazas_max'])): ?>
                            <div class="info-item">
                                <i class="fas fa-user-friends"></i>
                                <span><?php echo intval($evento['num_participantes']); ?> / <?php echo intval($evento['plazas_max']); ?> plazas</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="evento-stats">
                        <div class="participantes">
                            <i class="fas fa-users"></i>
                            <strong><?php echo intval($evento['num_participantes']); ?></strong>
                            <span><?php echo $evento['num_participantes'] == 1 ? 'participante' : 'participantes'; ?></span>
                        </div>

                        <div class="evento-actions">
                            <?php if ($es_creador): ?>
                                <a href="editar_evento.php?id=<?php echo intval($evento['id']); ?>" class="btn-accion btn-editar">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            <?php elseif ($estoy_apuntado): ?>
                                <span class="btn-accion btn-apuntado">
                                    <i class="fas fa-check"></i> Apuntado
                                </span>
                            <?php elseif ($plazas_llenas): ?>
                                <span class="btn-accion btn-apuntado">
                                    <i class="fas fa-times"></i> Completo
                                </span>
                            <?php else: ?>
                                <a href="apuntar_evento.php?id=<?php echo intval($evento['id']); ?>" class="btn-accion btn-apuntar">
                                    <i class="fas fa-plus"></i> Apuntarme
                                </a>
                            <?php endif; ?>
                            
                            <a href="ver_evento.php?id=<?php echo intval($evento['id']); ?>" class="btn-accion btn-ver">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
