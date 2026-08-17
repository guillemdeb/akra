<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$actividad = $_GET['actividad'] ?? 'general';

// Obtenir interessos de l'usuari actual
$sql = "SELECT GROUP_CONCAT(interes_id) as mis_intereses
        FROM usuario_interes
        WHERE usuario_id = :usuario_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$mis_intereses_raw = $stmt->fetchColumn();
$mis_intereses = $mis_intereses_raw ? explode(',', $mis_intereses_raw) : [];

// Mapeig d'activitats a interessos
$actividad_intereses = [
    'hablar' => ['Café y conversación', 'Lectura', 'Teatro', 'Historia', 'Idiomas'],
    'cine' => ['Cine', 'Teatro', 'Música'],
    'caminar' => ['Caminar', 'Naturaleza', 'Fotografía', 'Mascotas'],
    'deporte' => ['Yoga', 'Natación', 'Ciclismo', 'Bailar'],
    'cultura' => ['Lectura', 'Historia', 'Teatro', 'Cine', 'Música', 'Museos'],
    'cocinar' => ['Cocina', 'Café y conversación'],
    'tecnologia' => ['Informática', 'Redes Sociales', 'Fotografía'],
    'naturaleza' => ['Jardinería', 'Naturaleza', 'Caminar', 'Mascotas'],
    'social' => ['Café y conversación', 'Voluntariado', 'Juegos de mesa', 'Cartas']
];

