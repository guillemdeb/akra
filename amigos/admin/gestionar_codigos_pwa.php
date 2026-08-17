<?php
/**
 * ADMIN: Gestió de codis personals d'instal·lació PWA
 * URL: /amigos/admin/gestionar_codigos_pwa.php
 */
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../index.php"); exit(); }

require_once "../config.php";
require_once "../includes/email_helper.php";
require_once "../includes/pwa_codigos.php";

$uid = $_SESSION['usuario_id'];
$s   = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
$s->execute(['id' => $uid]);
$admin = $s->fetch(PDO::FETCH_ASSOC);
$admins_emails = ['admin@redamigos.com']; // ← Canvia!
if (!$admin || !in_array($admin['email'], $admins_emails)) {
    header("Location: ../dashboard.php"); exit();
}

$msg  = '';
$type = '';

// ── Accions POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio    = $_POST['accio'] ?? '';
    $target_id = (int)($_POST['usuario_id'] ?? 0);

    // Regenerar codi
    if ($accio === 'regenerar' && $target_id) {
        $nou_codi = pwa_regenerar_codi($target_id);
        $s = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id=:id");
        $s->execute(['id' => $target_id]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        $msg  = "✅ Codi regenerat per <strong>{$u['nombre']}</strong>: <code>{$nou_codi}</code>";
        $type = 'success';
    }

    // Enviar codi per email
    elseif ($accio === 'enviar' && $target_id) {
        $s = $pdo->prepare("SELECT nombre, email, codigo_pwa FROM usuarios WHERE id=:id");
        $s->execute(['id' => $target_id]);
        $u = $s->fetch(PDO::FETCH_ASSOC);
        if ($u && $u['codigo_pwa']) {
            $ok = pwa_email_codi($u['email'], $u['nombre'], $u['codigo_pwa']);
            $msg  = $ok
                ? "✅ Email enviat a <strong>{$u['email']}</strong> amb el codi {$u['codigo_pwa']}"
                : "⚠️ No s'ha pogut enviar l'email.";
            $type = $ok ? 'success' : 'error';
        }
    }

    // Generar codis per a tots els usuaris sense codi
    elseif ($accio === 'generar_tots') {
        $s = $pdo->query("SELECT id FROM usuarios WHERE aprobado=1 AND activo=1 AND (codigo_pwa IS NULL OR codigo_pwa='')");
        $ids = $s->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) pwa_assignar_codi($id);
        $msg  = "✅ Codis generats per " . count($ids) . " usuaris sense codi.";
        $type = 'success';
    }
}

// ── Estadístiques ──
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total_aprovats,
        SUM(codigo_pwa IS NOT NULL AND codigo_pwa != '') AS amb_codi,
        SUM(codigo_pwa IS NULL OR codigo_pwa = '')       AS sense_codi
    FROM usuarios WHERE aprobado=1 AND activo=1
")->fetch(PDO::FETCH_ASSOC);

$log_count = 0;
try {
    $log_count = $pdo->query("SELECT COUNT(*) FROM pwa_instalaciones")->fetchColumn();
} catch (Exception $e) {}

// ── Llista d'usuaris ──
$filtre = $_GET['filtre'] ?? 'tots';
$where  = match($filtre) {
    'sense_codi' => "WHERE u.aprobado=1 AND u.activo=1 AND (u.codigo_pwa IS NULL OR u.codigo_pwa='')",
    'amb_codi'   => "WHERE u.aprobado=1 AND u.activo=1 AND u.codigo_pwa IS NOT NULL AND u.codigo_pwa!=''",
    default      => "WHERE u.aprobado=1 AND u.activo=1",
};

