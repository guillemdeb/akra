<?php
require_once 'includes/core.php';
requireLogin();

$cfg = getAdminConfig();
$clients = getClients();

if (isset($_GET['run_recurring'])) {
    $created = generateDueRecurringInvoices();
    $created2 = generateDueDomainRenewalInvoices();
    header('Location: invoices.php?recurring_ran=' . (count($created) + count($created2))); exit;
}

if (isset($_GET['export_csv'])) {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to'] ?? date('Y-m-d');
    $csv  = exportInvoicesCsv($from, $to);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="factures-' . $from . '-a-' . $to . '.csv"');
    echo $csv;
    exit;
}

// ── ACCIONS ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = $_POST['id'] ?: generateId();
        $prev_invoice = !empty($_POST['id']) ? getInvoice($_POST['id']) : null;
        $number  = $_POST['number'] ?: nextInvoiceNumber();
        $lines   = [];
        $descs   = $_POST['line_desc']  ?? [];
        $qtys    = $_POST['line_qty']   ?? [];
        $prices  = $_POST['line_price'] ?? [];
        foreach ($descs as $k => $desc) {
            if (empty(trim($desc))) continue;
            $lines[] = [
                'desc'  => sanitize($desc),
                'qty'   => (float)($qtys[$k] ?? 1),
                'price' => (float)str_replace(',', '.', $prices[$k] ?? 0),
            ];
        }
        $tax_pct  = (int)($_POST['tax_pct']  ?? 21);
        $irpf_pct = (int)($_POST['irpf_pct'] ?? 0);
        $totals   = invoiceTotals($lines, $tax_pct, $irpf_pct);

        $invoice = [
            'id'          => $id,
            'number'      => $number,
            'client_id'   => $_POST['client_id'] ?? '',
            'status'      => $_POST['status'] ?? 'draft',
            'date'        => $_POST['date'] ?: date('Y-m-d'),
            'due_date'    => $_POST['due_date'] ?? '',
            'lines'       => $lines,
            'tax_pct'     => $tax_pct,
            'irpf_pct'    => $irpf_pct,
            'subtotal'    => $totals['subtotal'],
            'tax'         => $totals['tax'],
            'irpf'        => $totals['irpf'],
            'total'       => $totals['total'],
            'notes'       => sanitize($_POST['notes'] ?? ''),
            'payment_info'=> sanitize($_POST['payment_info'] ?? ''),
            'recurring'      => isset($_POST['recurring']),
            'recurring_freq' => $_POST['recurring_freq'] ?? 'monthly',
            'recurring_next' => isset($_POST['recurring']) ? ($_POST['recurring_next'] ?: date('Y-m-d', strtotime('+1 month'))) : null,
            'due_days'       => (int)($_POST['due_days'] ?? 30),
        ];
        saveInvoice($invoice);

        // Si el client encara estava en fase de "lead", en facturar-li ja és client de veritat.
        advanceClientStage($invoice['client_id'], 'guanyat');

        // Avisa el client quan la factura passa a "Enviada" per primera vegada
        if ($invoice['status'] === 'sent' && ($prev_invoice['status'] ?? '') !== 'sent') {
            notifyClientOfChange($invoice['client_id'], 'invoice_new', [
                'body_args' => [$invoice['number'], number_format($invoice['total'], 2, ',', '.')],
                'hub_page'  => 'factures.php',
            ]);
        }

        $success = 'Factura guardada.';
        if (isset($_POST['redirect_print'])) {
            header('Location: invoices.php?print=' . $id); exit;
        }
        header('Location: invoices.php?saved=1'); exit;
    }

    if ($action === 'delete') {
        deleteInvoice($_POST['id']);
        header('Location: invoices.php?deleted=1'); exit;
    }

    if ($action === 'set_status') {
        $inv = getInvoice($_POST['id']);
        if ($inv) {
            $prev_status = $inv['status'];
            $inv['status'] = $_POST['status'];
            saveInvoice($inv);
            if ($inv['status'] === 'sent' && $prev_status !== 'sent') {
                notifyClientOfChange($inv['client_id'], 'invoice_new', [
                    'body_args' => [$inv['number'], number_format($inv['total'], 2, ',', '.')],
                    'hub_page'  => 'factures.php',
                ]);
            }
        }
        header('Location: invoices.php'); exit;
    }

    if ($action === 'save_payment') {
        $inv_for_payment = getInvoice($_POST['invoice_id']);
        $payment = [
            'id'         => $_POST['payment_id'] ?: generateId(),
            'invoice_id' => $_POST['invoice_id'],
            'client_id'  => $inv_for_payment['client_id'] ?? '',
            'date'       => $_POST['pay_date'] ?: date('Y-m-d'),
            'amount'     => (float)str_replace(',', '.', $_POST['amount'] ?? 0),
            'method'     => $_POST['method'] ?? 'transferencia',
            'reference'  => sanitize($_POST['reference'] ?? ''),
        ];
        savePayment($payment);
        header('Location: invoices.php?id=' . $_POST['invoice_id'] . '&payment_saved=1'); exit;
    }

    if ($action === 'delete_payment') {
        deletePayment($_POST['payment_id']);
        header('Location: invoices.php?id=' . $_POST['invoice_id'] . '&payment_deleted=1'); exit;
    }
}

// ── DESCARREGAR PDF ──────────────────────────────────────────────────────────
if (isset($_GET['download_pdf'])):
    $lang = $_GET['lang'] ?? 'ca';
    $result = generateInvoicePdf($_GET['download_pdf'], $lang);
    if (!$result['ok']) {
        header('Content-Type: text/plain; charset=UTF-8');
        die('No s\'ha pogut generar el PDF: ' . $result['error']);
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . strlen($result['pdf']));
    echo $result['pdf'];
    exit;
endif;

