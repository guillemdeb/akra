<?php
require_once 'includes/core.php';
requireLogin();

// ── ACCIONS ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = $_POST['id'] ?: generateId();

        // Imatge destacada
        $cover = $_POST['cover_current'] ?? '';
        if (!empty($_FILES['cover']['name'])) {
            $upload = uploadImage($_FILES['cover'], 'blog');
            if ($upload['ok']) $cover = $upload['path'];
        }

        $slug = $_POST['slug'] ?: slugify($_POST['title_ca'] ?? $id);

        // Comprova slug únic
        $existing = getPost($slug);
        if ($existing && $existing['id'] !== $id) $slug = $slug . '-' . substr($id, 0, 6);

        $post = [
            'id'          => $id,
            'slug'        => $slug,
            'published'   => isset($_POST['published']),
            'featured'    => isset($_POST['featured']),
            'date'        => $_POST['date'] ?: date('Y-m-d'),
            'category'    => $_POST['category'] ?? 'disseny-web',
            'read_mins'   => (int)($_POST['read_mins'] ?? 5),
            'cover'       => $cover,
            'title'       => [
                'ca' => sanitize($_POST['title_ca'] ?? ''),
                'es' => sanitize($_POST['title_es'] ?? ''),
                'en' => sanitize($_POST['title_en'] ?? ''),
            ],
            'excerpt'     => [
                'ca' => sanitize($_POST['excerpt_ca'] ?? ''),
                'es' => sanitize($_POST['excerpt_es'] ?? ''),
            ],
            'body'        => [
                'ca' => $_POST['body_ca'] ?? '',
                'es' => $_POST['body_es'] ?? '',
            ],
            'seo_title'       => sanitize($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize($_POST['seo_desc'] ?? ''),
        ];
        savePost($post);
        $success = 'Article guardat correctament.';
    }

    if ($action === 'toggle_publish') {
        $post = getPost($_POST['slug']);
        if ($post) {
            $post['published'] = !($post['published'] ?? false);
            savePost($post);
        }
        header('Location: blog.php');
        exit;
    }

    if ($action === 'delete') {
        deletePost($_POST['id']);
        header('Location: blog.php?deleted=1');
        exit;
    }
}

// ── VISTA ────────────────────────────────────────────────────────────────────
$edit_id = $_GET['id'] ?? null;
$edit    = $edit_id ? current(array_filter(getAdminPosts(), fn($p) => $p['id'] === $edit_id)) : null;
$is_new  = !$edit_id;
if ($edit_id && !$edit) { header('Location: blog.php'); exit; }

$cats = getCategories();

