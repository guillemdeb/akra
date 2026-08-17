<?php
require_once 'includes/core.php';
requireLogin();

// ── ACCIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['id'] ?: generateId();
        
        // Puja imatge principal si n'hi ha
        $thumbnail = $_POST['thumbnail_current'] ?? '';
        if (!empty($_FILES['thumbnail']['name'])) {
            $upload = uploadImage($_FILES['thumbnail'], 'projects');
            if ($upload['ok']) $thumbnail = $upload['path'];
        }
        
        // Galeria: mantenir les existents + afegir noves
        $existing_gallery = json_decode($_POST['gallery_current'] ?? '[]', true) ?: [];
        // Eliminar les marcades per esborrar
        $delete_imgs = $_POST['delete_gallery'] ?? [];
        $existing_gallery = array_values(array_filter($existing_gallery, fn($g) => !in_array($g, $delete_imgs)));
        // Esborra fitxers físics
        foreach ($delete_imgs as $del) {
            $full = ADMIN_ROOT . '/../' . $del;
            if (file_exists($full)) @unlink($full);
        }
        // Afegir noves imatges de galeria
        if (!empty($_FILES['gallery']['name'][0])) {
            $existing_gallery = uploadGalleryImages($_FILES['gallery'], $existing_gallery);
        }
        
        $tech = array_filter(array_map('trim', explode(',', $_POST['tech'] ?? '')));
        
        $project = [
            'id'          => $id,
            'slug'        => $_POST['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $_POST['title_ca'] ?? $id)),
            'category'    => $_POST['category'] ?? 'web',
            'status'      => $_POST['status'] ?? 'active',
            'featured'    => isset($_POST['featured']),
            'active'      => isset($_POST['active']),
            'title'       => ['ca' => sanitize($_POST['title_ca'] ?? ''), 'es' => sanitize($_POST['title_es'] ?? ''), 'en' => sanitize($_POST['title_en'] ?? '')],
            'description' => ['ca' => sanitize($_POST['desc_ca'] ?? ''), 'es' => sanitize($_POST['desc_es'] ?? ''), 'en' => sanitize($_POST['desc_en'] ?? '')],
            'results'     => ['ca' => sanitize($_POST['results_ca'] ?? ''), 'es' => sanitize($_POST['results_es'] ?? ''), 'en' => sanitize($_POST['results_en'] ?? '')],
            'client'      => ['ca' => sanitize($_POST['client_ca'] ?? ''), 'es' => sanitize($_POST['client_es'] ?? '')],
            'thumbnail'   => $thumbnail,
            'gallery'     => $existing_gallery,
            'url'         => sanitize($_POST['url'] ?? ''),
            'demo_url'    => sanitize($_POST['demo_url'] ?? ''),
            'video'       => sanitize($_POST['video'] ?? ''),
            'tech'        => array_values($tech),
            'date'        => $_POST['date'] ?? date('Y-m'),
        ];
        saveProject($project);
        $success = 'Projecte guardat correctament.';
    }
    
    if ($action === 'delete') {
        deleteProject($_POST['id']);
        header('Location: projects.php?deleted=1');
        exit;
    }
}

// ── VISTA ──
$edit_id = $_GET['id'] ?? null;
$edit    = $edit_id ? getProjectById($edit_id) : null;
$is_new  = !$edit_id;

if ($edit_id && !$edit) { header('Location: projects.php'); exit; }
if ($is_new) $edit_id = null;

// Si no estem editant, mostrem la llista
if (!$edit_id && !isset($_GET['new'])):
    $projects = getAdminProjects();
    $page_title = 'Projectes';
    $page_subtitle = count($projects) . ' projectes al portfolio';
    $topbar_action_url = 'projects.php?new=1';
    $topbar_action_label = 'Nou projecte';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Projectes · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Projecte eliminat correctament.</div>
