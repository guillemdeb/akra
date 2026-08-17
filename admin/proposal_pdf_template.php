<?php
// Plantilla per a la generació del PDF de proposta comercial (via Dompdf).
// Variables disponibles: $prop (array proposta), $client (array client),
// $cfg (config admin), $lang.

$type_opts = getProposalTypeOptions();
$type_label = $type_opts[$prop['type']] ?? $prop['type'];

$L = $lang === 'es' ? [
    'titulo'   => 'PROPUESTA COMERCIAL',
    'de'       => 'De',
    'para'     => 'Para',
    'fecha'    => 'Fecha',
    'tipo'     => 'Tipo de proyecto',
    'desc'     => 'Descripción de la propuesta',
    'precio'   => 'Importe propuesto',
    'validez'  => 'Esta propuesta tiene una validez de 30 días desde la fecha de emisión.',
] : [
    'titulo'   => 'PROPOSTA COMERCIAL',
    'de'       => 'De',
    'para'     => 'Per a',
    'fecha'    => 'Data',
    'tipo'     => 'Tipus de projecte',
    'desc'     => 'Descripció de la proposta',
    'precio'   => 'Import proposat',
    'validez'  => 'Esta proposta té una validesa de 30 dies des de la data d\'emissió.',
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
    .doc-title { color: #ffffff; font-size: 19px; font-weight: bold; text-align: right; }

    .content { padding: 26px 28px; }

    .parties-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .parties-table td { width: 50%; background: #f9fafb; padding: 12px 16px; vertical-align: top; border: 1px solid #e5e7eb; }
    .party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 6px; }
    .party-name { font-size: 12px; font-weight: bold; color: #0a0a0a; margin-bottom: 3px; }
    .party-line { font-size: 9.5px; color: #6b7280; line-height: 1.5; }

    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .meta-table td { padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; width: 50%; }
    .meta-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; }
    .meta-value { font-size: 11px; font-weight: bold; color: #1a1a1a; }

    .desc-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin-bottom: 22px; font-size: 10.5px; line-height: 1.7; color: #374151; white-space: pre-line; }

    .price-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    .price-table td { padding: 16px 20px; }
    .price-table .p-label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #b8b8b8; }
    .price-table .p-value { font-size: 24px; font-weight: bold; color: #ffffff; text-align: right; }
    .price-row-bg { background: #0a0a0a; border-radius: 8px; }

    .validity { font-size: 9px; color: #9ca3af; font-style: italic; margin-bottom: 20px; }

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
            <div class="doc-title"><?= $L['titulo'] ?></div>
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
            </td>
            <td>
                <div class="party-label"><?= $L['para'] ?></div>
                <div class="party-name"><?= htmlspecialchars($client['name']) ?></div>
                <?php if (!empty($client['company'])): ?><div class="party-line"><?= htmlspecialchars($client['company']) ?></div><?php endif; ?>
                <?php if (!empty($client['email'])): ?><div class="party-line"><?= htmlspecialchars($client['email']) ?></div><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td>
                <div class="meta-label"><?= $L['fecha'] ?></div>
                <div class="meta-value"><?= date('d/m/Y', strtotime($prop['date'])) ?></div>
            </td>
            <td>
                <div class="meta-label"><?= $L['tipo'] ?></div>
                <div class="meta-value"><?= htmlspecialchars($type_label) ?></div>
            </td>
        </tr>
    </table>

    <div class="meta-label" style="margin-bottom:8px"><?= $L['desc'] ?></div>
    <div class="desc-box"><?= nl2br(htmlspecialchars($prop['description'] ?? '')) ?></div>

    <table class="price-table">
        <tr class="price-row-bg">
            <td><span class="p-label"><?= $L['precio'] ?></span></td>
            <td class="p-value"><?= number_format($prop['price'], 2, ',', '.') ?> €</td>
        </tr>
    </table>

    <div class="validity"><?= $L['validez'] ?></div>

    <div class="footer">
        <?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?>
        <?php if (!empty($cfg['email'])): ?> · <?= htmlspecialchars($cfg['email']) ?><?php endif; ?>
        <?php if (!empty($cfg['phone'])): ?> · <?= htmlspecialchars($cfg['phone']) ?><?php endif; ?>
    </div>
</div>
</body>
</html>
