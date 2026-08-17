<?php
/**
 * ADMIN: Gestió de codis d'invitació
 * URL: /amigos/admin/codigos_invitacion.php
 */
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php"); exit();
}

require_once "../config.php";
require_once "../includes/email_helper.php";
require_once "../includes/codigos_helper.php";

$usuario_id = $_SESSION['usuario_id'];

// Verificar admin
$s = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
$s->execute(['id' => $usuario_id]);
$admin = $s->fetch(PDO::FETCH_ASSOC);
$admins_emails = ['admin@redamigos.com']; // ← Canvia!
if (!$admin || !in_array($admin['email'], $admins_emails)) {
    header("Location: ../dashboard.php"); exit();
}

$missatge = '';
$tipus    = '';

// ── Accions POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    // Generar codis
    if ($accio === 'generar') {
        $quantitat = min((int)($_POST['quantitat'] ?? 1), 50);
        $nota      = trim($_POST['nota'] ?? '');
        $generats  = ra_crear_codigos($usuario_id, $quantitat, $nota);
        $n         = count($generats);
        $missatge  = "✅ S'han generat {$n} codi(s) nous.";
        $tipus     = 'success';
    }

    // Enviar codi per email
    elseif ($accio === 'enviar_email') {
        $codi      = strtoupper(trim($_POST['codi'] ?? ''));
        $to_email  = trim($_POST['email_dest'] ?? '');
        $to_nom    = trim($_POST['nom_dest']   ?? 'Amic/a');

        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $missatge = 'Email no vàlid.';
            $tipus    = 'error';
        } else {
            $ok = ra_email_invitacio($to_email, $to_nom, $codi, $admin['nombre']);
            $missatge = $ok
                ? "✅ Email enviat a {$to_email}."
                : "⚠️ No s'ha pogut enviar l'email. Comprova la configuració SMTP.";
            $tipus = $ok ? 'success' : 'error';
        }
    }

    // Desactivar codi
    elseif ($accio === 'desactivar') {
        $codi = strtoupper(trim($_POST['codi'] ?? ''));
        $pdo->prepare("UPDATE codigos_invitacion SET activo=0 WHERE codigo=:c")
            ->execute(['c' => $codi]);
        $missatge = "🚫 Codi {$codi} desactivat.";
        $tipus    = 'warning';
    }

    // Reactivar codi
    elseif ($accio === 'reactivar') {
        $codi = strtoupper(trim($_POST['codi'] ?? ''));
        $pdo->prepare("UPDATE codigos_invitacion SET activo=1 WHERE codigo=:c AND usado_por IS NULL")
            ->execute(['c' => $codi]);
        $missatge = "✅ Codi {$codi} reactivat.";
        $tipus    = 'success';
    }

    // Eliminar codi no usat
    elseif ($accio === 'eliminar') {
        $codi = strtoupper(trim($_POST['codi'] ?? ''));
        $pdo->prepare("DELETE FROM codigos_invitacion WHERE codigo=:c AND usado_por IS NULL")
            ->execute(['c' => $codi]);
        $missatge = "🗑️ Codi {$codi} eliminat.";
        $tipus    = 'info';
    }
}

