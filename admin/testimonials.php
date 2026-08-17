<?php
// admin/testimonials.php
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $t = [
            'id'      => $_POST['id'] ?: generateId(),
            'active'  => isset($_POST['active']),
            'name'    => ['ca' => sanitize($_POST['name_ca'] ?? ''), 'es' => sanitize($_POST['name_es'] ?? '')],
            'company' => ['ca' => sanitize($_POST['company_ca'] ?? ''), 'es' => sanitize($_POST['company_es'] ?? '')],
            'text'    => ['ca' => sanitize($_POST['text_ca'] ?? ''), 'es' => sanitize($_POST['text_es'] ?? '')],
            'rating'  => 5,
        ];
        saveTestimonial($t);
        $success = 'Testimoni guardat.';
    }
    if ($action === 'delete') { deleteTestimonial($_POST['id']); header('Location: testimonials.php?deleted=1'); exit; }
    if ($action === 'toggle') {
        $all = getAdminTestimonials();
        foreach ($all as &$t) if ($t['id'] === $_POST['id']) $t['active'] = !($t['active'] ?? true);
        writeData('testimonials', $all);
        syncTestimonialsToConfig();
        header('Location: testimonials.php'); exit;
    }
}

$testimonials  = getAdminTestimonials();
$page_title    = 'Testimonis';
$page_subtitle = count($testimonials) . ' testimonis';
$topbar_action_url = '#new-form';
$topbar_action_label = 'Afegir testimoni';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Testimonis · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?><div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= $success ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Testimoni eliminat.</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start">

<!-- Llista -->
<div class="card">
    <div class="card-header"><div class="card-title">Tots els testimonis</div></div>
    <?php if (empty($testimonials)): ?>
    <div style="padding:40px;text-align:center;color:#9ca3af">Cap testimoni. Afegeix el primer al formulari de la dreta.</div>
    <?php else: ?>
    <div class="form-grid" style="padding:16px;gap:10px">
    <?php foreach ($testimonials as $t): ?>
    <div style="background:<?= ($t['active']??true) ? '#f7f6f3' : '#fafafa' ?>;border:1px solid <?= ($t['active']??true) ? '#e5e7eb' : '#e5e7eb' ?>;border-radius:10px;padding:16px;display:flex;gap:14px;align-items:flex-start;">
        <div style="width:42px;height:42px;border-radius:50%;background:#0b1628;color:#c9a84c;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;flex-shrink:0"><?= strtoupper(substr($t['name']['ca'] ?? 'A', 0, 1)) ?></div>
        <div style="flex:1">
            <div style="font-weight:700;font-size:.9rem;color:#1a1f2e"><?= htmlspecialchars($t['name']['ca'] ?? '') ?></div>
            <div style="font-size:.78rem;color:#c9a84c"><?= htmlspecialchars($t['company']['ca'] ?? '') ?></div>
            <div style="font-size:.85rem;color:#6b7280;margin-top:6px;font-style:italic">"<?= htmlspecialchars(substr($t['text']['ca'] ?? '', 0, 100)) ?>..."</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0">
            <?php if ($t['active'] ?? true): ?><span class="badge badge-green">Visible</span><?php else: ?><span class="badge badge-gray">Ocult</span><?php endif; ?>
            <div style="display:flex;gap:4px">
                <form method="POST"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-sm btn-secondary"><?= ($t['active']??true) ? '🙈' : '👁' ?></button></form>
                <form method="POST" onsubmit="return confirm('Eliminar?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-sm btn-danger">🗑</button></form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Formulari -->
<div class="card" id="new-form">
    <div class="card-header"><div class="card-title">Afegir / editar testimoni</div></div>
    <div class="card-body">
    <form method="POST" class="form-grid">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="">
        <div class="form-group">
            <label>Nom (CA)</label>
            <input type="text" name="name_ca" placeholder="Maria García" required>
        </div>
        <div class="form-group">
            <label>Nom (ES)</label>
            <input type="text" name="name_es" placeholder="María García">
        </div>
        <div class="form-group">
            <label>Empresa (CA)</label>
            <input type="text" name="company_ca" placeholder="Empresa · Alacant">
        </div>
        <div class="form-group">
            <label>Empresa (ES)</label>
            <input type="text" name="company_es" placeholder="Empresa · Alicante">
        </div>
        <div class="form-group">
            <label>Text del testimoni (CA) *</label>
            <textarea name="text_ca" rows="4" placeholder="Testimoni en català..." required></textarea>
        </div>
        <div class="form-group">
            <label>Text del testimoni (ES)</label>
            <textarea name="text_es" rows="4" placeholder="Testimoni en castellà..."></textarea>
        </div>
        <div class="toggle-wrap">
            <label class="toggle"><input type="checkbox" name="active" checked><span class="toggle-slider"></span></label>
            <span class="toggle-label">Visible al frontend</span>
        </div>
        <button type="submit" class="btn btn-primary">Guardar testimoni</button>
    </form>
    </div>
</div>
</div>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
