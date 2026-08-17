<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

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

$usuario_id = $_SESSION['usuario_id'];
$evento_id = (int)($_GET['id'] ?? 0);

if ($evento_id <= 0) {
    header("Location: eventos.php");
    exit();
}

// Obtenir dades de l'esdeveniment
$sql = "SELECT e.*, u.nombre as creador_nombre, u.foto as creador_foto, u.ubicacion as creador_ubicacion,
        (SELECT COUNT(*) FROM evento_participantes WHERE evento_id = e.id AND estado = 'confirmado') as num_participantes,
        (SELECT COUNT(*) FROM evento_participantes WHERE evento_id = e.id AND usuario_id = :usuario_id) as estoy_apuntado
        FROM eventos e
        JOIN usuarios u ON e.creador_id = u.id
        WHERE e.id = :evento_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id, 'evento_id' => $evento_id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header("Location: eventos.php");
    exit();
}

// Obtenir participants
$sql = "SELECT u.id, u.nombre, u.foto, u.ubicacion, ep.fecha_inscripcion
        FROM evento_participantes ep
        JOIN usuarios u ON ep.usuario_id = u.id
        WHERE ep.evento_id = :evento_id AND ep.estado = 'confirmado'
        ORDER BY ep.fecha_inscripcion ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['evento_id' => $evento_id]);
$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtenir comentaris
$sql = "SELECT c.*, u.nombre, u.foto
        FROM evento_comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.evento_id = :evento_id
        ORDER BY c.fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['evento_id' => $evento_id]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$es_creador = $evento['creador_id'] == $usuario_id;
$estoy_apuntado = $evento['estoy_apuntado'] > 0;
$plazas_disponibles = $evento['plazas_max'] ? ($evento['plazas_max'] - $evento['num_participantes']) : 999;

