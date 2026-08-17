<?php
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move_stage') {
    advanceClientStage($_POST['client_id'], $_POST['stage'], true); // moviment manual: sempre permés, en qualsevol direcció
    header('Location: pipeline.php'); exit;
}

$stages  = getLeadStageOptions();
$clients = getClients();
$by_stage = array_fill_keys(array_keys($stages), []);
foreach ($clients as $c) {
    $s = $c['stage'] ?? 'lead';
    if (!isset($by_stage[$s])) $s = 'lead';
    $by_stage[$s][] = $c;
}

$page_title    = 'Pipeline comercial';
$page_subtitle = count($clients) . ' contactes en total';
$topbar_action_url   = 'clients.php?new=1';
$topbar_action_label = '+ Nou lead';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.pipeline-board { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 12px; align-items: flex-start; }
.pipeline-col { flex: 0 0 250px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
.pipeline-col-header { padding: 12px 14px; font-weight: 700; font-size: .82rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; }
.pipeline-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin: 10px; font-size: .82rem; }
.pipeline-card strong { display: block; font-size: .85rem; margin-bottom: 2px; }
.pipeline-card .co { color: #9ca3af; font-size: .74rem; margin-bottom: 8px; }
.pipeline-card select { width: 100%; font-size: .75rem; padding: 4px 6px; border-radius: 5px; border: 1px solid #d1d5db; margin-bottom: 6px; }
.pipeline-card .pc-actions { display: flex; gap: 6px; }
.pipeline-empty { padding: 20px 14px; color: #c1c5cb; font-size: .78rem; text-align: center; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<div class="pipeline-board">
<?php foreach ($stages as $key => $s): ?>
    <div class="pipeline-col">
        <div class="pipeline-col-header">
            <span><?= $s['label'] ?></span>
            <span class="badge <?= $s['class'] ?>"><?= count($by_stage[$key]) ?></span>
        </div>
        <?php if (empty($by_stage[$key])): ?>
            <div class="pipeline-empty">— buit —</div>
        <?php else: foreach ($by_stage[$key] as $c): ?>
            <div class="pipeline-card">
                <strong><?= htmlspecialchars($c['name']) ?></strong>
                <?php if (!empty($c['company'])): ?><div class="co"><?= htmlspecialchars($c['company']) ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="move_stage">
                    <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                    <select name="stage" onchange="this.form.submit()">
                        <?php foreach ($stages as $k2 => $s2): ?>
                        <option value="<?= $k2 ?>" <?= $k2 === $key ? 'selected' : '' ?>><?= $s2['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="pc-actions">
                    <a href="clients.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" style="flex:1;text-align:center">Fitxa</a>
                    <a href="audits.php?new=1&client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Nova auditoria">🔍</a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
<?php endforeach; ?>
</div>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
