<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $message = trim($_POST['message'] ?? '');
    if ($message !== '') {
        saveContact([
            'id'        => generateId(),
            'client_id' => $client['id'],
            'date'      => date('Y-m-d'),
            'channel'   => 'hub',
            'direction' => 'client_jo',
            'message'   => $message,
            'response'  => '',
            'status'    => 'pendent',
            'follow_up' => '',
        ]);
        header('Location: comunicacions.php?sent=1');
        exit;
    }
}

markContactsReadByClient($client['id']);

$contacts = getContacts($client['id']);
$channels = getContactChannelOptions();
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('comms_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('comms_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= htmlspecialchars(hubT('comms_sub', $lang)) ?></p>

    <?php if (isset($_GET['sent'])): ?>
    <div class="hub-alert hub-alert--success"><?= htmlspecialchars(hubT('comms_sent_ok', $lang)) ?></div>
    <?php endif; ?>

    <div class="hub-card">
        <div class="hub-card-header"><div class="hub-card-title"><?= htmlspecialchars(hubT('comms_write_title', $lang)) ?></div></div>
        <div class="hub-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="send">
                <div class="hub-form-group" style="margin-bottom:12px">
                    <textarea name="message" required placeholder="<?= htmlspecialchars(hubT('comms_placeholder', $lang)) ?>"></textarea>
                </div>
                <button type="submit" class="hub-btn hub-btn--primary"><?= htmlspecialchars(hubT('comms_send', $lang)) ?></button>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><div class="hub-card-title"><?= htmlspecialchars(hubT('comms_history', $lang)) ?> (<?= count($contacts) ?>)</div></div>
        <?php if (empty($contacts)): ?>
        <div class="hub-empty"><?= htmlspecialchars(hubT('comms_empty', $lang)) ?></div>
        <?php else: foreach ($contacts as $c): ?>
        <div class="hub-comm">
            <div class="hub-comm-top">
                <strong style="font-size:.86rem"><?= ($c['direction'] ?? '') === 'client_jo' ? '📤 ' . htmlspecialchars(hubT('dash_you', $lang)) : '📥 AKRA Tech Studio' ?></strong>
                <span class="hub-comm-meta"><?= !empty($c['date']) ? date('d/m/Y', strtotime($c['date'])) : '' ?> · <?= htmlspecialchars(hubTStatus($channels[$c['channel'] ?? 'altres'] ?? '', $lang)) ?></span>
            </div>
            <div class="hub-comm-body"><?= htmlspecialchars($c['message'] ?? '') ?></div>
            <?php if (!empty($c['response'])): ?>
            <div class="hub-comm-response"><?= htmlspecialchars($c['response']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</body></html>