$fecha_evento = new DateTime($evento['fecha_evento']);
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($evento['titulo']); ?> - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-warning: #F39C12;
            --color-error: #E74C3C;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); line-height: 1.6; }
        
        header { background: var(--color-principal); color: white; padding: 15px 20px; }
        header a { color: white; text-decoration: none; }
        header a:hover { text-decoration: underline; }
        
        main { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #e6ffe6; color: #27ae60; border-left: 4px solid var(--color-success); }
        .alert-error { background: #ffe6e6; color: var(--color-error); border-left: 4px solid var(--color-error); }
        
        .evento-header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .evento-tipo { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; margin-bottom: 15px; background: #E3F2FD; color: #1976D2; }
        .evento-titulo { font-size: 2.2rem; color: #333; margin-bottom: 20px; }
        .evento-creador { display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .evento-creador img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .creador-info h4 { color: #333; margin-bottom: 3px; }
        .creador-info p { color: #777; font-size: 0.9rem; }
        
        .evento-content { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .evento-descripcion { font-size: 1.05rem; line-height: 1.8; color: #555; margin-bottom: 30px; white-space: pre-line; }
        
        .evento-detalles { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .detalle-item { display: flex; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .detalle-item i { font-size: 1.5rem; color: var(--color-principal); width: 30px; }
        .detalle-info strong { display: block; color: #333; margin-bottom: 3px; }
        .detalle-info span { color: #666; font-size: 0.95rem; }
        
        .acciones { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 15px 30px; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-success { background: var(--color-success); color: white; }
        .btn-success:hover { background: #6DB91D; transform: translateY(-2px); }
        .btn-secondary { background: #888; color: white; }
        .btn-warning { background: var(--color-warning); color: white; }
        .btn-danger { background: var(--color-error); color: white; }
        .btn-disabled { background: #ddd; color: #999; cursor: not-allowed; }
        
        .participantes-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .participantes-section h3 { color: #333; margin-bottom: 20px; font-size: 1.5rem; }
        .participantes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .participante-card { display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px; transition: all 0.3s; }
        .participante-card:hover { background: #e9ecef; transform: translateX(5px); }
        .participante-card img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .participante-info h5 { color: #333; font-size: 1rem; margin-bottom: 3px; }
        .participante-info p { color: #777; font-size: 0.85rem; }
        .badge-creador { background: var(--color-principal); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; margin-left: 5px; }
        
        @media (max-width: 768px) {
            .evento-titulo { font-size: 1.6rem; }
            .evento-detalles { grid-template-columns: 1fr; }
            .acciones { flex-direction: column; }
            .participantes-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <a href="eventos.php"><i class="fas fa-arrow-left"></i> Volver a Eventos</a>
        <h1 style="margin-top: 10px;"><i class="fas fa-calendar-alt"></i> Detalles del Evento</h1>
    </header>

    <main>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="evento-header">
            <div class="evento-tipo">
                <?php 
                $iconos_tipo = [
                    'quedada' => 'fa-users',
                    'actividad' => 'fa-running',
                    'excursion' => 'fa-hiking',
                    'cafe' => 'fa-coffee',
                    'paseo' => 'fa-walking',
                    'otro' => 'fa-star'
                ];
                echo '<i class="fas ' . ($iconos_tipo[$evento['tipo']] ?? 'fa-calendar') . '"></i> ';
                echo ucfirst($evento['tipo']); 
                ?>
            </div>
            
            <h1 class="evento-titulo"><?php echo htmlspecialchars($evento['titulo']); ?></h1>
            
            <div class="evento-creador">
                <img src="uploads/<?php echo htmlspecialchars($evento['creador_foto'] ?: 'default.png'); ?>" alt="Creador">
                <div class="creador-info">
                    <h4>Organizado por: <?php echo htmlspecialchars($evento['creador_nombre']); ?></h4>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($evento['creador_ubicacion']); ?></p>
                </div>
            </div>
        </div>

        <div class="evento-content">
            <div class="evento-detalles">
                <div class="detalle-item">
                    <i class="fas fa-calendar"></i>
                    <div class="detalle-info">
                        <strong>Fecha</strong>
                        <span><?php echo formatearFechaEspanol($fecha_evento); ?></span>
                    </div>
                </div>
                
                <div class="detalle-item">
                    <i class="fas fa-clock"></i>
                    <div class="detalle-info">
                        <strong>Hora</strong>
                        <span><?php echo $fecha_evento->format('H:i'); ?> horas</span>
                    </div>
                </div>
                
                <div class="detalle-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="detalle-info">
                        <strong>Ubicación</strong>
                        <span><?php echo htmlspecialchars($evento['ubicacion']); ?></span>
                    </div>
                </div>
                
                <div class="detalle-item">
                    <i class="fas fa-users"></i>
                    <div class="detalle-info">
                        <strong>Participantes</strong>
                        <span>
                            <?php 
                            echo $evento['num_participantes'];
                            if ($evento['plazas_max']) {
                                echo ' / ' . $evento['plazas_max'];
                            }
                            echo ' ' . ($evento['num_participantes'] == 1 ? 'persona' : 'personas');
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <h3 style="color: #333; margin-bottom: 15px; font-size: 1.3rem;">Descripción</h3>
            <div class="evento-descripcion">
                <?php echo htmlspecialchars($evento['descripcion']); ?>
            </div>

            <div class="acciones">
                <?php if ($es_creador): ?>
                    <a href="editar_evento.php?id=<?php echo $evento_id; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar Evento
                    </a>
                    <a href="cancelar_evento.php?id=<?php echo $evento_id; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres cancelar este evento?')">
                        <i class="fas fa-times-circle"></i> Cancelar Evento
                    </a>
                <?php elseif ($estoy_apuntado): ?>
                    <span class="btn btn-disabled">
                        <i class="fas fa-check-circle"></i> Ya estás apuntado
                    </span>
                    <a href="desapuntar_evento.php?id=<?php echo $evento_id; ?>" class="btn btn-secondary" onclick="return confirm('¿Seguro que quieres desapuntarte?')">
                        <i class="fas fa-times"></i> Desapuntarme
                    </a>
                <?php elseif ($plazas_disponibles <= 0): ?>
                    <span class="btn btn-disabled">
                        <i class="fas fa-ban"></i> No hay plazas disponibles
                    </span>
                <?php else: ?>
                    <a href="apuntar_evento.php?id=<?php echo $evento_id; ?>" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> ¡Me apunto!
                    </a>
                <?php endif; ?>
                
                <?php if (!$es_creador): ?>
                    <a href="mensajes.php?chat=<?php echo $evento['creador_id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-comment"></i> Contactar Organizador
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($participantes)): ?>
        <div class="participantes-section">
            <h3><i class="fas fa-users"></i> Participantes (<?php echo count($participantes); ?>)</h3>
            <div class="participantes-grid">
                <?php foreach ($participantes as $participante): ?>
                <div class="participante-card">
                    <img src="uploads/<?php echo htmlspecialchars($participante['foto'] ?: 'default.png'); ?>" alt="<?php echo htmlspecialchars($participante['nombre']); ?>">
                    <div class="participante-info">
                        <h5>
                            <?php echo htmlspecialchars($participante['nombre']); ?>
                            <?php if ($participante['id'] == $evento['creador_id']): ?>
                                <span class="badge-creador">Organizador</span>
                            <?php endif; ?>
                        </h5>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($participante['ubicacion']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php include 'widget_comentarios.php'; ?>
    </main>
</body>
</html>