// ── Estadístiques ──
try {
    $stats = $pdo->query("
        SELECT
            COUNT(*)                                       AS total,
            SUM(activo = 1 AND usado_por IS NULL)          AS disponibles,
            SUM(usado_por IS NOT NULL)                     AS usats,
            SUM(activo = 0 AND usado_por IS NULL)          AS desactivats
        FROM codigos_invitacion
    ")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total'=>0,'disponibles'=>0,'usats'=>0,'desactivats'=>0];
}

// ── Llista de codis ──
$filtre = $_GET['filtre'] ?? 'tots';
$where  = match($filtre) {
    'disponibles' => "WHERE c.activo=1 AND c.usado_por IS NULL",
    'usats'       => "WHERE c.usado_por IS NOT NULL",
    'desactivats' => "WHERE c.activo=0 AND c.usado_por IS NULL",
    default       => "",
};

try {
    $codis = $pdo->query("
        SELECT c.*,
               a.nombre  AS nom_admin,
               u.nombre  AS nom_usuari,
               u.email   AS email_usuari
        FROM codigos_invitacion c
        LEFT JOIN usuarios a ON a.id = c.creado_por
        LEFT JOIN usuarios u ON u.id = c.usado_por
        {$where}
        ORDER BY c.fecha_creacion DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $codis = [];
}

$base  = '/amigos';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codis d'invitació · Admin · RedAmigos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { padding-top: 0; padding-bottom: 0; }

        .admin-wrap { min-height: 100vh; background: #0f172a; }

        .admin-topbar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 14px 24px;
            display: flex; align-items: center; gap: 14px;
        }
        .admin-topbar .logo { font-weight:800; font-size:1.15rem; color: #4A90E2; }
        .admin-topbar .logo span { color: #7ED321; }
        .admin-topbar .sep { color: #475569; margin: 0 4px; }
        .admin-topbar h1 { font-size:1rem; color: #cbd5e1; font-weight:600; }
        .admin-topbar a { color:#64748b; font-size:0.88rem; margin-left:auto; }
        .admin-topbar a:hover { color:#4A90E2; }

        .admin-content { max-width: 1100px; margin: 0 auto; padding: 28px 20px; }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
        }
        .stat-box .num { font-size: 2.4rem; font-weight: 800; margin-bottom: 4px; }
        .stat-box .lbl { font-size: 0.82rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-box.disp .num { color: #7ED321; }
        .stat-box.usat .num { color: #4A90E2; }
        .stat-box.deac .num { color: #ef4444; }
        .stat-box.tot  .num { color: #f8fafc; }

        /* Panell accions */
        .actions-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        @media (max-width: 700px) { .actions-panel { grid-template-columns: 1fr; } }

        .panel-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 22px;
        }
        .panel-card h3 { color: #f1f5f9; margin-bottom: 18px; font-size: 1rem; }
        .panel-card h3 i { color: #4A90E2; margin-right: 8px; }

        .field { margin-bottom: 14px; }
        .field label { display:block; color:#94a3b8; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
        .field input, .field select {
            width: 100%; padding: 10px 14px;
            background: #0f172a; border: 1px solid #334155;
            border-radius: 8px; color: #f1f5f9;
            font-family: var(--font); font-size: 0.95rem;
        }
        .field input:focus, .field select:focus {
            outline: none; border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74,144,226,0.15);
        }
        .field input::placeholder { color: #475569; }

        .btn-admin {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; border-radius: 8px; border: none;
            font-family: var(--font); font-size: 0.9rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
        }
        .btn-admin:hover { transform: translateY(-1px); text-decoration: none; }
        .btn-primary-dark { background: #4A90E2; color: white; }
        .btn-primary-dark:hover { background: #357ABD; color: white; }
        .btn-green  { background: #7ED321; color: white; }
        .btn-green:hover { background: #6BC318; color: white; }
        .btn-red    { background: #ef4444; color: white; }
        .btn-red:hover { background: #dc2626; color: white; }
        .btn-gray   { background: #334155; color: #94a3b8; }
        .btn-gray:hover { background: #475569; color: white; }
        .btn-full   { width: 100%; justify-content: center; }

        /* Alert */
        .alert-dark {
            padding: 14px 18px; border-radius: 10px;
            margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;
        }
        .alert-dark.success { background: #14532d; color: #86efac; border: 1px solid #166534; }
        .alert-dark.error   { background: #450a0a; color: #fca5a5; border: 1px solid #7f1d1d; }
        .alert-dark.warning { background: #451a03; color: #fcd34d; border: 1px solid #78350f; }
        .alert-dark.info    { background: #0c1e38; color: #93c5fd; border: 1px solid #1e40af; }

        /* Taula de codis */
        .filtre-bar {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .filtre-btn {
            padding: 7px 16px; border-radius: 20px;
            border: 1px solid #334155; background: transparent;
            color: #94a3b8; font-family: var(--font); font-size: 0.85rem;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
        }
        .filtre-btn:hover { border-color: #4A90E2; color: #4A90E2; }
        .filtre-btn.actiu { background: #4A90E2; border-color: #4A90E2; color: white; }

        .taula-wrap { overflow-x: auto; }
        table {
            width: 100%; border-collapse: collapse;
            font-size: 0.88rem;
        }
        th {
            background: #1e293b; color: #64748b;
            padding: 12px 16px; text-align: left;
            font-size: 0.78rem; text-transform: uppercase;
            letter-spacing: 0.5px; border-bottom: 1px solid #334155;
            white-space: nowrap;
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #1e293b;
            color: #cbd5e1; vertical-align: middle;
        }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .codi-badge {
            font-family: 'Courier New', monospace;
            font-size: 0.95rem; font-weight: 700;
            letter-spacing: 2px; color: #4A90E2;
            cursor: pointer;
        }
        .codi-badge:hover { color: #7BC3FF; }

        .estat-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 700;
        }
        .estat-disp { background:#14532d; color:#86efac; }
        .estat-usat { background:#1e3a5f; color:#93c5fd; }
        .estat-deac { background:#450a0a; color:#fca5a5; }

        .link-copy {
            background: #0f172a; border: 1px solid #334155;
            padding: 5px 10px; border-radius: 6px;
            font-size: 0.75rem; color: #64748b;
            cursor: pointer; transition: all 0.2s;
            white-space: nowrap;
        }
        .link-copy:hover { border-color: #4A90E2; color: #4A90E2; }
        .link-copy.copied { border-color: #7ED321; color: #7ED321; }

        /* Modal enviar email */
        .modal-dark {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.75); z-index: 2000;
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-dark.active { display: flex; }
        .modal-dark-box {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 16px; padding: 28px;
            max-width: 460px; width: 100%;
            animation: modal-in 0.2s ease;
        }
        .modal-dark-box h3 { color: #f1f5f9; margin-bottom: 18px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <!-- Topbar -->
    <div class="admin-topbar">
        <div class="logo">Red<span>Amigos</span></div>
        <span class="sep">/</span>
        <h1><i class="fas fa-ticket-alt" style="color:#4A90E2;margin-right:6px;"></i>Codis d'invitació</h1>
        <a href="../dashboard.php"><i class="fas fa-arrow-left"></i> Tornar</a>
    </div>

    <div class="admin-content">

        <!-- Missatge -->
        <?php if ($missatge): ?>
            <div class="alert-dark <?= $tipus ?>">
                <?= htmlspecialchars($missatge) ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box tot">
                <div class="num"><?= $stats['total'] ?></div>
                <div class="lbl"><i class="fas fa-ticket-alt"></i> Total generats</div>
            </div>
            <div class="stat-box disp">
                <div class="num"><?= $stats['disponibles'] ?></div>
                <div class="lbl"><i class="fas fa-check-circle"></i> Disponibles</div>
            </div>
            <div class="stat-box usat">
                <div class="num"><?= $stats['usats'] ?></div>
                <div class="lbl"><i class="fas fa-user-check"></i> Usats</div>
            </div>
            <div class="stat-box deac">
                <div class="num"><?= $stats['desactivats'] ?></div>
                <div class="lbl"><i class="fas fa-ban"></i> Desactivats</div>
            </div>
        </div>

        <!-- Accions -->
        <div class="actions-panel">

            <!-- Generar codis -->
            <div class="panel-card">
                <h3><i class="fas fa-magic"></i> Generar nous codis</h3>
                <form method="POST">
                    <input type="hidden" name="accio" value="generar">
                    <div class="field">
                        <label>Quantitat (màx. 50)</label>
                        <input type="number" name="quantitat" min="1" max="50" value="1">
                    </div>
                    <div class="field">
                        <label>Nota (per a qui és) <span style="color:#475569;">— opcional</span></label>
                        <input type="text" name="nota" placeholder="Per a: Maria García...">
                    </div>
                    <button type="submit" class="btn-admin btn-green btn-full">
                        <i class="fas fa-plus-circle"></i> Generar codis
                    </button>
                </form>
            </div>

            <!-- Copiar URL invitació ràpida -->
            <div class="panel-card">
                <h3><i class="fas fa-share-alt"></i> Compartir invitació</h3>
                <p style="color:#64748b;font-size:0.9rem;margin-bottom:16px;">
                    Envia un codi per email directament o copia l'enllaç per compartir-lo manualment.
                </p>
                <div style="background:#0f172a;border:1px solid #334155;border-radius:10px;padding:14px;margin-bottom:14px;">
                    <div style="color:#475569;font-size:0.75rem;margin-bottom:8px;">URL base de registre:</div>
                    <div style="color:#7ED321;font-family:monospace;font-size:0.8rem;word-break:break-all;">
                        https://akratechstudio.es/amigos/invitacio.php
                    </div>
                </div>
                <button onclick="obrirModalEmail(null)" class="btn-admin btn-primary-dark btn-full">
                    <i class="fas fa-envelope"></i> Enviar codi per email
                </button>
            </div>
        </div>

        <!-- Taula de codis -->
        <div class="panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                <h3 style="margin:0;"><i class="fas fa-list"></i> Tots els codis</h3>
                <!-- Filtres -->
                <div class="filtre-bar" style="margin:0;">
                    <?php
                    $filtres = ['tots'=>'Tots','disponibles'=>'Disponibles','usats'=>'Usats','desactivats'=>'Desactivats'];
                    foreach ($filtres as $k => $v):
                    ?>
                        <a href="?filtre=<?= $k ?>"
                           class="filtre-btn <?= $filtre === $k ? 'actiu' : '' ?>">
                            <?= $v ?> <?php if($k==='disponibles') echo "({$stats['disponibles']})"; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($codis)): ?>
                <div style="text-align:center;padding:40px;color:#475569;">
                    <i class="fas fa-ticket-alt" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
                    No hi ha codis per mostrar. Genera'n uns quants!
                </div>
            <?php else: ?>
            <div class="taula-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Codi</th>
                        <th>Estat</th>
                        <th>Nota</th>
                        <th>Creat per</th>
                        <th>Data creació</th>
                        <th>Usat per</th>
                        <th>Data ús</th>
                        <th>Accions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($codis as $c):
                    if ($c['usado_por']) {
                        $estat = 'usat'; $estat_txt = '✓ Usat';
                    } elseif (!$c['activo']) {
                        $estat = 'deac'; $estat_txt = '✗ Desactivat';
                    } else {
                        $estat = 'disp'; $estat_txt = '● Disponible';
                    }
                    $data_creacio = (new DateTime($c['fecha_creacion']))->format('d/m/Y H:i');
                    $data_us      = $c['fecha_uso'] ? (new DateTime($c['fecha_uso']))->format('d/m/Y H:i') : '—';
                    $url_inv      = ra_url_invitacio($c['codigo']);
                ?>
                <tr>
                    <td>
                        <span class="codi-badge" onclick="copiarText('<?= $c['codigo'] ?>', this)" title="Clic per copiar">
                            <?= htmlspecialchars($c['codigo']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="estat-badge estat-<?= $estat ?>">
                            <?= $estat_txt ?>
                        </span>
                    </td>
                    <td style="color:#64748b;font-style:italic;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= $c['nota'] ? htmlspecialchars($c['nota']) : '—' ?>
                    </td>
                    <td><?= htmlspecialchars($c['nom_admin'] ?? '—') ?></td>
                    <td style="white-space:nowrap;"><?= $data_creacio ?></td>
                    <td>
                        <?php if ($c['nom_usuari']): ?>
                            <span style="color:#93c5fd;">
                                <?= htmlspecialchars($c['nom_usuari']) ?><br>
                                <span style="font-size:0.78rem;color:#475569;"><?= htmlspecialchars($c['email_usuari']) ?></span>
                            </span>
                        <?php else: ?>
                            <span style="color:#334155;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;"><?= $data_us ?></td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                            <?php if (!$c['usado_por']): ?>
                                <!-- Copiar URL -->
                                <button class="link-copy" onclick="copiarURL('<?= htmlspecialchars($url_inv, ENT_QUOTES) ?>', this)" title="Copiar URL invitació">
                                    <i class="fas fa-link"></i> URL
                                </button>

                                <!-- Enviar email -->
                                <button class="link-copy" onclick="obrirModalEmail('<?= htmlspecialchars($c['codigo'], ENT_QUOTES) ?>')" title="Enviar per email">
                                    <i class="fas fa-envelope"></i>
                                </button>

                                <?php if ($c['activo']): ?>
                                <!-- Desactivar -->
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Desactivar aquest codi?')">
                                    <input type="hidden" name="accio"  value="desactivar">
                                    <input type="hidden" name="codi"   value="<?= $c['codigo'] ?>">
                                    <button type="submit" class="link-copy" style="color:#ef4444;border-color:#ef4444;" title="Desactivar">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <!-- Reactivar -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accio"  value="reactivar">
                                    <input type="hidden" name="codi"   value="<?= $c['codigo'] ?>">
                                    <button type="submit" class="link-copy" style="color:#7ED321;border-color:#7ED321;" title="Reactivar">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </form>
                                <!-- Eliminar -->
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar codi?')">
                                    <input type="hidden" name="accio" value="eliminar">
                                    <input type="hidden" name="codi"  value="<?= $c['codigo'] ?>">
                                    <button type="submit" class="link-copy" style="color:#ef4444;border-color:#ef4444;" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#334155;font-size:0.8rem;font-style:italic;">Ja usat</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.admin-content -->
</div><!-- /.admin-wrap -->

<!-- Modal Enviar Email -->
<div class="modal-dark" id="modal-email">
    <div class="modal-dark-box">
        <h3><i class="fas fa-envelope" style="color:#4A90E2;margin-right:8px;"></i>Enviar codi per email</h3>
        <form method="POST" id="form-email">
            <input type="hidden" name="accio" value="enviar_email">
            <div class="field">
                <label>Codi a enviar</label>
                <input type="text" name="codi" id="modal-codi"
                       style="font-family:monospace;letter-spacing:3px;text-transform:uppercase;"
                       placeholder="XXXX-XXXX-XXXX" required>
            </div>
            <div class="field">
                <label>Nom del destinatari</label>
                <input type="text" name="nom_dest" placeholder="Maria García">
            </div>
            <div class="field">
                <label>Email del destinatari</label>
                <input type="email" name="email_dest" placeholder="maria@email.com" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-admin btn-primary-dark">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
                <button type="button" class="btn-admin btn-gray" onclick="tancarModal()">
                    Cancel·lar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function obrirModalEmail(codi) {
    document.getElementById('modal-email').classList.add('active');
    if (codi) document.getElementById('modal-codi').value = codi;
    else document.getElementById('modal-codi').value = '';
}
function tancarModal() {
    document.getElementById('modal-email').classList.remove('active');
}
document.getElementById('modal-email').addEventListener('click', function(e) {
    if (e.target === this) tancarModal();
});

function copiarText(text, el) {
    navigator.clipboard?.writeText(text).then(() => {
        const orig = el.innerHTML;
        el.innerHTML = '✓ Copiat!';
        el.style.color = '#7ED321';
        setTimeout(() => { el.innerHTML = orig; el.style.color = ''; }, 1500);
    });
}

function copiarURL(url, el) {
    navigator.clipboard?.writeText(url).then(() => {
        el.classList.add('copied');
        el.innerHTML = '<i class="fas fa-check"></i> Copiat!';
        setTimeout(() => {
            el.classList.remove('copied');
            el.innerHTML = '<i class="fas fa-link"></i> URL';
        }, 1800);
    });
}
</script>
</body>
</html>
