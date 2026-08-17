<?php
// admin/project-types.php — Gestió de tipus/categories de projectes
require_once 'includes/core.php';
requireLogin();

$success = '';
$error   = '';

// ── ACCIONS POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        // Nou tipus o editar existent
        $raw_id = trim($_POST['type_id'] ?? '');
        $is_new = !empty($_POST['is_new']);

        // Genera un id de slug net
        $id = $raw_id ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $_POST['label_ca'] ?? ''));
        $id = trim($id, '-');

        if (!$id) { $error = 'L\'ID no pot estar buit.'; }
        else {
            // Si és nou, comprova que l'id no existeixi
            if ($is_new) {
                $existing = array_column(getProjectTypes(), 'id');
                if (in_array($id, $existing)) { $error = "Ja existeix un tipus amb l'ID «$id»."; }
            }
            if (!$error) {
                $types = getProjectTypes();
                $max_order = empty($types) ? 0 : max(array_column($types, 'order'));
                $type = [
                    'id'     => $id,
                    'order'  => (int)($_POST['order'] ?? ($max_order + 1)),
                    'active' => isset($_POST['active']),
                    'label'  => [
                        'ca' => sanitize($_POST['label_ca'] ?? ''),
                        'es' => sanitize($_POST['label_es'] ?? ''),
                        'en' => sanitize($_POST['label_en'] ?? ''),
                        'fr' => sanitize($_POST['label_fr'] ?? ''),
                        'it' => sanitize($_POST['label_it'] ?? ''),
                    ],
                ];
                saveProjectType($type);
                $success = $is_new ? "Tipus «{$type['label']['ca']}» creat." : "Tipus «{$type['label']['ca']}» actualitzat.";
            }
        }
    }

    if ($action === 'delete') {
        $id = $_POST['type_id'] ?? '';
        // No deixar eliminar si hi ha projectes amb aquest tipus
        $projects_with_type = array_filter(getAdminProjects(), fn($p) => ($p['category'] ?? '') === $id);
        if (count($projects_with_type) > 0) {
            $error = "No es pot eliminar: hi ha " . count($projects_with_type) . " projecte(s) amb aquest tipus. Canvia'ls de tipus primer.";
        } else {
            deleteProjectType($id);
            $success = 'Tipus eliminat.';
        }
    }

    if ($action === 'reorder') {
        $ids = $_POST['order'] ?? [];
        if (is_array($ids)) reorderProjectTypes($ids);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'toggle') {
        $id    = $_POST['type_id'] ?? '';
        $types = getProjectTypes();
        foreach ($types as &$t) {
            if ($t['id'] === $id) { $t['active'] = !($t['active'] ?? true); break; }
        }
        writeData('project_types', $types);
        header('Location: project-types.php?saved=1'); exit;
    }
}

$types      = getProjectTypes();
$page_title    = 'Tipus de projecte';
$page_subtitle = 'Categories per classificar el portfolio';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tipus de projecte · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if ($success): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Canvis guardats.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

