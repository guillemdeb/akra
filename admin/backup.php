<?php
require_once 'includes/core.php';
requireLogin();

if (isset($_GET['download'])) {
    $sections = !empty($_GET['sections']) ? $_GET['sections'] : null;
    $result = generateDataBackupZip($sections);
    if (!$result['ok']) { die(htmlspecialchars($result['error'])); }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . filesize($result['path']));
    readfile($result['path']);
    unlink($result['path']);
    exit;
}

if (isset($_GET['download_auto'])) {
    $fname = $_GET['download_auto'];
    if (!preg_match('/^auto-[\d\-]+\.zip$/', $fname)) die('Nom de fitxer no vàlid.');
    $path = backupsDir() . $fname;
    if (!file_exists($path)) die('Este fitxer ja no existeix.');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_auto') {
    deleteAutoBackup($_POST['filename'] ?? '');
    header('Location: backup.php?auto_deleted=1');
    exit;
}

$sections   = getBackupSections();
$files      = glob(DATA_DIR . '*.json');
$total_size = array_sum(array_map('filesize', $files));
$auto_backups = listAutoBackups();
$cfg = getAdminConfig();

$page_title    = 'Còpia de seguretat';
$page_subtitle = count($files) . ' fitxers de dades';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['auto_deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Còpia eliminada.</div>
<?php endif; ?>

<!-- Còpia manual, per seccions -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title">💾 Descarregar una còpia ara</div>
    </div>
    <div class="card-body">
        <p style="color:#6b7280;font-size:.9rem;margin-bottom:20px;line-height:1.6">
            Esta aplicació guarda totes les dades (clients, factures, pagaments, contactes, propostes, auditories...)
            en fitxers de text pla (JSON) dins de <code>admin/data/</code>, sense base de dades. Tria què vols
            incloure a la còpia — tot, o només algunes seccions — i descarrega-la en ZIP.
        </p>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin-bottom:20px">
            <div style="font-size:.82rem;color:#6b7280;margin-bottom:4px"><?= count($files) ?> fitxers · <?= number_format($total_size / 1024, 0) ?> KB en total</div>
            <div style="font-size:.78rem;color:#9ca3af">Última consulta: <?= date('d/m/Y H:i') ?></div>
        </div>

        <form method="GET" id="backup-form">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <strong style="font-size:.88rem">Seccions a incloure</strong>
                <div style="display:flex;gap:10px;font-size:.8rem">
                    <a href="#" onclick="toggleAll(true);return false">Seleccionar-ho tot</a>
                    <a href="#" onclick="toggleAll(false);return false">Netejar selecció</a>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:20px;font-size:.85rem">
                <?php foreach ($sections as $key => $s): ?>
                <label style="display:flex;align-items:center;gap:7px;padding:8px 10px;border:1px solid var(--a-border);border-radius:8px;cursor:pointer">
                    <input type="checkbox" class="section-check" name="sections[]" value="<?= $key ?>"> <?= htmlspecialchars($s['label']) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" name="download" value="1" onclick="return prepareSubmit(false)" class="btn btn-primary">⬇️ Descarregar seleccionades</button>
                <button type="submit" name="download" value="1" onclick="return prepareSubmit(true)" class="btn btn-secondary">⬇️ Descarregar-ho tot</button>
            </div>
        </form>
    </div>
</div>

<!-- Còpies automàtiques -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title">🤖 Còpies de seguretat automàtiques</div>
        <span class="badge <?= !empty($cfg['auto_backup_enabled']) ? 'badge-green' : 'badge-gray' ?>"><?= !empty($cfg['auto_backup_enabled']) ? 'Activades' : 'Desactivades' ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($cfg['auto_backup_enabled'])): ?>
        <p style="color:#6b7280;font-size:.88rem;line-height:1.6;margin-bottom:14px">
            Encara no tens les còpies automàtiques activades. Ves a <a href="settings.php">Configuració → Còpies de seguretat automàtiques</a> per activar-les i triar què s'ha de guardar.
        </p>
        <?php endif; ?>

        <h4 style="font-family:'Syne',sans-serif;font-size:.95rem;margin-bottom:10px">Com programar-ho</h4>
        <p style="color:#6b7280;font-size:.85rem;line-height:1.7;margin-bottom:8px">
            <strong>Opció 1 — Programador de tasques de Windows</strong> (si l'admin viu en un servidor Windows/WAMP que està sempre encés): crea una tasca nova → acció "Iniciar un programa":
        </p>
        <pre style="background:#f3f4f6;border-radius:6px;padding:12px 16px;font-size:.8rem;overflow-x:auto;margin:0 0 14px">Programa: C:\wamp64\bin\php\phpX.X.X\php.exe
Arguments: "C:\wamp64\www\akra\admin\cron_backup.php"
Freqüència recomanada: cada dia, a les 03:30</pre>
        <p style="color:#6b7280;font-size:.85rem;line-height:1.7;margin-bottom:8px">
            <strong>Opció 2 — Cron del teu allotjament web</strong> (habitual si el lloc ja està publicat en un hosting normal): al panell del teu hosting (cPanel, Plesk...) busca "Cron Jobs" i crea'n un que visite esta URL cada dia (canvia primer el token dins de <code>admin/cron_backup.php</code>):
        </p>
        <pre style="background:#f3f4f6;border-radius:6px;padding:12px 16px;font-size:.8rem;overflow-x:auto;margin:0 0 4px">https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'el-teu-domini.es') ?>/admin/cron_backup.php?token=EL_TEU_TOKEN_SECRET</pre>
        <p class="hint" style="margin:0">Les còpies es guarden a <code>admin/backups/</code> (protegida perquè ningú hi puga accedir directament per URL) i les més antigues que la retenció configurada s'esborren soles a cada execució.</p>
    </div>
</div>

<!-- Llistat de còpies automàtiques generades -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📦 Còpies automàtiques guardades (<?= count($auto_backups) ?>)</div>
    </div>
    <?php if (empty($auto_backups)): ?>
    <div style="padding:30px;text-align:center;color:#6b7280;font-size:.88rem">Encara no s'ha generat cap còpia automàtica.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Fitxer</th><th>Data</th><th>Mida</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($auto_backups as $b): ?>
        <tr>
            <td><code style="font-size:.8rem"><?= htmlspecialchars($b['name']) ?></code></td>
            <td><?= date('d/m/Y H:i', $b['time']) ?></td>
            <td><?= number_format($b['size'] / 1024, 0) ?> KB</td>
            <td>
                <div class="td-actions">
                    <a href="backup.php?download_auto=<?= urlencode($b['name']) ?>" class="btn btn-sm btn-secondary">⬇️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar esta còpia de seguretat?')">
                        <input type="hidden" name="action" value="delete_auto">
                        <input type="hidden" name="filename" value="<?= htmlspecialchars($b['name']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
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

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
<script>
function toggleAll(state) {
    document.querySelectorAll('.section-check').forEach(cb => cb.checked = state);
}
function prepareSubmit(all) {
    // "Descarregar-ho tot": desmarquem totes les caselles perquè no s'envie
    // cap 'sections[]' i el servidor entenga que és una còpia completa
    // (inclou també qualsevol fitxer futur que encara no estiga mapat a cap secció).
    if (all) { toggleAll(false); return true; }
    const checked = document.querySelectorAll('.section-check:checked').length;
    if (checked === 0) { alert('Selecciona almenys una secció, o fes clic a "Descarregar-ho tot".'); return false; }
    return true;
}
</script>
</body></html>
