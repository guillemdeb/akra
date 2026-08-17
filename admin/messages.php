<?php
require_once 'includes/core.php';
requireLogin();

// Accions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'delete') { deleteMessage($_POST['id']); header('Location: messages.php'); exit; }
    if ($_POST['action'] === 'read')   { markMessageRead($_POST['id']); }
}
if (isset($_GET['id'])) markMessageRead($_GET['id']);

$messages  = getMessages();
$open_id   = $_GET['id'] ?? null;
$open_msg  = $open_id ? (array_values(array_filter($messages, fn($m) => $m['id'] === $open_id))[0] ?? null) : null;
$unread    = count(array_filter($messages, fn($m) => !($m['read'] ?? false)));

$page_title    = 'Missatges';
$page_subtitle = $unread > 0 ? "{$unread} sense llegir" : 'Cap missatge nou';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Missatges · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.inbox { display: grid; grid-template-columns: 340px 1fr; gap: 20px; height: calc(100vh - 120px); }
.msg-list { overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
.msg-item { background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; cursor: pointer; transition: all 0.15s; display: block; }
.msg-item:hover { border-color: #c9a84c; }
.msg-item.unread { border-left: 3px solid #c9a84c; }
.msg-item.active { background: #fefcf5; border-color: #c9a84c; }
.msg-item__name { font-weight: 700; font-size: .9rem; color: #1a1f2e; }
.msg-item__email { font-size: .78rem; color: #9ca3af; }
.msg-item__preview { font-size: .82rem; color: #6b7280; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-item__meta { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
.msg-item__date { font-size: .72rem; color: #9ca3af; }
.msg-detail { background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
.msg-detail__header { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
.msg-detail__name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.1rem; color: #1a1f2e; }
.msg-detail__meta { display: flex; gap: 16px; margin-top: 8px; font-size: .85rem; color: #6b7280; flex-wrap: wrap; }
.msg-detail__body { padding: 24px; flex: 1; overflow-y: auto; }
.msg-detail__message { background: #f7f6f3; border-radius: 10px; padding: 16px; font-size: .95rem; line-height: 1.7; color: #374151; white-space: pre-wrap; }
.msg-detail__actions { padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; }
.empty-inbox { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #9ca3af; gap: 12px; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (empty($messages)): ?>
<div class="card" style="height:400px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:#9ca3af">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    <p>Encara no hi ha missatges al bústia.</p>
</div>
<?php else: ?>
<div class="inbox">
    <!-- Llista -->
    <div class="msg-list">
    <?php foreach ($messages as $m): ?>
    <a href="messages.php?id=<?= $m['id'] ?>" class="msg-item <?= !($m['read']??false) ? 'unread' : '' ?> <?= $open_id === $m['id'] ? 'active' : '' ?>">
        <div class="msg-item__name">
            <?= htmlspecialchars($m['name'] ?? '(Anònim)') ?>
            <?php if (!($m['read']??false)): ?><span class="badge badge-red" style="float:right;margin-top:-2px">Nou</span><?php endif; ?>
        </div>
        <div class="msg-item__email"><?= htmlspecialchars($m['email'] ?? '') ?></div>
        <div class="msg-item__preview"><?= htmlspecialchars(substr($m['message'] ?? '', 0, 70)) ?>...</div>
        <div class="msg-item__meta">
            <span class="msg-item__date"><?= isset($m['date']) ? date('d/m/Y H:i', strtotime($m['date'])) : '' ?></span>
            <?php if (!empty($m['service'])): ?><span class="badge badge-gray" style="font-size:.68rem"><?= htmlspecialchars($m['service']) ?></span><?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
    </div>

    <!-- Detall -->
    <div class="msg-detail">
    <?php if ($open_msg): ?>
        <div class="msg-detail__header">
            <div class="msg-detail__name"><?= htmlspecialchars($open_msg['name'] ?? '') ?></div>
            <div class="msg-detail__meta">
                <span>✉️ <?= htmlspecialchars($open_msg['email'] ?? '') ?></span>
                <?php if (!empty($open_msg['phone'])): ?><span>📞 <?= htmlspecialchars($open_msg['phone']) ?></span><?php endif; ?>
                <?php if (!empty($open_msg['company'])): ?><span>🏢 <?= htmlspecialchars($open_msg['company']) ?></span><?php endif; ?>
                <?php if (!empty($open_msg['service'])): ?><span>🛠 <?= htmlspecialchars($open_msg['service']) ?></span><?php endif; ?>
                <?php if (!empty($open_msg['budget'])): ?><span>💰 <?= htmlspecialchars($open_msg['budget']) ?></span><?php endif; ?>
                <span>🕐 <?= isset($open_msg['date']) ? date('d/m/Y H:i', strtotime($open_msg['date'])) : '' ?></span>
            </div>
        </div>
        <div class="msg-detail__body">
            <div class="msg-detail__message"><?= htmlspecialchars($open_msg['message'] ?? '') ?></div>
        </div>
        <div class="msg-detail__actions">
            <a href="mailto:<?= htmlspecialchars($open_msg['email'] ?? '') ?>?subject=Re: Missatge AKRA Tech Studio" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Respondre per email
            </a>
            <?php if (!empty($open_msg['phone'])): ?>
            <a href="tel:<?= htmlspecialchars($open_msg['phone']) ?>" class="btn btn-secondary">📞 Trucar</a>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Eliminar aquest missatge?')" style="margin-left:auto">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $open_msg['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑 Eliminar</button>
            </form>
        </div>
    <?php else: ?>
        <div class="empty-inbox">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <p>Selecciona un missatge per veure'l</p>
        </div>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
