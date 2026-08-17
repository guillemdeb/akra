<?php
// admin/media.php — Gestor de mitjans i arxius
require_once 'includes/core.php';
requireLogin();

$success = $error = '';
$current_tab = $_GET['tab'] ?? 'images';

// Directoris
$upload_dirs = [
    'images' => '../assets/img/',
    'projects' => '../assets/img/projects/',
    'logos' => '../assets/img/logos/',
    'icons' => '../assets/img/icons/',
];

// Crear directoris si no existeixen
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Pujar arxiu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $tab = $_POST['tab'] ?? 'images';
    $dir = $upload_dirs[$tab] ?? $upload_dirs['images'];
    
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
    $file = $_FILES['file'];
    
    if (!in_array($file['type'], $allowed)) {
        $error = 'Format no permès. Permesos: JPG, PNG, WebP, SVG, GIF';
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $error = 'Mida màxima: 10MB';
    } else {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        // Afegir timestamp si ja existeix
        if (file_exists($dir . $filename)) {
            $info = pathinfo($filename);
            $filename = $info['filename'] . '_' . time() . '.' . $info['extension'];
        }
        
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $success = 'Arxiu pujat: ' . $filename;
        } else {
            $error = 'Error en pujar l\'arxiu';
        }
    }
}

// Eliminar arxiu
if (isset($_GET['delete'])) {
    $tab = $_GET['tab'] ?? 'images';
    $dir = $upload_dirs[$tab] ?? $upload_dirs['images'];
    $file = basename($_GET['delete']);
    
    // Seguretat: només dins del directori permès
    $full_path = realpath($dir . $file);
    $real_dir = realpath($dir);
    
    if ($full_path && strpos($full_path, $real_dir) === 0 && file_exists($full_path)) {
        unlink($full_path);
        $success = 'Arxiu eliminat: ' . $file;
    } else {
        $error = 'No s\'ha pogut eliminar l\'arxiu';
    }
}

// Llistar arxius
function listFiles($dir) {
    $files = [];
    if (is_dir($dir)) {
        $iterator = new DirectoryIterator($dir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile() && !$fileinfo->isDot()) {
                $files[] = [
                    'name' => $fileinfo->getFilename(),
                    'size' => $fileinfo->getSize(),
                    'mtime' => $fileinfo->getMTime(),
                    'ext' => strtolower($fileinfo->getExtension()),
                ];
            }
        }
    }
    // Ordenar per data, més recent primer
    usort($files, fn($a, $b) => $b['mtime'] - $a['mtime']);
    return $files;
}

$files = listFiles($upload_dirs[$current_tab] ?? $upload_dirs['images']);

