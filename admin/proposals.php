<?php
require_once 'includes/core.php';
requireLogin();

// ── DESCARREGAR PDF ──────────────────────────────────────────────────────────
if (isset($_GET['download_pdf'])) {
    $lang = $_GET['lang'] ?? 'ca';
    $result = generateProposalPdf($_GET['download_pdf'], $lang);
    if (!$result['ok']) {
        header('Content-Type: text/plain; charset=UTF-8');
        die('No s\'ha pogut generar el PDF: ' . $result['error']);
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . strlen($result['pdf']));
    echo $result['pdf'];
    exit;
}

// ── ENVIAR PER EMAIL ─────────────────────────────────────────────────────────
if (isset($_GET['send_email']) && isset($_GET['prop_id'])) {
    $prop_id = $_GET['prop_id'];
    $lang    = $_GET['lang'] ?? 'ca';
    $prop    = getProposal($prop_id);
    $client  = $prop ? getClient($prop['client_id']) : null;
    $to      = $_GET['to'] ?? $client['email'] ?? '';
    if ($to) {
        $result = sendProposalEmail($prop_id, $to, $lang);
        if ($result['ok']) {
            if ($prop && $prop['status'] === 'borrador') { $prop['status'] = 'enviada'; saveProposal($prop); }
            header('Location: proposals.php?email_ok=1'); exit;
        }
        header('Location: proposals.php?email_err=1&msg=' . urlencode($result['error'] ?? '')); exit;
    }
    header('Location: proposals.php?email_err=1&msg=' . urlencode('El client no té email registrat')); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $prev_proposal = !empty($_POST['id']) ? getProposal($_POST['id']) : null;
        $proposal = [
            'id'          => $_POST['id'] ?: generateId(),
            'client_id'   => $_POST['client_id'] ?? '',
            'audit_id'    => $_POST['audit_id'] ?? '',
            'type'        => $_POST['type'] ?? '',
            'price'       => (float)str_replace(',', '.', $_POST['price'] ?? 0),
            'description' => sanitize($_POST['description'] ?? ''),
            'date'        => $_POST['date'] ?? date('Y-m-d'),
            'status'      => $_POST['status'] ?? 'borrador',
        ];
        saveProposal($proposal);
        advanceClientStage($proposal['client_id'], 'proposta');
        if ($proposal['status'] === 'enviada' && ($prev_proposal['status'] ?? '') !== 'enviada') {
            notifyClientOfChange($proposal['client_id'], 'proposal_new', ['hub_page' => 'propostes.php']);
        }
        header('Location: proposals.php?saved=1'); exit;
    }
    if ($action === 'delete') {
        deleteProposal($_POST['id']);
        header('Location: proposals.php?deleted=1'); exit;
    }
    if ($action === 'status') {
        $p = getProposal($_POST['id']);
        if ($p) {
            $prev_status = $p['status'];
            $p['status'] = $_POST['status'];
            saveProposal($p);
            if ($p['status'] === 'aceptada') advanceClientStage($p['client_id'], 'guanyat');
            if ($p['status'] === 'rechazada') advanceClientStage($p['client_id'], 'perdut');
            if ($p['status'] === 'enviada' && $prev_status !== 'enviada') {
                notifyClientOfChange($p['client_id'], 'proposal_new', ['hub_page' => 'propostes.php']);
            }
        }
        header('Location: proposals.php?saved=1'); exit;
    }
}

$edit_id = $_GET['id'] ?? null;
$edit    = $edit_id ? getProposal($edit_id) : null;
if ($edit_id && !$edit) { header('Location: proposals.php'); exit; }

$preselect_client = $_GET['client'] ?? '';
$preselect_audit  = $_GET['audit'] ?? '';
$clients   = getClients();
$audits    = getAudits();
$proposals = getProposals();
$type_opts   = getProposalTypeOptions();
$status_opts = getProposalStatusOptions();

$page_title    = $edit_id ? 'Editar proposta' : 'Propostes comercials';
$page_subtitle = $edit_id ? '' : count($proposals) . ' propostes';
$topbar_action_url   = 'proposals.php?new=1';
$topbar_action_label = '+ Nova proposta';
$show_form = $edit_id || isset($_GET['new']);
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Proposta guardada.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Proposta eliminada.</div><?php endif; ?>
<?php if (isset($_GET['email_ok'])): ?><div class="alert alert-success">✅ Proposta enviada per email amb el PDF adjunt.</div><?php endif; ?>
<?php if (isset($_GET['email_err'])): ?><div class="alert alert-error">❌ Error en enviar l'email<?= !empty($_GET['msg']) ? ': ' . htmlspecialchars($_GET['msg']) : '' ?>.</div><?php endif; ?>

