<?php
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'restore') {
        restoreRecord($_POST['type'], $_POST['id']);
        header('Location: trash.php?restored=1'); exit;
    }
    if ($action === 'purge') {
        purgeRecord($_POST['type'], $_POST['id']);
        header('Location: trash.php?purged=1'); exit;
    }
    if ($action === 'purge_all') {
        $n = purgeOldTrash(0); // 0 dies = purga tot el que hi ha ara mateix
        header('Location: trash.php?purged_all=' . $n); exit;
    }
}

$trashed = getTrashedRecords();

$page_title    = 'Paperera';
$page_subtitle = count($trashed) . ' elements esborrats';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['restored'])): ?><div class="alert alert-success">✅ Element restaurat.</div><?php endif; ?>
<?php if (isset($_GET['purged'])): ?><div class="alert alert-success">Element eliminat definitivament.</div><?php endif; ?>
<?php if (isset($_GET['purged_all'])): ?><div class="alert alert-success">✅ Paperera buidada (<?= (int)$_GET['purged_all'] ?> elements eliminats definitivament).</div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🗑️ Paperera</div>
        <?php if (!empty($trashed)): ?>
        <form method="POST" onsubmit="return confirm('Buidar tota la paperera? Açò esborra definitivament TOT el que hi ha en esta llista, sense possibilitat de recuperar-ho.')">
            <input type="hidden" name="action" value="purge_all">
            <button class="btn btn-sm btn-danger">🗑️ Buidar paperera</button>
        </form>
        <?php endif; ?>
    </div>
    <p style="padding:14px 22px 0;color:#6b7280;font-size:.85rem">
        Els elements esborrats es guarden ací durant 30 dies (es purguen sols automàticament amb el procés diari) abans de desaparèixer definitivament.
        Pots restaurar'ls en qualsevol moment mentre estiguen ací.
    </p>
    <?php if (empty($trashed)): ?>
    <div style="padding:48px;text-align:center;color:#9ca3af">La paperera està buida.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Tipus</th><th>Element</th><th>Client</th><th>Esborrat el</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($trashed as $t): ?>
        <tr>
            <td><span class="badge badge-gray"><?= htmlspecialchars($t['type_label']) ?></span></td>
            <td style="font-size:.85rem"><strong><?= htmlspecialchars((string)$t['label']) ?></strong></td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($t['client_name']) ?></td>
            <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap"><?= date('d/m/Y H:i', strtotime($t['deleted_at'])) ?></td>
            <td class="td-actions">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="type" value="<?= $t['type'] ?>">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button class="btn btn-sm btn-secondary">↩️ Restaurar</button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('Eliminar definitivament? Açò no es pot desfer.')">
                    <input type="hidden" name="action" value="purge">
                    <input type="hidden" name="type" value="<?= $t['type'] ?>">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑️ Eliminar definitivament</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
