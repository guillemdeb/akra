<?php
// admin/social-calendar.php — Calendari de publicacions a xarxes socials, per client.
require_once 'includes/core.php';
requireLogin();

$clients    = getClients();
$platforms  = getSocialPlatformOptions();
$formats    = getSocialFormatOptions();
$objectives = getSocialObjectiveOptions();
$statuses   = getSocialStatusOptions();

$filter_client = $_GET['client'] ?? '';
$filter_from   = $_GET['from'] ?? '';
$filter_to     = $_GET['to'] ?? '';
$filter_status = $_GET['status'] ?? '';

if (isset($_GET['export_csv'])) {
    $csv = exportSocialPostsCsv($filter_client ?: null, $filter_from, $filter_to);
    $client_obj = $filter_client ? getClient($filter_client) : null;
    $fname = 'calendari-' . ($client_obj ? slugify($client_obj['name']) : 'tots') . '-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo $csv;
    exit;
}

$success = '';
$error   = '';
$import_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $client_id = $_POST['client_id'] ?? '';
        $date      = $_POST['date'] ?? '';
        if (!$client_id) { $error = 'Cal seleccionar un client.'; }
        elseif (!$date)  { $error = 'Cal indicar una data.'; }
        else {
            $score = trim($_POST['score'] ?? '');
            saveSocialPost([
                'id'          => $_POST['id'] ?: generateId(),
                'client_id'   => $client_id,
                'date'        => $date,
                'platform'    => $_POST['platform'] ?? 'altres',
                'format'      => $_POST['format'] ?? 'altres',
                'series'      => trim($_POST['series'] ?? ''),
                'theme'       => trim($_POST['theme'] ?? ''),
                'objective'   => $_POST['objective'] ?? '',
                'hook'        => trim($_POST['hook'] ?? ''),
                'content'     => trim($_POST['content'] ?? ''),
                'cta'         => trim($_POST['cta'] ?? ''),
                'material'    => trim($_POST['material'] ?? ''),
                'reuse_notes' => trim($_POST['reuse_notes'] ?? ''),
                'status'      => $_POST['status'] ?? 'idea',
                'score'       => ($score !== '' && is_numeric($score)) ? max(0, min(100, (int)$score)) : '',
            ]);
            $redirect = 'social-calendar.php?saved=1';
            if ($filter_client) $redirect .= '&client=' . urlencode($filter_client);
            header('Location: ' . $redirect);
            exit;
        }
    }

    if ($action === 'delete') {
        deleteSocialPost($_POST['id'] ?? '');
        $redirect = 'social-calendar.php?deleted=1';
        if ($filter_client) $redirect .= '&client=' . urlencode($filter_client);
        header('Location: ' . $redirect);
        exit;
    }

    if ($action === 'delete_month') {
        $del_client = $_POST['delete_client'] ?? '';
        $del_month  = $_POST['delete_month'] ?? ''; // format YYYY-MM
        if ($del_client && preg_match('/^\d{4}-\d{2}$/', $del_month)) {
            $from = $del_month . '-01';
            $to   = date('Y-m-t', strtotime($from)); // últim dia del mes
            $n = deleteSocialPostsInRange($del_client, $from, $to);
            header('Location: social-calendar.php?client=' . urlencode($del_client) . '&month_deleted=' . $n);
            exit;
        }
        $error = 'Selecciona client i mes per eliminar.';
    }

    if ($action === 'publish_approval') {
        $pub_client = $_POST['approval_client'] ?? '';
        $pub_month  = $_POST['approval_month'] ?? '';
        if ($pub_client && preg_match('/^\d{4}-\d{2}$/', $pub_month)) {
            publishCalendarForApproval($pub_client, $pub_month);
            header('Location: social-calendar.php?client=' . urlencode($pub_client) . '&approval_sent=1');
            exit;
        }
    }

    if ($action === 'import_csv') {
        if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Selecciona un fitxer CSV vàlid.';
        } else {
            $import_result = importSocialPostsCsv($_FILES['csv_file']['tmp_name'], $_POST['import_default_client'] ?? '');
            if ($import_result['imported'] > 0) {
                $success = $import_result['imported'] . ' publicació(ns) importada(es) correctament.';
            }
            if (!empty($import_result['errors'])) {
                $error = $import_result['skipped'] . ' fila(es) no s\'han pogut importar.';
            }
        }
    }
}