// ── ENVIAR EMAIL ─────────────────────────────────────────────────────────────
if (isset($_GET['send_email']) && isset($_GET['inv_id'])):
    $inv_id  = $_GET['inv_id'];
    $lang    = $_GET['lang'] ?? 'ca';
    $inv     = getInvoice($inv_id);
    $client  = $inv ? getClient($inv['client_id']) : null;
    $to      = $_GET['to'] ?? $client['email'] ?? '';
    if ($to) {
        $result = sendInvoiceEmail($inv_id, $to, $lang);
        if ($result['ok']) {
            // Actualitza estat a "enviada" si era esborrany
            if ($inv && $inv['status'] === 'draft') {
                $inv['status'] = 'sent'; saveInvoice($inv);
            }
            header('Location: invoices.php?email_ok=1&id=' . $inv_id); exit;
        }
        header('Location: invoices.php?email_err=1&id=' . $inv_id . '&email_err_msg=' . urlencode($result['error'] ?? '')); exit;
    }
    header('Location: invoices.php?email_err=1&id=' . $inv_id); exit;
endif;

// ── VISTA IMPRESSIÓ ──────────────────────────────────────────────────────────
if (isset($_GET['print'])):
    $inv    = getInvoice($_GET['print']);
    $client = $inv ? getClient($inv['client_id']) : null;
    if (!$inv) { header('Location: invoices.php'); exit; }

    // Idioma — per defecte CA, canviable via ?lang=es
    $lang = $_GET['lang'] ?? 'ca';
    $L = $lang === 'es' ? [
        'INVOICE'   => 'FACTURA',
        'de'        => 'De',
        'para'      => 'Para',
        'fecha'     => 'Fecha de emisión',
        'venc'      => 'Vencimiento',
        'desc'      => 'Descripción',
        'uds'       => 'Uds.',
        'precio'    => 'Precio unit.',
        'total'     => 'Total',
        'base'      => 'Base imponible',
        'iva'       => 'IVA',
        'irpf'      => 'IRPF',
        'total_f'   => 'TOTAL',
        'pagament'  => 'Forma de pago',
        'obs'       => 'Observaciones',
        'print_btn' => '🖨 Imprimir / PDF',
        'email_btn' => '✉️ Enviar por email',
        'back'      => 'Volver',
        'lang_alt'  => 'Català',
        'lang_alt_code' => 'ca',
    ] : [
        'INVOICE'   => 'FACTURA',
        'de'        => 'De',
        'para'      => 'Per a',
        'fecha'     => 'Data d\'emissió',
        'venc'      => 'Venciment',
        'desc'      => 'Descripció',
        'uds'       => 'Ut.',
        'precio'    => 'Preu unit.',
        'total'     => 'Total',
        'base'      => 'Base imposable',
        'iva'       => 'IVA',
        'irpf'      => 'IRPF',
        'total_f'   => 'TOTAL',
        'pagament'  => 'Forma de pagament',
        'obs'       => 'Observacions',
        'print_btn' => '🖨 Imprimir / PDF',
        'email_btn' => '✉️ Enviar per email',
        'back'      => 'Tornar',
        'lang_alt'  => 'Castellano',
        'lang_alt_code' => 'es',
    ];

    $t      = invoiceTotals($inv['lines'], $inv['tax_pct'] ?? 21, $inv['irpf_pct'] ?? 0);
    $status = invoiceStatusLabel($inv['status']);
    $client_email = $client['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $L['INVOICE'] ?> <?= htmlspecialchars($inv['number']) ?> · AKRA Tech Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
<style>
:root {
    --ink:    #0a0a0a;
    --ink2:   #374151;
    --muted:  #6b7280;
    --border: #e5e7eb;
    --bg:     #f9fafb;
    --gold:   #c9a84c;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

body {
    font-family: 'DM Sans', 'Helvetica Neue', Arial, sans-serif;
    font-size: 13px; color: var(--ink); background: #f0f0f0;
}

/* ─── TOOLBAR (no s'imprimeix) ─── */
.toolbar {
    position: fixed; top: 0; left: 0; right: 0;
    background: var(--ink); color: white; padding: 0 32px;
    height: 56px; display: flex; align-items: center; justify-content: space-between;
    z-index: 100; gap: 12px;
    font-family: 'DM Sans', sans-serif;
}
.toolbar__left { display: flex; align-items: center; gap: 8px; }
.toolbar__logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 15px; letter-spacing: -0.02em; color: white; display: flex; align-items: center; gap: 8px; }
.toolbar__logo svg { opacity: 0.7; }
.toolbar__sep { width: 1px; height: 20px; background: rgba(255,255,255,0.15); }
.toolbar__title { font-size: 13px; color: rgba(255,255,255,0.5); }
.toolbar__actions { display: flex; align-items: center; gap: 8px; }
.tb-btn {
    padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; text-decoration: none; display: inline-flex;
    align-items: center; gap: 6px; font-family: 'DM Sans', sans-serif;
    transition: all 0.15s;
}
.tb-btn--primary  { background: white; color: var(--ink); }
.tb-btn--primary:hover { background: #f0f0f0; }
.tb-btn--ghost    { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.85); }
.tb-btn--ghost:hover   { background: rgba(255,255,255,0.2); }
.tb-btn--danger   { background: rgba(239,68,68,0.15); color: #fca5a5; }
.tb-btn--lang     { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.6); font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; }

/* ─── EMAIL MODAL ─── */
.email-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6);
    z-index: 200; align-items: center; justify-content: center;
}
.email-modal-overlay.open { display: flex; }
.email-modal {
    background: white; border-radius: 14px; padding: 32px;
    width: 480px; max-width: 90vw; box-shadow: 0 24px 80px rgba(0,0,0,0.25);
}
.email-modal h3 { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--ink); margin-bottom: 20px; }
.email-modal label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); display: block; margin-bottom: 6px; }
.email-modal input, .email-modal select {
    width: 100%; padding: 10px 14px; border: 1.5px solid var(--border);
    border-radius: 8px; font-size: 13px; color: var(--ink); margin-bottom: 16px;
    font-family: 'DM Sans', sans-serif;
}
.email-modal input:focus, .email-modal select:focus { outline: none; border-color: var(--ink); }
.email-modal__actions { display: flex; gap: 10px; margin-top: 4px; }
.em-btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: 'DM Sans', sans-serif; }
.em-btn--send  { background: var(--ink); color: white; flex: 1; }
.em-btn--cancel { background: var(--bg); color: var(--ink); }

