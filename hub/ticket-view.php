<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

$id = $_GET['id'] ?? '';
$ticket = getTicket($id);
if (!$ticket || $ticket['client_id'] !== $client['id']) { http_response_code(404); die('Tiquet no trobat.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reply') {
    $body = trim($_POST['body'] ?? '');
    if ($body !== '') {
        saveTicketMessage(['id' => generateId(), 'ticket_id' => $ticket['id'], 'sender' => 'client', 'body' => $body]);
        // Si el tiquet estava tancat/resolt i el client torna a escriure, el reobrim
        if (in_array($ticket['status'], ['resolt', 'tancat'])) {
            $ticket['status'] = 'obert';
            saveTicket($ticket);
        }
        notifyAgencyOfTicket($ticket, false);
    }
    header('Location: ticket-view.php?id=' . $ticket['id'] . '&replied=1');
    exit;
}

markTicketMessagesReadByClient($ticket['id']);
$messages   = getTicketMessages($ticket['id']);
$cats       = getTicketCategoryOptions();
$priorities = getTicketPriorityOptions();
$statuses   = getTicketStatusOptions();
$st = $statuses[$ticket['status'] ?? 'obert'] ?? $statuses['obert'];
$pr = $priorities[$ticket['priority'] ?? 'mitjana'] ?? $priorities['mitjana'];
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($ticket['subject']) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <div style="margin-bottom:10px"><a href="tickets.php" style="font-size:.82rem;color:var(--h-muted);text-decoration:none"><?= htmlspecialchars(hubT('tix_back', $lang)) ?></a></div>
    <h1 class="hub-page-title"><?= htmlspecialchars($ticket['subject']) ?></h1>
    <p class="hub-page-subtitle">
        <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $pr['class']) ?>"><?= htmlspecialchars(hubT('tix_pri_' . ($ticket['priority'] ?? 'mitjana'), $lang)) ?></span>
        <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $st['class']) ?>"><?= htmlspecialchars(hubTStatus($st['label'], $lang)) ?></span>
        · <?= htmlspecialchars(hubT('tix_opened_on', $lang)) ?> <?= !empty($ticket['created_at']) ? date('d/m/Y', strtotime($ticket['created_at'])) : '' ?>
    </p>

    <?php if (isset($_GET['replied'])): ?>
    <div class="hub-alert hub-alert--success"><?= htmlspecialchars(hubT('tix_replied_ok', $lang)) ?></div>
    <?php endif; ?>

    <div class="hub-card">
        <div style="padding:18px;display:flex;flex-direction:column;gap:12px">
            <?php if (empty($messages)): ?>
            <div class="hub-empty" style="padding:20px"><?= htmlspecialchars(hubT('tix_no_messages', $lang)) ?></div>
            <?php else: foreach ($messages as $m): $is_client = ($m['sender'] ?? '') === 'client'; ?>
            <div style="max-width:82%;<?= $is_client ? 'align-self:flex-end' : '' ?>">
                <div style="font-size:.72rem;color:var(--h-muted);margin-bottom:3px;<?= $is_client ? 'text-align:right' : '' ?>">
                    <?= $is_client ? htmlspecialchars(hubT('tix_you', $lang)) : htmlspecialchars(hubT('tix_us', $lang)) ?> · <?= !empty($m['created_at']) ? date('d/m/Y H:i', strtotime($m['created_at'])) : '' ?>
                </div>
                <div style="background:<?= $is_client ? 'rgba(201,168,76,.14)' : '#f3f4f6' ?>;border-radius:12px;padding:10px 14px;font-size:.88rem;white-space:pre-wrap;line-height:1.5"><?= htmlspecialchars($m['body'] ?? '') ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div style="padding:16px 18px;border-top:1px solid var(--h-border)">
            <form method="POST">
                <input type="hidden" name="action" value="reply">
                <div class="hub-form-group" style="margin-bottom:10px">
                    <textarea name="body" required placeholder="<?= htmlspecialchars(hubT('tix_reply_ph', $lang)) ?>"></textarea>
                </div>
                <button type="submit" class="hub-btn hub-btn--primary"><?= htmlspecialchars(hubT('tix_reply_submit', $lang)) ?></button>
            </form>
        </div>
    </div>
</div>
</body></html>
