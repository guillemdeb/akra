<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];

// Obtenir publicacions (meves + amics)
$sql = "SELECT p.*, u.nombre as usuario_nombre, u.foto as usuario_foto,
        (SELECT COUNT(*) FROM publicacion_likes WHERE publicacion_id = p.id) as num_likes,
        (SELECT COUNT(*) FROM publicacion_comentarios WHERE publicacion_id = p.id) as num_comentarios,
        (SELECT COUNT(*) FROM publicacion_likes WHERE publicacion_id = p.id AND usuario_id = :usuario_id) as me_gusta,
        e.titulo as evento_titulo
        FROM publicaciones p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN eventos e ON p.evento_id = e.id
        LEFT JOIN amistades a1 ON (a1.usuario_id = :usuario_id2 AND a1.amigo_id = p.usuario_id AND a1.estado = 'aceptada')
        LEFT JOIN amistades a2 ON (a2.usuario_id = p.usuario_id AND a2.amigo_id = :usuario_id3 AND a2.estado = 'aceptada')
        WHERE (p.visibilidad = 'publico' OR (p.visibilidad = 'amigos' AND (a1.id IS NOT NULL OR a2.id IS NOT NULL OR p.usuario_id = :usuario_id4)))
        ORDER BY p.fecha_publicacion DESC
        LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'usuario_id2' => $usuario_id,
    'usuario_id3' => $usuario_id,
    'usuario_id4' => $usuario_id
]);
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = 'Inici';
require_once "includes/pwa_head.php";
?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'feed'; require_once "includes/navbar.php"; ?>
<style>
    .comentarios { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
    .comentario { padding: 10px 14px; background: var(--bg); border-radius: var(--radius-md); margin-bottom: 8px; }
    .comentario-header { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
    .comentario-header img { width: 32px; height: 32px; border-radius: 50%; object-fit:cover; }
    .comentario-texto { color: var(--text); line-height: 1.5; font-size: 0.92rem; }
    .form-comentario { display: flex; gap: 8px; margin-top: 12px; }
    .form-comentario input { flex: 1; padding: 9px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-full); font-family: var(--font); font-size: 0.9rem; }
    .form-comentario input:focus { outline: none; border-color: var(--primary); }
    .form-comentario button { background: var(--primary); color: white; border: none; padding: 9px 16px; border-radius: var(--radius-full); cursor: pointer; font-family: var(--font); font-weight: 700; }
    .publicacion-evento { background: var(--primary-light); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 14px; border-left: 4px solid var(--primary); }
    .publicacion-evento h5 { color: var(--primary); margin-bottom: 5px; }
</style>

    <main style="max-width:800px; margin:0 auto; padding:20px 16px;">
        <!-- Formulari per crear publicació -->
        <div class="post-new">
            <form action="crear_publicacion.php" method="POST">
                <div class="post-new-inner">
                    <img src="uploads/<?= htmlspecialchars($_nav_user['foto'] ?? 'default.png') ?>"
                         class="user-avatar-sm"
                         style="width:44px;height:44px;"
                         onerror="this.src='uploads/default.png'">
                    <textarea name="contenido" placeholder="Què vols compartir avui?" required maxlength="1000"></textarea>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
                    <select name="visibilidad" class="form-control" style="width:auto;padding:8px 14px;">
                        <option value="amigos">Només amics</option>
                        <option value="publico">Tothom</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Publicar
                    </button>
                </div>
            </form>
        </div>

        <!-- Publicacions -->
        <?php if (empty($publicaciones)): ?>
            <div class="empty-state">
                <i class="fas fa-newspaper"></i>
                <h3 style="color: #666; margin-bottom: 10px;">No hay publicaciones todavía</h3>
                <p style="color: #999;">Sé el primero en compartir algo con tus amigos</p>
            </div>
        <?php else: ?>
            <?php foreach ($publicaciones as $pub): 
                $fecha_pub = new DateTime($pub['fecha_publicacion']);
                $ahora = new DateTime();
                $diff = $ahora->diff($fecha_pub);
                
                if ($diff->days > 7) {
                    $tiempo = $fecha_pub->format('d/m/Y');
                } elseif ($diff->days > 0) {
                    $tiempo = 'Hace ' . $diff->days . ' día' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $tiempo = 'Hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
                } else {
                    $tiempo = 'Hace ' . max(1, $diff->i) . ' minuto' . ($diff->i > 1 ? 's' : '');
                }
            ?>
            <div class="post-card fade-in" id="pub-<?php echo $pub['id']; ?>">
                <div class="post-header">
                    <img src="uploads/<?php echo htmlspecialchars($pub['usuario_foto'] ?: 'default.png'); ?>"
                         class="user-avatar-sm"
                         onerror="this.src='uploads/default.png'"
                         alt="">
                    <div class="post-author">
                        <div class="post-author-name"><?php echo htmlspecialchars($pub['usuario_nombre']); ?></div>
                        <div class="post-time"><i class="fas fa-clock"></i> <?php echo $tiempo; ?></div>
                    </div>
                </div>

                <div class="post-content">
                    <?php echo htmlspecialchars($pub['contenido']); ?>
                </div>

                <?php if ($pub['evento_id']): ?>
                <div class="publicacion-evento" style="margin:0 18px 14px;">
                    <h5><i class="fas fa-calendar-check"></i> Esdeveniment relacionat</h5>
                    <p><?php echo htmlspecialchars($pub['evento_titulo']); ?></p>
                    <a href="ver_evento.php?id=<?php echo $pub['evento_id']; ?>" class="btn btn-ghost btn-sm mt-1">Veure →</a>
                </div>
                <?php endif; ?>

                <?php if ($pub['imagen']): ?>
                <img src="uploads/<?php echo htmlspecialchars($pub['imagen']); ?>" class="post-image" alt="Imatge">
                <?php endif; ?>

                <div class="post-actions">
                    <button class="post-action-btn <?php echo $pub['me_gusta'] ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $pub['id']; ?>)">
                        <i class="fas fa-heart"></i>
                        <span id="likes-<?php echo $pub['id']; ?>"><?php echo $pub['num_likes']; ?></span>
                        M'agrada
                    </button>
                    <button class="post-action-btn" onclick="toggleComentarios(<?php echo $pub['id']; ?>)">
                        <i class="fas fa-comment"></i>
                        <?php echo $pub['num_comentarios']; ?> Comentaris
                    </button>
                </div>

                <!-- Comentaris -->
                <div class="comentarios" id="comentarios-<?php echo $pub['id']; ?>" style="display: none;">
                    <?php
                    $sql_com = "SELECT c.*, u.nombre, u.foto 
                                FROM publicacion_comentarios c
                                JOIN usuarios u ON c.usuario_id = u.id
                                WHERE c.publicacion_id = :pub_id
                                ORDER BY c.fecha_comentario ASC";
                    $stmt_com = $pdo->prepare($sql_com);
                    $stmt_com->execute(['pub_id' => $pub['id']]);
                    $comentarios = $stmt_com->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($comentarios as $com):
                        $fecha_com = new DateTime($com['fecha_comentario']);
                    ?>
                    <div class="comentario">
                        <div class="comentario-header">
                            <img src="uploads/<?php echo htmlspecialchars($com['foto'] ?: 'default.png'); ?>" alt="">
                            <strong><?php echo htmlspecialchars($com['nombre']); ?></strong>
                            <span>· <?php echo $fecha_com->format('d/m H:i'); ?></span>
                        </div>
                        <div class="comentario-texto"><?php echo htmlspecialchars($com['comentario']); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <form class="form-comentario" action="agregar_comentario.php" method="POST">
                        <input type="hidden" name="publicacion_id" value="<?php echo $pub['id']; ?>">
                        <input type="text" name="comentario" placeholder="Escriu un comentari..." required maxlength="500">
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        function toggleLike(publicacionId) {
            fetch('toggle_like.php?id=' + publicacionId)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('likes-' + publicacionId).textContent = data.likes;
                    const btn = event.currentTarget;
                    btn.classList.toggle('liked', data.me_gusta);
                });
        }

        function toggleComentarios(publicacionId) {
            const comentarios = document.getElementById('comentarios-' + publicacionId);
            comentarios.style.display = comentarios.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