// ── LLISTA ───────────────────────────────────────────────────────────────────
if (!$edit_id && !isset($_GET['new'])):
    $posts = getAdminPosts();
    $page_title    = 'Blog';
    $page_subtitle = count($posts) . ' articles';
    $topbar_action_url   = 'blog.php?new=1';
    $topbar_action_label = 'Nou article';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Article eliminat.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Tots els articles</div>
        <a href="blog.php?new=1" class="btn btn-primary btn-sm">+ Nou article</a>
    </div>
    <?php if (empty($posts)): ?>
    <div style="padding:48px;text-align:center">
        <div style="font-size:3rem;margin-bottom:16px">✍️</div>
        <h3 style="font-family:'Syne',sans-serif;color:#1a1f2e;margin-bottom:8px">Cap article encara</h3>
        <p style="color:#6b7280;margin-bottom:20px">Crea el primer article del blog per atraure tràfic orgànic.</p>
        <a href="blog.php?new=1" class="btn btn-primary">+ Escriure primer article</a>
    </div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Portada</th><th>Títol</th><th>Categoria</th><th>Estat</th><th>Data</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($posts as $p): ?>
        <tr>
            <td>
                <?php if (!empty($p['cover'])): ?>
                <img src="../<?= htmlspecialchars($p['cover']) ?>" style="width:72px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #e4e4e7">
                <?php else: ?>
                <div style="width:72px;height:44px;background:#f4f4f5;border-radius:6px;border:1px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#9ca3af">IMG</div>
                <?php endif; ?>
            </td>
            <td>
                <strong><?= htmlspecialchars($p['title']['ca'] ?? '') ?></strong>
                <div style="font-size:.75rem;color:#9ca3af">/<?= htmlspecialchars($p['slug']) ?> · <?= $p['read_mins'] ?? 5 ?> min lectura</div>
            </td>
            <td><span class="badge badge-gray"><?= htmlspecialchars($cats[$p['category']]['ca'] ?? $p['category']) ?></span></td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_publish">
                    <input type="hidden" name="slug" value="<?= $p['slug'] ?>">
                    <button type="submit" class="badge <?= ($p['published'] ?? false) ? 'badge-green' : 'badge-gray' ?>" style="cursor:pointer;border:none;background:none;padding:0">
                        <?= ($p['published'] ?? false) ? '✅ Publicat' : '⚫ Esborrany' ?>
                    </button>
                </form>
            </td>
            <td style="color:#9ca3af;font-size:.82rem"><?= htmlspecialchars($p['date'] ?? '') ?></td>
            <td>
                <div class="td-actions">
                    <a href="blog.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                    <?php if ($p['published'] ?? false): ?>
                    <a href="../pages/bloc.php?post=<?= $p['slug'] ?>" target="_blank" class="btn btn-sm btn-secondary">👁 Veure</a>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('Eliminar aquest article?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
</div></div>
<?php include 'includes/admin-footer.php'; ?>

<?php else: // ── FORMULARI ────────────────────────────────────────────────────
$p = $edit ?? ['id'=>'','slug'=>'','published'=>false,'featured'=>false,'date'=>date('Y-m-d'),'category'=>'disseny-web','read_mins'=>5,'cover'=>'','title'=>[],'excerpt'=>[],'body'=>[],'seo_title'=>'','seo_description'=>''];
$page_title    = $edit ? 'Editar article' : 'Nou article';
$page_subtitle = $edit ? ($p['title']['ca'] ?? '') : 'Escriure nou post';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.editor-wrap { border: 1.5px solid var(--a-border); border-radius: 10px; overflow: hidden; }
.editor-toolbar {
    display: flex; flex-wrap: wrap; gap: 4px;
    padding: 10px 12px; background: var(--a-bg);
    border-bottom: 1px solid var(--a-border);
}
.editor-toolbar button {
    padding: 5px 10px; border-radius: 6px; font-size: .82rem; font-weight: 600;
    border: 1px solid var(--a-border); background: white; cursor: pointer;
    color: var(--a-text); transition: all .15s;
}
.editor-toolbar button:hover { background: var(--a-navy); color: white; border-color: var(--a-navy); }
.editor-toolbar .sep { width: 1px; background: var(--a-border); margin: 0 4px; align-self: stretch; }
.rich-editor {
    min-height: 320px; padding: 16px;
    font-family: 'DM Sans', sans-serif; font-size: .95rem; line-height: 1.75;
    outline: none; color: var(--a-text);
}
.rich-editor h2 { font-size: 1.3rem; font-weight: 700; margin: 1.2em 0 .5em; }
.rich-editor h3 { font-size: 1.1rem; font-weight: 600; margin: 1em 0 .4em; }
.rich-editor p  { margin: 0 0 .8em; }
.rich-editor ul, .rich-editor ol { margin: 0 0 .8em 1.4em; }
.rich-editor blockquote { border-left: 3px solid var(--a-gold); padding-left: 12px; color: var(--a-muted); margin: .8em 0; font-style: italic; }
.rich-editor a  { color: var(--a-blue); }
.char-count { font-size: .75rem; color: var(--a-muted); text-align: right; padding: 6px 12px; background: var(--a-bg); border-top: 1px solid var(--a-border); }
.seo-preview { padding: 16px; background: white; border: 1px solid var(--a-border); border-radius: 10px; }
.seo-preview__url { font-size: .78rem; color: #0d652d; margin-bottom: 4px; }
.seo-preview__title { font-size: 1.1rem; color: #1a0dab; font-weight: 500; margin-bottom: 4px; line-height: 1.3; }
.seo-preview__desc { font-size: .85rem; color: #4d5156; line-height: 1.5; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($success)): ?>
<div class="alert alert-success">✅ <?= $success ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
    <input type="hidden" name="cover_current" value="<?= htmlspecialchars($p['cover']) ?>">

    <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

    <!-- Columna principal -->
    <div class="form-grid">

        <!-- Títol -->
        <div class="card">
            <div class="card-header"><div class="card-title">Títol de l'article</div></div>
            <div class="card-body">
                <div class="form-group">
                    <div class="lang-tabs">
                        <button type="button" class="lang-tab active" onclick="switchLang('title','ca',this)">🇨🇦 CA</button>
                        <button type="button" class="lang-tab" onclick="switchLang('title','es',this)">🇪🇸 ES</button>
                        <button type="button" class="lang-tab" onclick="switchLang('title','en',this)">🇬🇧 EN</button>
                    </div>
                    <input type="text" name="title_ca" id="title-ca" class="lang-input active" value="<?= htmlspecialchars($p['title']['ca'] ?? '') ?>" placeholder="Títol en català" oninput="updateSlug(this.value);updateSeoPreview()">
                    <input type="text" name="title_es" id="title-es" class="lang-input" value="<?= htmlspecialchars($p['title']['es'] ?? '') ?>" placeholder="Título en castellano">
                    <input type="text" name="title_en" id="title-en" class="lang-input" value="<?= htmlspecialchars($p['title']['en'] ?? '') ?>" placeholder="Title in English">
                </div>
            </div>
        </div>

        <!-- Extracte -->
        <div class="card">
            <div class="card-header"><div class="card-title">Extracte (resum breu)</div></div>
            <div class="card-body">
                <div class="form-group">
                    <div class="lang-tabs">
                        <button type="button" class="lang-tab active" onclick="switchLang('excerpt','ca',this)">🇨🇦 CA</button>
                        <button type="button" class="lang-tab" onclick="switchLang('excerpt','es',this)">🇪🇸 ES</button>
                    </div>
                    <textarea name="excerpt_ca" id="excerpt-ca" class="lang-input active" rows="2" placeholder="2-3 frases que apareixeran al llistat del blog..." oninput="updateSeoPreview()"><?= htmlspecialchars($p['excerpt']['ca'] ?? '') ?></textarea>
                    <textarea name="excerpt_es" id="excerpt-es" class="lang-input" rows="2" placeholder="Extracto en castellano..."><?= htmlspecialchars($p['excerpt']['es'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Editor CA -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Contingut · Català</div>
                <span style="font-size:.75rem;color:#9ca3af">Editor ric</span>
            </div>
            <div class="card-body" style="padding:0">
                <div class="editor-wrap">
                    <div class="editor-toolbar">
                        <button type="button" onclick="fmt('bold')"><b>B</b></button>
                        <button type="button" onclick="fmt('italic')"><i>I</i></button>
                        <button type="button" onclick="fmt('underline')"><u>U</u></button>
                        <div class="sep"></div>
                        <button type="button" onclick="fmt('formatBlock','h2')">H2</button>
                        <button type="button" onclick="fmt('formatBlock','h3')">H3</button>
                        <button type="button" onclick="fmt('formatBlock','p')">¶</button>
                        <div class="sep"></div>
                        <button type="button" onclick="fmt('insertUnorderedList')">• Llista</button>
                        <button type="button" onclick="fmt('insertOrderedList')">1. Llista</button>
                        <button type="button" onclick="insertBlockquote()">❝ Cita</button>
                        <div class="sep"></div>
                        <button type="button" onclick="insertLink()">🔗 Link</button>
                    </div>
                    <div id="editor-ca" class="rich-editor" contenteditable="true"><?= $p['body']['ca'] ?? '' ?></div>
                    <div class="char-count"><span id="char-ca">0</span> caràcters</div>
                </div>
                <textarea name="body_ca" id="body-ca-hidden" style="display:none"></textarea>
            </div>
        </div>

        <!-- Editor ES -->
        <div class="card">
            <div class="card-header"><div class="card-title">Contingut · Castellà <span style="font-size:.75rem;color:#9ca3af;font-weight:400">(opcional)</span></div></div>
            <div class="card-body" style="padding:0">
                <div class="editor-wrap">
                    <div class="editor-toolbar">
                        <button type="button" onclick="fmtEs('bold')"><b>B</b></button>
                        <button type="button" onclick="fmtEs('italic')"><i>I</i></button>
                        <button type="button" onclick="fmtEs('formatBlock','h2')">H2</button>
                        <button type="button" onclick="fmtEs('formatBlock','h3')">H3</button>
                        <button type="button" onclick="fmtEs('formatBlock','p')">¶</button>
                        <button type="button" onclick="fmtEs('insertUnorderedList')">• Llista</button>
                        <button type="button" onclick="fmtEs('insertOrderedList')">1. Llista</button>
                    </div>
                    <div id="editor-es" class="rich-editor" contenteditable="true"><?= $p['body']['es'] ?? '' ?></div>
                    <div class="char-count"><span id="char-es">0</span> caràcters</div>
                </div>
                <textarea name="body_es" id="body-es-hidden" style="display:none"></textarea>
            </div>
        </div>

        <!-- SEO -->
        <div class="card">
            <div class="card-header"><div class="card-title">SEO de l'article</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Títol SEO <span style="color:#9ca3af;font-weight:400">(recomanat: 50-60 caràcters)</span></label>
                    <input type="text" name="seo_title" id="seo-title" maxlength="70" value="<?= htmlspecialchars($p['seo_title'] ?? '') ?>" placeholder="Deixa buit per usar el títol de l'article" oninput="updateSeoPreview()">
                </div>
                <div class="form-group">
                    <label>Meta descripció <span style="color:#9ca3af;font-weight:400">(recomanat: 120-160 caràcters)</span></label>
                    <textarea name="seo_desc" id="seo-desc" rows="2" maxlength="180" placeholder="Deixa buit per usar l'extracte" oninput="updateSeoPreview()"><?= htmlspecialchars($p['seo_description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label style="font-size:.78rem;font-weight:600;color:var(--a-muted);letter-spacing:.06em;text-transform:uppercase;display:block;margin-bottom:8px">Previsualització a Google</label>
                    <div class="seo-preview">
                        <div class="seo-preview__url" id="seo-url">akratechstudio.es/blog/<?= htmlspecialchars($p['slug'] ?? 'titol-article') ?></div>
                        <div class="seo-preview__title" id="seo-preview-title"><?= htmlspecialchars($p['seo_title'] ?: ($p['title']['ca'] ?? 'Títol de l\'article')) ?></div>
                        <div class="seo-preview__desc" id="seo-preview-desc"><?= htmlspecialchars($p['seo_description'] ?: ($p['excerpt']['ca'] ?? 'Descripció de l\'article...')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral -->
    <div class="form-grid">

        <div class="card">
            <div class="card-header"><div class="card-title">Publicació</div></div>
            <div class="card-body form-grid">
                <div class="toggle-wrap">
                    <label class="toggle"><input type="checkbox" name="published" <?= ($p['published'] ?? false) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    <span class="toggle-label">Publicat (visible al blog)</span>
                </div>
                <div class="toggle-wrap">
                    <label class="toggle"><input type="checkbox" name="featured" <?= ($p['featured'] ?? false) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                    <span class="toggle-label">⭐ Destacat a l'inici</span>
                </div>
                <div class="form-group">
                    <label>Data de publicació</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($p['date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label>Temps de lectura (minuts)</label>
                    <input type="number" name="read_mins" min="1" max="60" value="<?= (int)($p['read_mins'] ?? 5) ?>">
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <?php foreach ($cats as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($p['category'] ?? '') === $k ? 'selected' : '' ?>><?= $v['ca'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Slug URL</label>
                    <input type="text" name="slug" id="slug-input" value="<?= htmlspecialchars($p['slug'] ?? '') ?>" placeholder="titol-de-l-article">
                    <p class="hint">Es genera automàticament del títol.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Imatge de portada</div></div>
            <div class="card-body">
                <label class="img-upload" for="cover-input" id="cover-label">
                    <?php if (!empty($p['cover'])): ?>
                    <img src="../<?= htmlspecialchars($p['cover']) ?>" class="img-preview">
                    <?php else: ?>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <p>Imatge destacada</p>
                    <span>JPG, PNG, WebP · màx 5MB · rec. 1200×630px</span>
                    <?php endif; ?>
                </label>
                <input type="file" name="cover" id="cover-input" accept="image/*" style="display:none" onchange="previewCover(this)">
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary" style="flex:1" onclick="syncEditors()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar article
            </button>
            <a href="blog.php" class="btn btn-secondary">Cancel·lar</a>
        </div>
    </div>
    </div>
</form>
</div></div>
<?php include 'includes/admin-footer.php'; ?>

<script>
// Editor ric
function fmt(cmd, val) { document.getElementById('editor-ca').focus(); document.execCommand(cmd, false, val || null); updateChars(); }
function fmtEs(cmd, val) { document.getElementById('editor-es').focus(); document.execCommand(cmd, false, val || null); }
function insertBlockquote() {
    const sel = window.getSelection().toString();
    document.execCommand('insertHTML', false, '<blockquote>' + (sel || 'Cita aquí...') + '</blockquote>');
}
function insertLink() {
    const url = prompt('URL del link:');
    if (url) document.execCommand('createLink', false, url);
}

function updateChars() {
    document.getElementById('char-ca').textContent = document.getElementById('editor-ca').innerText.length;
    document.getElementById('char-es').textContent = document.getElementById('editor-es').innerText.length;
}
document.getElementById('editor-ca').addEventListener('input', updateChars);
document.getElementById('editor-es').addEventListener('input', updateChars);
updateChars();

function syncEditors() {
    document.getElementById('body-ca-hidden').value = document.getElementById('editor-ca').innerHTML;
    document.getElementById('body-es-hidden').value = document.getElementById('editor-es').innerHTML;
}

// Slug automàtic
function updateSlug(title) {
    const slugInput = document.getElementById('slug-input');
    if (slugInput.dataset.manual) return;
    const slug = title.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '').trim()
        .replace(/[\s-]+/g, '-');
    slugInput.value = slug;
    document.getElementById('seo-url').textContent = 'akratechstudio.es/blog/' + slug;
}
document.getElementById('slug-input').addEventListener('input', function() { this.dataset.manual = '1'; });

// SEO preview
function updateSeoPreview() {
    const title   = document.getElementById('seo-title').value || document.getElementById('title-ca').value || 'Títol de l\'article';
    const desc    = document.getElementById('seo-desc').value  || document.getElementById('excerpt-ca').value || 'Descripció de l\'article...';
    const slug    = document.getElementById('slug-input').value || 'titol-article';
    document.getElementById('seo-preview-title').textContent = title.substring(0, 60) + (title.length > 60 ? '...' : '');
    document.getElementById('seo-preview-desc').textContent  = desc.substring(0, 160) + (desc.length > 160 ? '...' : '');
    document.getElementById('seo-url').textContent = 'akratechstudio.es/blog/' + slug;
}

// Lang tabs
function switchLang(group, lang, btn) {
    document.querySelectorAll('#' + group + '-ca, #' + group + '-es, #' + group + '-en').forEach(el => el.classList.remove('active'));
    document.getElementById(group + '-' + lang)?.classList.add('active');
    btn.closest('.form-group').querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
}

// Portada preview
function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('cover-label').innerHTML = '<img src="' + e.target.result + '" class="img-preview">'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// Sync on submit
document.querySelector('form').addEventListener('submit', syncEditors);
</script>
<?php endif; ?>
