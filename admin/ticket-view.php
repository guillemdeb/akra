<?php
// admin/ticket-view.php — Fitxa d'un tiquet: fil de missatges + control d'estat/prioritat.
require_once 'includes/core.php';
requireLogin();

$id = $_GET['id'] ?? '';
$ticket = getTicket($id);
if (!$ticket) { die('Tiquet no trobat. <a href="tickets.php">Tornar</a>'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            saveTicketMessage(['id' => generateId(), 'ticket_id' => $ticket['id'], 'sender' => 'agency', 'body' => $body, 'read_by_agency' => true]);
            notifyClientOfChange($ticket['client_id'], 'ticket_reply', [
                'body_args' => [$ticket['subject']],
                'hub_page'  => 'ticket-view.php?id=' . $ticket['id'],
            ]);
        }
        header('Location: ticket-view.php?id=' . $ticket['id'] . '&replied=1');
        exit;
    }

    if ($action === 'update') {
        $prev_status = $ticket['status'];
        $ticket['category'] = $_POST['category'] ?? $ticket['category'];
        $ticket['priority'] = $_POST['priority'] ?? $ticket['priority'];
        $ticket['status']   = $_POST['status'] ?? $ticket['status'];
        if ($ticket['status'] !== $prev_status && in_array($ticket['status'], ['resolt', 'tancat']) && empty($ticket['resolved_at'])) {
            $ticket['resolved_at'] = date('Y-m-d H:i:s');
        }
        saveTicket($ticket);
        if ($ticket['status'] !== $prev_status) {
            $st = getTicketStatusOptions();
            notifyClientOfChange($ticket['client_id'], 'ticket_status', [
                'body_args' => [$ticket['subject'], $st[$ticket['status']]['label'] ?? $ticket['status']],
                'hub_page'  => 'ticket-view.php?id=' . $ticket['id'],
            ]);
        }
        header('Location: ticket-view.php?id=' . $ticket['id'] . '&updated=1');
        exit;
    }
}

// Marca com a llegits (per l'agència) els missatges del client
markTicketMessagesReadByAgency($ticket['id']);
$ticket = getTicket($id); // recarrega per si de cas

$client     = getClient($ticket['client_id'] ?? '');
$cats       = getTicketCategoryOptions();
$priorities = getTicketPriorityOptions();
$statuses   = getTicketStatusOptions();
$messages   = getTicketMessages($ticket['id']);

$page_title    = htmlspecialchars($ticket['subject']);
$page_subtitle = $client ? htmlspecialchars($client['name']) : '';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($ticket['subject']) ?> · Tiquets · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<div style="margin-bottom:14px"><a href="tickets.php" style="font-size:.82rem;color:#6b7280;text-decoration:none">← Tornar a Tiquets</a></div>

<?php if (isset($_GET['replied'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Resposta enviada.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Tiquet actualitzat.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

<!-- Fil de conversa -->
<div class="card">
    <div class="card-header">
        <div class="card-title">💬 Conversa</div>
    </div>
    <div style="padding:18px;display:flex;flex-direction:column;gap:12px;max-height:520px;overflow-y:auto">
        <?php if (empty($messages)): ?>
        <div style="color:#9ca3af;font-size:.85rem;text-align:center;padding:20px">Encara no hi ha cap missatge en este tiquet.</div>
        <?php else: foreach ($messages as $m): $is_client = ($m['sender'] ?? '') === 'client'; ?>
        <div style="max-width:80%;<?= $is_client ? '' : 'align-self:flex-end' ?>">
            <div style="font-size:.72rem;color:#9ca3af;margin-bottom:3px;<?= $is_client ? '' : 'text-align:right' ?>">
                <?= $is_client ? htmlspecialchars($client['name'] ?? 'Client') : 'AKRA Tech Studio' ?> · <?= !empty($m['created_at']) ? date('d/m/Y H:i', strtotime($m['created_at'])) : '' ?>
            </div>
            <div style="background:<?= $is_client ? '#f3f4f6' : 'rgba(201,168,76,.14)' ?>;border-radius:12px;padding:10px 14px;font-size:.88rem;white-space:pre-wrap;line-height:1.5"><?= htmlspecialchars($m['body'] ?? '') ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <div style="padding:16px 18px;border-top:1px solid var(--a-border)">
        <form method="POST">
            <input type="hidden" name="action" value="reply">
            <textarea name="body" required placeholder="Escriu una resposta..." style="width:100%;min-height:80px;padding:10px;border:1px solid var(--a-border);border-radius:8px;font-family:inherit;font-size:.88rem;resize:vertical"></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top:10px">Enviar resposta</button>
        </form>
    </div>
</div>

<!-- Detalls i control d'estat -->
<div class="card">
    <div class="card-header"><div class="card-title">🎫 Detalls</div></div>
    <div class="card-body form-grid">
        <?php if ($client): ?>
        <div class="form-group">
            <label>Client</label>
            <a href="clients.php?id=<?= htmlspecialchars($client['id']) ?>" style="font-weight:700;color:inherit"><?= htmlspecialchars($client['name']) ?></a>
        </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-group">
                <label>Estat</label>
                <select name="status">
                    <?php foreach ($statuses as $key => $s): ?><option value="<?= $key ?>" <?= ($ticket['status'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Prioritat</label>
                <select name="priority">
                    <?php foreach ($priorities as $key => $p): ?><option value="<?= $key ?>" <?= ($ticket['priority'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <select name="category">
                    <?php foreach ($cats as $key => $label): ?><option value="<?= $key ?>" <?= ($ticket['category'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" style="width:100%">Guardar canvis</button>
        </form>
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--a-border);font-size:.78rem;color:#9ca3af">
            Obert el <?= !empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime($ticket['created_at'])) : '' ?>
            <?php if (!empty($ticket['resolved_at'])): ?><br>Resolt el <?= date('d/m/Y H:i', strtotime($ticket['resolved_at'])) ?><?php endif; ?>
        </div>
    </div>
</div>

</div>
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
