<?php
// admin/payments.php — Registre general de pagaments: es poden anotar sense
// saber encara a quina factura corresponen, i assignar-los després.
require_once 'includes/core.php';
requireLogin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $client_id = $_POST['client_id'] ?? '';
        $amount    = (float)str_replace(',', '.', $_POST['amount'] ?? 0);
        if (!$client_id || $amount <= 0) {
            $error = 'Cal client i un import més gran que 0.';
        } else {
            $payment = [
                'id'         => generateId(),
                'client_id'  => $client_id,
                'invoice_id' => $_POST['invoice_id'] ?? '', // buit = sense assignar encara
                'date'       => $_POST['date'] ?: date('Y-m-d'),
                'amount'     => $amount,
                'method'     => $_POST['method'] ?? 'transferencia',
                'reference'  => sanitize($_POST['reference'] ?? ''),
            ];
            savePayment($payment);
            $success = 'Pagament registrat' . ($payment['invoice_id'] ? ' i assignat.' : ' — encara sense assignar a cap factura.');
        }
    }

    if ($action === 'assign') {
        $payment_id = $_POST['payment_id'] ?? '';
        $invoice_id = $_POST['invoice_id'] ?? '';
        if ($payment_id && $invoice_id) {
            assignPaymentToInvoice($payment_id, $invoice_id);
            $success = 'Pagament assignat a la factura.';
        } else {
            $error = 'Selecciona una factura.';
        }
    }

    if ($action === 'unassign') {
        unassignPaymentFromInvoice($_POST['payment_id'] ?? '');
        $success = 'Pagament tornat a deixar sense assignar.';
    }

    if ($action === 'delete') {
        deletePayment($_POST['id'] ?? '');
        $success = 'Pagament eliminat.';
    }
}

$clients = getClients();
$methods = getPaymentMethodOptions();

$filter_client = $_GET['client'] ?? '';
$unassigned = getUnassignedPayments($filter_client ?: null);
$all_payments = getAllPayments($filter_client ?: null);

$page_title    = 'Pagaments';
$page_subtitle = count($unassigned) . ' sense assignar · ' . count($all_payments) . ' en total';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pagaments · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if ($success): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

