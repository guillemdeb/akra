<?php
// admin/expenses.php — Registre de despeses (amb desglossament d'IVA i justificant).
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $existing = !empty($_POST['id']) ? getExpense($_POST['id']) : null;
        $receipt = $existing['receipt_file'] ?? '';
        if (!empty($_FILES['receipt']['name'])) {
            $upload = uploadDocument($_FILES['receipt'], 'expenses');
            if ($upload['ok']) {
                if ($receipt) { $full = AKRA_ROOT . '/' . $receipt; if (file_exists($full)) @unlink($full); }
                $receipt = $upload['path'];
            }
        }
        saveExpense([
            'id'           => $_POST['id'] ?: generateId(),
            'date'         => $_POST['date'] ?: date('Y-m-d'),
            'concept'      => sanitize($_POST['concept'] ?? ''),
            'category'     => $_POST['category'] ?? 'altres',
            'supplier_id'  => $_POST['supplier_id'] ?? '',
            'base'         => (float)str_replace(',', '.', $_POST['base'] ?? 0),
            'vat_pct'      => (int)($_POST['vat_pct'] ?? 21),
            'payment_method' => $_POST['payment_method'] ?? 'transferencia',
            'deductible'   => isset($_POST['deductible']),
            'receipt_file' => $receipt,
            'notes'        => sanitize($_POST['notes'] ?? ''),
        ]);
        header('Location: expenses.php?saved=1'); exit;
    }

    if ($action === 'delete') {
        deleteExpense($_POST['id'] ?? '');
        header('Location: expenses.php?deleted=1'); exit;
    }
}

$suppliers = getSuppliers();
$cats      = getExpenseCategoryOptions();
$vat_opts  = getVatRateOptions();
$methods   = getPaymentMethodOptions();

$filter_from     = $_GET['from'] ?? '';
$filter_to       = $_GET['to'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_supplier = $_GET['supplier'] ?? '';

$expenses = getExpenses(array_filter([
    'from' => $filter_from, 'to' => $filter_to, 'category' => $filter_category, 'supplier_id' => $filter_supplier,
]));
$total_base = array_sum(array_column($expenses, 'base'));
$total_tax  = array_sum(array_column($expenses, 'tax'));

$edit = !empty($_GET['edit']) ? getExpense($_GET['edit']) : null;

$page_title    = 'Despeses';
$page_subtitle = count($expenses) . ' despesa' . (count($expenses) !== 1 ? 'es' : '') . ' · ' . number_format($total_base, 2, ',', '.') . ' € base';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Despeses · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Despesa guardada.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Despesa eliminada.</div><?php endif; ?>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;justify-content:space-between">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>" style="font-size:.82rem">
            <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>" style="font-size:.82rem">
            <select name="category" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Totes les categories</option>
                <?php foreach ($cats as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filter_category === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="supplier" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Tots els proveïdors</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= htmlspecialchars($s['id']) ?>" <?= $filter_supplier === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
            <?php if ($filter_from || $filter_to || $filter_category || $filter_supplier): ?><a href="expenses.php" style="font-size:.8rem;color:#6b7280">Netejar</a><?php endif; ?>
        </form>
        <div style="text-align:right;font-size:.82rem;color:#6b7280">
            Base: <strong style="color:#1a1a1a"><?= number_format($total_base, 2, ',', '.') ?> €</strong> ·
            IVA: <strong style="color:#1a1a1a"><?= number_format($total_tax, 2, ',', '.') ?> €</strong>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start">

<div class="card">
    <div class="card-header"><div class="card-title">🧾 Despeses</div></div>
    <?php if (empty($expenses)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Cap despesa amb estos filtres.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Data</th><th>Concepte</th><th>Categoria</th><th>Proveïdor</th><th>Base</th><th>IVA</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($expenses as $e): $sp = !empty($e['supplier_id']) ? getSupplier($e['supplier_id']) : null; ?>
        <tr>
            <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($e['date'])) ?></td>
            <td><?= htmlspecialchars($e['concept']) ?> <?php if (!empty($e['receipt_file'])): ?><a href="../<?= htmlspecialchars($e['receipt_file']) ?>" target="_blank" title="Veure justificant">📎</a><?php endif; ?></td>
            <td><span style="font-size:.78rem;color:#6b7280"><?= htmlspecialchars($cats[$e['category'] ?? ''] ?? '') ?></span></td>
            <td><?= $sp ? htmlspecialchars($sp['name']) : '<span style="color:#9ca3af">—</span>' ?></td>
            <td><?= number_format($e['base'], 2, ',', '.') ?> €</td>
            <td><?= number_format($e['tax'], 2, ',', '.') ?> €</td>
            <td style="font-weight:700"><?= number_format($e['total'], 2, ',', '.') ?> €</td>
            <td>
                <div class="td-actions">
                    <a href="expenses.php?edit=<?= htmlspecialchars($e['id']) ?>" class="btn btn-sm btn-secondary">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar esta despesa?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($e['id']) ?>">
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

<div class="card" id="expense-form">
    <div class="card-header"><div class="card-title"><?= $edit ? '✏️ Editar despesa' : '➕ Nova despesa' ?></div></div>
    <div class="card-body form-grid">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">
            <div class="form-group"><label>Concepte *</label><input type="text" name="concept" required value="<?= htmlspecialchars($edit['concept'] ?? '') ?>" placeholder="Ex. Hosting anual"></div>
            <div class="form-row-2">
                <div class="form-group"><label>Data</label><input type="date" name="date" value="<?= htmlspecialchars($edit['date'] ?? date('Y-m-d')) ?>"></div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <?php foreach ($cats as $key => $label): ?><option value="<?= $key ?>" <?= ($edit['category'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Proveïdor (opcional)</label>
                <select name="supplier_id">
                    <option value="">— Sense proveïdor —</option>
                    <?php foreach ($suppliers as $s): ?><option value="<?= htmlspecialchars($s['id']) ?>" <?= ($edit['supplier_id'] ?? '') === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Base imposable (€) *</label><input type="text" name="base" required value="<?= htmlspecialchars($edit['base'] ?? '') ?>" placeholder="100,00"></div>
                <div class="form-group">
                    <label>% IVA</label>
                    <select name="vat_pct">
                        <?php foreach ($vat_opts as $val => $label): ?><option value="<?= $val ?>" <?= (int)($edit['vat_pct'] ?? 21) === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Mètode de pagament</label>
                    <select name="payment_method">
                        <?php foreach ($methods as $key => $label): ?><option value="<?= $key ?>" <?= ($edit['payment_method'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px;margin-top:26px"><input type="checkbox" name="deductible" <?= ($edit['deductible'] ?? true) ? 'checked' : '' ?>> Deduïble</label>
                </div>
            </div>
            <div class="form-group">
                <label>Justificant (factura/tiquet)</label>
                <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($edit['receipt_file'])): ?><p class="hint">Ja n'hi ha un pujat: <a href="../<?= htmlspecialchars($edit['receipt_file']) ?>" target="_blank">veure'l</a> (puja'n un altre per substituir-lo).</p><?php endif; ?>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes"><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
                <?php if ($edit): ?><a href="expenses.php" class="btn btn-secondary">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

</div>
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