$page_title = 'Biblioteca de mitjans';
$page_subtitle = 'Gestiona imatges, logos i favicon';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitjans · AKRA Admin</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .media-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .media-tab { 
            padding: 10px 20px; border-radius: 8px; 
            background: white; border: 1.5px solid var(--a-border);
            font-size: 0.9rem; font-weight: 600; color: var(--a-muted);
            transition: all 0.15s;
        }
        .media-tab:hover { border-color: var(--a-navy); color: var(--a-navy); }
        .media-tab.active { background: var(--a-navy); color: white; border-color: var(--a-navy); }
        
        .upload-zone { 
            border: 2px dashed var(--a-border); border-radius: 12px; 
            padding: 40px; text-align: center; 
            background: var(--a-bg); transition: all 0.2s;
            margin-bottom: 24px;
        }
        .upload-zone:hover, .upload-zone.dragover { 
            border-color: var(--a-gold); background: rgba(201,168,76,0.05);
        }
        .upload-zone svg { color: var(--a-muted); margin-bottom: 12px; }
        .upload-zone p { color: var(--a-text); font-weight: 600; margin-bottom: 4px; }
        .upload-zone span { color: var(--a-muted); font-size: 0.85rem; }
        
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .media-item { 
            background: white; border-radius: 10px; border: 1.5px solid var(--a-border);
            overflow: hidden; transition: all 0.15s;
        }
        .media-item:hover { border-color: var(--a-navy); box-shadow: var(--shadow-md); }
        .media-thumb { 
            height: 140px; background: var(--a-bg); 
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .media-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .media-thumb svg { color: var(--a-muted); }
        .media-info { padding: 12px; }
        .media-name { 
            font-size: 0.85rem; font-weight: 600; color: var(--a-text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .media-meta { 
            font-size: 0.75rem; color: var(--a-muted); margin-top: 4px;
            display: flex; justify-content: space-between;
        }
        .media-actions { 
            display: flex; gap: 8px; padding: 0 12px 12px;
        }
        .media-btn { 
            flex: 1; padding: 6px; border-radius: 6px; 
            font-size: 0.75rem; font-weight: 600; text-align: center;
            border: 1.5px solid var(--a-border); background: white;
            cursor: pointer; transition: all 0.15s;
        }
        .media-btn:hover { background: var(--a-bg); }
        .media-btn.danger { color: var(--a-red); border-color: rgba(239,68,68,0.2); }
        .media-btn.danger:hover { background: var(--a-red); color: white; border-color: var(--a-red); }
        
        .special-files { 
            background: linear-gradient(135deg, rgba(37,99,235,0.05) 0%, rgba(37,99,235,0.02) 100%);
            border: 1px solid rgba(37,99,235,0.15); border-radius: 10px;
            padding: 16px; margin-bottom: 24px;
        }
        .special-files h4 { 
            font-size: 0.9rem; font-weight: 700; color: var(--a-navy); 
            margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
        }
        .special-file { 
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px; background: white; border-radius: 8px;
            margin-bottom: 8px; font-size: 0.85rem;
        }
        .special-file:last-child { margin-bottom: 0; }
        .special-file code { 
            background: var(--a-bg); padding: 4px 8px; border-radius: 4px;
            font-family: monospace; font-size: 0.8rem;
        }
        .special-file .status { 
            display: flex; align-items: center; gap: 6px;
            font-size: 0.8rem;
        }
        .status-ok { color: var(--a-green); }
        .status-missing { color: var(--a-red); }
    </style>
</head>
<body>
<?php include 'includes/layout.php'; ?>

<?php if ($success): ?>
<div class="alert alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= $success ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= $error ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="media-tabs">
    <a href="?tab=images" class="media-tab <?= $current_tab === 'images' ? 'active' : '' ?>">🖼️ Imatges generals</a>
    <a href="?tab=projects" class="media-tab <?= $current_tab === 'projects' ? 'active' : '' ?>">📁 Projectes</a>
    <a href="?tab=logos" class="media-tab <?= $current_tab === 'logos' ? 'active' : '' ?>">🏷️ Logos</a>
    <a href="?tab=icons" class="media-tab <?= $current_tab === 'icons' ? 'active' : '' ?>">🎯 Icones</a>
</div>

<!-- Arxius especials (solo en imatges generals) -->
<?php if ($current_tab === 'images'): ?>
<div class="special-files">
    <h4>🔑 Arxius especials del lloc</h4>
    <?php
    $special_files = [
        'logo.svg' => 'Logo principal (header)',
        'logo-white.svg' => 'Logo versió blanca (footer)',
        'favicon.svg' => 'Favicon del lloc',
        'favicon.ico' => 'Favicon ICO (legacy)',
        'og-image.jpg' => 'Imatge Open Graph (xarxes socials)',
        'hero-bg.jpg' => 'Fons del hero (opcional)',
    ];
    foreach ($special_files as $file => $desc):
        $exists = file_exists('../assets/img/' . $file);
    ?>
    <div class="special-file">
        <div>
            <strong><?= $file ?></strong>
            <span style="color: var(--a-muted); margin-left: 8px;"><?= $desc ?></span>
        </div>
        <div class="status <?= $exists ? 'status-ok' : 'status-missing' ?>">
            <?php if ($exists): ?>
            ✅ Present
            <?php else: ?>
            ❌ Falta
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <p style="font-size: 0.8rem; color: var(--a-muted); margin-top: 12px; margin-bottom: 0;">
        💡 Per canviar aquests arxius, puja un arxiu amb el mateix nom i extensió.
    </p>
</div>
<?php endif; ?>

<!-- Zona de pujada -->
<form method="POST" enctype="multipart/form-data" id="upload-form">
    <input type="hidden" name="tab" value="<?= $current_tab ?>">
    <div class="upload-zone" id="drop-zone">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <p>Arrossega arxius aquí o fes clic per seleccionar</p>
        <span>Formats: JPG, PNG, WebP, SVG, GIF · Màx: 10MB</span>
        <input type="file" name="file" id="file-input" style="display: none;" accept="image/*">
    </div>
</form>

<!-- Grid d'imatges -->
<?php if (empty($files)): ?>
<div class="card" style="padding: 60px; text-align: center; color: var(--a-muted);">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 16px; opacity: 0.5;">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/>
        <polyline points="21 15 16 10 5 21"/>
    </svg>
    <p style="font-size: 1.1rem; font-weight: 600; color: var(--a-text); margin-bottom: 8px;">No hi ha arxius</p>
    <p>Puja la primera imatge arrossegant-la a la zona de dalt</p>
</div>
<?php else: ?>
<div class="media-grid">
    <?php foreach ($files as $file): 
        $is_image = in_array($file['ext'], ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $path = 'assets/img/' . ($current_tab !== 'images' ? $current_tab . '/' : '') . $file['name'];
    ?>
    <div class="media-item">
        <div class="media-thumb">
            <?php if ($is_image): ?>
            <img src="../<?= $path ?>" alt="<?= htmlspecialchars($file['name']) ?>" loading="lazy">
            <?php else: ?>
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <?php endif; ?>
        </div>
        <div class="media-info">
            <div class="media-name" title="<?= htmlspecialchars($file['name']) ?>"><?= htmlspecialchars($file['name']) ?></div>
            <div class="media-meta">
                <span><?= number_format($file['size'] / 1024, 1) ?> KB</span>
                <span><?= date('d/m/Y', $file['mtime']) ?></span>
            </div>
        </div>
        <div class="media-actions">
            <button class="media-btn" onclick="copyPath('<?= $path ?>')">📋 Copiar ruta</button>
            <a href="?tab=<?= $current_tab ?>&delete=<?= urlencode($file['name']) ?>" class="media-btn danger" onclick="return confirm('Eliminar <?= htmlspecialchars($file['name']) ?>?')">🗑️</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Drag & drop
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const uploadForm = document.getElementById('upload-form');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        uploadForm.submit();
    }
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length) uploadForm.submit();
});

// Copiar ruta
function copyPath(path) {
    navigator.clipboard.writeText(path).then(() => {
        alert('Ruta copiada: ' + path);
    });
}
</script>

<?php include 'includes/admin-footer.php'; ?>