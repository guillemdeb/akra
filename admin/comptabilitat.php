<?php
// admin/comptabilitat.php — Informes: benefici real, IVA trimestral i estimació d'IRPF.
require_once 'includes/core.php';
requireLogin();

if (isset($_GET['export_csv'])) {
    $from = $_GET['from'] ?? date('Y-01-01');
    $to   = $_GET['to'] ?? date('Y-12-31');
    $csv = exportAccountingCsv($from, $to);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="comptabilitat-' . $from . '-a-' . $to . '.csv"');
    echo $csv;
    exit;
}

$year    = (int)($_GET['year'] ?? date('Y'));
$quarter = (int)($_GET['quarter'] ?? ceil((int)date('n') / 3));
$quarter = max(1, min(4, $quarter));

[$q_from, $q_to] = quarterDateRange($year, $quarter);
$pl_quarter = getProfitAndLoss($q_from, $q_to);
$vat        = getQuarterlyVatSummary($year, $quarter);
$irpf       = getQuarterlyIrpfEstimate($year, $quarter);
$pl_year    = getProfitAndLoss("$year-01-01", "$year-12-31");
$cats       = getExpenseCategoryOptions();

$page_title    = 'Comptabilitat';
$page_subtitle = "T$quarter $year";
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comptabilitat · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;margin-bottom:20px">
    ℹ️ Estos informes són una ajuda per decidir i per passar-los a la teua gestoria — <strong>no presenten impostos ni substitueixen l'assessorament fiscal</strong>. Els càlculs segueixen les regles generals d'estimació directa per a autònoms i no cobreixen casos especials (mòduls, prorrata, béns d'inversió...).
</div>

<!-- Selector de període -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <select name="year" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <?php for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select name="quarter" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <?php for ($q = 1; $q <= 4; $q++): ?>
                <option value="<?= $q ?>" <?= $q === $quarter ? 'selected' : '' ?>>Trimestre <?= $q ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Veure</button>
        </form>
        <a href="comptabilitat.php?export_csv=1&from=<?= $q_from ?>&to=<?= $q_to ?>" class="btn btn-sm btn-secondary">⬇️ Exportar CSV del trimestre (per a la gestoria)</a>
    </div>
</div>

<!-- Benefici del trimestre -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon stat-icon--green">💶</div>
        <div class="stat-label">Ingressos (base) — T<?= $quarter ?></div>
        <div class="stat-num"><?= number_format($pl_quarter['income_base'], 2, ',', '.') ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--red">📤</div>
        <div class="stat-label">Despeses (base) — T<?= $quarter ?></div>
        <div class="stat-num"><?= number_format($pl_quarter['expense_base'], 2, ',', '.') ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--gold">📊</div>
        <div class="stat-label">Benefici net — T<?= $quarter ?></div>
        <div class="stat-num"><?= number_format($pl_quarter['net_profit'], 2, ',', '.') ?> €</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--blue">📅</div>
        <div class="stat-label">Benefici net — <?= $year ?> acumulat</div>
        <div class="stat-num"><?= number_format($pl_year['net_profit'], 2, ',', '.') ?> €</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- IVA trimestral -->
    <div class="card">
        <div class="card-header"><div class="card-title">🧮 IVA del trimestre (orientatiu Model 303)</div></div>
        <div class="card-body">
            <div class="table-wrap">
            <table>
                <tbody>
                    <tr><td>IVA repercutit (a les teues factures)</td><td style="text-align:right;font-weight:700"><?= number_format($vat['iva_repercutit'], 2, ',', '.') ?> €</td></tr>
                    <tr><td>IVA suportat (a les teues despeses)</td><td style="text-align:right;font-weight:700"><?= number_format($vat['iva_suportat'], 2, ',', '.') ?> €</td></tr>
                    <tr style="border-top:2px solid var(--a-border)">
                        <td><strong><?= $vat['a_pagar'] > 0 ? 'A pagar' : 'A compensar' ?></strong></td>
                        <td style="text-align:right;font-weight:800;font-size:1.1rem;color:<?= $vat['a_pagar'] > 0 ? '#dc2626' : '#059669' ?>">
                            <?= number_format($vat['a_pagar'] > 0 ? $vat['a_pagar'] : $vat['a_compensar'], 2, ',', '.') ?> €
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- IRPF trimestral -->
    <div class="card">
        <div class="card-header"><div class="card-title">📋 IRPF estimat (orientatiu Model 130)</div></div>
        <div class="card-body">
            <div class="table-wrap">
            <table>
                <tbody>
                    <tr><td>Rendiment net acumulat (<?= $year ?>)</td><td style="text-align:right;font-weight:700"><?= number_format($irpf['net_profit_cumulative'], 2, ',', '.') ?> €</td></tr>
                    <tr><td>20% del rendiment</td><td style="text-align:right;font-weight:700"><?= number_format($irpf['gross_payment'], 2, ',', '.') ?> €</td></tr>
                    <tr><td>Retencions ja practicades (<?= $year ?>)</td><td style="text-align:right;font-weight:700">− <?= number_format($irpf['retention_cumulative'], 2, ',', '.') ?> €</td></tr>
                    <tr><td>Pagaments fraccionats anteriors</td><td style="text-align:right;font-weight:700">− <?= number_format($irpf['prior_payments'], 2, ',', '.') ?> €</td></tr>
                    <tr style="border-top:2px solid var(--a-border)">
                        <td><strong>A ingressar este trimestre</strong></td>
                        <td style="text-align:right;font-weight:800;font-size:1.1rem"><?= number_format($irpf['due'], 2, ',', '.') ?> €</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- Despeses per categoria -->
<div class="card">
    <div class="card-header"><div class="card-title">🗂️ Despeses per categoria — T<?= $quarter ?> <?= $year ?></div></div>
    <?php if (empty($pl_quarter['by_category'])): ?>
    <div style="padding:30px;text-align:center;color:#6b7280;font-size:.88rem">Cap despesa registrada este trimestre.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Categoria</th><th style="text-align:right">Base</th><th style="text-align:right">% del total</th></tr></thead>
        <tbody>
        <?php foreach ($pl_quarter['by_category'] as $cat => $amount): ?>
        <tr>
            <td><?= htmlspecialchars($cats[$cat] ?? $cat) ?></td>
            <td style="text-align:right;font-weight:700"><?= number_format($amount, 2, ',', '.') ?> €</td>
            <td style="text-align:right;color:#6b7280"><?= $pl_quarter['expense_base'] > 0 ? number_format($amount / $pl_quarter['expense_base'] * 100, 0) : 0 ?>%</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