$posts = getSocialPosts($filter_client ?: null);
if ($filter_from)   $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') >= $filter_from));
if ($filter_to)     $posts = array_values(array_filter($posts, fn($p) => ($p['date'] ?? '') <= $filter_to));
if ($filter_status) $posts = array_values(array_filter($posts, fn($p) => ($p['status'] ?? '') === $filter_status));

$print_url = 'social-calendar-print.php?' . http_build_query(array_filter([
    'client' => $filter_client, 'from' => $filter_from, 'to' => $filter_to, 'status' => $filter_status,
]));

$page_title    = 'Calendari de xarxes socials';
$page_subtitle = count($posts) . ' publicació' . (count($posts) !== 1 ? 'ns' : '') . ' planificades';

// Renderitza el bloc (acordeó) d'un mes dins de la llista de publicacions.
function renderMonthGroup($key, $items, $open, $platforms, $formats, $objectives, $statuses, $filter_client = '') {
    $label = $key === '0000-00' ? 'Sense data' : monthLabelCa(strtotime($key . '-01'));
    ?>
    <details <?= $open ? 'open' : '' ?> class="month-group">
        <summary><?= htmlspecialchars($label) ?> <span class="month-group-count"><?= count($items) ?></span></summary>
        <?php if ($filter_client && $key !== '0000-00') renderApprovalPanel($filter_client, $key); ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Client</th><th>Plataforma / Format</th><th>Tema</th><th>Objectiu</th><th>Estat</th><th>Accions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $p): $rc = getClient($p['client_id'] ?? ''); $st = $statuses[$p['status'] ?? 'idea'] ?? $statuses['idea']; ?>
            <tr>
                <td style="white-space:nowrap"><?= !empty($p['date']) ? date('d/m/Y', strtotime($p['date'])) : '—' ?></td>
                <td><?= $rc ? htmlspecialchars($rc['name']) : '<span style="color:#9ca3af">—</span>' ?></td>
                <td><?= htmlspecialchars($platforms[$p['platform'] ?? ''] ?? '') ?> <span style="color:#9ca3af">· <?= htmlspecialchars($formats[$p['format'] ?? ''] ?? '') ?></span></td>
                <td><?= htmlspecialchars(mb_strimwidth($p['theme'] ?? ($p['series'] ?? ''), 0, 34, '…')) ?></td>
                <td><span style="font-size:.78rem;color:#6b7280"><?= htmlspecialchars($objectives[$p['objective'] ?? ''] ?? '—') ?></span></td>
                <td><span class="badge <?= $st['class'] ?>"><?= htmlspecialchars($st['label']) ?></span></td>
                <td>
                    <div class="td-actions">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="editPost(<?= htmlspecialchars(json_encode($p)) ?>)">✏️</button>
                        <form method="POST" onsubmit="return confirm('Eliminar esta publicació?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </details>
    <?php
}