$usuaris = $pdo->query("
    SELECT u.id, u.nombre, u.email, u.foto, u.codigo_pwa, u.fecha_registro,
           COUNT(p.id) AS num_instalacions,
           MAX(p.fecha) AS ultima_instalacio
    FROM usuarios u
    LEFT JOIN pwa_instalaciones p ON p.usuario_id = u.id
    {$where}
    GROUP BY u.id
    ORDER BY u.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codis PWA · Admin · RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { padding:0; background:#0f172a; }
        .wrap { max-width:1100px; margin:0 auto; padding:0 20px 40px; }

        /* Topbar */
        .topbar {
            background:#1e293b; border-bottom:1px solid #334155;
            padding:14px 24px; display:flex; align-items:center; gap:14px;
            margin-bottom:28px;
        }
        .topbar .logo { font-weight:800; font-size:1.1rem; color:#4A90E2; }
        .topbar .logo span { color:#7ED321; }
        .topbar h1 { font-size:0.95rem; color:#cbd5e1; font-weight:600; }
        .topbar .sep { color:#475569; }
        .topbar .back { color:#64748b; font-size:0.85rem; margin-left:auto; text-decoration:none; }
        .topbar .back:hover { color:#4A90E2; }

        /* Alerts */
        .alert-dark { padding:13px 18px; border-radius:10px; margin-bottom:20px;
                      font-size:0.9rem; border:1px solid; }
        .alert-dark.success { background:rgba(34,197,94,0.1); color:#86efac; border-color:rgba(34,197,94,0.25); }
        .alert-dark.error   { background:rgba(239,68,68,0.1); color:#fca5a5; border-color:rgba(239,68,68,0.25); }
        .alert-dark.warning { background:rgba(251,191,36,0.1); color:#fde68a; border-color:rgba(251,191,36,0.25); }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
                     gap:14px; margin-bottom:24px; }
        .stat-box { background:#1e293b; border:1px solid #334155; border-radius:12px;
                    padding:18px; text-align:center; }
        .stat-box .n { font-size:2rem; font-weight:800; margin-bottom:4px; }
        .stat-box .l { font-size:0.75rem; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; }
        .s-total .n { color:#f8fafc; }
        .s-codi  .n { color:#7ED321; }
        .s-sense .n { color:#ef4444; }
        .s-inst  .n { color:#4A90E2; }

        /* Panel accions */
        .actions-bar {
            background:#1e293b; border:1px solid #334155; border-radius:12px;
            padding:16px 20px; margin-bottom:20px;
            display:flex; align-items:center; gap:12px; flex-wrap:wrap;
        }
        .actions-bar span { color:#64748b; font-size:0.85rem; }

        /* Filtres */
        .filtres { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .f-btn { padding:6px 14px; border-radius:20px; border:1px solid #334155;
                 background:transparent; color:#94a3b8; font-family:inherit;
                 font-size:0.82rem; cursor:pointer; text-decoration:none; transition:all 0.2s; }
        .f-btn:hover { border-color:#4A90E2; color:#4A90E2; }
        .f-btn.actiu { background:#4A90E2; border-color:#4A90E2; color:white; }

        /* Taula */
        .panel { background:#1e293b; border:1px solid #334155; border-radius:14px; overflow:hidden; }
        .panel-head { padding:16px 20px; border-bottom:1px solid #334155;
                      display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
        .panel-head h3 { color:#f1f5f9; font-size:0.95rem; }
        .tw { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th { background:#0f172a; color:#475569; padding:10px 16px; text-align:left;
             font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px;
             border-bottom:1px solid #334155; white-space:nowrap; }
        td { padding:12px 16px; border-bottom:1px solid #1e293b;
             color:#cbd5e1; vertical-align:middle; }
        tr:hover td { background:rgba(255,255,255,0.02); }

        /* Codi badge */
        .codi-pill {
            font-family:'Courier New',monospace; font-size:0.9rem;
            font-weight:800; letter-spacing:3px; color:#4A90E2;
            background:rgba(74,144,226,0.1); border:1px solid rgba(74,144,226,0.25);
            padding:4px 12px; border-radius:6px; cursor:pointer;
            transition:all 0.2s; white-space:nowrap;
        }
        .codi-pill:hover { background:rgba(74,144,226,0.2); color:#7BC3FF; }
        .sense-codi { color:#475569; font-style:italic; font-size:0.82rem; }

        /* Botons taula */
        .tbtn {
            display:inline-flex; align-items:center; gap:5px;
            padding:5px 10px; border-radius:6px; border:1px solid;
            font-size:0.78rem; font-weight:700; cursor:pointer;
            background:transparent; font-family:inherit; transition:all 0.2s; white-space:nowrap;
        }
        .tbtn-blue  { color:#4A90E2; border-color:rgba(74,144,226,0.35); }
        .tbtn-blue:hover  { background:rgba(74,144,226,0.15); }
        .tbtn-green { color:#7ED321; border-color:rgba(126,211,33,0.35); }
        .tbtn-green:hover { background:rgba(126,211,33,0.15); }
        .tbtn-orange{ color:#f97316; border-color:rgba(249,115,22,0.35); }
        .tbtn-orange:hover { background:rgba(249,115,22,0.15); }

        .inst-badge { background:rgba(74,144,226,0.15); color:#93c5fd;
                      border-radius:20px; padding:2px 8px; font-size:0.75rem; font-weight:700; }
        .inst-zero  { color:#334155; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="logo">Red<span>Amigos</span></div>
    <span class="sep">/</span>
    <h1><i class="fas fa-mobile-alt" style="color:#4A90E2;margin-right:6px;"></i>Codis d'instal·lació PWA</h1>
    <a href="../dashboard.php" class="back"><i class="fas fa-arrow-left"></i> Tornar</a>
</div>

<div class="wrap">

    <?php if ($msg): ?>
        <div class="alert-dark <?= $type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box s-total">
            <div class="n"><?= $stats['total_aprovats'] ?></div>
            <div class="l"><i class="fas fa-users"></i> Usuaris aprovats</div>
        </div>
        <div class="stat-box s-codi">
            <div class="n"><?= $stats['amb_codi'] ?></div>
            <div class="l"><i class="fas fa-key"></i> Amb codi</div>
        </div>
        <div class="stat-box s-sense">
            <div class="n"><?= $stats['sense_codi'] ?></div>
            <div class="l"><i class="fas fa-exclamation-triangle"></i> Sense codi</div>
        </div>
        <div class="stat-box s-inst">
            <div class="n"><?= $log_count ?></div>
            <div class="l"><i class="fas fa-download"></i> Instal·lacions</div>
        </div>
    </div>

    <!-- Accions globals -->
    <div class="actions-bar">
        <span><i class="fas fa-magic"></i> Accions globals:</span>
        <?php if ($stats['sense_codi'] > 0): ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="accio" value="generar_tots">
            <button type="submit" class="tbtn tbtn-green" onclick="return confirm('Generar codis per <?= $stats['sense_codi'] ?> usuaris sense codi?')">
                <i class="fas fa-plus-circle"></i>
                Generar codis per a <?= $stats['sense_codi'] ?> usuaris sense codi
            </button>
        </form>
        <?php else: ?>
        <span style="color:#7ED321;font-size:0.82rem;">
            <i class="fas fa-check-circle"></i> Tots els usuaris aprovats ja tenen codi
        </span>
        <?php endif; ?>

        <a href="aprobar_usuarios.php" class="tbtn tbtn-blue" style="margin-left:auto;">
            <i class="fas fa-user-check"></i> Panell d'aprovació
        </a>
    </div>

    <!-- Taula -->
    <div class="panel">
        <div class="panel-head">
            <h3>Usuaris aprovats (<?= count($usuaris) ?>)</h3>
            <div class="filtres" style="margin:0;">
                <?php
                $fs = ['tots'=>'Tots', 'amb_codi'=>'Amb codi', 'sense_codi'=>'Sense codi'];
                foreach ($fs as $k => $v):
                ?>
                    <a href="?filtre=<?= $k ?>" class="f-btn <?= $filtre === $k ? 'actiu' : '' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($usuaris)): ?>
            <div style="text-align:center;padding:40px;color:#475569;">
                <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
                Cap usuari per mostrar
            </div>
        <?php else: ?>
        <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Usuari</th>
                    <th>Codi personal</th>
                    <th>Instal·lacions</th>
                    <th>Última instal·lació</th>
                    <th>Accions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuaris as $u):
                $data_reg = (new DateTime($u['fecha_registro']))->format('d/m/Y');
                $data_inst = $u['ultima_instalacio']
                    ? (new DateTime($u['ultima_instalacio']))->format('d/m/Y H:i')
                    : null;
                $install_url = $u['codigo_pwa']
                    ? 'https://akratechstudio.es/amigos/instalar.php?c=' . urlencode($u['codigo_pwa'])
                    : null;
            ?>
            <tr>
                <!-- Usuari -->
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="../uploads/<?= htmlspecialchars($u['foto'] ?: 'default.png') ?>"
                             style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #334155;"
                             onerror="this.src='../uploads/default.png'">
                        <div>
                            <div style="font-weight:700;color:#f1f5f9;"><?= htmlspecialchars($u['nombre']) ?></div>
                            <div style="font-size:0.75rem;color:#64748b;"><?= htmlspecialchars($u['email']) ?></div>
                        </div>
                    </div>
                </td>

                <!-- Codi -->
                <td>
                    <?php if ($u['codigo_pwa']): ?>
                        <span class="codi-pill"
                              onclick="copiar('<?= htmlspecialchars($u['codigo_pwa']) ?>',this)"
                              title="Clic per copiar">
                            <?= htmlspecialchars($u['codigo_pwa']) ?>
                        </span>
                    <?php else: ?>
                        <span class="sense-codi"><i class="fas fa-exclamation-triangle"></i> Sense codi</span>
                    <?php endif; ?>
                </td>

                <!-- Instal·lacions -->
                <td>
                    <?php if ($u['num_instalacions'] > 0): ?>
                        <span class="inst-badge"><?= $u['num_instalacions'] ?>x</span>
                    <?php else: ?>
                        <span class="inst-zero">—</span>
                    <?php endif; ?>
                </td>

                <!-- Última instal·lació -->
                <td style="color:#64748b;font-size:0.82rem;white-space:nowrap;">
                    <?= $data_inst ?: '—' ?>
                </td>

                <!-- Accions -->
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                        <?php if ($u['codigo_pwa']): ?>
                            <!-- Copiar URL -->
                            <button class="tbtn tbtn-blue"
                                    onclick="copiarURL('<?= htmlspecialchars($install_url, ENT_QUOTES) ?>',this)">
                                <i class="fas fa-link"></i> URL
                            </button>

                            <!-- Enviar email -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="accio" value="enviar">
                                <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="tbtn tbtn-green">
                                    <i class="fas fa-envelope"></i> Email
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Regenerar -->
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Regenerar el codi de <?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>?\nEl codi antic quedarà inutilitzable.')">
                            <input type="hidden" name="accio" value="regenerar">
                            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="tbtn tbtn-orange">
                                <i class="fas fa-sync-alt"></i>
                                <?= $u['codigo_pwa'] ? 'Regenerar' : 'Generar' ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.wrap -->

<script>
function copiar(text, el) {
    navigator.clipboard?.writeText(text).then(() => {
        const orig = el.innerHTML;
        el.innerHTML = '✓ Copiat!';
        el.style.color = '#7ED321';
        setTimeout(() => { el.innerHTML = orig; el.style.color = ''; }, 1600);
    });
}
function copiarURL(url, el) {
    navigator.clipboard?.writeText(url).then(() => {
        const orig = el.innerHTML;
        el.innerHTML = '<i class="fas fa-check"></i> Copiat!';
        el.style.borderColor = '#7ED321'; el.style.color = '#7ED321';
        setTimeout(() => { el.innerHTML = orig; el.style.borderColor = ''; el.style.color = ''; }, 1800);
    });
}
</script>
</body>
</html>
