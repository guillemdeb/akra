<?php
// admin/seo.php — Gestió SEO completa per pàgina
require_once 'includes/core.php';
requireLogin();

// Pàgines disponibles per a SEO
$pages = [
    'index' => ['ca' => 'Inici', 'es' => 'Inicio', 'path' => 'index.php'],
    'serveis' => ['ca' => 'Serveis', 'es' => 'Servicios', 'path' => 'pages/serveis.php'],
    'projectes' => ['ca' => 'Projectes', 'es' => 'Proyectos', 'path' => 'pages/projectes.php'],
    'nosaltres' => ['ca' => 'Nosaltres', 'es' => 'Nosotros', 'path' => 'pages/nosaltres.php'],
    'proces' => ['ca' => 'Procés', 'es' => 'Proceso', 'path' => 'pages/proces.php'],
    'contacte' => ['ca' => 'Contacte', 'es' => 'Contacto', 'path' => 'pages/contacte.php'],
    'bloc' => ['ca' => 'Bloc', 'es' => 'Blog', 'path' => 'pages/bloc.php'],
];

// Llegir SEO actual
$seo_data = readData('seo');
if (empty($seo_data)) {
    $seo_data = [];
    foreach ($pages as $key => $info) {
        $seo_data[$key] = [
            'title' => ['ca' => '', 'es' => '', 'en' => ''],
            'description' => ['ca' => '', 'es' => '', 'en' => ''],
            'keywords' => ['ca' => '', 'es' => '', 'en' => ''],
            'og_image' => '',
            'canonical' => '',
            'noindex' => false,
        ];
    }
}

// Guardar canvis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_key = $_POST['page_key'] ?? '';
    if (isset($pages[$page_key])) {
        $seo_data[$page_key] = [
            'title' => [
                'ca' => sanitize($_POST['title_ca'] ?? ''),
                'es' => sanitize($_POST['title_es'] ?? ''),
                'en' => sanitize($_POST['title_en'] ?? ''),
            ],
            'description' => [
                'ca' => sanitize($_POST['desc_ca'] ?? ''),
                'es' => sanitize($_POST['desc_es'] ?? ''),
                'en' => sanitize($_POST['desc_en'] ?? ''),
            ],
            'keywords' => [
                'ca' => sanitize($_POST['keywords_ca'] ?? ''),
                'es' => sanitize($_POST['keywords_es'] ?? ''),
                'en' => sanitize($_POST['keywords_en'] ?? ''),
            ],
            'og_image' => sanitize($_POST['og_image'] ?? ''),
            'canonical' => sanitize($_POST['canonical'] ?? ''),
            'noindex' => isset($_POST['noindex']),
        ];
        writeData('seo', $seo_data);
        syncSeoToConfig($seo_data);
        $success = 'SEO actualitzat per a "' . $pages[$page_key]['ca'] . '"';
    }
}

$current_page_key = $_GET['page'] ?? 'index';
if (!isset($pages[$current_page_key])) $current_page_key = 'index';

