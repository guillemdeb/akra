<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$perfil_id = (int)($_GET['id'] ?? 0);

if ($perfil_id <= 0) {
    header("Location: feed.php");
    exit();
}

// Obtenir dades de l'usuari
$sql = "SELECT nombre, edad, genero, ubicacion, foto, descripcion, 
               mostrar_telefono, mostrar_email, telefono, email
        FROM usuarios 
        WHERE id = :id AND activo = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $perfil_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: feed.php");
    exit();
}

// Obtenir interessos
$sql = "SELECT i.nombre, i.icono 
        FROM usuario_interes ui
        INNER JOIN intereses i ON i.id = ui.interes_id
        WHERE ui.usuario_id = :id
        ORDER BY i.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $perfil_id]);
$intereses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar relació d'amistat
$sql = "SELECT estado FROM amistades 
        WHERE (usuario_id = :usuario_id AND amigo_id = :perfil_id) 
           OR (usuario_id = :perfil_id AND amigo_id = :usuario_id)";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id, 'perfil_id' => $perfil_id]);
$relacion = $stmt->fetch(PDO::FETCH_ASSOC);

$estado_amistad = $relacion['estado'] ?? 'ninguna';
?>
<?php $page_title = 'Perfil d'usuari'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'amigos'; require_once "includes/navbar.php"; ?>

    <main>
        <!-- CAPÇALERA DEL PERFIL -->
        <div class="profile-header">
            <img src="uploads/<?php echo htmlspecialchars($usuario['foto'] ?: 'default.png'); ?>" 
                 alt="<?php echo htmlspecialchars($usuario['nombre']); ?>" 
                 class="profile-avatar">
            
            <h1><?php echo htmlspecialchars($usuario['nombre']); ?></h1>
            
            <div class="profile-meta">
                <?php echo $usuario['edad']; ?> años · 
                <?php echo htmlspecialchars($usuario['genero']); ?> · 
                📍 <?php echo htmlspecialchars($usuario['ubicacion']); ?>
            </div>

            <!-- ACCIONS SEGONS L'ESTAT D'AMISTAT -->
            <div class="actions">
                <?php if ($estado_amistad === 'ninguna'): ?>
                    <a href="enviar_solicitud.php?id=<?php echo $perfil_id; ?>" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Enviar solicitud
                    </a>
                <?php elseif ($estado_amistad === 'pendiente'): ?>
                    <button class="btn btn-disabled" disabled>
                        <i class="fas fa-clock"></i> Solicitud pendiente
                    </button>
                <?php elseif ($estado_amistad === 'aceptada'): ?>
                    <a href="mensajes.php?usuario=<?php echo $perfil_id; ?>" class="btn btn-primary">
                        <i class="fas fa-comment"></i> Enviar mensaje
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- SOBRE MI -->
        <?php if (!empty($usuario['descripcion'])): ?>
            <div class="section">
                <h2><i class="fas fa-info-circle"></i> Sobre mí</h2>
                <p><?php echo nl2br(htmlspecialchars($usuario['descripcion'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- INFORMACIÓ DE CONTACTE -->
        <?php if ($estado_amistad === 'aceptada' && ($usuario['mostrar_telefono'] || $usuario['mostrar_email'])): ?>
            <div class="section">
                <h2><i class="fas fa-address-book"></i> Información de contacto</h2>
                <div class="contact-info">
                    <?php if ($usuario['mostrar_telefono'] && !empty($usuario['telefono'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($usuario['telefono']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($usuario['mostrar_email'] && !empty($usuario['email'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($usuario['email']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- INTERESSOS -->
        <?php if (count($intereses) > 0): ?>
            <div class="section">
                <h2><i class="fas fa-heart"></i> Intereses</h2>
                <div class="interests-grid">
                    <?php foreach ($intereses as $interes): ?>
                        <div class="interest-item">
                            <i class="<?php echo htmlspecialchars($interes['icono']); ?>"></i>
                            <span><?php echo htmlspecialchars($interes['nombre']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
