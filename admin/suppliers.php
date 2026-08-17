<?php
// admin/suppliers.php — Proveïdors (per associar-los a despeses).
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        saveSupplier([
            'id'    => $_POST['id'] ?: generateId(),
            'name'  => sanitize($_POST['name'] ?? ''),
            'nif'   => sanitize($_POST['nif'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
        ]);
        header('Location: suppliers.php?saved=1'); exit;
    }
    if ($action === 'delete') {
        deleteSupplier($_POST['id'] ?? '');
        header('Location: suppliers.php?deleted=1'); exit;
    }
}

$suppliers = getSuppliers();
$edit = !empty($_GET['edit']) ? getSupplier($_GET['edit']) : null;

$page_title    = 'Proveïdors';
$page_subtitle = count($suppliers) . ' proveïdor' . (count($suppliers) !== 1 ? 's' : '');
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Proveïdors · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Proveïdor guardat.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Proveïdor eliminat.</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

<div class="card">
    <div class="card-header"><div class="card-title">🏷️ Proveïdors</div></div>
    <?php if (empty($suppliers)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Encara no tens cap proveïdor. Crea'n un amb el formulari de la dreta.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Nom</th><th>NIF</th><th>Contacte</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td><?= htmlspecialchars($s['nif'] ?? '') ?></td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($s['email'] ?? '') ?> <?= !empty($s['phone']) ? '· ' . htmlspecialchars($s['phone']) : '' ?></td>
            <td>
                <div class="td-actions">
                    <a href="suppliers.php?edit=<?= htmlspecialchars($s['id']) ?>" class="btn btn-sm btn-secondary">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar este proveïdor?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($s['id']) ?>">
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

<div class="card">
    <div class="card-header"><div class="card-title"><?= $edit ? '✏️ Editar proveïdor' : '➕ Nou proveïdor' ?></div></div>
    <div class="card-body form-grid">
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">
            <div class="form-group"><label>Nom *</label><input type="text" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>"></div>
            <div class="form-group"><label>NIF/CIF</label><input type="text" name="nif" value="<?= htmlspecialchars($edit['nif'] ?? '') ?>"></div>
            <div class="form-row-2">
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>"></div>
                <div class="form-group"><label>Telèfon</label><input type="text" name="phone" value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes"><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
                <?php if ($edit): ?><a href="suppliers.php" class="btn btn-secondary">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

</div>
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