<!-- Llista de tipus -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🏷️ Tipus actuals</div>
        <span style="font-size:.78rem;color:#6b7280">Arrossega per reordenar</span>
    </div>
    <?php if (empty($types)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Cap tipus definit encara.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th style="width:36px"></th><th>Nom (CA · ES · EN)</th><th>ID</th><th style="width:80px">Ordre</th><th style="width:80px">Actiu</th><th>Accions</th></tr></thead>
        <tbody id="types-sortable">
        <?php foreach ($types as $t): 
            $projects_count = count(array_filter(getAdminProjects(), fn($p) => ($p['category'] ?? '') === $t['id']));
        ?>
        <tr data-id="<?= htmlspecialchars($t['id']) ?>" class="sortable-row">
            <td class="drag-handle" style="cursor:grab;color:#9ca3af;text-align:center;user-select:none">⠿</td>
            <td>
                <strong><?= htmlspecialchars($t['label']['ca'] ?? '') ?></strong>
                <span style="color:#9ca3af;font-size:.78rem"> · <?= htmlspecialchars($t['label']['es'] ?? '') ?> · <?= htmlspecialchars($t['label']['en'] ?? '') ?></span>
                <?php if ($projects_count > 0): ?>
                <div style="font-size:.72rem;color:#6b7280;margin-top:2px"><?= $projects_count ?> projecte<?= $projects_count !== 1 ? 's' : '' ?></div>
                <?php endif; ?>
            </td>
            <td><code style="font-size:.75rem;background:#f4f4f5;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($t['id']) ?></code></td>
            <td style="color:#9ca3af;font-size:.82rem;text-align:center"><?= $t['order'] ?></td>
            <td style="text-align:center">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="type_id" value="<?= htmlspecialchars($t['id']) ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.1rem" title="<?= ($t['active'] ?? true) ? 'Desactivar' : 'Activar' ?>">
                        <?= ($t['active'] ?? true) ? '🟢' : '⚫' ?>
                    </button>
                </form>
            </td>
            <td>
                <div class="td-actions">
                    <button type="button" class="btn btn-sm btn-secondary"
                        onclick="editType(<?= htmlspecialchars(json_encode($t)) ?>)">✏️ Editar</button>
                    <?php if ($projects_count === 0): ?>
                    <form method="POST" onsubmit="return confirm('Eliminar el tipus «<?= htmlspecialchars($t['label']['ca'] ?? '') ?>»?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="type_id" value="<?= htmlspecialchars($t['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:.72rem;color:#9ca3af" title="No es pot eliminar si té projectes">🔒</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Formulari crear/editar -->
<div class="card" id="type-form-card">
    <div class="card-header">
        <div class="card-title" id="form-card-title">➕ Nou tipus</div>
    </div>
    <div class="card-body form-grid">
        <form method="POST" id="type-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="is_new" id="form-is-new" value="1">
            <input type="hidden" name="order" id="form-order" value="">

            <div class="form-group">
                <label>ID intern <span style="color:#9ca3af;font-size:.75rem">(sense espais ni accents)</span></label>
                <input type="text" name="type_id" id="form-type-id" placeholder="disseny-grafic" pattern="[a-z0-9\-]+" required>
                <p class="hint">S'usa internament per classificar els projectes. No es pot canviar un cop creat.</p>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>🇪🇸 Castellà *</label>
                    <input type="text" name="label_es" id="form-label-es" placeholder="Diseño gráfico" required>
                </div>
                <div class="form-group">
                    <label>🏴 Català *</label>
                    <input type="text" name="label_ca" id="form-label-ca" placeholder="Disseny gràfic" required>
                </div>
                <div class="form-group">
                    <label>🇬🇧 Anglès</label>
                    <input type="text" name="label_en" id="form-label-en" placeholder="Graphic design">
                </div>
                <div class="form-group">
                    <label>🇫🇷 Francès</label>
                    <input type="text" name="label_fr" id="form-label-fr" placeholder="Design graphique">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label>🇮🇹 Italià</label>
                    <input type="text" name="label_it" id="form-label-it" placeholder="Design grafico">
                </div>
            </div>

            <div class="toggle-wrap" style="margin-top:4px">
                <label class="toggle"><input type="checkbox" name="active" id="form-active" checked><span class="toggle-slider"></span></label>
                <span class="toggle-label">Actiu (visible als filtres del portfolio)</span>
            </div>

            <div style="display:flex;gap:10px;margin-top:16px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="form-btn-label">Crear tipus</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel·lar</button>
            </div>
        </form>
    </div>
</div>

</div><!-- /grid -->
</div></div>

<?php include 'includes/admin-footer.php'; ?>

<script>
// ── Editar tipus ────────────────────────────────────────────────────────────
function editType(t) {
    document.getElementById('form-card-title').textContent = '✏️ Editar tipus';
    document.getElementById('form-is-new').value = '';
    document.getElementById('form-type-id').value = t.id;
    document.getElementById('form-type-id').readOnly = true;
    document.getElementById('form-type-id').style.opacity = '.5';
    document.getElementById('form-order').value = t.order;
    document.getElementById('form-label-ca').value = (t.label && t.label.ca) ? t.label.ca : '';
    document.getElementById('form-label-es').value = (t.label && t.label.es) ? t.label.es : '';
    document.getElementById('form-label-en').value = (t.label && t.label.en) ? t.label.en : '';
    document.getElementById('form-label-fr').value = (t.label && t.label.fr) ? t.label.fr : '';
    document.getElementById('form-label-it').value = (t.label && t.label.it) ? t.label.it : '';
    document.getElementById('form-active').checked = (t.active !== false);
    document.getElementById('form-btn-label').textContent = 'Guardar canvis';
    document.getElementById('type-form-card').scrollIntoView({behavior:'smooth',block:'start'});
}

function resetForm() {
    document.getElementById('form-card-title').textContent = '➕ Nou tipus';
    document.getElementById('form-is-new').value = '1';
    document.getElementById('form-type-id').value = '';
    document.getElementById('form-type-id').readOnly = false;
    document.getElementById('form-type-id').style.opacity = '1';
    document.getElementById('form-order').value = '';
    ['ca','es','en','fr','it'].forEach(l => document.getElementById('form-label-'+l).value = '');
    document.getElementById('form-active').checked = true;
    document.getElementById('form-btn-label').textContent = 'Crear tipus';
}

// Auto-genera ID des del nom en castellà
document.getElementById('form-label-es')?.addEventListener('input', function() {
    const idField = document.getElementById('form-type-id');
    if (!idField.readOnly && !idField.value) {
        idField.value = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    }
});

// ── Reordenar (drag & drop simple) ─────────────────────────────────────────
let dragSrc = null;
document.querySelectorAll('.sortable-row').forEach(row => {
    row.draggable = true;
    row.addEventListener('dragstart', e => { dragSrc = row; row.style.opacity = '.4'; });
    row.addEventListener('dragend',   e => { row.style.opacity = '1'; });
    row.addEventListener('dragover',  e => { e.preventDefault(); row.style.background = 'rgba(201,168,76,.08)'; });
    row.addEventListener('dragleave', e => { row.style.background = ''; });
    row.addEventListener('drop', e => {
        e.preventDefault();
        row.style.background = '';
        if (dragSrc === row) return;
        const tbody = row.parentNode;
        const rows  = [...tbody.children];
        const fromIdx = rows.indexOf(dragSrc);
        const toIdx   = rows.indexOf(row);
        if (fromIdx < toIdx) tbody.insertBefore(dragSrc, row.nextSibling);
        else tbody.insertBefore(dragSrc, row);
        // Envia nou ordre al servidor
        const ids = [...tbody.querySelectorAll('[data-id]')].map(r => r.dataset.id);
        const fd  = new FormData();
        fd.append('action', 'reorder');
        ids.forEach(id => fd.append('order[]', id));
        fetch('project-types.php', { method:'POST', body:fd });
    });
});
</script>