$page_seo = $seo_data[$current_page_key] ?? [];
$page_title = 'SEO & Meta Tags';
$page_subtitle = 'Optimització per a cercadors';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO · AKRA Admin</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .seo-tabs { display: flex; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
        .seo-tab { padding: 10px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; background: white; border: 1.5px solid var(--a-border); color: var(--a-muted); transition: all 0.15s; }
        .seo-tab:hover { border-color: var(--a-navy); color: var(--a-navy); }
        .seo-tab.active { background: var(--a-navy); color: white; border-color: var(--a-navy); }
        .seo-preview { background: white; border: 1px solid var(--a-border); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .seo-preview-google { font-family: Arial, sans-serif; max-width: 600px; }
        .seo-preview-title { color: #1a0dab; font-size: 1.1rem; font-weight: 400; line-height: 1.3; margin-bottom: 4px; cursor: pointer; }
        .seo-preview-title:hover { text-decoration: underline; }
        .seo-preview-url { color: #006621; font-size: 0.9rem; line-height: 1.3; margin-bottom: 4px; }
        .seo-preview-desc { color: #4d5156; font-size: 0.9rem; line-height: 1.5; }
        .char-count { font-size: 0.75rem; color: var(--a-muted); text-align: right; margin-top: 4px; }
        .char-count.warning { color: var(--a-orange); }
        .char-count.error { color: var(--a-red); }
    </style>
</head>
<body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= $success ?>
</div>
<?php endif; ?>

<!-- Selector de pàgina -->
<div class="seo-tabs">
    <?php foreach ($pages as $key => $info): ?>
    <a href="?page=<?= $key ?>" class="seo-tab <?= $current_page_key === $key ? 'active' : '' ?>">
        <?= $info['ca'] ?>
    </a>
    <?php endforeach; ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px; align-items: start;">
    <!-- Formulari SEO -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Meta tags per a: <?= $pages[$current_page_key]['ca'] ?></div>
        </div>
        <div class="card-body">
            <form method="POST" class="form-grid">
                <input type="hidden" name="page_key" value="<?= $current_page_key ?>">
                
                <!-- Títol -->
                <div class="form-group">
                    <label>Títol de la pàgina (CA) <span style="color: var(--a-muted); font-weight: 400;">— Recomanat: 50-60 caràcters</span></label>
                    <input type="text" name="title_ca" id="title-ca" value="<?= htmlspecialchars($page_seo['title']['ca'] ?? '') ?>" maxlength="70" placeholder="Títol en català">
                    <div class="char-count" id="count-title-ca">0 caràcters</div>
                </div>
                <div class="form-group">
                    <label>Títol de la pàgina (ES)</label>
                    <input type="text" name="title_es" id="title-es" value="<?= htmlspecialchars($page_seo['title']['es'] ?? '') ?>" maxlength="70" placeholder="Título en castellano">
                    <div class="char-count" id="count-title-es">0 caràcters</div>
                </div>
                <div class="form-group">
                    <label>Títol de la pàgina (EN)</label>
                    <input type="text" name="title_en" id="title-en" value="<?= htmlspecialchars($page_seo['title']['en'] ?? '') ?>" maxlength="70" placeholder="Title in English">
                    <div class="char-count" id="count-title-en">0 caràcters</div>
                </div>

                <!-- Descripció -->
                <div class="form-group">
                    <label>Meta descripció (CA) <span style="color: var(--a-muted); font-weight: 400;">— Recomanat: 150-160 caràcters</span></label>
                    <textarea name="desc_ca" id="desc-ca" rows="3" maxlength="170" placeholder="Descripció en català"><?= htmlspecialchars($page_seo['description']['ca'] ?? '') ?></textarea>
                    <div class="char-count" id="count-desc-ca">0 caràcters</div>
                </div>
                <div class="form-group">
                    <label>Meta descripció (ES)</label>
                    <textarea name="desc_es" id="desc-es" rows="3" maxlength="170" placeholder="Descripción en castellano"><?= htmlspecialchars($page_seo['description']['es'] ?? '') ?></textarea>
                    <div class="char-count" id="count-desc-es">0 caràcters</div>
                </div>
                <div class="form-group">
                    <label>Meta descripció (EN)</label>
                    <textarea name="desc_en" id="desc-en" rows="3" maxlength="170" placeholder="Description in English"><?= htmlspecialchars($page_seo['description']['en'] ?? '') ?></textarea>
                    <div class="char-count" id="count-desc-en">0 caràcters</div>
                </div>

                <!-- Keywords -->
                <div class="form-group">
                    <label>Keywords (CA) <span style="color: var(--a-muted); font-weight: 400;">— Separades per comes</span></label>
                    <input type="text" name="keywords_ca" value="<?= htmlspecialchars($page_seo['keywords']['ca'] ?? '') ?>" placeholder="agencia web alacant, disseny web, seo local...">
                </div>
                <div class="form-group">
                    <label>Keywords (ES)</label>
                    <input type="text" name="keywords_es" value="<?= htmlspecialchars($page_seo['keywords']['es'] ?? '') ?>" placeholder="agencia web alicante, diseño web, seo local...">
                </div>
                <div class="form-group">
                    <label>Keywords (EN)</label>
                    <input type="text" name="keywords_en" value="<?= htmlspecialchars($page_seo['keywords']['en'] ?? '') ?>" placeholder="web agency alicante, web design, local seo...">
                </div>

                <!-- Open Graph Image -->
                <div class="form-group">
                    <label>Imatge Open Graph (OG:image)</label>
                    <input type="text" name="og_image" value="<?= htmlspecialchars($page_seo['og_image'] ?? '') ?>" placeholder="assets/img/og-image.jpg">
                    <p class="hint">Recomanat: 1200x630px. Deixa buit per usar la imatge per defecte.</p>
                </div>

                <!-- Canonical -->
                <div class="form-group">
                    <label>URL Canònica (opcional)</label>
                    <input type="url" name="canonical" value="<?= htmlspecialchars($page_seo['canonical'] ?? '') ?>" placeholder="https://akratechstudio.es/pagina">
                    <p class="hint">Deixa buit per usar l'URL automàtica.</p>
                </div>

                <!-- Noindex -->
                <div class="toggle-wrap">
                    <label class="toggle">
                        <input type="checkbox" name="noindex" <?= ($page_seo['noindex'] ?? false) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label">🔒 No indexar aquesta pàgina (noindex)</span>
                </div>

                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Guardar canvis SEO
                </button>
            </form>
        </div>
    </div>

    <!-- Previsualització -->
    <div>
        <div class="seo-preview">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--a-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
                Previsualització Google
            </div>
            <div class="seo-preview-google">
                <div class="seo-preview-title" id="preview-title">Títol de la pàgina</div>
                <div class="seo-preview-url"><?= SITE_URL ?>/<?= $pages[$current_page_key]['path'] ?></div>
                <div class="seo-preview-desc" id="preview-desc">Descripció de la pàgina que apareixerà als resultats de cerca de Google...</div>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <div class="card-title">💡 Consells SEO</div>
            </div>
            <div class="card-body" style="font-size: 0.85rem; color: var(--a-muted); line-height: 1.7;">
                <ul style="padding-left: 16px; margin: 0;">
                    <li style="margin-bottom: 8px;"><strong>Títol:</strong> 50-60 caràcters. Inclou "Alacant" o "Alicante".</li>
                    <li style="margin-bottom: 8px;"><strong>Descripció:</strong> 150-160 caràcters. Crida a l'acció clara.</li>
                    <li style="margin-bottom: 8px;"><strong>Keywords:</strong> 3-5 termes rellevants separats per comes.</li>
                    <li>Utilitza paraules clau locals: "Alacant", "Costa Blanca", "Comunitat Valenciana".</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Comptadors de caràcters i previsualització en temps real
function updateCounts() {
    const fields = ['title-ca', 'title-es', 'title-en', 'desc-ca', 'desc-es', 'desc-en'];
    fields.forEach(id => {
        const input = document.getElementById(id);
        const countEl = document.getElementById('count-' + id);
        if (input && countEl) {
            const len = input.value.length;
            const max = input.tagName === 'TEXTAREA' ? 170 : 70;
            const recommended = input.tagName === 'TEXTAREA' ? 160 : 60;
            
            countEl.textContent = len + ' caràcters';
            countEl.className = 'char-count';
            
            if (len > max) countEl.classList.add('error');
            else if (len > recommended) countEl.classList.add('warning');
        }
    });
    
    // Actualitzar previsualització
    const titleCa = document.getElementById('title-ca')?.value || 'Títol de la pàgina';
    const descCa = document.getElementById('desc-ca')?.value || 'Descripció de la pàgina...';
    
    document.getElementById('preview-title').textContent = titleCa;
    document.getElementById('preview-desc').textContent = descCa;
}

// Escuchar canvis
document.querySelectorAll('input, textarea').forEach(el => {
    el.addEventListener('input', updateCounts);
});

// Inicialitzar
updateCounts();
</script>

<?php include 'includes/admin-footer.php'; ?>