/* ─── DOCUMENT ─── */
.page-wrap { padding: 72px 24px 40px; }
.invoice {
    max-width: 794px; margin: 0 auto;
    background: white; border-radius: 4px;
    box-shadow: 0 8px 48px rgba(0,0,0,0.12);
    overflow: hidden;
}

/* Header negre */
.inv-head {
    background: var(--ink); padding: 36px 48px;
    display: flex; justify-content: space-between; align-items: flex-start;
}
.inv-head__logo { display: flex; align-items: center; gap: 12px; }
.inv-head__logo-mark svg { display: block; }
.inv-head__logo-text { }
.inv-head__logo-name {
    font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800;
    color: white; letter-spacing: -0.03em; line-height: 1;
}
.inv-head__logo-sub {
    font-size: 10px; font-weight: 400; color: rgba(255,255,255,0.4);
    letter-spacing: 0.1em; text-transform: uppercase; display: block; margin-top: 3px;
}
.inv-head__meta { text-align: right; }
.inv-head__title {
    font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 700;
    color: white; letter-spacing: -0.04em; line-height: 1; margin-bottom: 6px;
}
.inv-head__number { font-size: 13px; color: rgba(255,255,255,0.45); margin-bottom: 8px; }
.inv-status {
    display: inline-block; padding: 3px 12px; border-radius: 100px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
}
.s-paid    { background: #dcfce7; color: #166534; }
.s-sent    { background: #dbeafe; color: #1e40af; }
.s-draft   { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.6); }
.s-overdue { background: #fee2e2; color: #991b1b; }
.s-cancelled { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.4); }

/* Barra daurada decorativa */
.inv-stripe { height: 4px; background: linear-gradient(90deg, var(--gold) 0%, #e8c878 50%, var(--gold) 100%); }

/* Cos del document */
.inv-body { padding: 40px 48px; }

/* Parties */
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 32px; }
.inv-party { padding: 20px 24px; background: var(--bg); }
.inv-party__label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 10px; }
.inv-party strong { font-size: 14px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 5px; line-height: 1.2; }
.inv-party p { font-size: 12px; color: var(--muted); line-height: 1.75; }

/* Dates */
.inv-dates { display: flex; gap: 40px; margin-bottom: 32px; padding-bottom: 28px; border-bottom: 1px solid var(--border); }
.inv-date { }
.inv-date__label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); display: block; margin-bottom: 4px; }
.inv-date__val   { font-size: 14px; font-weight: 600; color: var(--ink); }