// Obtenir suggeriments segons activitat
if ($actividad !== 'general') {
    $intereses_buscados = $actividad_intereses[$actividad] ?? [];
    
    if (!empty($intereses_buscados)) {
        $placeholders = implode(',', array_fill(0, count($intereses_buscados), '?'));
        
        // Buscar usuaris amb aquests interessos
        $sql = "SELECT DISTINCT u.id, u.nombre, u.foto, u.edad, u.genero, u.ubicacion, u.descripcion,
                COUNT(DISTINCT ui.interes_id) as intereses_comunes,
                GROUP_CONCAT(DISTINCT i.nombre SEPARATOR ', ') as intereses_nombres,
                (SELECT COUNT(*) FROM amistades 
                 WHERE (usuario_id = :usuario_id AND amigo_id = u.id) 
                 OR (usuario_id = u.id AND amigo_id = :usuario_id2)) as ya_conectado,
                (SELECT estado FROM amistades 
                 WHERE ((usuario_id = :usuario_id3 AND amigo_id = u.id) 
                 OR (usuario_id = u.id AND amigo_id = :usuario_id4)) LIMIT 1) as estado_amistad
                FROM usuarios u
                JOIN usuario_interes ui ON u.id = ui.usuario_id
                JOIN intereses i ON ui.interes_id = i.id
                WHERE u.id != :usuario_id5
                AND u.activo = 1
                AND i.nombre IN ($placeholders)
                GROUP BY u.id
                ORDER BY intereses_comunes DESC, RAND()
                LIMIT 20";
        
        $params = array_merge(
            [
                'usuario_id' => $usuario_id,
                'usuario_id2' => $usuario_id,
                'usuario_id3' => $usuario_id,
                'usuario_id4' => $usuario_id,
                'usuario_id5' => $usuario_id
            ],
            $intereses_buscados
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sugerencias = [];
    }
} else {
    // Suggeriments generals (usuaris amb interessos comuns)
    if (!empty($mis_intereses)) {
        $placeholders = implode(',', array_fill(0, count($mis_intereses), '?'));
        
        $sql = "SELECT DISTINCT u.id, u.nombre, u.foto, u.edad, u.genero, u.ubicacion, u.descripcion,
                COUNT(DISTINCT ui.interes_id) as intereses_comunes,
                GROUP_CONCAT(DISTINCT i.nombre SEPARATOR ', ') as intereses_nombres,
                (SELECT COUNT(*) FROM amistades 
                 WHERE (usuario_id = :usuario_id AND amigo_id = u.id) 
                 OR (usuario_id = u.id AND amigo_id = :usuario_id2)) as ya_conectado,
                (SELECT estado FROM amistades 
                 WHERE ((usuario_id = :usuario_id3 AND amigo_id = u.id) 
                 OR (usuario_id = u.id AND amigo_id = :usuario_id4)) LIMIT 1) as estado_amistad
                FROM usuarios u
                JOIN usuario_interes ui ON u.id = ui.usuario_id
                JOIN intereses i ON ui.interes_id = i.id
                WHERE u.id != :usuario_id5
                AND u.activo = 1
                AND ui.interes_id IN ($placeholders)
                GROUP BY u.id
                HAVING intereses_comunes >= 2
                ORDER BY intereses_comunes DESC, RAND()
                LIMIT 30";
        
        $params = array_merge(
            [
                'usuario_id' => $usuario_id,
                'usuario_id2' => $usuario_id,
                'usuario_id3' => $usuario_id,
                'usuario_id4' => $usuario_id,
                'usuario_id5' => $usuario_id
            ],
            $mis_intereses
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sugerencias = [];
    }
}

// Títols segons activitat
$titulos = [
    'general' => 'Personas que te pueden interesar',
    'hablar' => 'Personas para conversar',
    'cine' => 'Amantes del cine cerca de ti',
    'caminar' => 'Compañeros de paseo',
    'deporte' => 'Compañeros de deporte',
    'cultura' => 'Amantes de la cultura',
    'cocinar' => 'Compañeros de cocina',
    'tecnologia' => 'Aficionados a la tecnología',
    'naturaleza' => 'Amantes de la naturaleza',
    'social' => 'Personas sociables'
];

$titulo = $titulos[$actividad] ?? 'Sugerencias';
$descripcion_actividad = [
    'general' => 'Basado en tus intereses comunes',
    'hablar' => 'Gente que también le gusta conversar y compartir',
    'cine' => 'Personas interesadas en ir al cine',
    'caminar' => 'Personas que disfrutan caminando',
    'deporte' => 'Personas activas que practican deportes',
    'cultura' => 'Interesados en museos, teatro y cultura',
    'cocinar' => 'Apasionados de la gastronomía',
    'tecnologia' => 'Interesados en tecnología e informática',
    'naturaleza' => 'Amantes de jardines, plantas y naturaleza',
    'social' => 'Personas que disfrutan de actividades sociales'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-principal: #4A90E2;
            --color-success: #7ED321;
            --color-warning: #F39C12;
            --color-fondo: #F5F5F5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: var(--color-fondo); line-height: 1.6; }
        
        header { background: var(--color-principal); color: white; padding: 15px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .header-nav { display: flex; gap: 15px; flex-wrap: wrap; }
        .header-nav a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .header-nav a:hover { background: rgba(255,255,255,0.2); }
        
        main { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .page-header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .page-header h1 { font-size: 2rem; color: #333; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 1.1rem; }
        
        .filtros-rapidos { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .filtros-rapidos h3 { color: #333; margin-bottom: 15px; }
        .filtro-chips { display: flex; gap: 10px; flex-wrap: wrap; }
        .chip { padding: 10px 20px; background: #f0f0f0; border-radius: 25px; text-decoration: none; color: #666; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; border: 2px solid transparent; }
        .chip:hover { background: #e0e0e0; }
        .chip.active { background: var(--color-principal); color: white; border-color: var(--color-principal); }
        .chip i { font-size: 1.1rem; }
        
        .sugerencias-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .persona-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.3s; }
        .persona-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        
        .persona-header { position: relative; padding: 20px; background: linear-gradient(135deg, var(--color-principal) 0%, #357ABD 100%); }
        .persona-foto { width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; margin: 0 auto; display: block; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .compatibilidad { position: absolute; top: 15px; right: 15px; background: var(--color-success); color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        
        .persona-info { padding: 20px; }
        .persona-nombre { font-size: 1.4rem; color: #333; text-align: center; margin-bottom: 5px; }
        .persona-detalles { text-align: center; color: #777; font-size: 0.95rem; margin-bottom: 15px; }
        .persona-detalles i { margin: 0 3px; color: var(--color-principal); }
        
        .persona-descripcion { color: #555; line-height: 1.6; margin-bottom: 15px; font-size: 0.95rem; }
        
        .persona-intereses { margin-bottom: 20px; }
        .persona-intereses h5 { color: #333; margin-bottom: 10px; font-size: 0.9rem; }
        .intereses-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .interes-tag { padding: 5px 12px; background: #e3f2fd; color: var(--color-principal); border-radius: 15px; font-size: 0.85rem; font-weight: 500; }
        
        .persona-actions { display: flex; gap: 10px; padding: 0 20px 20px; }
        .btn-accion { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.3s; font-size: 0.95rem; }
        .btn-conectar { background: var(--color-success); color: white; }
        .btn-conectar:hover { background: #6DB91D; transform: translateY(-2px); }
        .btn-ver-perfil { background: #f0f0f0; color: #666; }
        .btn-ver-perfil:hover { background: #e0e0e0; }
        .btn-ya-amigo { background: #ddd; color: #999; cursor: default; }
        .btn-pendiente { background: var(--color-warning); color: white; cursor: default; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        .empty-state i { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
        
        @media (max-width: 768px) {
            .sugerencias-grid { grid-template-columns: 1fr; }
            .filtro-chips { justify-content: center; }
            .page-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><i class="fas fa-lightbulb"></i> Descubre Personas</h1>
            <nav class="header-nav">
                <a href="dashboard.php"><i class="fas fa-user"></i> Perfil</a>
                <a href="timeline.php"><i class="fas fa-home"></i> Timeline</a>
                <a href="feed.php"><i class="fas fa-users"></i> Amigos</a>
                <a href="eventos.php"><i class="fas fa-calendar"></i> Eventos</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1><i class="fas fa-magic"></i> <?php echo $titulo; ?></h1>
            <p><?php echo $descripcion_actividad[$actividad] ?? ''; ?></p>
        </div>

        <div class="filtros-rapidos">
            <h3><i class="fas fa-filter"></i> ¿Qué te apetece hacer?</h3>
            <div class="filtro-chips">
                <a href="sugerencias.php?actividad=general" class="chip <?php echo $actividad === 'general' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Ver todos
                </a>
                <a href="sugerencias.php?actividad=hablar" class="chip <?php echo $actividad === 'hablar' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Ganas de hablar
                </a>
                <a href="sugerencias.php?actividad=cine" class="chip <?php echo $actividad === 'cine' ? 'active' : ''; ?>">
                    <i class="fas fa-film"></i> Ir al cine
                </a>
                <a href="sugerencias.php?actividad=caminar" class="chip <?php echo $actividad === 'caminar' ? 'active' : ''; ?>">
                    <i class="fas fa-walking"></i> Caminar
                </a>
                <a href="sugerencias.php?actividad=deporte" class="chip <?php echo $actividad === 'deporte' ? 'active' : ''; ?>">
                    <i class="fas fa-running"></i> Hacer deporte
                </a>
                <a href="sugerencias.php?actividad=cultura" class="chip <?php echo $actividad === 'cultura' ? 'active' : ''; ?>">
                    <i class="fas fa-theater-masks"></i> Cultura
                </a>
                <a href="sugerencias.php?actividad=cocinar" class="chip <?php echo $actividad === 'cocinar' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Cocinar
                </a>
                <a href="sugerencias.php?actividad=naturaleza" class="chip <?php echo $actividad === 'naturaleza' ? 'active' : ''; ?>">
                    <i class="fas fa-tree"></i> Naturaleza
                </a>
                <a href="sugerencias.php?actividad=social" class="chip <?php echo $actividad === 'social' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Socializar
                </a>
            </div>
        </div>

        <?php if (empty($sugerencias)): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3 style="color: #666; margin-bottom: 10px;">No encontramos sugerencias</h3>
                <p style="color: #999;">Intenta con otra actividad o completa más intereses en tu perfil</p>
                <br>
                <a href="editar_perfil.php" style="display: inline-block; padding: 12px 30px; background: var(--color-principal); color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    <i class="fas fa-heart"></i> Añadir más intereses
                </a>
            </div>
        <?php else: ?>
            <div class="sugerencias-grid">
                <?php foreach ($sugerencias as $persona): 
                    $intereses_array = explode(', ', $persona['intereses_nombres']);
                    $intereses_mostrar = array_slice($intereses_array, 0, 5);
                    $mas_intereses = count($intereses_array) - count($intereses_mostrar);
                ?>
                <div class="persona-card">
                    <div class="persona-header">
                        <?php if ($persona['intereses_comunes'] >= 3): ?>
                        <div class="compatibilidad">
                            <i class="fas fa-star"></i> <?php echo $persona['intereses_comunes']; ?> en común
                        </div>
                        <?php endif; ?>
                        <img src="uploads/<?php echo htmlspecialchars($persona['foto'] ?: 'default.png'); ?>" 
                             alt="<?php echo htmlspecialchars($persona['nombre']); ?>" 
                             class="persona-foto">
                    </div>

                    <div class="persona-info">
                        <h3 class="persona-nombre"><?php echo htmlspecialchars($persona['nombre']); ?></h3>
                        <div class="persona-detalles">
                            <i class="fas fa-birthday-cake"></i> <?php echo $persona['edad']; ?> años
                            <i class="fas fa-venus-mars"></i> <?php echo $persona['genero']; ?>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($persona['ubicacion']); ?>
                        </div>

                        <?php if ($persona['descripcion']): ?>
                        <p class="persona-descripcion">
                            <?php echo htmlspecialchars(substr($persona['descripcion'], 0, 100)) . (strlen($persona['descripcion']) > 100 ? '...' : ''); ?>
                        </p>
                        <?php endif; ?>

                        <div class="persona-intereses">
                            <h5><i class="fas fa-heart"></i> Intereses comunes:</h5>
                            <div class="intereses-tags">
                                <?php foreach ($intereses_mostrar as $interes): ?>
                                    <span class="interes-tag"><?php echo htmlspecialchars($interes); ?></span>
                                <?php endforeach; ?>
                                <?php if ($mas_intereses > 0): ?>
                                    <span class="interes-tag">+<?php echo $mas_intereses; ?> más</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="persona-actions">
                        <?php if ($persona['estado_amistad'] === 'aceptada'): ?>
                            <span class="btn-accion btn-ya-amigo">
                                <i class="fas fa-check"></i> Ya sois amigos
                            </span>
                        <?php elseif ($persona['estado_amistad'] === 'pendiente'): ?>
                            <span class="btn-accion btn-pendiente">
                                <i class="fas fa-clock"></i> Solicitud enviada
                            </span>
                        <?php else: ?>
                            <a href="enviar_solicitud.php?usuario_id=<?php echo $persona['id']; ?>&redirect=sugerencias&actividad=<?php echo $actividad; ?>" 
                               class="btn-accion btn-conectar">
                                <i class="fas fa-user-plus"></i> Conectar
                            </a>
                        <?php endif; ?>
                        
                        <a href="perfil_usuario.php?id=<?php echo $persona['id']; ?>" class="btn-accion btn-ver-perfil">
                            <i class="fas fa-eye"></i> Ver perfil
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
