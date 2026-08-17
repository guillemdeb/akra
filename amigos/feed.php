<?php
$page_title = 'Descobrir amics';
require_once "includes/pwa_head.php";
?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }
require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$tab = $_GET['tab'] ?? 'coincidencias';
$q   = trim($_GET['q'] ?? '');

// ── Consulta per pestanya ──
if ($tab === 'amigos') {
    $sql = "SELECT u.id, u.nombre, u.edad, u.genero, u.ubicacion, u.foto, u.descripcion,
                   GROUP_CONCAT(i.icono SEPARATOR '|') AS iconos_intereses
            FROM usuarios u
            INNER JOIN amistades a ON 
                (a.usuario_id = :uid AND a.amigo_id = u.id) OR 
                (a.amigo_id = :uid2 AND a.usuario_id = u.id)
            LEFT JOIN usuario_interes ui ON ui.usuario_id = u.id
            LEFT JOIN intereses i ON i.id = ui.interes_id
            WHERE a.estado = 'aceptada' AND u.id != :uid3
            " . ($q ? "AND (u.nombre LIKE :q OR u.ubicacion LIKE :q2)" : "") . "
            GROUP BY u.id ORDER BY u.nombre ASC";
    $params = ['uid' => $usuario_id, 'uid2' => $usuario_id, 'uid3' => $usuario_id];
    if ($q) { $params['q'] = "%{$q}%"; $params['q2'] = "%{$q}%"; }
} else {
    $sql = "SELECT u.id, u.nombre, u.edad, u.genero, u.ubicacion, u.foto, u.descripcion,
                   GROUP_CONCAT(DISTINCT i.icono SEPARATOR '|') AS iconos_intereses,
                   COUNT(DISTINCT ui.interes_id) AS intereses_comunes
            FROM usuarios u
            INNER JOIN usuario_interes ui ON ui.usuario_id = u.id
            INNER JOIN usuario_interes ui2 ON ui2.interes_id = ui.interes_id AND ui2.usuario_id = :uid
            LEFT JOIN intereses i ON i.id = ui.interes_id
            WHERE u.id != :uid2 AND u.activo = 1 AND u.aprobado = 1
              " . ($q ? "AND (u.nombre LIKE :q OR u.ubicacion LIKE :q2)" : "") . "
              AND NOT EXISTS (
                  SELECT 1 FROM amistades a 
                  WHERE ((a.usuario_id = :uid3 AND a.amigo_id = u.id) OR 
                         (a.amigo_id = :uid4 AND a.usuario_id = u.id))
                    AND a.estado IN ('aceptada', 'pendiente', 'bloqueada')
              )
            GROUP BY u.id HAVING intereses_comunes > 0
            ORDER BY intereses_comunes DESC, u.nombre ASC LIMIT 30";
    $params = ['uid' => $usuario_id, 'uid2' => $usuario_id, 'uid3' => $usuario_id, 'uid4' => $usuario_id];
    if ($q) { $params['q'] = "%{$q}%"; $params['q2'] = "%{$q}%"; }
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuaris = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php $active_page = 'amigos'; require_once "includes/navbar.php"; ?>

<div class="page-container">

    <!-- Pestanyes -->
    <div class="tabs">
        <a href="feed.php?tab=coincidencias<?= $q ? '&q='.urlencode($q) : '' ?>"
           class="tab-item <?= $tab === 'coincidencias' ? 'active' : '' ?>">
            <i class="fas fa-sparkles"></i> Descobrir
        </a>
        <a href="feed.php?tab=amigos<?= $q ? '&q='.urlencode($q) : '' ?>"
           class="tab-item <?= $tab === 'amigos' ? 'active' : '' ?>">
            <i class="fas fa-user-friends"></i> Els meus amics
        </a>
    </div>

    <div class="tab-content">
        <!-- Cerca dins la pestanya -->
        <form method="GET" style="display:flex;gap:10px;margin-bottom:18px;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="input-icon-wrap" style="flex:1;">
                <i class="fas fa-search input-icon"></i>
                <input type="text" name="q" class="form-control"
                       placeholder="Cercar per nom o ubicació..."
                       value="<?= htmlspecialchars($q) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Cercar</button>
            <?php if ($q): ?>
                <a href="feed.php?tab=<?= $tab ?>" class="btn btn-secondary">✕</a>
            <?php endif; ?>
        </form>

        <?php if (empty($usuaris)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas <?= $tab === 'amigos' ? 'fa-user-friends' : 'fa-search' ?>"></i>
                </div>
                <?php if ($q): ?>
                    <h3>Cap resultat per "<?= htmlspecialchars($q) ?>"</h3>
                    <p>Prova amb un altre terme de cerca.</p>
                <?php elseif ($tab === 'amigos'): ?>
                    <h3>Encara no tens amics</h3>
                    <p>Explora la pestanya "Descobrir" per trobar persones amb interessos comuns.</p>
                    <a href="feed.php?tab=coincidencias" class="btn btn-primary mt-3">
                        <i class="fas fa-sparkles"></i> Descobrir persones
                    </a>
                <?php else: ?>
                    <h3>Cap coincidència disponible</h3>
                    <p>Afegeix més interessos al teu perfil per trobar persones afins.</p>
                    <a href="editar_perfil.php" class="btn btn-primary mt-3">
                        <i class="fas fa-edit"></i> Editar interessos
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($tab === 'coincidencias'): ?>
                <p class="text-muted mb-2" style="font-size:0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    <?= count($usuaris) ?> persones amb interessos comuns
                </p>
            <?php endif; ?>

            <?php foreach ($usuaris as $u): ?>
                <div class="user-card fade-in">
                    <img src="uploads/<?= htmlspecialchars($u['foto'] ?: 'default.png') ?>"
                         alt="<?= htmlspecialchars($u['nombre']) ?>"
                         class="user-avatar"
                         onerror="this.src='uploads/default.png'">
                    <div style="flex:1;">
                        <div class="user-name"><?= htmlspecialchars($u['nombre']) ?></div>
                        <div class="user-meta">
                            <?= $u['edad'] ?> anys · <?= htmlspecialchars($u['genero']) ?>
                            <?php if ($u['ubicacion']): ?> · 📍 <?= htmlspecialchars($u['ubicacion']) ?><?php endif; ?>
                            <?php if (isset($u['intereses_comunes']) && $u['intereses_comunes'] > 0): ?>
                                · <span style="color:var(--accent);font-weight:700;">
                                    <i class="fas fa-heart"></i> <?= $u['intereses_comunes'] ?> interessos comuns
                                  </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($u['descripcion']): ?>
                            <div class="user-desc">
                                <?= htmlspecialchars(mb_substr($u['descripcion'], 0, 140)) ?>
                                <?= mb_strlen($u['descripcion']) > 140 ? '…' : '' ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($u['iconos_intereses']): ?>
                            <div class="user-interests">
                                <?php foreach (array_unique(array_slice(explode('|', $u['iconos_intereses']), 0, 8)) as $ic): ?>
                                    <i class="<?= htmlspecialchars($ic) ?> interest-icon" title="Interès comú"></i>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="user-actions">
                            <a href="perfil_usuario.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i> Veure perfil
                            </a>
                            <?php if ($tab === 'coincidencias'): ?>
                                <a href="enviar_solicitud.php?id=<?= $u['id'] ?>" class="btn btn-accent btn-sm">
                                    <i class="fas fa-user-plus"></i> Afegir amic
                                </a>
                            <?php else: ?>
                                <a href="mensajes.php?usuario=<?= $u['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-comment"></i> Missatge
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
