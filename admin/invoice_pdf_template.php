<?php
// Plantilla per a la generació del PDF de factura (via Dompdf).
// IMPORTANT: Dompdf no suporta flexbox ni CSS grid, per això esta plantilla
// és independent de la vista d'impressió del navegador (invoices.php?print=)
// i fa tot el maquetat amb <table> i blocs normals.
//
// Variables disponibles (injectades per generateInvoicePdf()):
// $inv (array factura), $client (array client), $cfg (config admin), $lang

$t      = invoiceTotals($inv['lines'], $inv['tax_pct'] ?? 21, $inv['irpf_pct'] ?? 0);
$status = invoiceStatusLabel($inv['status']);

$L = $lang === 'es' ? [
    'factura'  => 'FACTURA',
    'de'       => 'De',
    'para'     => 'Para',
    'fecha'    => 'Fecha de emisión',
    'venc'     => 'Vencimiento',
    'desc'     => 'Descripción',
    'uds'      => 'Uds.',
    'precio'   => 'Precio unit.',
    'total'    => 'Total',
    'base'     => 'Base imponible',
    'iva'      => 'IVA',
    'irpf'     => 'IRPF',
    'total_f'  => 'TOTAL',
    'pagament' => 'Forma de pago',
    'obs'      => 'Observaciones',
] : [
    'factura'  => 'FACTURA',
    'de'       => 'De',
    'para'     => 'Per a',
    'fecha'    => 'Data d\'emissió',
    'venc'     => 'Venciment',
    'desc'     => 'Descripció',
    'uds'      => 'Ut.',
    'precio'   => 'Preu unit.',
    'total'    => 'Total',
    'base'     => 'Base imposable',
    'iva'      => 'IVA',
    'irpf'     => 'IRPF',
    'total_f'  => 'TOTAL',
    'pagament' => 'Forma de pagament',
    'obs'      => 'Observacions',
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
    .header-table { width: 100%; border-collapse: collapse; background: #0a0a0a; }
    .header-table td { padding: 22px 28px; vertical-align: middle; }
    .brand { color: #ffffff; font-size: 17px; font-weight: bold; }
    .brand-sub { color: #b8b8b8; font-size: 8px; letter-spacing: 1px; text-transform: uppercase; }
    .inv-title { color: #ffffff; font-size: 22px; font-weight: bold; text-align: right; }
    .inv-number { color: #b8b8b8; font-size: 10px; text-align: right; margin-top: 2px; }
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; margin-top: 4px; }
    .s-paid      { background: #dcfce7; color: #166534; }
    .s-sent      { background: #dbeafe; color: #1e40af; }
    .s-draft     { background: #f4f4f5; color: #52525b; }
    .s-overdue   { background: #fee2e2; color: #991b1b; }
    .s-cancelled { background: #f4f4f5; color: #52525b; }

    .content { padding: 20px 28px; }

    .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .parties-table td { width: 50%; background: #f9fafb; padding: 12px 16px; vertical-align: top; border: 1px solid #e5e7eb; }
    .party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 6px; }
    .party-name { font-size: 12px; font-weight: bold; color: #0a0a0a; margin-bottom: 3px; }
    .party-line { font-size: 9.5px; color: #6b7280; line-height: 1.5; }

    .dates-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .dates-table td { padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
    .date-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; }
    .date-value { font-size: 11px; font-weight: bold; color: #1a1a1a; }

    table.lines { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.lines thead td { background: #0a0a0a; color: #ffffff; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; padding: 8px 10px; }
    table.lines tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; font-size: 10px; color: #374151; }
    .col-right { text-align: right; }

    .totals-table { width: 260px; float: right; margin-top: 12px; margin-bottom: 30px; border-collapse: collapse; }
    .totals-table td { padding: 4px 0; font-size: 10.5px; }
    .totals-table .t-label { color: #6b7280; }
    .totals-table .t-value { text-align: right; font-weight: bold; }
    .totals-table .t-final td { border-top: 1.5px solid #0a0a0a; padding-top: 8px; font-size: 13px; font-weight: bold; color: #0a0a0a; }

    .clearfix { clear: both; }

    .notes-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .notes-table td { width: 50%; vertical-align: top; padding: 10px 0; }
    .notes-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 5px; }
    .notes-text { font-size: 9.5px; color: #374151; line-height: 1.5; white-space: pre-line; }

    .footer { text-align: center; font-size: 8.5px; color: #9ca3af; padding: 14px 0 0; border-top: 1px solid #e5e7eb; margin-top: 20px; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="text-align:left">
            <div class="brand"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></div>
            <div class="brand-sub"><?= htmlspecialchars($cfg['slogan'] ?? 'Agencia Digital') ?></div>
        </td>
        <td>
            <div class="inv-title"><?= $L['factura'] ?></div>
            <div class="inv-number"><?= htmlspecialchars($inv['number']) ?></div>
            <div style="text-align:right"><span class="status-badge s-<?= $inv['status'] ?>"><?= $status['text'] ?></span></div>
        </td>
    </tr>
</table>

<div class="content">

    <table class="parties-table">
        <tr>
            <td>
                <div class="party-label"><?= $L['de'] ?></div>
                <div class="party-name"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></div>
                <?php if (!empty($cfg['invoice_nif'])): ?><div class="party-line"><?= htmlspecialchars($cfg['invoice_nif']) ?></div><?php endif; ?>
                <?php if (!empty($cfg['email'])): ?><div class="party-line"><?= htmlspecialchars($cfg['email']) ?></div><?php endif; ?>
                <?php if (!empty($cfg['phone'])): ?><div class="party-line"><?= htmlspecialchars($cfg['phone']) ?></div><?php endif; ?>
            </td>
            <td>
                <div class="party-label"><?= $L['para'] ?></div>
                <div class="party-name"><?= htmlspecialchars($client['name']) ?></div>
                <?php if (!empty($client['company'])): ?><div class="party-line"><?= htmlspecialchars($client['company']) ?></div><?php endif; ?>
                <?php if (!empty($client['nif'])): ?><div class="party-line"><?= htmlspecialchars($client['nif']) ?></div><?php endif; ?>
                <?php if (!empty($client['address'])): ?><div class="party-line"><?= htmlspecialchars($client['address']) ?></div><?php endif; ?>
                <?php if (!empty($client['city'])): ?><div class="party-line"><?= htmlspecialchars(trim(($client['cp'] ?? '') . ' ' . $client['city'])) ?></div><?php endif; ?>
                <?php if (!empty($client['email'])): ?><div class="party-line"><?= htmlspecialchars($client['email']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="dates-table">
        <tr>
            <td style="width:50%">
                <div class="date-label"><?= $L['fecha'] ?></div>
                <div class="date-value"><?= date('d/m/Y', strtotime($inv['date'])) ?></div>
            </td>
            <?php if (!empty($inv['due_date'])): ?>
            <td style="width:50%">
                <div class="date-label"><?= $L['venc'] ?></div>
                <div class="date-value"><?= date('d/m/Y', strtotime($inv['due_date'])) ?></div>
            </td>
            <?php endif; ?>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <td style="width:52%"><?= $L['desc'] ?></td>
                <td style="width:12%" class="col-right"><?= $L['uds'] ?></td>
                <td style="width:18%" class="col-right"><?= $L['precio'] ?></td>
                <td style="width:18%" class="col-right"><?= $L['total'] ?></td>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($inv['lines'] as $line): ?>
            <tr>
                <td><?= htmlspecialchars($line['desc']) ?></td>
                <td class="col-right"><?= rtrim(rtrim(number_format($line['qty'], 2, ',', '.'), '0'), ',') ?></td>
                <td class="col-right"><?= number_format($line['price'], 2, ',', '.') ?> €</td>
                <td class="col-right"><?= number_format($line['qty'] * $line['price'], 2, ',', '.') ?> €</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr><td class="t-label"><?= $L['base'] ?></td><td class="t-value"><?= number_format($t['subtotal'], 2, ',', '.') ?> €</td></tr>
        <?php if ($t['tax'] > 0): ?>
        <tr><td class="t-label"><?= $L['iva'] ?> (<?= $t['tax_pct'] ?>%)</td><td class="t-value"><?= number_format($t['tax'], 2, ',', '.') ?> €</td></tr>
        <?php endif; ?>
        <?php if ($t['irpf'] > 0): ?>
        <tr><td class="t-label"><?= $L['irpf'] ?> (-<?= $t['irpf_pct'] ?>%)</td><td class="t-value">-<?= number_format($t['irpf'], 2, ',', '.') ?> €</td></tr>
        <?php endif; ?>
        <tr class="t-final"><td><?= $L['total_f'] ?></td><td class="t-value"><?= number_format($t['total'], 2, ',', '.') ?> €</td></tr>
    </table>
    <div class="clearfix"></div>

    <?php if (!empty($inv['payment_info']) || !empty($inv['notes']) || !empty($cfg['payment_link'])): ?>
    <table class="notes-table">
        <tr>
            <?php if (!empty($inv['payment_info'])): ?>
            <td>
                <div class="notes-label"><?= $L['pagament'] ?></div>
                <div class="notes-text"><?= htmlspecialchars($inv['payment_info']) ?></div>
                <?php if (!empty($cfg['payment_link'])): ?>
                <div class="notes-text" style="margin-top:6px"><a href="<?= htmlspecialchars($cfg['payment_link']) ?>"><?= $lang === 'es' ? 'Pagar online' : 'Pagar en línia' ?></a></div>
                <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if (!empty($inv['notes'])): ?>
            <td>
                <div class="notes-label"><?= $L['obs'] ?></div>
                <div class="notes-text"><?= htmlspecialchars($inv['notes']) ?></div>
            </td>
            <?php endif; ?>
        </tr>
    </table>
    <?php endif; ?>

    <div class="footer">
        <?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?>
        <?php if (!empty($cfg['invoice_nif'])): ?> · <?= htmlspecialchars($cfg['invoice_nif']) ?><?php endif; ?>
        <?php if (!empty($cfg['email'])): ?> · <?= htmlspecialchars($cfg['email']) ?><?php endif; ?>
    </div>
</div>
</body>
</html>
