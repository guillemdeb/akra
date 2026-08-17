<?php
// admin/tickets.php — Llista de tiquets (incidències/problemes que informen els clients).
require_once 'includes/core.php';
requireLogin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $client_id = $_POST['client_id'] ?? '';
        $subject   = trim($_POST['subject'] ?? '');
        if (!$client_id || $subject === '') {
            $error = 'Cal client i assumpte.';
        } else {
            $ticket = saveTicket([
                'id'          => generateId(),
                'client_id'   => $client_id,
                'subject'     => $subject,
                'category'    => $_POST['category'] ?? 'altres',
                'priority'    => $_POST['priority'] ?? 'mitjana',
                'status'      => 'obert',
            ]);
            $desc = trim($_POST['description'] ?? '');
            if ($desc !== '') {
                saveTicketMessage(['id' => generateId(), 'ticket_id' => $ticket['id'], 'sender' => 'agency', 'body' => $desc, 'read_by_agency' => true]);
            }
            header('Location: ticket-view.php?id=' . $ticket['id']);
            exit;
        }
    }

    if ($action === 'delete') {
        deleteTicket($_POST['id'] ?? '');
        header('Location: tickets.php?deleted=1');
        exit;
    }
}

$clients  = getClients();
$cats     = getTicketCategoryOptions();
$priorities = getTicketPriorityOptions();
$statuses = getTicketStatusOptions();

$filter_client   = $_GET['client'] ?? '';
$filter_status   = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';

$tickets = getTickets($filter_client ?: null, $filter_status ?: null);
if ($filter_priority) $tickets = array_values(array_filter($tickets, fn($t) => ($t['priority'] ?? '') === $filter_priority));

$page_title    = 'Tiquets';
$page_subtitle = count($tickets) . ' tiquet' . (count($tickets) !== 1 ? 's' : '') . ' · ' . countOpenTickets() . ' oberts';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tiquets · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Tiquet eliminat.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filtres -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <select name="client" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Tots els clients</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Tots els estats</option>
                <?php foreach ($statuses as $key => $s): ?>
                <option value="<?= $key ?>" <?= $filter_status === $key ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Totes les prioritats</option>
                <?php foreach ($priorities as $key => $p): ?>
                <option value="<?= $key ?>" <?= $filter_priority === $key ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
            <?php if ($filter_client || $filter_status || $filter_priority): ?><a href="tickets.php" style="font-size:.8rem;color:#6b7280">Netejar</a><?php endif; ?>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

<!-- Llista -->
<div class="card">
    <div class="card-header"><div class="card-title">🎫 Tiquets</div></div>
    <?php if (empty($tickets)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Cap tiquet amb estos filtres.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Assumpte</th><th>Client</th><th>Categoria</th><th>Prioritat</th><th>Estat</th><th>Actualitzat</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t):
            $client = getClient($t['client_id'] ?? '');
            $pr = $priorities[$t['priority'] ?? 'mitjana'] ?? $priorities['mitjana'];
            $st = $statuses[$t['status'] ?? 'obert'] ?? $statuses['obert'];
            $n_unread = count(array_filter(getTicketMessages($t['id']), fn($m) => ($m['sender'] ?? '') === 'client' && empty($m['read_by_agency'])));
        ?>
        <tr>
            <td>
                <a href="ticket-view.php?id=<?= htmlspecialchars($t['id']) ?>" style="color:inherit;text-decoration:none;font-weight:700">
                    <?= htmlspecialchars($t['subject']) ?>
                </a>
                <?php if ($n_unread): ?><span class="badge badge-red" style="font-size:.65rem;margin-left:6px"><?= $n_unread ?> nou<?= $n_unread !== 1 ? 's' : '' ?></span><?php endif; ?>
            </td>
            <td><?= $client ? htmlspecialchars($client['name']) : '<span style="color:#9ca3af">—</span>' ?></td>
            <td><span style="font-size:.78rem;color:#6b7280"><?= htmlspecialchars($cats[$t['category'] ?? ''] ?? '') ?></span></td>
            <td><span class="badge <?= $pr['class'] ?>"><?= htmlspecialchars($pr['label']) ?></span></td>
            <td><span class="badge <?= $st['class'] ?>"><?= htmlspecialchars($st['label']) ?></span></td>
            <td style="color:#9ca3af;font-size:.8rem"><?= !empty($t['updated_at']) ? date('d/m/Y H:i', strtotime($t['updated_at'])) : '' ?></td>
            <td>
                <div class="td-actions">
                    <a href="ticket-view.php?id=<?= htmlspecialchars($t['id']) ?>" class="btn btn-sm btn-secondary">Obrir</a>
                    <form method="POST" onsubmit="return confirm('Eliminar este tiquet i tot el seu fil de missatges?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($t['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Crear tiquet en nom d'un client -->
<div class="card">
    <div class="card-header"><div class="card-title">➕ Nou tiquet</div></div>
    <div class="card-body form-grid">
        <p class="hint" style="margin:0 0 4px">Normalment els tiquets els obri el client des del Hub, però també en pots crear un tu mateix (per exemple, si t'avisa per telèfon).</p>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Client *</label>
                <select name="client_id" required>
                    <option value="">— Selecciona client —</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Assumpte *</label>
                <input type="text" name="subject" required placeholder="Ex. La web no carrega les imatges">
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <?php foreach ($cats as $key => $label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prioritat</label>
                    <select name="priority">
                        <?php foreach ($priorities as $key => $p): ?><option value="<?= $key ?>" <?= $key === 'mitjana' ? 'selected' : '' ?>><?= htmlspecialchars($p['label']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Descripció inicial (opcional)</label>
                <textarea name="description" placeholder="Detalls del problema..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Crear tiquet</button>
        </form>
    </div>
</div>

</div>
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