<div>
    <!-- Filtre per client -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="display:flex;gap:8px;align-items:center">
            <form method="GET" style="display:flex;gap:8px;align-items:center">
                <select name="client" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                    <option value="">Tots els clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
                <?php if ($filter_client): ?><a href="payments.php" style="font-size:.8rem;color:#6b7280">Netejar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Sense assignar -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <div class="card-title">⏳ Sense assignar a cap factura (<?= count($unassigned) ?>)</div>
        </div>
        <?php if (empty($unassigned)): ?>
        <div style="padding:30px;text-align:center;color:#6b7280;font-size:.88rem">Cap pagament pendent d'assignar.</div>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Client</th><th>Import</th><th>Mètode</th><th>Referència</th><th>Assignar a</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($unassigned as $p):
                $pc = getClient($p['client_id'] ?? '');
                $client_invoices = $pc ? array_values(array_filter(getInvoices($pc['id']), fn($i) => ($i['status'] ?? '') !== 'cancelled' && invoicePaymentSummary($i)['status'] !== 'paid')) : [];
            ?>
            <tr>
                <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($p['date'])) ?></td>
                <td><?= $pc ? htmlspecialchars($pc['name']) : '—' ?></td>
                <td style="font-weight:700"><?= number_format($p['amount'], 2, ',', '.') ?> €</td>
                <td><?= htmlspecialchars($methods[$p['method'] ?? ''] ?? '') ?></td>
                <td style="color:#6b7280;font-size:.82rem"><?= htmlspecialchars($p['reference'] ?? '') ?></td>
                <td>
                    <?php if (empty($client_invoices)): ?>
                    <span style="font-size:.78rem;color:#9ca3af">Sense factures pendents</span>
                    <?php else: ?>
                    <form method="POST" style="display:flex;gap:6px">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="payment_id" value="<?= htmlspecialchars($p['id']) ?>">
                        <select name="invoice_id" required style="font-size:.8rem;padding:5px 8px;border:1px solid var(--a-border);border-radius:6px">
                            <option value="">— Factura —</option>
                            <?php foreach ($client_invoices as $inv): $s = invoicePaymentSummary($inv); ?>
                            <option value="<?= htmlspecialchars($inv['id']) ?>"><?= htmlspecialchars($inv['number']) ?> · pendent <?= number_format($s['due'], 2, ',', '.') ?> €</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Assignar</button>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Eliminar este pagament?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Historial complet -->
    <div class="card">
        <div class="card-header"><div class="card-title">🗂️ Tots els pagaments (<?= count($all_payments) ?>)</div></div>
        <?php if (empty($all_payments)): ?>
        <div style="padding:30px;text-align:center;color:#6b7280;font-size:.88rem">Encara no hi ha pagaments registrats.</div>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Client</th><th>Import</th><th>Mètode</th><th>Factura</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($all_payments as $p): $pc = getClient($p['client_id'] ?? ''); $inv = !empty($p['invoice_id']) ? getInvoice($p['invoice_id']) : null; ?>
            <tr>
                <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($p['date'])) ?></td>
                <td><?= $pc ? htmlspecialchars($pc['name']) : '—' ?></td>
                <td style="font-weight:700"><?= number_format($p['amount'], 2, ',', '.') ?> €</td>
                <td><?= htmlspecialchars($methods[$p['method'] ?? ''] ?? '') ?></td>
                <td>
                    <?php if ($inv): ?>
                    <a href="invoices.php?id=<?= htmlspecialchars($inv['id']) ?>"><?= htmlspecialchars($inv['number']) ?></a>
                    <?php else: ?>
                    <span class="badge badge-gold">Sense assignar</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="td-actions">
                        <?php if ($inv): ?>
                        <form method="POST" onsubmit="return confirm('Desassignar este pagament de la factura?')">
                            <input type="hidden" name="action" value="unassign">
                            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($p['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-secondary">Desassignar</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Eliminar este pagament?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
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
</div>

<!-- Registrar un pagament nou -->
<div class="card">
    <div class="card-header"><div class="card-title">➕ Registrar un pagament</div></div>
    <div class="card-body form-grid">
        <p class="hint" style="margin:0 0 4px">Si encara no saps a quina factura correspon, deixa "Factura" en blanc — el podràs assignar més tard des de la llista de l'esquerra.</p>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Client *</label>
                <select name="client_id" id="pay-client" required onchange="loadClientInvoices()">
                    <option value="">— Selecciona client —</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Import (€) *</label>
                    <input type="text" name="amount" required placeholder="250,00">
                </div>
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Mètode</label>
                    <select name="method">
                        <?php foreach ($methods as $key => $label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Referència</label>
                    <input type="text" name="reference" placeholder="Ex. concepte de la transferència">
                </div>
            </div>
            <div class="form-group">
                <label>Factura (opcional)</label>
                <select name="invoice_id" id="pay-invoice">
                    <option value="">— Sense assignar (ho faré després) —</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Registrar pagament</button>
        </form>
    </div>
</div>

</div>
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
<script>
const invoicesByClient = <?= json_encode(array_reduce($clients, function($acc, $c) {
    $acc[$c['id']] = array_map(fn($i) => ['id' => $i['id'], 'label' => $i['number'] . ' · pendent ' . number_format(invoicePaymentSummary($i)['due'], 2, ',', '.') . ' €'],
        array_values(array_filter(getInvoices($c['id']), fn($i) => ($i['status'] ?? '') !== 'cancelled' && invoicePaymentSummary($i)['status'] !== 'paid')));
    return $acc;
}, [])) ?>;
function loadClientInvoices() {
    const clientId = document.getElementById('pay-client').value;
    const sel = document.getElementById('pay-invoice');
    sel.innerHTML = '<option value="">— Sense assignar (ho faré després) —</option>';
    (invoicesByClient[clientId] || []).forEach(inv => {
        const opt = document.createElement('option');
        opt.value = inv.id;
        opt.textContent = inv.label;
        sel.appendChild(opt);
    });
}
</script>
</body></html>
