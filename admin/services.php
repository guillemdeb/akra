<?php
// admin/services.php — Gestió de serveis
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id']);
        $svc = [
            'id'    => $id,
            'slug'  => sanitize($_POST['slug'] ?? ''),
            'order' => intval($_POST['order'] ?? 99),
            'active'=> isset($_POST['active']),
            'icon_svg' => $_POST['icon_svg'] ?? '',
            'title'    => ['ca' => sanitize($_POST['title_ca'] ?? ''), 'es' => sanitize($_POST['title_es'] ?? ''), 'en' => sanitize($_POST['title_en'] ?? '')],
            'desc_short' => ['ca' => sanitize($_POST['desc_ca'] ?? ''), 'es' => sanitize($_POST['desc_es'] ?? ''), 'en' => sanitize($_POST['desc_en'] ?? '')],
            'highlight' => !empty($_POST['badge_ca']) ? ['ca' => sanitize($_POST['badge_ca']), 'es' => sanitize($_POST['badge_es'] ?? ''), 'en' => sanitize($_POST['badge_en'] ?? '')] : null,
        ];
        saveService($svc);
        $success = 'Servei guardat i sincronitzat.';
    }
    if ($action === 'reorder') {
        $ids = array_map('intval', explode(',', $_POST['order'] ?? ''));
        reorderServices($ids);
        echo 'ok'; exit;
    }
    if ($action === 'toggle') {
        $services = getAdminServices();
        foreach ($services as &$s) if ($s['id'] == $_POST['id']) $s['active'] = !($s['active'] ?? true);
        writeData('services', $services);
        syncServicesToConfig();
        header('Location: services.php'); exit;
    }
}

$services = getAdminServices();
$edit_id  = isset($_GET['edit']) ? intval($_GET['edit']) : null;
$edit_svc = null;
if ($edit_id) foreach ($services as $s) if ($s['id'] == $edit_id) { $edit_svc = $s; break; }

$page_title    = 'Serveis';
$page_subtitle = 'Gestió dels serveis que s\'ofereix';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Serveis · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= $success ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr <?= $edit_svc ? '420px' : '0' ?>;gap:20px">

<div class="card">
    <div class="card-header">
        <div class="card-title">Serveis actuals <span style="font-weight:400;color:#9ca3af;font-size:.85rem">(arrossega per reordenar)</span></div>
    </div>
    <div id="services-list" style="padding:16px;display:flex;flex-direction:column;gap:8px">
    <?php foreach ($services as $s): ?>
    <div class="sortable-item" data-id="<?= $s['id'] ?>" style="background:<?= ($s['active']??true) ? 'white' : '#fafafa' ?>;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:14px;">
        <div class="drag-handle" style="cursor:grab;color:#d1d5db">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="9" y1="5" x2="9" y2="19"/><line x1="15" y1="5" x2="15" y2="19"/></svg>
        </div>
        <div style="width:40px;height:40px;background:#0b1628;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#c9a84c;flex-shrink:0"><?= $s['icon_svg'] ?></div>
        <div style="flex:1">
            <div style="font-weight:700;font-size:.9rem;color:#1a1f2e"><?= htmlspecialchars($s['title']['ca'] ?? '') ?></div>
            <div style="font-size:.8rem;color:#9ca3af"><?= htmlspecialchars(substr($s['desc_short']['ca'] ?? '', 0, 80)) ?>...</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <?php if ($s['active']??true): ?><span class="badge badge-green">Actiu</span><?php else: ?><span class="badge badge-gray">Ocult</span><?php endif; ?>
            <a href="services.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
            <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-sm btn-secondary"><?= ($s['active']??true) ? '🙈' : '👁' ?></button></form>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <div style="padding:12px 16px;border-top:1px solid #e5e7eb;font-size:.8rem;color:#9ca3af">
        💡 Els canvis d'ordre s'apliquen automàticament al frontend en arrossegar.
    </div>
</div>

<?php if ($edit_svc): ?>
<div class="card">
    <div class="card-header"><div class="card-title">Editar servei</div><a href="services.php" class="btn btn-sm btn-secondary">✕ Tancar</a></div>
    <div class="card-body">
    <form method="POST" class="form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $edit_svc['id'] ?>">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($edit_svc['slug'] ?? '') ?>">
        <input type="hidden" name="order" value="<?= $edit_svc['order'] ?? 99 ?>">
        <input type="hidden" name="icon_svg" value="<?= htmlspecialchars($edit_svc['icon_svg'] ?? '') ?>">
        
        <div class="form-group">
            <label>Títol (CA)</label>
            <input type="text" name="title_ca" value="<?= htmlspecialchars($edit_svc['title']['ca'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Títol (ES)</label>
            <input type="text" name="title_es" value="<?= htmlspecialchars($edit_svc['title']['es'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Títol (EN)</label>
            <input type="text" name="title_en" value="<?= htmlspecialchars($edit_svc['title']['en'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Descripció curta (CA)</label>
            <textarea name="desc_ca" rows="3"><?= htmlspecialchars($edit_svc['desc_short']['ca'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Descripció curta (ES)</label>
            <textarea name="desc_es" rows="3"><?= htmlspecialchars($edit_svc['desc_short']['es'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Badge / Etiqueta (opcional)</label>
            <input type="text" name="badge_ca" value="<?= htmlspecialchars($edit_svc['highlight']['ca'] ?? '') ?>" placeholder="Ex: Més demanat">
            <input type="text" name="badge_es" value="<?= htmlspecialchars($edit_svc['highlight']['es'] ?? '') ?>" placeholder="Ex: Más solicitado" style="margin-top:6px">
        </div>
        <div class="toggle-wrap">
            <label class="toggle"><input type="checkbox" name="active" <?= ($edit_svc['active']??true) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
            <span class="toggle-label">Visible al frontend</span>
        </div>
        <button type="submit" class="btn btn-primary">Guardar canvis</button>
    </form>
    </div>
</div>
<?php endif; ?>
</div>
</div></div>

<script>
// Drag & drop reorder (simple, sense llibreria externa)
const list = document.getElementById('services-list');
if (list) {
    let dragging = null;
    list.querySelectorAll('.sortable-item').forEach(item => {
        item.draggable = true;
        item.addEventListener('dragstart', e => { dragging = item; item.classList.add('dragging'); });
        item.addEventListener('dragend', e => {
            item.classList.remove('dragging');
            // Envia nou ordre al servidor
            const ids = [...list.querySelectorAll('.sortable-item')].map(i => i.dataset.id).join(',');
            const fd = new FormData();
            fd.append('action', 'reorder');
            fd.append('order', ids);
            fetch('services.php', { method: 'POST', body: fd });
        });
        item.addEventListener('dragover', e => {
            e.preventDefault();
            const rect = item.getBoundingClientRect();
            const mid  = rect.top + rect.height / 2;
            if (e.clientY < mid) list.insertBefore(dragging, item);
            else list.insertBefore(dragging, item.nextSibling);
        });
    });
}
</script>
<?php include 'includes/admin-footer.php'; ?>
