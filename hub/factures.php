<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

if (isset($_GET['download_pdf'])) {
    $inv = getInvoice($_GET['download_pdf']);
    if (!$inv || $inv['client_id'] !== $client['id']) { http_response_code(403); die('No autoritzat.'); }
    $pdf_lang = in_array($_GET['lang'] ?? '', ['ca', 'es']) ? $_GET['lang'] : ($lang === 'es' ? 'es' : 'ca');
    $result = generateInvoicePdf($inv['id'], $pdf_lang);
    if (!$result['ok']) die('No s\'ha pogut generar el PDF.');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . strlen($result['pdf']));
    echo $result['pdf'];
    exit;
}

$invoices = getInvoices($client['id']);
$fin      = getClientFinancialSummary($client['id']);
$cfg      = getAdminConfig();
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('inv_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('inv_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= count($invoices) ?> · <?= number_format($fin['due'], 2, ',', '.') ?> € <?= htmlspecialchars(hubT('inv_due_suffix', $lang)) ?></p>

    <?php if (empty($invoices)): ?>
    <div class="hub-card"><div class="hub-empty"><?= htmlspecialchars(hubT('inv_empty', $lang)) ?></div></div>
    <?php else: ?>
    <div class="hub-card">
        <?php foreach ($invoices as $inv):
            if (($inv['status'] ?? '') === 'cancelled') continue;
            $sl = invoiceStatusLabel($inv['status'] ?? 'draft');
            $summary = invoicePaymentSummary($inv);
        ?>
        <div class="hub-row">
            <div class="hub-row-main">
                <div class="hub-row-title"><?= htmlspecialchars($inv['number'] ?? '') ?></div>
                <div class="hub-row-sub">
                    <?= htmlspecialchars(hubT('inv_issued', $lang)) ?> <?= !empty($inv['date']) ? date('d/m/Y', strtotime($inv['date'])) : '—' ?>
                    <?php if (!empty($inv['due_date'])): ?> · <?= htmlspecialchars(hubT('inv_due_date', $lang)) ?> <?= date('d/m/Y', strtotime($inv['due_date'])) ?><?php endif; ?>
                </div>
                <?php if ($summary['due'] > 0 && $summary['paid'] > 0): ?>
                <div class="hub-row-sub" style="margin-top:4px"><?= htmlspecialchars(hubT('inv_partial', $lang)) ?> <?= number_format($summary['paid'], 2, ',', '.') ?> € / <?= number_format($summary['total'], 2, ',', '.') ?> €</div>
                <?php endif; ?>
            </div>
            <div class="hub-row-side">
                <div class="hub-row-amount"><?= number_format($inv['total'] ?? 0, 2, ',', '.') ?> €</div>
                <div style="margin:6px 0"><span class="hub-badge hub-badge--<?= str_replace('badge-', '', $sl['class']) ?>"><?= htmlspecialchars(hubTStatus($sl['text'], $lang)) ?></span></div>
                <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
                    <?php if (!empty($cfg['payment_link']) && $summary['due'] > 0): ?>
                    <a href="<?= htmlspecialchars($cfg['payment_link']) ?>" target="_blank" class="hub-btn hub-btn--gold hub-btn--sm"><?= htmlspecialchars(hubT('inv_pay', $lang)) ?></a>
                    <?php endif; ?>
                    <a href="factures.php?download_pdf=<?= htmlspecialchars($inv['id']) ?>" class="hub-btn hub-btn--sm"><?= htmlspecialchars(hubT('inv_pdf', $lang)) ?></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body></html>
