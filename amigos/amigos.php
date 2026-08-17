<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";
require_once "notificaciones_helper.php";

$usuario_id = $_SESSION['usuario_id'];

// Obtenir sol·licituds pendents
$sql = "SELECT a.*, u.nombre, u.foto, u.ubicacion, u.intereses
        FROM amistades a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.amigo_id = :usuario_id AND a.estado = 'pendiente'
        ORDER BY a.fecha_solicitud DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtenir amics confirmats
$sql = "SELECT u.id, u.nombre, u.foto, u.ubicacion, u.intereses,
        CASE 
            WHEN a1.usuario_id = :usuario_id THEN a1.fecha_solicitud
            ELSE a2.fecha_solicitud
        END as fecha_amistad
        FROM usuarios u
        LEFT JOIN amistades a1 ON (a1.usuario_id = :usuario_id2 AND a1.amigo_id = u.id AND a1.estado = 'aceptada')
        LEFT JOIN amistades a2 ON (a2.usuario_id = u.id AND a2.amigo_id = :usuario_id3 AND a2.estado = 'aceptada')
        WHERE (a1.id IS NOT NULL OR a2.id IS NOT NULL)
        ORDER BY fecha_amistad DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'usuario_id2' => $usuario_id,
    'usuario_id3' => $usuario_id
]);
$amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Suggeriments d'amics (amics d'amics que no són amics meus)
$sql = "SELECT DISTINCT u.id, u.nombre, u.foto, u.ubicacion, u.intereses,
        COUNT(DISTINCT a2.usuario_id) as amigos_comunes
        FROM usuarios u
        JOIN amistades a1 ON (
            (a1.usuario_id = u.id OR a1.amigo_id = u.id) 
            AND a1.estado = 'aceptada'
        )
        JOIN amistades a2 ON (
            (a2.usuario_id IN (
                SELECT CASE 
                    WHEN a.usuario_id = :usuario_id THEN a.amigo_id 
                    ELSE a.usuario_id 
                END
                FROM amistades a
                WHERE (a.usuario_id = :usuario_id2 OR a.amigo_id = :usuario_id3)
                AND a.estado = 'aceptada'
            ))
            AND (a2.usuario_id = u.id OR a2.amigo_id = u.id)
            AND a2.estado = 'aceptada'
        )
        WHERE u.id != :usuario_id4
        AND u.id NOT IN (
            SELECT CASE 
                WHEN a.usuario_id = :usuario_id5 THEN a.amigo_id 
                ELSE a.usuario_id 
            END
            FROM amistades a
            WHERE (a.usuario_id = :usuario_id6 OR a.amigo_id = :usuario_id7)
            AND a.estado IN ('aceptada', 'pendiente')
        )
        GROUP BY u.id
        ORDER BY amigos_comunes DESC
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'usuario_id2' => $usuario_id,
    'usuario_id3' => $usuario_id,
    'usuario_id4' => $usuario_id,
    'usuario_id5' => $usuario_id,
    'usuario_id6' => $usuario_id,
    'usuario_id7' => $usuario_id
]);
$sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Amigos - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-danger: #E74C3C;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); }
        
        header { background: var(--color-principal); color: white; padding: 15px 20px; }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .header-nav { display: flex; gap: 15px; }
        .header-nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; }
        .header-nav a:hover { background: rgba(255,255,255,0.2); }
        
        main { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .seccion { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .seccion h2 { color: #333; margin-bottom: 20px; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .seccion h2 i { color: var(--color-principal); }
        
        .solicitudes-grid, .amigos-grid, .sugerencias-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
        }
        
        .user-card { 
            background: #f8f9fa; 
            border-radius: 10px; 
            padding: 20px; 
            transition: all 0.3s; 
            border: 2px solid transparent;
        }
        .user-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .user-card.solicitud { border-color: var(--color-principal); background: #e3f2fd; }
        
        .user-header { display: flex; gap: 15px; margin-bottom: 15px; }
        .user-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
        .user-info h4 { color: #333; margin-bottom: 5px; }
        .user-info p { color: #777; font-size: 0.9rem; }
        
        .user-intereses { 
            display: flex; 
            gap: 5px; 
            flex-wrap: wrap; 
            margin: 10px 0; 
        }
        .tag-interes { 
            background: #e0e0e0; 
            color: #555; 
            padding: 4px 10px; 
            border-radius: 15px; 
            font-size: 0.8rem; 
        }
        
        .user-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { 
            flex: 1; 
            padding: 10px; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: all 0.3s; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center;
        }
        .btn-aceptar { background: var(--color-success); color: white; }
        .btn-aceptar:hover { background: #6DB91D; }
        .btn-rechazar { background: var(--color-danger); color: white; }
        .btn-rechazar:hover { background: #C0392B; }
        .btn-agregar { background: var(--color-principal); color: white; }
        .btn-agregar:hover { background: #357ABD; }
        .btn-mensaje { background: #888; color: white; }
        .btn-mensaje:hover { background: #666; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #ddd; }
        
        .badge { 
            background: var(--color-danger); 
            color: white; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .solicitudes-grid, .amigos-grid, .sugerencias-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><i class="fas fa-user-friends"></i> Mis Amigos</h1>
            <nav class="header-nav">
                <a href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="eventos.php"><i class="fas fa-calendar"></i> Eventos</a>
                <a href="mensajes.php"><i class="fas fa-comments"></i> Mensajes</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- Solicitudes pendientes -->
        <?php if (!empty($solicitudes)): ?>
        <div class="seccion">
            <h2>
                <i class="fas fa-user-clock"></i> 
                Solicitudes de Amistad 
                <span class="badge"><?php echo count($solicitudes); ?></span>
            </h2>
            <div class="solicitudes-grid">
                <?php foreach ($solicitudes as $sol): ?>
                <div class="user-card solicitud">
                    <div class="user-header">
                        <img src="uploads/<?php echo htmlspecialchars($sol['foto'] ?: 'default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($sol['nombre']); ?>" 
                             class="user-avatar">
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($sol['nombre']); ?></h4>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($sol['ubicacion']); ?></p>
                            <p style="font-size: 0.8rem; color: #999;">
                                Hace <?php 
                                $diff = time() - strtotime($sol['fecha_solicitud']);
                                if ($diff < 3600) echo floor($diff/60) . ' min';
                                elseif ($diff < 86400) echo floor($diff/3600) . ' h';
                                else echo floor($diff/86400) . ' días';
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($sol['intereses'])): ?>
                    <div class="user-intereses">
                        <?php 
                        $intereses = explode(',', $sol['intereses']);
                        foreach (array_slice($intereses, 0, 3) as $interes): 
                        ?>
                            <span class="tag-interes"><?php echo htmlspecialchars(trim($interes)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-actions">
                        <button class="btn btn-aceptar" onclick="responderSolicitud(<?php echo $sol['id']; ?>, 'aceptar')">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                        <button class="btn btn-rechazar" onclick="responderSolicitud(<?php echo $sol['id']; ?>, 'rechazar')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mis amigos -->
        <div class="seccion">
            <h2><i class="fas fa-users"></i> Mis Amigos (<?php echo count($amigos); ?>)</h2>
            <?php if (empty($amigos)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Todavía no tienes amigos</p>
                    <p>Empieza a conectar con personas con intereses similares</p>
                </div>
            <?php else: ?>
                <div class="amigos-grid">
                    <?php foreach ($amigos as $amigo): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <img src="uploads/<?php echo htmlspecialchars($amigo['foto'] ?: 'default.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($amigo['nombre']); ?>" 
                                 class="user-avatar">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($amigo['nombre']); ?></h4>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($amigo['ubicacion']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($amigo['intereses'])): ?>
                        <div class="user-intereses">
                            <?php 
                            $intereses = explode(',', $amigo['intereses']);
                            foreach (array_slice($intereses, 0, 3) as $interes): 
                            ?>
                                <span class="tag-interes"><?php echo htmlspecialchars(trim($interes)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="user-actions">
                            <a href="perfil.php?id=<?php echo $amigo['id']; ?>" class="btn btn-agregar">
                                <i class="fas fa-eye"></i> Ver Perfil
                            </a>
                            <a href="mensajes.php?chat=<?php echo $amigo['id']; ?>" class="btn btn-mensaje">
                                <i class="fas fa-comment"></i> Mensaje
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sugerencias -->
        <?php if (!empty($sugerencias)): ?>
        <div class="seccion">
            <h2><i class="fas fa-user-plus"></i> Gente que quizás conozcas</h2>
            <div class="sugerencias-grid">
                <?php foreach ($sugerencias as $sug): ?>
                <div class="user-card">
                    <div class="user-header">
                        <img src="uploads/<?php echo htmlspecialchars($sug['foto'] ?: 'default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($sug['nombre']); ?>" 
                             class="user-avatar">
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars($sug['nombre']); ?></h4>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($sug['ubicacion']); ?></p>
                            <p style="font-size: 0.85rem; color: #4A90E2;">
                                <i class="fas fa-users"></i> <?php echo $sug['amigos_comunes']; ?> amigos en común
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($sug['intereses'])): ?>
                    <div class="user-intereses">
                        <?php 
                        $intereses = explode(',', $sug['intereses']);
                        foreach (array_slice($intereses, 0, 3) as $interes): 
                        ?>
                            <span class="tag-interes"><?php echo htmlspecialchars(trim($interes)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="user-actions">
                        <button class="btn btn-agregar" onclick="enviarSolicitud(<?php echo $sug['id']; ?>)">
                            <i class="fas fa-user-plus"></i> Agregar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
    function responderSolicitud(solicitudId, accion) {
        if (accion === 'rechazar' && !confirm('¿Seguro que quieres rechazar esta solicitud?')) {
            return;
        }
        
        fetch('api_amistades.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `accion=${accion}&solicitud_id=${solicitudId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error al procesar la solicitud');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
    
    function enviarSolicitud(usuarioId) {
        fetch('api_amistades.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `accion=enviar&usuario_id=${usuarioId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Solicitud enviada correctamente');
                location.reload();
            } else {
                alert(data.error || 'Error al enviar la solicitud');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
        });
    }
    </script>
</body>
</html>
