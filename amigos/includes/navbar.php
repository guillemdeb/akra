<?php
/**
 * NAVBAR UNIFICADA - RedAmigos
 * Inclou: top navbar (desktop) + bottom navbar (mòbil estil Facebook)
 * 
 * Ús: require_once "includes/navbar.php";
 * Variables esperades: $pdo, $_SESSION['usuario_id']
 * 
 * Paràmetre opcional: $active_page = 'feed'|'amigos'|'mensajes'|'notificaciones'|'perfil'
 */

if (!isset($active_page)) $active_page = '';

// Dades de l'usuari logat
$_nav_user = null;
$_nav_pendientes = 0;
$_nav_mensajes = 0;
$_nav_notif = 0;

if (isset($_SESSION['usuario_id']) && isset($pdo)) {
    $nav_id = $_SESSION['usuario_id'];
    
    // Foto i nom
    $s = $pdo->prepare("SELECT nombre, foto FROM usuarios WHERE id = :id");
    $s->execute(['id' => $nav_id]);
    $_nav_user = $s->fetch(PDO::FETCH_ASSOC);
    
    // Sol·licituds d'amistat pendents
    $s = $pdo->prepare("SELECT COUNT(*) FROM amistades WHERE amigo_id = :id AND estado = 'pendiente'");
    $s->execute(['id' => $nav_id]);
    $_nav_pendientes = (int)$s->fetchColumn();
    
    // Missatges no llegits
    $s = $pdo->prepare("SELECT COUNT(*) FROM mensajes WHERE destinatario_id = :id AND leido = 0");
    $s->execute(['id' => $nav_id]);
    $_nav_mensajes = (int)$s->fetchColumn();
    
    // Notificacions no llegides
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :id AND leida = 0");
        $s->execute(['id' => $nav_id]);
        $_nav_notif = (int)$s->fetchColumn();
    } catch (Exception $e) { $_nav_notif = 0; }
}

// Determinar base path (per si estem dins /admin/)
$is_admin = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$base = $is_admin ? '../' : '';

$foto_src = $base . 'uploads/' . htmlspecialchars($_nav_user['foto'] ?? 'default.png');
?>

<!-- ═══════════════════════════════════
     TOP NAVBAR
═══════════════════════════════════ -->
<nav class="top-navbar">
    <div class="navbar-inner">

        <!-- Logo -->
        <a href="<?= $base ?>timeline.php" class="navbar-logo">Red<span>Amigos</span></a>

        <!-- Cerca -->
        <div class="navbar-search">
            <i class="fas fa-search search-icon"></i>
            <input type="text"
                   placeholder="Buscar personas..."
                   id="navbar-search-input"
                   autocomplete="off"
                   onkeydown="if(event.key==='Enter'&&this.value.trim()){window.location='<?= $base ?>feed.php?q='+encodeURIComponent(this.value.trim());}">
        </div>

        <!-- Links de navegació (desktop) -->
        <div class="navbar-links">
            <a href="<?= $base ?>timeline.php"
               class="navbar-link <?= $active_page === 'feed' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span class="link-label">Inici</span>
            </a>
            <a href="<?= $base ?>feed.php"
               class="navbar-link <?= $active_page === 'amigos' ? 'active' : '' ?>">
                <i class="fas fa-user-friends"></i>
                <span class="link-label">Amics</span>
            </a>
            <a href="<?= $base ?>eventos.php"
               class="navbar-link <?= $active_page === 'eventos' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span class="link-label">Esdeveniments</span>
            </a>
        </div>

        <!-- Icones dreta -->
        <div class="navbar-actions">
            <!-- Sol·licituds amistat -->
            <a href="<?= $base ?>solicitudes.php"
               class="nav-action-btn"
               title="Sol·licituds d'amistat">
                <i class="fas fa-user-plus"></i>
                <?php if ($_nav_pendientes > 0): ?>
                    <span class="nav-badge"><?= $_nav_pendientes ?></span>
                <?php endif; ?>
            </a>

            <!-- Missatges -->
            <a href="<?= $base ?>mensajes.php"
               class="nav-action-btn"
               title="Missatges">
                <i class="fas fa-comment-dots"></i>
                <?php if ($_nav_mensajes > 0): ?>
                    <span class="nav-badge"><?= $_nav_mensajes ?></span>
                <?php endif; ?>
            </a>

            <!-- Notificacions -->
            <a href="<?= $base ?>notificaciones.php"
               class="nav-action-btn"
               title="Notificacions">
                <i class="fas fa-bell"></i>
                <?php if ($_nav_notif > 0): ?>
                    <span class="nav-badge"><?= $_nav_notif ?></span>
                <?php endif; ?>
            </a>

            <!-- Avatar / Perfil -->
            <a href="<?= $base ?>dashboard.php" title="El meu perfil">
                <img src="<?= $foto_src ?>"
                     alt="<?= htmlspecialchars($_nav_user['nombre'] ?? 'Perfil') ?>"
                     class="nav-avatar"
                     onerror="this.src='<?= $base ?>uploads/default.png'">
            </a>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════
     BOTTOM NAVBAR (mòbil)
═══════════════════════════════════ -->
<nav class="bottom-navbar">
    <div class="bottom-nav-inner">

        <a href="<?= $base ?>timeline.php"
           class="bottom-nav-item <?= $active_page === 'feed' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Inici</span>
        </a>

        <a href="<?= $base ?>feed.php"
           class="bottom-nav-item <?= $active_page === 'amigos' ? 'active' : '' ?>">
            <i class="fas fa-user-friends"></i>
            <span>Amics</span>
            <?php if ($_nav_pendientes > 0): ?>
                <span class="bottom-nav-badge"><?= $_nav_pendientes ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $base ?>mensajes.php"
           class="bottom-nav-item <?= $active_page === 'mensajes' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i>
            <span>Xat</span>
            <?php if ($_nav_mensajes > 0): ?>
                <span class="bottom-nav-badge"><?= $_nav_mensajes ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $base ?>notificaciones.php"
           class="bottom-nav-item <?= $active_page === 'notificaciones' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i>
            <span>Notif.</span>
            <?php if ($_nav_notif > 0): ?>
                <span class="bottom-nav-badge"><?= $_nav_notif ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $base ?>dashboard.php"
           class="bottom-nav-item <?= $active_page === 'perfil' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>Perfil</span>
        </a>

    </div>
</nav>
