<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];

// Dades usuari
$sql = "SELECT nombre, edad, genero, telefono, email, mostrar_telefono, mostrar_email, foto, descripcion, ubicacion, codigo_pwa
        FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Interessos (només els actius)
$sqlIntereses = "SELECT i.id, i.nombre, i.icono
                 FROM intereses i
                 INNER JOIN usuario_interes ui ON i.id = ui.interes_id
                 WHERE ui.usuario_id = :usuario_id
                 ORDER BY i.nombre ASC";
$stmtInt = $pdo->prepare($sqlIntereses);
$stmtInt->execute(['usuario_id' => $usuario_id]);
$intereses = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

// Comptar amics
$sqlAmigos = "SELECT COUNT(*) as total FROM amistades 
              WHERE ((usuario_id = :id OR amigo_id = :id) AND estado = 'aceptada')";
$stmtAmigos = $pdo->prepare($sqlAmigos);
$stmtAmigos->execute(['id' => $usuario_id]);
$total_amigos = $stmtAmigos->fetch(PDO::FETCH_ASSOC)['total'];

// Comptar sol·licituds pendents
$sqlPendientes = "SELECT COUNT(*) as total FROM amistades 
                  WHERE amigo_id = :id AND estado = 'pendiente'";
$stmtPendientes = $pdo->prepare($sqlPendientes);
$stmtPendientes->execute(['id' => $usuario_id]);
$solicitudes_pendientes = $stmtPendientes->fetch(PDO::FETCH_ASSOC)['total'];
?>
<?php $page_title = 'El meu perfil'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'perfil'; require_once "includes/navbar.php"; ?>

    <main>
        <!-- TARGETA DE PERFIL -->
        <div class="profile-card">
            <img src="uploads/<?php echo htmlspecialchars($usuario['foto'] ?: 'default.png'); ?>" 
                 alt="Mi foto" 
                 class="profile-avatar">
            
            <h1 class="profile-name"><?php echo htmlspecialchars($usuario['nombre']); ?></h1>
            
            <div class="profile-meta">
                <?php echo $usuario['edad']; ?> años · 
                <?php echo htmlspecialchars($usuario['genero']); ?> · 
                📍 <?php echo htmlspecialchars($usuario['ubicacion']); ?>
            </div>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_amigos; ?></div>
                    <div class="stat-label">Amigos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($intereses); ?></div>
                    <div class="stat-label">Intereses</div>
                </div>
                <?php if ($solicitudes_pendientes > 0): ?>
                <div class="stat-item">
                    <div class="stat-number" style="color:#E74C3C;"><?php echo $solicitudes_pendientes; ?></div>
                    <div class="stat-label">Solicitudes</div>
                </div>
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
        <?php if ($usuario['mostrar_telefono'] || $usuario['mostrar_email']): ?>
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
        <div class="section">
            <h2><i class="fas fa-heart"></i> Mis intereses</h2>
            
            <?php if (count($intereses) > 0): ?>
                <div class="interests-grid">
                    <?php foreach ($intereses as $interes): ?>
                        <div class="interest-item">
                            <i class="<?php echo htmlspecialchars($interes['icono']); ?>"></i>
                            <span><?php echo htmlspecialchars($interes['nombre']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-interests">
                    <i class="fas fa-heart-broken"></i>
                    <p>Todavía no has añadido intereses.<br>
                    ¡Añádelos para encontrar personas afines!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- BOTONS D'ACCIÓ -->
        <div class="actions">
            <a href="sugerencias.php" class="btn btn-success" style="font-size: 1.1rem; padding: 18px;">
                <i class="fas fa-magic"></i> Descubre Personas
            </a>
            <a href="timeline.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Ver Timeline
            </a>
            <a href="feed.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Ver Red Social
            </a>
            <a href="eventos.php" class="btn btn-success">
                <i class="fas fa-calendar-alt"></i> Eventos y Quedadas
            </a>
            <a href="mensajes.php" class="btn btn-primary">
                <i class="fas fa-comments"></i> Mensajes
            </a>
            <a href="editar_perfil_completo.php" class="btn btn-secondary">
                <i class="fas fa-user-edit"></i> Editar mi perfil
            </a>
            <a href="editar_perfil.php" class="btn btn-secondary">
                <i class="fas fa-heart"></i> Editar intereses
            </a>

        <!-- ═══════════════════════════════════
             WIDGET CODI PERSONAL PWA
        ═══════════════════════════════════ -->
        <?php require_once "includes/pwa_codigos.php"; ?>
        <?php
        // Assignar codi si no en té (per usuaris antics)
        if (empty($usuario['codigo_pwa'])) {
            $usuario['codigo_pwa'] = pwa_assignar_codi($usuario_id);
        }
        ?>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <i class="fas fa-mobile-alt"></i> El teu codi d'instal·lació
            </div>
            <div class="card-body" style="text-align:center;">
                <p class="text-muted" style="font-size:0.88rem;margin-bottom:14px;">
                    Necessites aquest codi per instal·lar l'app en un nou dispositiu.
                    <strong>No el comparteixis.</strong>
                </p>
                <div id="codi-display" style="
                    display:inline-block;
                    background:var(--primary-light);
                    border:2px dashed var(--primary);
                    border-radius:12px;
                    padding:14px 28px;
                    font-size:1.6rem;
                    font-weight:800;
                    letter-spacing:5px;
                    color:var(--primary);
                    font-family:'Courier New',monospace;
                    margin-bottom:16px;
                    cursor:pointer;
                    transition:all 0.2s;
                " onclick="copiarCodi()" title="Clic per copiar">
                    <?php echo htmlspecialchars($usuario['codigo_pwa']); ?>
                </div>
                <div id="codi-copiat" style="display:none;color:var(--success);font-size:0.88rem;margin-bottom:8px;">
                    <i class="fas fa-check-circle"></i> Copiat al portapapers!
                </div>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                    <button onclick="copiarCodi()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-copy"></i> Copiar codi
                    </button>
                    <a href="instalar.php?c=<?php echo urlencode($usuario['codigo_pwa']); ?>"
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> Instal·lar ara
                    </a>
                    <button onclick="compartirCodi()" class="btn btn-accent btn-sm" id="btn-compartir" style="display:none;">
                        <i class="fas fa-share-alt"></i> Compartir
                    </button>
                </div>
            </div>
        </div>

        <script>
        function copiarCodi() {
            const codi = '<?php echo htmlspecialchars($usuario['codigo_pwa']); ?>';
            navigator.clipboard?.writeText(codi).then(() => {
                document.getElementById('codi-copiat').style.display = 'block';
                document.getElementById('codi-display').style.borderColor = 'var(--success)';
                setTimeout(() => {
                    document.getElementById('codi-copiat').style.display = 'none';
                    document.getElementById('codi-display').style.borderColor = '';
                }, 2000);
            });
        }
        // Web Share API (mòbil)
        if (navigator.share) {
            document.getElementById('btn-compartir').style.display = 'inline-flex';
        }
        function compartirCodi() {
            const codi = '<?php echo htmlspecialchars($usuario['codigo_pwa']); ?>';
            navigator.share({
                title: 'RedAmigos — El meu codi',
                text: 'El meu codi per instal·lar RedAmigos: ' + codi,
                url: 'https://akratechstudio.es/amigos/instalar.php?c=' + encodeURIComponent(codi)
            }).catch(() => {});
        }
        </script>

            <a href="logout.php" class="btn btn-tertiary">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </main>
</body>
</html>
