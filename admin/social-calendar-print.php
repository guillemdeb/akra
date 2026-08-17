<?php
require_once 'includes/core.php';
requireLogin();

$client_id = $_GET['client'] ?? '';
$client    = $client_id ? getClient($client_id) : null;

$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';

$posts = getSocialPosts($client_id ?: null);
if ($from)   $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') >= $from));
if ($to)     $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') <= $to));
if ($status) $posts = array_values(array_filter($posts, fn($p) => ($p['status'] ?? '') === $status));
usort($posts, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));

$platforms  = getSocialPlatformOptions();
$formats    = getSocialFormatOptions();
$objectives = getSocialObjectiveOptions();
$statuses   = getSocialStatusOptions();
$cfg        = getAdminConfig();
$clients    = getClients();

$status_print_class = [
    'idea' => 'sp-gray', 'planificat' => 'sp-blue', 'produccio' => 'sp-gold',
    'programat' => 'sp-gold', 'publicat' => 'sp-green', 'descartat' => 'sp-red',
];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<title>Calendari de xarxes<?= $client ? ' — ' . htmlspecialchars($client['name']) : '' ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #1a1a1a; margin: 0; padding: 30px 36px; background: #fff; }
    .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; }
    .toolbar select, .toolbar input, .toolbar button, .toolbar a { font-size: 13px; padding: 7px 12px; border-radius: 6px; border: 1px solid #d1d5db; background: white; cursor: pointer; text-decoration: none; color: #1a1a1a; }
    .toolbar button.primary, .toolbar a.primary { background: #0a0a0a; color: white; border-color: #0a0a0a; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 26px; padding-bottom: 18px; border-bottom: 2px solid #0a0a0a; }
    .brand { font-size: 19px; font-weight: 800; letter-spacing: .3px; }
    .brand-sub { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    .title { font-size: 21px; font-weight: 800; text-align: right; }
    .subtitle { font-size: 12px; color: #6b7280; text-align: right; margin-top: 3px; line-height: 1.5; }
    .month-heading { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #9ca3af; margin: 26px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
    .month-heading:first-of-type { margin-top: 0; }
    .entry { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; page-break-inside: avoid; }
    .entry-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
    .entry-date { font-weight: 800; font-size: 13px; min-width: 78px; }
    .entry-tags { display: flex; gap: 6px; flex-wrap: wrap; }
    .tag { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; background: #f4f4f5; color: #52525b; }
    .sp-gray  { background: #f4f4f5; color: #52525b; }
    .sp-blue  { background: #dbeafe; color: #1e40af; }
    .sp-gold  { background: #fef3c7; color: #92400e; }
    .sp-green { background: #dcfce7; color: #166534; }
    .sp-red   { background: #fee2e2; color: #991b1b; }
    .entry-theme { font-size: 14.5px; font-weight: 700; margin-bottom: 3px; }
    .entry-series { font-size: 10.5px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 8px; }
    .entry-hook { font-size: 12.5px; font-style: italic; color: #374151; margin-bottom: 6px; padding-left: 10px; border-left: 2px solid #d1d5db; }
    .entry-content { font-size: 12px; color: #374151; line-height: 1.55; margin-bottom: 8px; white-space: pre-wrap; }
    .entry-footer { display: flex; gap: 18px; flex-wrap: wrap; font-size: 10.5px; color: #6b7280; border-top: 1px dashed #e5e7eb; padding-top: 8px; margin-top: 4px; }
    .entry-footer b { color: #1a1a1a; }
    .entry-client { font-size: 10.5px; color: #9ca3af; font-weight: 600; }
    .empty { padding: 60px; text-align: center; color: #9ca3af; }
    .count-line { font-size: 11px; color: #9ca3af; margin-bottom: 16px; }
    @media print { .toolbar { display: none; } body { padding: 0 22px; } .entry { break-inside: avoid; } }
</style>
</head>
<body>

<div class="toolbar">
    <a href="social-calendar.php" class="primary" style="margin-right:8px">← Tornar</a>
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <select name="client">
            <option value="">Tots els clients</option>
            <?php foreach ($clients as $c): ?>
            <option value="<?= htmlspecialchars($c['id']) ?>" <?= $client_id === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>">
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>">
        <button type="submit">Filtrar</button>
    </form>
    <div style="flex:1"></div>
    <button type="button" class="primary" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
</div>

<div class="header">
    <div>
        <div class="brand"><?= htmlspecialchars($cfg['site_name'] ?? 'AKRA Tech Studio') ?></div>
        <div class="brand-sub"><?= htmlspecialchars($cfg['slogan'] ?? 'Agència Digital') ?></div>
    </div>
    <div>
        <div class="title">CALENDARI DE CONTINGUT</div>
        <div class="subtitle">
            <?= $client ? htmlspecialchars($client['name']) . ($client['company'] ? ' · ' . htmlspecialchars($client['company']) : '') : 'Tots els clients' ?><br>
            <?php if ($from || $to): ?>
                Del <?= $from ? date('d/m/Y', strtotime($from)) : 'inici' ?> al <?= $to ? date('d/m/Y', strtotime($to)) : 'hui' ?>
            <?php else: ?>
                Planificació completa
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (empty($posts)): ?>
<div class="empty">Cap publicació planificada <?= ($from || $to || $status) ? 'amb estos filtres' : 'encara' ?>.</div>
<?php else: ?>
<div class="count-line"><?= count($posts) ?> publicació<?= count($posts) !== 1 ? 'ns' : '' ?> · generat el <?= date('d/m/Y H:i') ?></div>

<?php
$current_month = '';
foreach ($posts as $p):
    $m = !empty($p['date']) ? monthLabelCa(strtotime($p['date'])) : 'Sense data';
    if ($m !== $current_month):
        $current_month = $m;
?>
<div class="month-heading"><?= htmlspecialchars($current_month) ?></div>
<?php endif;
    $st = $statuses[$p['status'] ?? 'idea'] ?? $statuses['idea'];
    $entry_client = getClient($p['client_id'] ?? '');
?>
<div class="entry">
    <div class="entry-top">
        <div class="entry-date"><?= !empty($p['date']) ? date('d/m/Y', strtotime($p['date'])) : '—' ?></div>
        <div class="entry-tags">
            <?php if (!$client_id && $entry_client): ?><span class="tag sp-gray"><?= htmlspecialchars($entry_client['name']) ?></span><?php endif; ?>
            <span class="tag"><?= htmlspecialchars($platforms[$p['platform'] ?? ''] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($formats[$p['format'] ?? ''] ?? '') ?></span>
            <?php if (!empty($p['objective'])): ?><span class="tag"><?= htmlspecialchars($objectives[$p['objective']] ?? '') ?></span><?php endif; ?>
            <span class="tag <?= $status_print_class[$p['status'] ?? 'idea'] ?? 'sp-gray' ?>"><?= htmlspecialchars($st['label']) ?></span>
        </div>
    </div>
    <?php if (!empty($p['series'])): ?><div class="entry-series"><?= htmlspecialchars($p['series']) ?></div><?php endif; ?>
    <?php if (!empty($p['theme'])): ?><div class="entry-theme"><?= htmlspecialchars($p['theme']) ?></div><?php endif; ?>
    <?php if (!empty($p['hook'])): ?><div class="entry-hook">"<?= htmlspecialchars($p['hook']) ?>"</div><?php endif; ?>
    <?php if (!empty($p['content'])): ?><div class="entry-content"><?= htmlspecialchars($p['content']) ?></div><?php endif; ?>
    <?php if (!empty($p['cta']) || !empty($p['material']) || !empty($p['reuse_notes'])): ?>
    <div class="entry-footer">
        <?php if (!empty($p['cta'])): ?><div><b>CTA:</b> <?= htmlspecialchars($p['cta']) ?></div><?php endif; ?>
        <?php if (!empty($p['material'])): ?><div><b>Material:</b> <?= htmlspecialchars($p['material']) ?></div><?php endif; ?>
        <?php if (!empty($p['reuse_notes'])): ?><div><b>Reaprofitament:</b> <?= htmlspecialchars($p['reuse_notes']) ?></div><?php endif; ?>
        <?php if (($p['score'] ?? '') !== '' && $p['score'] !== null): ?><div><b>Puntuació:</b> <?= (int)$p['score'] ?>/100</div><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
