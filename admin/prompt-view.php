<?php
// admin/prompt-view.php — Fitxa d'un prompt: text complet + historial de resultats
require_once 'includes/core.php';
requireLogin();

$id = $_GET['id'] ?? '';
$prompt = getPrompt($id);
if (!$prompt) { die('Prompt no trobat. <a href="prompts.php">Tornar</a>'); }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_result') {
        $result_text = trim($_POST['result_text'] ?? '');
        if ($result_text === '') {
            $error = 'El resultat no pot estar buit.';
        } else {
            savePromptResult([
                'id'          => $_POST['id'] ?: generateId(),
                'prompt_id'   => $prompt['id'],
                'title'       => trim($_POST['title'] ?? '') ?: ('Resultat del ' . date('d/m/Y')),
                'result_text' => $result_text,
            ]);
            header('Location: prompt-view.php?id=' . urlencode($prompt['id']) . '&saved=1');
            exit;
        }
    }

    if ($action === 'delete_result') {
        deletePromptResult($_POST['id'] ?? '');
        header('Location: prompt-view.php?id=' . urlencode($prompt['id']) . '&deleted=1');
        exit;
    }
}

$client  = !empty($prompt['client_id']) ? getClient($prompt['client_id']) : null;
$categories = getPromptCategoryOptions();
$results = getPromptResults($prompt['id']);

$edit_result = null;
if (!empty($_GET['edit_result'])) {
    foreach ($results as $r) if ($r['id'] === $_GET['edit_result']) { $edit_result = $r; break; }
}

$page_title    = htmlspecialchars($prompt['title']);
$page_subtitle = ($client ? $client['name'] . ' · ' : '') . ($categories[$prompt['category'] ?? ''] ?? '');
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($prompt['title']) ?> · Prompts · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<div style="margin-bottom:14px"><a href="prompts.php" style="font-size:.82rem;color:#6b7280;text-decoration:none">← Tornar a Prompts</a></div>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Resultat guardat.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Resultat eliminat.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Text del prompt -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title">📄 Text del prompt</div>
        <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-sm btn-secondary" onclick="copyPrompt()">📋 Copiar</button>
            <a href="prompts.php?edit=<?= htmlspecialchars($prompt['id']) ?>#prompt-form" class="btn btn-sm btn-secondary">✏️ Editar fitxa</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($prompt['notes'])): ?>
        <p class="hint" style="margin-bottom:10px">📝 <?= nl2br(htmlspecialchars($prompt['notes'])) ?></p>
        <?php endif; ?>
        <pre id="prompt-text" style="white-space:pre-wrap;font-family:ui-monospace,monospace;font-size:.8rem;line-height:1.6;background:#f9fafb;border:1px solid var(--a-border);border-radius:10px;padding:16px;max-height:420px;overflow:auto;margin:0"><?= htmlspecialchars($prompt['prompt_text']) ?></pre>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 420px;gap:24px;align-items:start">

<!-- Historial de resultats -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🗂️ Historial de resultats (<?= count($results) ?>)</div>
    </div>
    <?php if (empty($results)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Encara no hi ha cap resultat guardat per a este prompt. Enganxa'l al formulari de la dreta.</div>
    <?php else: ?>
    <div style="padding:14px;display:flex;flex-direction:column;gap:12px">
    <?php foreach ($results as $r): ?>
        <div style="border:1px solid var(--a-border);border-radius:10px;padding:14px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px">
                <div>
                    <strong style="font-size:.88rem"><?= htmlspecialchars($r['title'] ?? '') ?></strong>
                    <div style="font-size:.75rem;color:#9ca3af"><?= !empty($r['created_at']) ? date('d/m/Y H:i', strtotime($r['created_at'])) : '' ?></div>
                </div>
                <div class="td-actions">
                    <a href="prompt-view.php?id=<?= htmlspecialchars($prompt['id']) ?>&edit_result=<?= htmlspecialchars($r['id']) ?>#result-form" class="btn btn-sm btn-secondary">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar este resultat?')">
                        <input type="hidden" name="action" value="delete_result">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </div>
            <div class="result-text-collapsed" style="white-space:pre-wrap;font-size:.82rem;color:#374151;max-height:120px;overflow:hidden;position:relative;cursor:pointer" onclick="this.style.maxHeight = this.style.maxHeight === 'none' ? '120px' : 'none'"><?= htmlspecialchars($r['result_text'] ?? '') ?></div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Formulari afegir/editar resultat -->
<div class="card" id="result-form">
    <div class="card-header">
        <div class="card-title"><?= $edit_result ? '✏️ Editar resultat' : '➕ Afegir resultat' ?></div>
    </div>
    <div class="card-body form-grid">
        <form method="POST">
            <input type="hidden" name="action" value="save_result">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_result['id'] ?? '') ?>">

            <div class="form-group">
                <label>Etiqueta / data</label>
                <input type="text" name="title" placeholder="Ex. Idees de reels — agost 2026" value="<?= htmlspecialchars($edit_result['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Resultat *</label>
                <textarea name="result_text" required style="min-height:260px;font-size:.85rem" placeholder="Enganxa ací el resultat generat en executar el prompt..."><?= htmlspecialchars($edit_result['result_text'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:10px;margin-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $edit_result ? 'Guardar canvis' : 'Guardar resultat' ?>
                </button>
                <?php if ($edit_result): ?><a href="prompt-view.php?id=<?= htmlspecialchars($prompt['id']) ?>" class="btn btn-secondary">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

</div><!-- /grid -->
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
<script>
function copyPrompt() {
    const text = document.getElementById('prompt-text').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target.closest('button');
        const original = btn.innerHTML;
        btn.innerHTML = '✅ Copiat';
        setTimeout(() => btn.innerHTML = original, 1500);
    });
}
</script>
</body></html>