/* Taula de línies */
.lines-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
.lines-table thead th {
    padding: 9px 12px; font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--muted); border-bottom: 2px solid var(--ink);
    text-align: left; background: white;
}
.lines-table thead th:nth-child(2),
.lines-table thead th:nth-child(3),
.lines-table thead th:nth-child(4) { text-align: right; }
.lines-table tbody tr { border-bottom: 1px solid var(--border); }
.lines-table tbody tr:last-child { border-bottom: none; }
.lines-table tbody td { padding: 12px; font-size: 13px; color: var(--ink2); vertical-align: top; }
.lines-table tbody td:nth-child(2),
.lines-table tbody td:nth-child(3),
.lines-table tbody td:nth-child(4) { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.lines-table tbody td:nth-child(4) { font-weight: 600; }

/* Totals */
.inv-totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 32px; }
.inv-totals { width: 280px; border-collapse: collapse; }
.inv-totals td { padding: 5px 0; font-size: 13px; }
.inv-totals td:first-child { color: var(--muted); }
.inv-totals td:last-child  { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
.inv-totals .total-final td {
    padding-top: 14px; border-top: 2px solid var(--ink);
    font-size: 18px; font-weight: 700; color: var(--ink);
}
.inv-totals .irpf-row td:last-child { color: #dc2626; }

/* Footer del document */
.inv-doc-footer {
    margin-top: 8px; padding-top: 28px; border-top: 1px solid var(--border);
    display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
}
.inv-doc-footer__block h5 {
    font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--muted); margin-bottom: 8px;
}
.inv-doc-footer__block p {
    font-size: 12px; color: var(--muted); line-height: 1.75; white-space: pre-line;
}

/* Firma bottom */
.inv-sign {
    margin-top: 40px; padding: 20px 48px; background: var(--bg);
    border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.inv-sign__brand { display: flex; align-items: center; gap: 8px; }
.inv-sign__name  { font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; color: var(--ink); letter-spacing: -0.01em; }
.inv-sign__url   { font-size: 11px; color: var(--muted); }
.inv-sign__note  { font-size: 11px; color: var(--muted); }

/* ─── PRINT ─── */
@media print {
    body { background: white; }
    .toolbar, .email-modal-overlay { display: none !important; }
    .page-wrap { padding: 0; }
    .invoice { box-shadow: none; border-radius: 0; max-width: 100%; }
    .inv-head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .inv-stripe { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

<!-- ─── TOOLBAR ─── -->
<div class="toolbar">
    <div class="toolbar__left">
        <div class="toolbar__logo">
            <svg width="22" height="22" viewBox="0 0 36 36" fill="none">
                <rect width="36" height="36" rx="8" fill="white" fill-opacity="0.1"/>
                <path d="M8 28L18 8L28 28" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.5 22H24.5" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            AKRA
        </div>
        <div class="toolbar__sep"></div>
        <div class="toolbar__title"><?= htmlspecialchars($inv['number']) ?></div>
    </div>
    <div class="toolbar__actions">
        <!-- Canvi d'idioma -->
        <a href="?print=<?= $inv['id'] ?>&lang=<?= $L['lang_alt_code'] ?>"
           class="tb-btn tb-btn--lang"><?= $L['lang_alt'] ?></a>

        <!-- Enviar per email -->
        <?php if (!empty($client_email)): ?>
        <button class="tb-btn tb-btn--ghost" onclick="document.getElementById('email-modal').classList.add('open')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?= $L['email_btn'] ?>
        </button>
        <?php endif; ?>

        <!-- Imprimir/PDF -->
        <button class="tb-btn tb-btn--primary" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            <?= $L['print_btn'] ?>
        </button>

        <!-- Descarregar PDF (generat al servidor amb Dompdf) -->
        <a href="invoices.php?download_pdf=<?= $inv['id'] ?>&lang=<?= $lang ?>" class="tb-btn tb-btn--ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            PDF
        </a>

        <a href="invoices.php" class="tb-btn tb-btn--ghost"><?= $L['back'] ?></a>
    </div>
</div>

<!-- ─── EMAIL MODAL ─── -->
<div class="email-modal-overlay" id="email-modal">
    <div class="email-modal">
        <h3><?= $L['email_btn'] ?></h3>
        <form method="GET" action="invoices.php">
            <input type="hidden" name="send_email" value="1">
            <input type="hidden" name="inv_id" value="<?= $inv['id'] ?>">
            <label>Adreça de destí</label>
            <input type="email" name="to" value="<?= htmlspecialchars($client_email) ?>" required placeholder="client@empresa.es">
            <label>Idioma de la factura</label>
            <select name="lang">
                <option value="ca" <?= $lang==='ca'?'selected':'' ?>>Català</option>
                <option value="es" <?= $lang==='es'?'selected':'' ?>>Castellano</option>
            </select>
            <p style="font-size:11px;color:#9ca3af;margin:-8px 0 16px">S'adjuntarà la factura en PDF automàticament.</p>
            <div class="email-modal__actions">
                <button type="submit" class="em-btn em-btn--send">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Enviar ara
                </button>
                <button type="button" class="em-btn em-btn--cancel" onclick="document.getElementById('email-modal').classList.remove('open')">Cancel·lar</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── DOCUMENT ─── -->
<div class="page-wrap">
<div class="invoice">

    <!-- Header -->
    <div class="inv-head">
        <div class="inv-head__logo">
            <div class="inv-head__logo-mark">
                <svg width="44" height="44" viewBox="0 0 36 36" fill="none">
                    <rect width="36" height="36" rx="8" fill="white" fill-opacity="0.08"/>
                    <path d="M8 28L18 8L28 28" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M11.5 22H24.5" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="inv-head__logo-text">
                <div class="inv-head__logo-name"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA') ?></div>
                <span class="inv-head__logo-sub">Tech Studio</span>
            </div>
        </div>
        <div class="inv-head__meta">
            <div class="inv-head__title"><?= $L['INVOICE'] ?></div>
            <div class="inv-head__number"><?= htmlspecialchars($inv['number']) ?></div>
            <span class="inv-status s-<?= $inv['status'] ?>"><?= $status['text'] ?></span>
        </div>
    </div>
    <div class="inv-stripe"></div>

    <!-- Cos -->
    <div class="inv-body">

        <!-- Parties -->
        <div class="inv-parties">
            <div class="inv-party">
                <div class="inv-party__label"><?= $L['de'] ?></div>
                <strong><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></strong>
                <p>
                    <?php if (!empty($cfg['invoice_nif'])): ?><?= htmlspecialchars($cfg['invoice_nif']) ?><br><?php endif; ?>
                    <?= htmlspecialchars($cfg['invoice_address'] ?? '') ?><br>
                    <?php if (!empty($cfg['email'])): ?><?= htmlspecialchars($cfg['email']) ?><br><?php endif; ?>
                    <?php if (!empty($cfg['phone'])): ?><?= htmlspecialchars($cfg['phone']) ?><?php endif; ?>
                </p>
            </div>
            <div class="inv-party">
                <div class="inv-party__label"><?= $L['para'] ?></div>
                <?php if ($client): ?>
                <strong><?= htmlspecialchars($client['name']) ?></strong>
                <p>
                    <?php if (!empty($client['company'])): ?><?= htmlspecialchars($client['company']) ?><br><?php endif; ?>
                    <?php if (!empty($client['nif'])): ?><?= htmlspecialchars($client['nif']) ?><br><?php endif; ?>
                    <?php if (!empty($client['address'])): ?><?= htmlspecialchars($client['address']) ?><br><?php endif; ?>
                    <?php if (!empty($client['city'])): ?><?= htmlspecialchars(trim($client['cp'].' '.$client['city'])) ?><br><?php endif; ?>
                    <?php if (!empty($client['email'])): ?><?= htmlspecialchars($client['email']) ?><?php endif; ?>
                </p>
                <?php else: ?><p style="color:#9ca3af">—</p><?php endif; ?>
            </div>
        </div>

        <!-- Dates -->
        <div class="inv-dates">
            <div class="inv-date">
                <span class="inv-date__label"><?= $L['fecha'] ?></span>
                <span class="inv-date__val"><?= date('d / m / Y', strtotime($inv['date'])) ?></span>
            </div>
            <?php if (!empty($inv['due_date'])): ?>
            <div class="inv-date">
                <span class="inv-date__label"><?= $L['venc'] ?></span>
                <span class="inv-date__val"><?= date('d / m / Y', strtotime($inv['due_date'])) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Línies -->
        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width:55%"><?= $L['desc'] ?></th>
                    <th style="width:10%"><?= $L['uds'] ?></th>
                    <th style="width:18%"><?= $L['precio'] ?></th>
                    <th style="width:17%"><?= $L['total'] ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($inv['lines'] as $i => $line):
                $row_bg = $i % 2 === 1 ? 'background:#fafafa' : '';
            ?>
            <tr style="<?= $row_bg ?>">
                <td><?= nl2br(htmlspecialchars($line['desc'])) ?></td>
                <td><?= number_format($line['qty'], $line['qty']==intval($line['qty'])?0:2) ?></td>
                <td><?= number_format($line['price'],2,',','.') ?> €</td>
                <td><?= number_format($line['qty']*$line['price'],2,',','.') ?> €</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="inv-totals-wrap">
            <table class="inv-totals">
                <tr><td><?= $L['base'] ?></td><td><?= number_format($t['subtotal'],2,',','.') ?> €</td></tr>
                <?php if ($t['tax'] > 0): ?>
                <tr><td><?= $L['iva'] ?> (<?= $t['tax_pct'] ?>%)</td><td><?= number_format($t['tax'],2,',','.') ?> €</td></tr>
                <?php endif; ?>
                <?php if ($t['irpf'] > 0): ?>
                <tr class="irpf-row"><td><?= $L['irpf'] ?> (–<?= $t['irpf_pct'] ?>%)</td><td>–<?= number_format($t['irpf'],2,',','.') ?> €</td></tr>
                <?php endif; ?>
                <tr class="total-final"><td><?= $L['total_f'] ?></td><td><?= number_format($t['total'],2,',','.') ?> €</td></tr>
            </table>
        </div>

        <!-- Pagament / Notes -->
        <?php if (!empty($inv['payment_info']) || !empty($inv['notes'])): ?>
        <div class="inv-doc-footer">
            <?php if (!empty($inv['payment_info'])): ?>
            <div class="inv-doc-footer__block">
                <h5><?= $L['pagament'] ?></h5>
                <p><?= htmlspecialchars($inv['payment_info']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($inv['notes'])): ?>
            <div class="inv-doc-footer__block">
                <h5><?= $L['obs'] ?></h5>
                <p><?= htmlspecialchars($inv['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /inv-body -->

    <!-- Firma inferior -->
    <div class="inv-sign">
        <div class="inv-sign__brand">
            <svg width="22" height="22" viewBox="0 0 36 36" fill="none">
                <rect width="36" height="36" rx="8" fill="#0a0a0a"/>
                <path d="M8 28L18 8L28 28" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.5 22H24.5" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <div>
                <div class="inv-sign__name"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></div>
                <div class="inv-sign__url"><?= htmlspecialchars($cfg['site_url'] ?? 'akratechstudio.es') ?></div>
            </div>
        </div>
        <div class="inv-sign__note"><?= htmlspecialchars($inv['number']) ?> · <?= date('Y') ?></div>
    </div>

</div><!-- /invoice -->
</div><!-- /page-wrap -->

</body>
</html>
<?php
die();
endif;

// ── LLISTA / FORMULARI ───────────────────────────────────────────────────────
$edit_id     = $_GET['id'] ?? null;
$client_filter = $_GET['client'] ?? null;
$edit        = $edit_id ? getInvoice($edit_id) : null;
$is_new      = isset($_GET['new']);
if ($edit_id && !$edit) { header('Location: invoices.php'); exit; }

// Stats ràpides
$all_inv = getInvoices();
$stats = [
    'total_pending' => array_sum(array_map(fn($i) => $i['status'] === 'sent'   ? $i['total'] : 0, $all_inv)),
    'total_paid'    => array_sum(array_map(fn($i) => $i['status'] === 'paid'   ? $i['total'] : 0, $all_inv)),
    'total_overdue' => array_sum(array_map(fn($i) => $i['status'] === 'overdue'? $i['total'] : 0, $all_inv)),
    'count_draft'   => count(array_filter($all_inv, fn($i) => $i['status'] === 'draft')),
];

$page_title    = $edit_id ? 'Editar factura' : ($is_new ? 'Nova factura' : 'Factures');
$page_subtitle = $edit_id ? ($edit['number'] ?? '') : ($is_new ? '' : count($all_inv) . ' factures');
$topbar_action_url   = 'invoices.php?new=1';
$topbar_action_label = 'Nova factura';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.inv-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 20px; }
.inv-stat { background: white; border: 1px solid var(--a-border); border-radius: 10px; padding: 16px 20px; }
.inv-stat__label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--a-muted); margin-bottom: 6px; }
.inv-stat__value { font-family: 'Syne',sans-serif; font-size: 1.6rem; font-weight: 700; color: var(--a-navy); letter-spacing: -.03em; }
.inv-stat__value--green  { color: #16a34a; }
.inv-stat__value--red    { color: #dc2626; }
.inv-stat__value--gold   { color: var(--a-gold); }
.lines-table { width: 100%; border-collapse: collapse; }
.lines-table th { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--a-muted); padding: 8px 10px; border-bottom: 2px solid var(--a-border); text-align: left; }
.lines-table td { padding: 8px 10px; border-bottom: 1px solid var(--a-border); vertical-align: middle; }
.lines-table input { border: 1px solid var(--a-border); border-radius: 6px; padding: 6px 10px; font-size: .88rem; background: var(--a-bg); width: 100%; }
.lines-table input:focus { outline: none; border-color: var(--a-gold); }
.btn-remove-line { background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 1rem; padding: 4px; border-radius: 4px; }
.btn-remove-line:hover { color: #ef4444; background: #fee2e2; }
.totals-preview { margin-top: 12px; border-top: 2px solid var(--a-border); padding-top: 12px; }
.totals-preview tr td { padding: 4px 0; font-size: .88rem; }
.totals-preview tr td:last-child { text-align: right; font-weight: 600; }
.totals-preview .total-final td { font-size: 1.1rem; font-weight: 700; color: var(--a-navy); border-top: 1px solid var(--a-border); padding-top: 8px; margin-top: 4px; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Factura guardada.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Factura eliminada.</div><?php endif; ?>
<?php if (isset($_GET['email_ok'])): ?><div class="alert alert-success">✅ Factura enviada per email amb el PDF adjunt.</div><?php endif; ?>
<?php if (isset($_GET['email_err'])): ?><div class="alert alert-error">❌ Error en enviar l'email<?= !empty($_GET['email_err_msg']) ? ': ' . htmlspecialchars($_GET['email_err_msg']) : '. Comprova la configuració SMTP del servidor.' ?></div><?php endif; ?>
<?php if (isset($_GET['payment_saved'])): ?><div class="alert alert-success">✅ Pagament registrat.</div><?php endif; ?>
<?php if (isset($_GET['payment_deleted'])): ?><div class="alert alert-success">Pagament eliminat.</div><?php endif; ?>
<?php if (isset($_GET['recurring_ran'])): ?><div class="alert alert-success"><?= (int)$_GET['recurring_ran'] > 0 ? '✅ Generades ' . (int)$_GET['recurring_ran'] . ' factura(es) recurrent(s).' : 'Cap factura recurrent pendent de generar hui.' ?></div><?php endif; ?>

<?php if (!$edit_id && !$is_new):
    $show_inv = $client_filter ? array_filter($all_inv, fn($i) => $i['client_id'] === $client_filter) : $all_inv;
    $show_inv = array_values($show_inv);
?>

<!-- Stats -->
<div class="inv-stats">
    <div class="inv-stat">
        <div class="inv-stat__label">💰 Pendent de cobrar</div>
        <div class="inv-stat__value inv-stat__value--gold"><?= number_format($stats['total_pending'], 2, ',', '.') ?> €</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__label">✅ Cobrat (total)</div>
        <div class="inv-stat__value inv-stat__value--green"><?= number_format($stats['total_paid'], 2, ',', '.') ?> €</div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat__label">🔴 Vençut sense cobrar</div>
        <div class="inv-stat__value inv-stat__value--red"><?= number_format($stats['total_overdue'], 2, ',', '.') ?> €</div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <input type="hidden" name="export_csv" value="1">
            <label style="font-size:.82rem;color:#6b7280">📊 Exportar factures (comptabilitat):</label>
            <input type="date" name="from" value="<?= date('Y-m-01') ?>" style="font-size:.82rem">
            <span style="font-size:.8rem;color:#9ca3af">a</span>
            <input type="date" name="to" value="<?= date('Y-m-d') ?>" style="font-size:.82rem">
            <button type="submit" class="btn btn-sm btn-secondary">⬇️ Exportar CSV</button>
        </form>
        <a href="invoices.php?run_recurring=1" class="btn btn-sm btn-secondary" onclick="return confirm('Generar ara totes les factures recurrents pendents?')">🔁 Generar factures recurrents ara</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            Totes les factures
            <?php if ($client_filter && ($cf = getClient($client_filter))): ?>
            <span style="font-size:.78rem;font-weight:400;color:#9ca3af">· <?= htmlspecialchars($cf['name']) ?></span>
            <a href="invoices.php" style="font-size:.75rem;margin-left:8px;color:var(--a-gold)">Veure totes</a>
            <?php endif; ?>
        </div>
        <a href="invoices.php?new=1" class="btn btn-primary btn-sm">+ Nova factura</a>
    </div>
    <?php if (empty($show_inv)): ?>
    <div style="padding:48px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">🧾</div>
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:8px">Cap factura encara</h3>
        <p style="color:#6b7280;margin-bottom:20px">Crea la primera factura per a un client.</p>
        <a href="invoices.php?new=1" class="btn btn-primary">+ Nova factura</a>
    </div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Número</th><th>Client</th><th>Data</th><th>Venc.</th><th>Import</th><th>Estat</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($show_inv as $inv):
            $client  = getClient($inv['client_id']);
            $s       = invoiceStatusLabel($inv['status']);
            $overdue = $inv['status'] === 'sent' && !empty($inv['due_date']) && $inv['due_date'] < date('Y-m-d');
        ?>
        <tr <?= $overdue ? 'style="background:#fff5f5"' : '' ?>>
            <td><strong style="font-family:'Syne',sans-serif"><?= htmlspecialchars($inv['number']) ?></strong></td>
            <td>
                <?php if ($client): ?>
                <a href="invoices.php?client=<?= $client['id'] ?>" style="font-weight:600;color:var(--a-navy)"><?= htmlspecialchars($client['name']) ?></a>
                <?php else: ?><span style="color:#9ca3af">—</span><?php endif; ?>
            </td>
            <td style="font-size:.82rem;color:#6b7280"><?= date('d/m/Y', strtotime($inv['date'])) ?></td>
            <td style="font-size:.82rem;color:<?= $overdue ? '#dc2626' : '#6b7280' ?>">
                <?= !empty($inv['due_date']) ? date('d/m/Y', strtotime($inv['due_date'])) : '—' ?>
                <?= $overdue ? ' ⚠️' : '' ?>
            </td>
            <td style="font-weight:700;font-size:.95rem"><?= number_format($inv['total'], 2, ',', '.') ?> €</td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="set_status">
                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                    <select name="status" onchange="this.form.submit()" style="font-size:.78rem;border:1px solid var(--a-border);border-radius:6px;padding:3px 6px;background:var(--a-bg)">
                        <?php foreach(['draft','sent','paid','overdue','cancelled'] as $st):
                            $sl = invoiceStatusLabel($st); ?>
                        <option value="<?= $st ?>" <?= $inv['status'] === $st ? 'selected' : '' ?>><?= $sl['text'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
            <td>
                <div class="td-actions">
                    <a href="invoices.php?print=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary" target="_blank" title="Imprimir / PDF · CA">🖨 CA</a>
                    <a href="invoices.php?print=<?= $inv['id'] ?>&lang=es" class="btn btn-sm btn-secondary" target="_blank" title="Imprimir / PDF · ES">🖨 ES</a>
                    <a href="invoices.php?download_pdf=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary" title="Descarregar PDF · CA">⬇️ CA</a>
                    <a href="invoices.php?download_pdf=<?= $inv['id'] ?>&lang=es" class="btn btn-sm btn-secondary" title="Descarregar PDF · ES">⬇️ ES</a>
                    <a href="invoices.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar factura?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<?php else:
$from_proposal = null;
if (!$edit_id && !empty($_GET['from_proposal'])) {
    $from_proposal = getProposal($_GET['from_proposal']);
}
$inv = $edit ?? [
    'id'=>'','number'=>nextInvoiceNumber(),'client_id'=>($_GET['client'] ?? ($from_proposal['client_id'] ?? '')),'status'=>'draft',
    'date'=>date('Y-m-d'),'due_date'=>date('Y-m-d', strtotime('+30 days')),
    'lines'=> $from_proposal ? [['desc'=>$from_proposal['description'], 'qty'=>1, 'price'=>$from_proposal['price']]] : [['desc'=>'','qty'=>1,'price'=>0]],
    'tax_pct'=>21,'irpf_pct'=>0,
    'notes'=>'','payment_info'=>$cfg['invoice_payment'] ?? '',
];
?>
<form method="POST" id="inv-form">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($inv['id']) ?>">

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
    <div class="form-grid">

        <!-- Línies -->
        <div class="card">
            <div class="card-header"><div class="card-title">Conceptes facturats</div></div>
            <div class="card-body" style="padding:0">
                <table class="lines-table" id="lines-table">
                    <thead><tr><th style="width:55%">Descripció</th><th style="width:12%">Ut.</th><th style="width:18%">Preu unit.</th><th style="width:15%">Total</th><th style="width:40px"></th></tr></thead>
                    <tbody id="lines-body">
                    <?php foreach ($inv['lines'] as $k => $line): ?>
                    <tr class="line-row">
                        <td><input type="text" name="line_desc[]" value="<?= htmlspecialchars($line['desc']) ?>" placeholder="Disseny web corporativa a mida..."></td>
                        <td><input type="number" name="line_qty[]" value="<?= $line['qty'] ?>" min="0.01" step="0.01" style="width:80px" oninput="updateTotals()"></td>
                        <td><input type="number" name="line_price[]" value="<?= $line['price'] ?>" min="0" step="0.01" oninput="updateTotals()"> €</td>
                        <td style="font-weight:600;white-space:nowrap" class="line-total"><?= number_format($line['qty'] * $line['price'], 2, ',', '.') ?> €</td>
                        <td><button type="button" class="btn-remove-line" onclick="removeLine(this)">✕</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:12px 16px;border-top:1px solid var(--a-border);display:flex;justify-content:space-between;align-items:center">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addLine()">+ Afegir línia</button>
                    <table class="totals-preview">
                        <tr><td>Base imposable:</td><td id="prev-subtotal">0,00 €</td></tr>
                        <tr><td>IVA (<span id="prev-tax-pct">21</span>%):</td><td id="prev-tax">0,00 €</td></tr>
                        <tr id="irpf-row" style="display:none"><td>IRPF (<span id="prev-irpf-pct">0</span>%):</td><td id="prev-irpf" style="color:#dc2626">0,00 €</td></tr>
                        <tr class="total-final"><td><strong>TOTAL:</strong></td><td id="prev-total"><strong>0,00 €</strong></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes / Pagament -->
        <div class="card">
            <div class="card-header"><div class="card-title">Informació addicional</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Forma de pagament / dades bancàries</label>
                    <textarea name="payment_info" rows="3" placeholder="Transferència bancaria&#10;IBAN: ES00 0000 0000 00 0000000000&#10;Titular: AKRA Tech Studio"><?= htmlspecialchars($inv['payment_info']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Observacions (apareixerà a la factura)</label>
                    <textarea name="notes" rows="2" placeholder="Condicions, agraïments..."><?= htmlspecialchars($inv['notes']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Lateral -->
    <div class="form-grid">
        <div class="card">
            <div class="card-header"><div class="card-title">Dades de la factura</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Número de factura</label>
                    <input type="text" name="number" value="<?= htmlspecialchars($inv['number']) ?>">
                </div>
                <div class="form-group">
                    <label>Client *</label>
                    <select name="client_id" required>
                        <option value="">Selecciona un client</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($inv['client_id'] === $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?> <?= !empty($c['company']) ? '· ' . htmlspecialchars($c['company']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint"><a href="clients.php?new=1" target="_blank">+ Crear client nou</a></p>
                </div>
                <div class="form-group">
                    <label>Estat</label>
                    <select name="status">
                        <?php foreach(['draft'=>'Esborrany','sent'=>'Enviada','paid'=>'Cobrada','overdue'=>'Vençuda','cancelled'=>'Cancel·lada'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= ($inv['status'] === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row-2">
                    <div class="form-group"><label>Data emissió</label><input type="date" name="date" value="<?= $inv['date'] ?>"></div>
                    <div class="form-group"><label>Data venciment</label><input type="date" name="due_date" value="<?= $inv['due_date'] ?>"></div>
                </div>
                <div class="form-group" style="border-top:1px solid #eee;padding-top:14px">
                    <label style="display:flex;align-items:center;gap:8px;font-weight:600">
                        <input type="checkbox" name="recurring" value="1" onchange="document.getElementById('recurring-fields').style.display=this.checked?'block':'none'" <?= !empty($inv['recurring']) ? 'checked' : '' ?>>
                        🔁 Factura recurrent
                    </label>
                    <p class="hint">Es generarà automàticament una còpia nova cada vegada que toque (cal executar el procés de generació — vore instruccions al final de la llista de factures).</p>
                </div>
                <div id="recurring-fields" style="display:<?= !empty($inv['recurring']) ? 'block' : 'none' ?>">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Freqüència</label>
                            <select name="recurring_freq">
                                <?php foreach (['monthly'=>'Mensual','quarterly'=>'Trimestral','yearly'=>'Anual'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($inv['recurring_freq'] ?? 'monthly') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pròxima generació</label>
                            <input type="date" name="recurring_next" value="<?= htmlspecialchars($inv['recurring_next'] ?? date('Y-m-d', strtotime('+1 month'))) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Dies de venciment (per a les còpies generades)</label>
                        <input type="number" name="due_days" min="1" value="<?= htmlspecialchars($inv['due_days'] ?? 30) ?>">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>IVA (%)</label>
                        <select name="tax_pct" id="tax-pct-sel" onchange="updateTotals()">
                            <?php foreach([0,4,10,21] as $v): ?>
                            <option value="<?= $v ?>" <?= ($inv['tax_pct'] ?? 21) == $v ? 'selected' : '' ?>><?= $v ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>IRPF (%)</label>
                        <select name="irpf_pct" id="irpf-pct-sel" onchange="updateTotals()">
                            <?php foreach([0,7,15,19] as $v): ?>
                            <option value="<?= $v ?>" <?= ($inv['irpf_pct'] ?? 0) == $v ? 'selected' : '' ?>>-<?= $v ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
            <button type="submit" class="btn btn-primary">✅ Guardar factura</button>
            <button type="submit" name="redirect_print" value="1" class="btn btn-secondary">🖨 Guardar i imprimir</button>
            <?php if ($edit_id): ?>
            <a href="invoices.php?download_pdf=<?= $inv['id'] ?>" class="btn btn-secondary" style="text-align:center">⬇️ Descarregar PDF (CA)</a>
            <a href="invoices.php?download_pdf=<?= $inv['id'] ?>&lang=es" class="btn btn-secondary" style="text-align:center">⬇️ Descarregar PDF (ES)</a>
            <?php endif; ?>
            <a href="invoices.php" class="btn btn-secondary" style="text-align:center">Cancel·lar</a>
        </div>
    </div>
    </div>
</form>

<?php if ($edit_id):
    $psum = invoicePaymentSummary($inv);
    $methods = getPaymentMethodOptions();
    $c_payments = getPayments($inv['id']);
    $edit_payment_id = $_GET['payment_id'] ?? null;
    $edit_payment = $edit_payment_id ? getPayment($edit_payment_id) : null;
    $pay_badge = match($psum['status']) {
        'paid'    => ['label' => '✅ Cobrada completament', 'class' => 'badge-green'],
        'partial' => ['label' => '🟡 Pagament parcial',     'class' => 'badge-gold'],
        default   => ['label' => '⏳ Pendent de cobrament',  'class' => 'badge-gray'],
    };
?>
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">Historial de pagaments</div>
        <span class="badge <?= $pay_badge['class'] ?>"><?= $pay_badge['label'] ?></span>
    </div>
    <div class="card-body" style="padding-bottom:0">
        <div class="inv-stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:14px">
            <div class="inv-stat">
                <div class="inv-stat__label">Total factura</div>
                <div class="inv-stat__value"><?= number_format($psum['total'], 2, ',', '.') ?> €</div>
            </div>
            <div class="inv-stat">
                <div class="inv-stat__label">Pagat</div>
                <div class="inv-stat__value inv-stat__value--green"><?= number_format($psum['paid'], 2, ',', '.') ?> €</div>
            </div>
            <div class="inv-stat">
                <div class="inv-stat__label">Queda pendent</div>
                <div class="inv-stat__value <?= $psum['due'] > 0 ? 'inv-stat__value--red' : 'inv-stat__value--green' ?>"><?= number_format(max(0,$psum['due']), 2, ',', '.') ?> €</div>
            </div>
        </div>
        <div style="background:#f3f4f6;border-radius:999px;height:10px;overflow:hidden;margin-bottom:22px">
            <div style="height:100%;border-radius:999px;width:<?= $psum['pct'] ?>%;background:<?= $psum['status'] === 'paid' ? '#16a34a' : 'var(--a-gold)' ?>;transition:width .3s"></div>
        </div>
    </div>
    <div class="card-body form-grid" style="border-top:1px solid #eee;border-bottom:1px solid #eee;padding-top:20px;padding-bottom:20px">
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="save_payment">
            <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($edit_payment['id'] ?? '') ?>">
            <div class="form-row-2">
                <div class="form-group"><label>Data del pagament *</label><input type="date" name="pay_date" value="<?= htmlspecialchars($edit_payment['date'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group"><label>Import *</label><input type="number" step="0.01" min="0.01" name="amount" value="<?= htmlspecialchars($edit_payment['amount'] ?? '') ?>" placeholder="Ex: 300.00" required></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Mètode</label>
                    <select name="method">
                        <?php foreach ($methods as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($edit_payment['method'] ?? 'transferencia') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Referència (opcional)</label><input type="text" name="reference" value="<?= htmlspecialchars($edit_payment['reference'] ?? '') ?>" placeholder="Ex: bizum, primer termini..."></div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_payment ? 'Actualitzar pagament' : '+ Registrar pagament' ?></button>
                <?php if ($edit_payment): ?><a href="invoices.php?id=<?= $inv['id'] ?>" class="btn btn-secondary btn-sm">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
    <?php if (empty($c_payments)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Encara no s'ha registrat cap pagament d'esta factura.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Data</th><th>Import</th><th>Mètode</th><th>Referència</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($c_payments as $p): ?>
        <tr>
            <td style="font-size:.82rem;color:#6b7280;white-space:nowrap"><?= htmlspecialchars($p['date']) ?></td>
            <td style="font-weight:600"><?= number_format($p['amount'], 2, ',', '.') ?> €</td>
            <td style="font-size:.82rem"><?= htmlspecialchars($methods[$p['method']] ?? $p['method']) ?></td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($p['reference'] ?: '—') ?></td>
            <td class="td-actions">
                <a href="invoices.php?id=<?= $inv['id'] ?>&payment_id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                <form method="POST" onsubmit="return confirm('Eliminar este pagament?')" style="display:inline">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function fmt(n) {
    return n.toLocaleString('ca-ES', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' €';
}
function updateTotals() {
    let sub = 0;
    document.querySelectorAll('#lines-body .line-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('[name="line_qty[]"]').value)   || 0;
        const price = parseFloat(row.querySelector('[name="line_price[]"]').value) || 0;
        const total = qty * price;
        row.querySelector('.line-total').textContent = fmt(total).replace(' €','') + ' €';
        sub += total;
    });
    const taxPct  = parseInt(document.getElementById('tax-pct-sel').value)  || 0;
    const irpfPct = parseInt(document.getElementById('irpf-pct-sel').value) || 0;
    const tax  = sub * taxPct  / 100;
    const irpf = sub * irpfPct / 100;
    const total = sub + tax - irpf;

    document.getElementById('prev-subtotal').textContent = fmt(sub);
    document.getElementById('prev-tax').textContent      = fmt(tax);
    document.getElementById('prev-tax-pct').textContent  = taxPct;
    document.getElementById('prev-irpf').textContent     = '–' + fmt(irpf);
    document.getElementById('prev-irpf-pct').textContent = irpfPct;
    document.getElementById('prev-total').innerHTML      = '<strong>' + fmt(total) + '</strong>';
    document.getElementById('irpf-row').style.display    = irpfPct > 0 ? '' : 'none';
}
function addLine() {
    const row = document.createElement('tr');
    row.className = 'line-row';
    row.innerHTML = `<td><input type="text" name="line_desc[]" placeholder="Descripció del servei..."></td>
        <td><input type="number" name="line_qty[]" value="1" min="0.01" step="0.01" style="width:80px" oninput="updateTotals()"></td>
        <td><input type="number" name="line_price[]" value="0" min="0" step="0.01" oninput="updateTotals()"> €</td>
        <td style="font-weight:600" class="line-total">0,00 €</td>
        <td><button type="button" class="btn-remove-line" onclick="removeLine(this)">✕</button></td>`;
    document.getElementById('lines-body').appendChild(row);
    row.querySelector('input').focus();
}
function removeLine(btn) {
    const rows = document.querySelectorAll('#lines-body .line-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    updateTotals();
}
updateTotals();
</script>
<?php endif; ?>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