<?php if (!$show_form): ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Totes les propostes</div>
        <a href="proposals.php?new=1" class="btn btn-primary btn-sm">+ Nova proposta</a>
    </div>
    <?php if (empty($proposals)): ?>
    <div style="padding:48px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">💶</div>
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:8px">Cap proposta encara</h3>
        <p style="color:#6b7280;margin-bottom:20px">Genera propostes a partir de les auditories realitzades.</p>
        <a href="proposals.php?new=1" class="btn btn-primary">+ Crear primera proposta</a>
    </div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Client</th><th>Tipus</th><th>Preu</th><th>Data</th><th>Estat</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($proposals as $p):
            $c = getClient($p['client_id']);
            $st = proposalStatusLabel($p['status'] ?? 'borrador');
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($c['name'] ?? '— client eliminat —') ?></strong>
                <?php if (!empty($c['company'])): ?><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($c['company']) ?></div><?php endif; ?>
            </td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($type_opts[$p['type']] ?? '—') ?></td>
            <td style="font-weight:700;font-family:'Syne',sans-serif"><?= number_format($p['price'], 0, ',', '.') ?> €</td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($p['date']) ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <select name="status" onchange="this.form.submit()" class="badge <?= $st['class'] ?>" style="border:none;cursor:pointer">
                        <?php foreach ($status_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($p['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
            <td>
                <div class="td-actions">
                    <a href="proposals.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                    <a href="proposals.php?download_pdf=<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="Descarregar PDF (CA)">⬇️ CA</a>
                    <a href="proposals.php?download_pdf=<?= $p['id'] ?>&lang=es" class="btn btn-sm btn-secondary" title="Descarregar PDF (ES)">⬇️ ES</a>
                    <?php if (!empty($c['email'])): ?>
                    <a href="proposals.php?send_email=1&prop_id=<?= $p['id'] ?>&to=<?= urlencode($c['email']) ?>&lang=ca" class="btn btn-sm btn-secondary" title="Enviar per email en català" onclick="return confirm('Enviar la proposta en català a <?= htmlspecialchars($c['email']) ?>?')">✉️ CA</a>
                    <a href="proposals.php?send_email=1&prop_id=<?= $p['id'] ?>&to=<?= urlencode($c['email']) ?>&lang=es" class="btn btn-sm btn-secondary" title="Enviar per email en castellà" onclick="return confirm('¿Enviar la propuesta en castellano a <?= htmlspecialchars($c['email']) ?>?')">✉️ ES</a>
                    <?php endif; ?>
                    <?php if (($p['status'] ?? '') === 'aceptada'): ?>
                    <a href="invoices.php?new=1&client=<?= $p['client_id'] ?>&from_proposal=<?= $p['id'] ?>" class="btn btn-sm btn-primary" title="Convertir en factura">🧾 A factura</a>
                    <?php endif; ?>
                    <?php if (!empty($p['audit_id'])): ?>
                    <a href="audit-report.php?id=<?= $p['audit_id'] ?>" class="btn btn-sm btn-secondary" target="_blank" title="Veure informe">📄</a>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('Eliminar esta proposta?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<?php else:
$p = $edit ?? [
    'id'=>'','client_id'=>$preselect_client,'audit_id'=>$preselect_audit,'type'=>'',
    'price'=>'','description'=>'','date'=>date('Y-m-d'),'status'=>'borrador',
];
?>
<form method="POST" class="form-grid">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
    <div class="card">
        <div class="card-header"><div class="card-title">Dades de la proposta</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Client *</label>
                    <select name="client_id" required>
                        <option value="">— Selecciona client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $p['client_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?><?= $c['company'] ? ' · ' . htmlspecialchars($c['company']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Auditoria relacionada (opcional)</label>
                    <select name="audit_id">
                        <option value="">— Cap —</option>
                        <?php foreach ($audits as $a): $ac = getClient($a['client_id']); ?>
                        <option value="<?= $a['id'] ?>" <?= $p['audit_id'] === $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ac['name'] ?? '?') ?> · <?= htmlspecialchars($a['date']) ?> · <?= auditScoreAvg($a) ?>/10</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Tipus de proposta *</label>
                    <select name="type" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($type_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($p['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Preu (€) *</label><input type="number" step="0.01" min="0" name="price" value="<?= htmlspecialchars((string)($p['price'] ?? '')) ?>" required></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Data</label><input type="date" name="date" value="<?= htmlspecialchars($p['date']) ?>"></div>
                <div class="form-group">
                    <label>Estat</label>
                    <select name="status">
                        <?php foreach ($status_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($p['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Descripció de l'abast</label><textarea name="description" rows="5" placeholder="Detall del que inclou la proposta..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea></div>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar proposta</button>
        <a href="proposals.php" class="btn btn-secondary">Cancel·lar</a>
    </div>
</form>
<?php endif; ?>

</div></div>
<?php include 'includes/admin-footer.php'; ?>
