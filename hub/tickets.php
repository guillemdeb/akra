<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $subject = trim($_POST['subject'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    if ($subject !== '') {
        $ticket = saveTicket([
            'id'        => generateId(),
            'client_id' => $client['id'],
            'subject'   => $subject,
            'category'  => $_POST['category'] ?? 'altres',
            'priority'  => $_POST['priority'] ?? 'mitjana',
            'status'    => 'obert',
        ]);
        if ($desc !== '') {
            saveTicketMessage(['id' => generateId(), 'ticket_id' => $ticket['id'], 'sender' => 'client', 'body' => $desc]);
        }
        notifyAgencyOfTicket($ticket, true);
        header('Location: tickets.php?created=1');
        exit;
    }
}

$tickets    = getTickets($client['id']);
$cats       = getTicketCategoryOptions();
$priorities = getTicketPriorityOptions();
$statuses   = getTicketStatusOptions();
$cat_keys   = ['incidencia', 'dubte', 'peticio', 'facturacio', 'altres'];
$pri_keys   = ['baixa', 'mitjana', 'alta', 'urgent'];
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('tix_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('tix_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= htmlspecialchars(hubT('tix_sub', $lang)) ?></p>

    <?php if (isset($_GET['created'])): ?>
    <div class="hub-alert hub-alert--success"><?= htmlspecialchars(hubT('tix_created_ok', $lang)) ?></div>
    <?php endif; ?>

    <div class="hub-card">
        <div class="hub-card-header"><div class="hub-card-title"><?= htmlspecialchars(hubT('tix_new', $lang)) ?></div></div>
        <div class="hub-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="hub-form-group">
                    <label><?= htmlspecialchars(hubT('tix_new_subject', $lang)) ?></label>
                    <input type="text" name="subject" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="hub-form-group">
                        <label><?= htmlspecialchars(hubT('tix_new_category', $lang)) ?></label>
                        <select name="category">
                            <?php foreach ($cat_keys as $k): ?><option value="<?= $k ?>"><?= htmlspecialchars(hubT('tix_cat_' . $k, $lang)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="hub-form-group">
                        <label><?= htmlspecialchars(hubT('tix_new_priority', $lang)) ?></label>
                        <select name="priority">
                            <?php foreach ($pri_keys as $k): ?><option value="<?= $k ?>" <?= $k === 'mitjana' ? 'selected' : '' ?>><?= htmlspecialchars(hubT('tix_pri_' . $k, $lang)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="hub-form-group">
                    <label><?= htmlspecialchars(hubT('tix_new_desc', $lang)) ?></label>
                    <textarea name="description" placeholder="<?= htmlspecialchars(hubT('tix_new_desc_ph', $lang)) ?>"></textarea>
                </div>
                <button type="submit" class="hub-btn hub-btn--primary"><?= htmlspecialchars(hubT('tix_new_submit', $lang)) ?></button>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <?php if (empty($tickets)): ?>
        <div class="hub-empty"><?= htmlspecialchars(hubT('tix_empty', $lang)) ?></div>
        <?php else: foreach ($tickets as $t):
            $st = $statuses[$t['status'] ?? 'obert'] ?? $statuses['obert'];
            $pr = $priorities[$t['priority'] ?? 'mitjana'] ?? $priorities['mitjana'];
        ?>
        <a href="ticket-view.php?id=<?= htmlspecialchars($t['id']) ?>" class="hub-row" style="text-decoration:none;color:inherit">
            <div class="hub-row-main">
                <div class="hub-row-title"><?= htmlspecialchars($t['subject']) ?></div>
                <div class="hub-row-sub"><?= htmlspecialchars(hubT('tix_opened_on', $lang)) ?> <?= !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '' ?></div>
            </div>
            <div class="hub-row-side">
                <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $pr['class']) ?>"><?= htmlspecialchars(hubT('tix_pri_' . ($t['priority'] ?? 'mitjana'), $lang)) ?></span>
                <div style="margin-top:6px"><span class="hub-badge hub-badge--<?= str_replace('badge-', '', $st['class']) ?>"><?= htmlspecialchars(hubTStatus($st['label'], $lang)) ?></span></div>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>
</div>
</body></html>