// Panell d'aprovació del client per a un client+mes concret: mostra l'estat
// actual (si n'hi ha) i el botó per publicar-lo / tornar-lo a enviar.
function renderApprovalPanel($client_id, $month) {
    $approval = getCalendarApproval($client_id, $month);
    $st_opts  = getCalendarApprovalStatusOptions();
    ?>
    <div class="approval-panel">
        <?php if (!$approval): ?>
            <span class="hint" style="margin:0">Encara no s'ha enviat este mes al client per aprovació.</span>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="publish_approval">
                <input type="hidden" name="approval_client" value="<?= htmlspecialchars($client_id) ?>">
                <input type="hidden" name="approval_month" value="<?= htmlspecialchars($month) ?>">
                <button type="submit" class="btn btn-sm btn-primary">📤 Enviar per aprovació</button>
            </form>
        <?php else:
            $s = $st_opts[$approval['status']] ?? $st_opts['pendent'];
            $deadline = calendarApprovalDeadline($approval);
        ?>
            <span class="badge <?= $s['class'] ?>"><?= htmlspecialchars($s['label']) ?></span>
            <?php if ($approval['status'] === 'pendent'): ?>
                <span class="hint" style="margin:0">Termini de confirmació: <?= date('d/m/Y', $deadline) ?> (si no respon, es donarà per acceptat sol)</span>
            <?php elseif (!empty($approval['decided_at'])): ?>
                <span class="hint" style="margin:0">Decidit el <?= date('d/m/Y H:i', strtotime($approval['decided_at'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($approval['client_comment'])): ?>
                <span class="hint" style="margin:0;color:#374151">💬 «<?= htmlspecialchars($approval['client_comment']) ?>»</span>
            <?php endif; ?>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="publish_approval">
                <input type="hidden" name="approval_client" value="<?= htmlspecialchars($client_id) ?>">
                <input type="hidden" name="approval_month" value="<?= htmlspecialchars($month) ?>">
                <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Açò torna a marcar el mes com a pendent d\'aprovació i reinicia el termini. Continuar?')">🔄 <?= $approval['status'] === 'pendent' ? 'Reenviar' : 'Tornar a enviar' ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Calendari de xarxes socials · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Publicació guardada.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Publicació eliminada.</div>
<?php endif; ?>
<?php if (isset($_GET['month_deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= (int)$_GET['month_deleted'] ?> publicació(ns) eliminada(es) del mes sencer.</div>
<?php endif; ?>
<?php if (isset($_GET['approval_sent'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Calendari enviat al client per aprovació.</div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?>
    <?php if ($import_result && !empty($import_result['errors'])): ?>
    <ul style="margin:8px 0 0 20px;font-size:.8rem">
        <?php foreach (array_slice($import_result['errors'], 0, 10) as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Filtres, exportació, impressió i importació -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;justify-content:space-between">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <div>
                <label style="display:block;font-size:.72rem;color:#6b7280;margin-bottom:3px">Client</label>
                <select name="client" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                    <option value="">Tots els clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.72rem;color:#6b7280;margin-bottom:3px">Des de</label>
                <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>" style="font-size:.82rem">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;color:#6b7280;margin-bottom:3px">Fins a</label>
                <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>" style="font-size:.82rem">
            </div>
            <div>
                <label style="display:block;font-size:.72rem;color:#6b7280;margin-bottom:3px">Estat</label>
                <select name="status" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                    <option value="">Tots</option>
                    <?php foreach ($statuses as $key => $s): ?>
                    <option value="<?= $key ?>" <?= $filter_status === $key ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
            <?php if ($filter_client || $filter_from || $filter_to || $filter_status): ?><a href="social-calendar.php" style="font-size:.8rem;color:#6b7280">Netejar</a><?php endif; ?>
        </form>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?= htmlspecialchars($print_url) ?>" target="_blank" class="btn btn-sm btn-secondary">🖨️ Imprimir</a>
            <a href="social-calendar.php?export_csv=1&<?= http_build_query(array_filter(['client'=>$filter_client,'from'=>$filter_from,'to'=>$filter_to])) ?>" class="btn btn-sm btn-secondary">⬇️ Exportar CSV</a>
            <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('import-box').classList.toggle('open-import')">⬆️ Importar CSV</button>
            <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('delete-month-box').classList.toggle('open-import')">🗑️ Eliminar un mes sencer</button>
        </div>
    </div>
    <div id="import-box" class="import-box">
        <form method="POST" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:0 20px 18px">
            <input type="hidden" name="action" value="import_csv">
            <input type="file" name="csv_file" accept=".csv" required style="font-size:.82rem">
            <select name="import_default_client" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Client per defecte (si el CSV no en porta)…</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Importar</button>
            <span class="hint" style="margin:0">El CSV ha de tindre la mateixa capçalera que l'exportació (columna «Client» amb el nom exacte, o tria un client per defecte).</span>
        </form>
    </div>
    <div id="delete-month-box" class="import-box">
        <form method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:0 20px 18px" onsubmit="return confirm('Açò eliminarà TOTES les publicacions del calendari d\'eixe client en eixe mes, sense poder desfer-ho. Continuar?')">
            <input type="hidden" name="action" value="delete_month">
            <select name="delete_client" required style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">— Client —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="month" name="delete_month" required style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
            <button type="submit" class="btn btn-sm btn-danger">Eliminar tot eixe mes</button>
            <span class="hint" style="margin:0;color:#dc2626">Acció irreversible: esborra totes les publicacions d'eixe client dins d'eixe mes, d'un sol colp.</span>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 440px;gap:24px;align-items:start">

<!-- Llista -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🗓️ Publicacions</div>
    </div>
    <?php if (empty($posts)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Cap publicació planificada encara amb estos filtres.</div>
    <?php else:
        // Agrupa per mes (AAAA-MM). El mes actual i els futurs es mostren oberts;
        // els mesos ja passats queden plegats per defecte ("arxivats"), tret que
        // s'haja aplicat un filtre de dates explícit (en eixe cas es mostra tot obert).
        $today_month = date('Y-m');
        $has_explicit_date_filter = ($filter_from !== '' || $filter_to !== '');
        $groups = [];
        foreach ($posts as $p) {
            $key = !empty($p['date']) ? substr($p['date'], 0, 7) : '0000-00';
            $groups[$key][] = $p;
        }
        $future_groups = []; $past_groups = [];
        $current_group = $groups[$today_month] ?? [];
        foreach ($groups as $key => $items) {
            if ($key === $today_month) continue;
            if ($key > $today_month) $future_groups[$key] = $items; else $past_groups[$key] = $items;
        }
        ksort($future_groups);
        krsort($past_groups);

        // Mes actual primer (sempre obert)
        if (!empty($current_group)) {
            renderMonthGroup($today_month, $current_group, true, $platforms, $formats, $objectives, $statuses, $filter_client);
        }
        foreach ($future_groups as $key => $items) {
            renderMonthGroup($key, $items, true, $platforms, $formats, $objectives, $statuses, $filter_client);
        }
        if (!empty($past_groups)):
    ?>
    <div class="month-archive-label">📦 Mesos arxivats</div>
    <?php
        foreach ($past_groups as $key => $items) {
            renderMonthGroup($key, $items, $has_explicit_date_filter, $platforms, $formats, $objectives, $statuses, $filter_client);
        }
        endif;
    endif; ?>
</div>

<!-- Formulari crear/editar publicació -->
<div class="card" id="post-form-card">
    <div class="card-header">
        <div class="card-title" id="post-form-title">➕ Nova publicació</div>
    </div>
    <div class="card-body form-grid">
        <form method="POST" id="post-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="f-id" value="">

            <div class="form-row-2">
                <div class="form-group">
                    <label>Client *</label>
                    <select name="client_id" id="f-client_id" required>
                        <option value="">— Selecciona client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data *</label>
                    <input type="date" name="date" id="f-date" required>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Plataforma</label>
                    <select name="platform" id="f-platform">
                        <?php foreach ($platforms as $key => $label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Format</label>
                    <select name="format" id="f-format">
                        <?php foreach ($formats as $key => $label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Sèrie</label>
                    <input type="text" name="series" id="f-series" placeholder="Ex. Alacant, paraula a paraula">
                </div>
                <div class="form-group">
                    <label>Objectiu</label>
                    <select name="objective" id="f-objective">
                        <option value="">—</option>
                        <?php foreach ($objectives as $key => $label): ?><option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Tema</label>
                <input type="text" name="theme" id="f-theme" placeholder="De què tracta esta peça">
            </div>

            <div class="form-group">
                <label>Hook</label>
                <textarea name="hook" id="f-hook" style="min-height:50px" placeholder="Primera frase / gafet inicial"></textarea>
            </div>

            <div class="form-group">
                <label>Contingut</label>
                <textarea name="content" id="f-content" style="min-height:100px" placeholder="Guió, text o desenvolupament de la peça"></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>CTA</label>
                    <input type="text" name="cta" id="f-cta" placeholder="Ex. Descobrir, Llegir, Reservar...">
                </div>
                <div class="form-group">
                    <label>Estat</label>
                    <select name="status" id="f-status">
                        <?php foreach ($statuses as $key => $s): ?><option value="<?= $key ?>"><?= htmlspecialchars($s['label']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Material necessari</label>
                <input type="text" name="material" id="f-material" placeholder="Fotos, arxiu, veu en off...">
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Reutilització / reaprofitament</label>
                    <input type="text" name="reuse_notes" id="f-reuse_notes" placeholder="En quins altres formats es pot reaprofitar">
                </div>
                <div class="form-group">
                    <label>Puntuació (0-100)</label>
                    <input type="number" name="score" id="f-score" min="0" max="100">
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="post-form-btn-label">Crear publicació</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetPostForm()">Cancel·lar</button>
            </div>
        </form>
    </div>
</div>

</div><!-- /grid -->
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
<style>
.import-box { max-height: 0; overflow: hidden; transition: max-height .2s ease; }
.import-box.open-import { max-height: 260px; }

.month-group { border-bottom: 1px solid var(--a-border); }
.month-group:last-child { border-bottom: none; }
.month-group summary {
    list-style: none; cursor: pointer; padding: 14px 20px; font-weight: 700; font-size: .88rem;
    display: flex; align-items: center; gap: 10px; user-select: none;
}
.month-group summary::-webkit-details-marker { display: none; }
.month-group summary::before { content: '▸'; color: #9ca3af; font-size: .75rem; transition: transform .15s; }
.month-group[open] summary::before { transform: rotate(90deg); }
.month-group-count { background: #f3f4f6; color: #6b7280; font-size: .7rem; font-weight: 700; padding: 1px 8px; border-radius: 100px; }
.month-archive-label { padding: 16px 20px 4px; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; }
.approval-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 20px; background: #f9fafb; border-top: 1px solid var(--a-border); border-bottom: 1px solid var(--a-border); }
</style>
<script>
function editPost(p) {
    document.getElementById('post-form-title').textContent = '✏️ Editar publicació';
    document.getElementById('post-form-btn-label').textContent = 'Guardar canvis';
    document.getElementById('f-id').value = p.id || '';
    document.getElementById('f-client_id').value = p.client_id || '';
    document.getElementById('f-date').value = p.date || '';
    document.getElementById('f-platform').value = p.platform || 'altres';
    document.getElementById('f-format').value = p.format || 'altres';
    document.getElementById('f-series').value = p.series || '';
    document.getElementById('f-objective').value = p.objective || '';
    document.getElementById('f-theme').value = p.theme || '';
    document.getElementById('f-hook').value = p.hook || '';
    document.getElementById('f-content').value = p.content || '';
    document.getElementById('f-cta').value = p.cta || '';
    document.getElementById('f-status').value = p.status || 'idea';
    document.getElementById('f-material').value = p.material || '';
    document.getElementById('f-reuse_notes').value = p.reuse_notes || '';
    document.getElementById('f-score').value = (p.score === '' || p.score === undefined) ? '' : p.score;
    document.getElementById('post-form-card').scrollIntoView({behavior:'smooth', block:'start'});
}
function resetPostForm() {
    document.getElementById('post-form-title').textContent = '➕ Nova publicació';
    document.getElementById('post-form-btn-label').textContent = 'Crear publicació';
    document.getElementById('post-form').reset();
    document.getElementById('f-id').value = '';
}
</script>
</body></html>
