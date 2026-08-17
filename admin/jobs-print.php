<?php
require_once 'includes/core.php';
requireLogin();

$client_id = $_GET['client'] ?? '';
$client = $client_id ? getClient($client_id) : null;
if (!$client) { die('Client no trobat.'); }

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$jobs = getJobs($client_id);
if ($from) $jobs = array_values(array_filter($jobs, fn($j) => ($j['start_date'] ?? '') >= $from));
if ($to)   $jobs = array_values(array_filter($jobs, fn($j) => ($j['start_date'] ?? '') <= $to));
// Ordenem de més antic a més nou per a la impressió (lectura cronològica)
usort($jobs, fn($a, $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));

$job_status_opts = getJobStatusOptions();
$job_type_opts    = getJobTypeOptions();
$cfg = getAdminConfig();

$total_price = array_sum(array_map(fn($j) => (float)($j['price'] ?? 0), $jobs));
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Treballs — <?= htmlspecialchars($client['name']) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 13px; color: #1a1a1a; margin: 0; padding: 30px 36px; }
    .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; }
    .toolbar input, .toolbar button, .toolbar a { font-size: 13px; padding: 7px 12px; border-radius: 6px; border: 1px solid #d1d5db; background: white; cursor: pointer; text-decoration: none; color: #1a1a1a; }
    .toolbar button, .toolbar a.btn-primary { background: #0a0a0a; color: white; border-color: #0a0a0a; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .brand { font-size: 18px; font-weight: bold; }
    .brand-sub { font-size: 11px; color: #9ca3af; }
    .title { font-size: 20px; font-weight: bold; text-align: right; }
    .subtitle { font-size: 12px; color: #6b7280; text-align: right; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead td { background: #0a0a0a; color: white; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; padding: 8px 10px; font-weight: bold; }
    tbody td { padding: 9px 10px; border-bottom: 1px solid #f0f0f0; font-size: 12px; vertical-align: top; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: bold; }
    .b-gray  { background: #f4f4f5; color: #52525b; }
    .b-blue  { background: #dbeafe; color: #1e40af; }
    .b-gold  { background: #fef3c7; color: #92400e; }
    .b-green { background: #dcfce7; color: #166534; }
    .b-red   { background: #fee2e2; color: #991b1b; }
    .col-right { text-align: right; }
    .totals { text-align: right; margin-top: 14px; font-size: 14px; font-weight: bold; }
    .empty { padding: 40px; text-align: center; color: #9ca3af; }
    @media print { .toolbar { display: none; } body { padding: 0 20px; } }
</style>
</head>
<body>

<div class="toolbar">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
        <input type="hidden" name="client" value="<?= htmlspecialchars($client_id) ?>">
        <label style="font-size:12px;color:#6b7280">Des de</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        <label style="font-size:12px;color:#6b7280">Fins a</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        <button type="submit">Filtrar</button>
        <?php if ($from || $to): ?><a href="jobs-print.php?client=<?= htmlspecialchars($client_id) ?>">Veure tots (sense filtre)</a><?php endif; ?>
    </form>
    <div style="flex:1"></div>
    <button type="button" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="header">
    <div>
        <div class="brand"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></div>
        <div class="brand-sub"><?= htmlspecialchars($cfg['slogan'] ?? 'Agència Digital') ?></div>
    </div>
    <div>
        <div class="title">TREBALLS REALITZATS</div>
        <div class="subtitle">
            <?= htmlspecialchars($client['name']) ?><?= !empty($client['company']) ? ' · ' . htmlspecialchars($client['company']) : '' ?><br>
            <?php if ($from || $to): ?>
                Del <?= $from ? date('d/m/Y', strtotime($from)) : 'inici' ?> al <?= $to ? date('d/m/Y', strtotime($to)) : 'hui' ?>
            <?php else: ?>
                Historial complet
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($jobs)): ?>
<div class="empty">Cap treball registrat <?= ($from || $to) ? 'en este rang de dates' : 'encara' ?>.</div>
<?php else: ?>
<table>
    <thead>
        <tr>
            <td style="width:26%">Treball</td>
            <td style="width:14%">Tipus</td>
            <td style="width:11%">Inici</td>
            <td style="width:11%">Fi</td>
            <td style="width:12%">Estat</td>
            <td style="width:12%" class="col-right">Import</td>
            <td style="width:14%">Descripció</td>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($jobs as $j): $js = $job_status_opts[$j['status'] ?? 'pressupostat']; ?>
        <tr>
            <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
            <td><?= htmlspecialchars($job_type_opts[$j['type']] ?? $j['type']) ?></td>
            <td><?= !empty($j['start_date']) ? date('d/m/Y', strtotime($j['start_date'])) : '—' ?></td>
            <td><?= !empty($j['end_date']) ? date('d/m/Y', strtotime($j['end_date'])) : '—' ?></td>
            <td><span class="badge b-<?= str_replace('badge-', '', $js['class']) ?>"><?= $js['label'] ?></span></td>
            <td class="col-right"><?= ($j['price'] ?? 0) > 0 ? number_format($j['price'], 2, ',', '.') . ' €' : '—' ?></td>
            <td style="color:#6b7280"><?= htmlspecialchars($j['description'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($total_price > 0): ?>
<div class="totals">Total import dels treballs mostrats: <?= number_format($total_price, 2, ',', '.') ?> €</div>
<?php endif; ?>
<?php endif; ?>

</body>
</html>
