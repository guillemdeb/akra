<?php
require_once 'includes/core.php';
requireLogin();

$q = trim($_GET['q'] ?? '');
$results = $q !== '' ? globalSearch($q) : ['clients'=>[],'invoices'=>[],'contacts'=>[],'proposals'=>[]];
$total = count($results['clients']) + count($results['invoices']) + count($results['contacts']) + count($results['proposals']);

$page_title    = 'Cerca';
$page_subtitle = $q !== '' ? "Resultats per «{$q}»" : 'Cerca global';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if ($q === ''): ?>
<div class="card"><div style="padding:48px;text-align:center;color:#9ca3af">Escriu alguna cosa al cercador de dalt per començar.</div></div>
<?php elseif ($total === 0): ?>
<div class="card"><div style="padding:48px;text-align:center;color:#9ca3af">Cap resultat per «<?= htmlspecialchars($q) ?>».</div></div>
<?php else: ?>

<?php if (!empty($results['clients'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">👤 Clients (<?= count($results['clients']) ?>)</div></div>
    <div class="table-wrap"><table><tbody>
        <?php foreach ($results['clients'] as $r): ?>
        <tr><td><strong><?= htmlspecialchars($r['label']) ?></strong><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($r['subtitle']) ?></div></td>
            <td style="text-align:right"><a href="<?= htmlspecialchars($r['url']) ?>" class="btn btn-sm btn-secondary">Obrir</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if (!empty($results['invoices'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">🧾 Factures (<?= count($results['invoices']) ?>)</div></div>
    <div class="table-wrap"><table><tbody>
        <?php foreach ($results['invoices'] as $r): ?>
        <tr><td><strong><?= htmlspecialchars($r['label']) ?></strong><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($r['subtitle']) ?></div></td>
            <td style="text-align:right"><a href="<?= htmlspecialchars($r['url']) ?>" class="btn btn-sm btn-secondary">Obrir</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if (!empty($results['proposals'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">💶 Propostes (<?= count($results['proposals']) ?>)</div></div>
    <div class="table-wrap"><table><tbody>
        <?php foreach ($results['proposals'] as $r): ?>
        <tr><td><strong><?= htmlspecialchars($r['label']) ?></strong><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($r['subtitle']) ?></div></td>
            <td style="text-align:right"><a href="<?= htmlspecialchars($r['url']) ?>" class="btn btn-sm btn-secondary">Obrir</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php if (!empty($results['contacts'])): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">💬 Contactes (<?= count($results['contacts']) ?>)</div></div>
    <div class="table-wrap"><table><tbody>
        <?php foreach ($results['contacts'] as $r): ?>
        <tr><td><strong><?= htmlspecialchars($r['label']) ?></strong><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($r['subtitle']) ?></div></td>
            <td style="text-align:right"><a href="<?= htmlspecialchars($r['url']) ?>" class="btn btn-sm btn-secondary">Obrir</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<?php endif; ?>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