<?php endif; ?>
<?php if (isset($success)): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= $success ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Tots els projectes</div>
        <a href="projects.php?new=1" class="btn btn-primary btn-sm">+ Nou projecte</a>
    </div>
    <?php if (empty($projects)): ?>
    <div style="padding:48px;text-align:center;">
        <div style="width:80px;height:80px;border:2px dashed rgba(201,168,76,0.4);border-radius:16px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;color:#c9a84c;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        </div>
        <h3 style="font-family:'Syne',sans-serif;color:#1a1f2e;margin-bottom:8px">Cap projecte encara</h3>
        <p style="color:#6b7280;font-size:.9rem;margin-bottom:20px">Afegeix el primer projecte al portfolio.</p>
        <a href="projects.php?new=1" class="btn btn-primary">+ Afegir primer projecte</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Imatge</th><th>Projecte</th><th>Categoria</th><th>Estat</th><th>Destacat</th><th>Data</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
        <tr>
            <td>
                <?php if (!empty($p['thumbnail'])): ?>
                <img src="../<?= htmlspecialchars($p['thumbnail']) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
                <?php else: ?>
                <div style="width:60px;height:40px;background:#f3f4f6;border-radius:6px;border:1px dashed #d1d5db;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:.6rem">IMG</div>
                <?php endif; ?>
            </td>
            <td>
                <strong><?= htmlspecialchars($p['title']['ca'] ?? '') ?></strong>
                <?php if (!empty($p['url'])): ?>
                <div><a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" style="font-size:.75rem;color:#c9a84c">↗ <?= htmlspecialchars(parse_url($p['url'], PHP_URL_HOST) ?? '') ?></a></div>
                <?php endif; ?>
            </td>
            <td><?php
                $pt_all = getProjectTypes();
                $pt_map = array_column($pt_all, null, 'id');
                $pt = $pt_map[$p['category']] ?? null;
                $pt_label = $pt ? ($pt['label']['ca'] ?? $pt['id']) : htmlspecialchars($p['category']);
                echo '<span class="badge badge-gray">' . htmlspecialchars($pt_label) . '</span>';
            ?></td>
            <td>
                <?php match($p['status'] ?? '') {
                    'active'  => print('<span class="badge badge-green">Actiu</span>'),
                    'demo'    => print('<span class="badge badge-blue">Demo</span>'),
                    'concept' => print('<span class="badge badge-gold">Concepte</span>'),
                    'closed'  => print('<span class="badge badge-gray">Tancat</span>'),
                    default   => print('<span class="badge badge-gray">' . htmlspecialchars($p['status'] ?? '') . '</span>'),
                }; ?>
            </td>
            <td style="text-align:center"><?= ($p['featured'] ?? false) ? '⭐' : '—' ?></td>
            <td style="color:#9ca3af;font-size:.82rem"><?= htmlspecialchars($p['date'] ?? '') ?></td>
            <td>
                <div class="td-actions">
                    <a href="projects.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                    <form method="POST" onsubmit="return confirm('Eliminar aquest projecte?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
</div></div>
<?php include 'includes/admin-footer.php'; ?>
<?php else: // FORMULARI D'EDICIÓ
    $page_title    = $edit ? 'Editar projecte' : 'Nou projecte';
    $page_subtitle = $edit ? ($edit['title']['ca'] ?? '') : 'Afegir al portfolio';
    $p = $edit ?? ['id'=>'','slug'=>'','category'=>'web','status'=>'active','featured'=>false,'active'=>true,'title'=>[],'description'=>[],'results'=>[],'client'=>[],'thumbnail'=>'','url'=>'','video'=>'','tech'=>[],'date'=>date('Y-m')];
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= $success ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
    <input type="hidden" name="thumbnail_current" value="<?= htmlspecialchars($p['thumbnail']) ?>">
    <input type="hidden" name="gallery_current" value="<?= htmlspecialchars(json_encode($p['gallery'] ?? [])) ?>">

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <!-- Columna principal -->
    <div class="form-grid">
        <div class="card">
            <div class="card-header"><div class="card-title">Títol del projecte</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <div class="lang-tabs">
                        <button type="button" class="lang-tab active" onclick="switchLang('title','ca',this)">🇨🇦 CA</button>
                        <button type="button" class="lang-tab" onclick="switchLang('title','es',this)">🇪🇸 ES</button>
                        <button type="button" class="lang-tab" onclick="switchLang('title','en',this)">🇬🇧 EN</button>
                    </div>
                    <input type="text" name="title_ca" id="title-ca" class="lang-input active" value="<?= htmlspecialchars($p['title']['ca'] ?? '') ?>" placeholder="Nom del projecte en català">
                    <input type="text" name="title_es" id="title-es" class="lang-input" value="<?= htmlspecialchars($p['title']['es'] ?? '') ?>" placeholder="Nombre del proyecto en castellano">
                    <input type="text" name="title_en" id="title-en" class="lang-input" value="<?= htmlspecialchars($p['title']['en'] ?? '') ?>" placeholder="Project name in English">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Descripció</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <div class="lang-tabs">
                        <button type="button" class="lang-tab active" onclick="switchLang('desc','ca',this)">🇨🇦 CA</button>
                        <button type="button" class="lang-tab" onclick="switchLang('desc','es',this)">🇪🇸 ES</button>
                        <button type="button" class="lang-tab" onclick="switchLang('desc','en',this)">🇬🇧 EN</button>
                    </div>
                    <textarea name="desc_ca" id="desc-ca" class="lang-input active" rows="3" placeholder="Descripció en català"><?= htmlspecialchars($p['description']['ca'] ?? '') ?></textarea>
                    <textarea name="desc_es" id="desc-es" class="lang-input" rows="3" placeholder="Descripción en castellano"><?= htmlspecialchars($p['description']['es'] ?? '') ?></textarea>
                    <textarea name="desc_en" id="desc-en" class="lang-input" rows="3" placeholder="Description in English"><?= htmlspecialchars($p['description']['en'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Resultat / Mètrica destacada</div></div>
            <div class="card-body">
                <div class="form-group">
                    <div class="lang-tabs">
                        <button type="button" class="lang-tab active" onclick="switchLang('results','ca',this)">🇨🇦 CA</button>
                        <button type="button" class="lang-tab" onclick="switchLang('results','es',this)">🇪🇸 ES</button>
                    </div>
                    <input type="text" name="results_ca" id="results-ca" class="lang-input active" value="<?= htmlspecialchars($p['results']['ca'] ?? '') ?>" placeholder="Exemple: +340% de visites en 3 mesos">
                    <input type="text" name="results_es" id="results-es" class="lang-input" value="<?= htmlspecialchars($p['results']['es'] ?? '') ?>" placeholder="Ejemplo: +340% de visitas en 3 meses">
                    <p class="hint">Apareix com a métrica verda al portfolio. Deixa-ho buit si no n'hi ha.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Tecnologies usades</div></div>
            <div class="card-body">
                <div class="form-group">
                    <input type="text" name="tech" value="<?= htmlspecialchars(implode(', ', $p['tech'] ?? [])) ?>" placeholder="WordPress, SEO, WooCommerce, PHP...">
                    <p class="hint">Separades per comes.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral -->
    <div class="form-grid">
        <div class="card">
            <div class="card-header"><div class="card-title">Estat i visibilitat</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Categoria / Tipus</label>
                    <select name="category">
                        <?php foreach (getActiveProjectTypes() as $pt):
                            $label = $pt['label']['ca'] ?? $pt['label']['es'] ?? $pt['id'];
                        ?>
                        <option value="<?= htmlspecialchars($pt['id']) ?>" <?= ($p['category'] ?? '') === $pt['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint"><a href="project-types.php" target="_blank" style="color:var(--a-accent)">Gestionar tipus →</a></p>
                </div>
                <div class="form-group">
                    <label>Estat del projecte</label>
                    <select name="status">
                        <?php foreach (['active'=>'✅ Actiu','demo'=>'🔵 Demo','concept'=>'🟡 Concepte','closed'=>'⚫ Tancat'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= ($p['status'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data del projecte</label>
                    <input type="month" name="date" value="<?= htmlspecialchars($p['date'] ?? date('Y-m')) ?>">
                </div>
                <div class="toggle-wrap">
                    <label class="toggle"><input type="checkbox" name="featured" <?= ($p['featured'] ?? false) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    <span class="toggle-label">⭐ Destacat a l'inici</span>
                </div>
                <div class="toggle-wrap">
                    <label class="toggle"><input type="checkbox" name="active" <?= ($p['active'] ?? true) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    <span class="toggle-label">Visible al portfolio</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Imatge principal</div></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="img-upload" for="thumbnail" id="img-label">
                        <?php if (!empty($p['thumbnail'])): ?>
                        <img src="../<?= htmlspecialchars($p['thumbnail']) ?>" class="img-preview" id="img-preview">
                        <?php else: ?>
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p id="img-preview">Fes clic o arrossega una imatge</p>
                        <span>JPG, PNG o WebP · màx 5MB</span>
                        <?php endif; ?>
                    </label>
                    <input type="file" name="thumbnail" id="thumbnail" accept="image/*" style="display:none" onchange="previewImg(this)">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Galeria d'imatges</div>
                <span style="font-size:.78rem;color:#6b7280">Per a disseny gràfic o projectes sense URL</span>
            </div>
            <div class="card-body">
                <?php $gallery = $p['gallery'] ?? []; ?>
                <?php if (!empty($gallery)): ?>
                <div class="gallery-admin-grid" id="gallery-admin-grid">
                    <?php foreach ($gallery as $img): ?>
                    <div class="gallery-admin-item">
                        <img src="../<?= htmlspecialchars($img) ?>" alt="">
                        <label class="gallery-delete-check" title="Eliminar aquesta imatge">
                            <input type="checkbox" name="delete_gallery[]" value="<?= htmlspecialchars($img) ?>">
                            <span>🗑</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:.78rem;color:#ef4444;margin-bottom:12px">Marca les imatges que vols eliminar i guarda el projecte.</p>
                <?php endif; ?>
                <label class="img-upload" for="gallery-input" style="min-height:80px;padding:16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <p style="margin:0;font-size:.9rem">Afegir imatges a la galeria</p>
                    <span>Pots seleccionar múltiples imatges · JPG, PNG, WebP</span>
                </label>
                <input type="file" name="gallery[]" id="gallery-input" accept="image/*" multiple style="display:none" onchange="previewGallery(this)">
                <div id="gallery-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Enllaços</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>URL del projecte</label>
                    <input type="url" name="url" value="<?= htmlspecialchars($p['url'] ?? '') ?>" placeholder="https://clienteweb.es">
                </div>
                <div class="form-group" id="demo-url-group" style="<?= ($p['category'] ?? '') === 'app' ? '' : 'display:none' ?>">
                    <label style="display:flex;align-items:center;gap:6px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        URL de Demo (iframe)
                    </label>
                    <input type="url" name="demo_url" value="<?= htmlspecialchars($p['demo_url'] ?? '') ?>" placeholder="https://demo.clienteweb.es">
                    <p class="hint">S'incrusta directament a la pàgina de projectes. Ha de permetre ser embeddada (sense X-Frame-Options).</p>
                </div>
                <div class="form-group">
                    <label>Vídeo (YouTube embed)</label>
                    <input type="url" name="video" value="<?= htmlspecialchars($p['video'] ?? '') ?>" placeholder="https://www.youtube.com/embed/ID">
                </div>
                <div class="form-group">
                    <label>Slug URL</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($p['slug'] ?? '') ?>" placeholder="nom-del-projecte">
                    <p class="hint">Deixa buit per generar automàticament.</p>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary" style="flex:1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar projecte
            </button>
            <a href="projects.php" class="btn btn-secondary">Cancel·lar</a>
        </div>
    </div>
    </div>
</form>

</div></div>
<?php include 'includes/admin-footer.php'; ?>
<script>
function switchLang(group, lang, btn) {
    document.querySelectorAll('#' + group + '-ca, #' + group + '-es, #' + group + '-en').forEach(el => el.classList.remove('active'));
    document.getElementById(group + '-' + lang)?.classList.add('active');
    btn.closest('.form-group').querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
}
function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const label = document.getElementById('img-label');
            label.innerHTML = '<img src="' + e.target.result + '" class="img-preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewGallery(input) {
    const preview = document.getElementById('gallery-preview');
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #e4e4e7';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}
// Mostra/amaga el camp demo_url segons la categoria
const catSelect = document.querySelector('select[name="category"]');
const demoGroup = document.getElementById('demo-url-group');
if (catSelect && demoGroup) {
    catSelect.addEventListener('change', () => {
        demoGroup.style.display = catSelect.value === 'app' ? '' : 'none';
    });
}
</script>
<?php endif; ?>
