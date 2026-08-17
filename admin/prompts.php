<?php
// admin/prompts.php — Biblioteca de prompts d'IA (guarda el text del prompt
// i, per a cada prompt, un historial de resultats obtinguts en executar-lo).
require_once 'includes/core.php';
requireLogin();

if (isset($_GET['export_csv'])) {
    $csv = exportPromptsCsv();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="prompts-' . date('Y-m-d') . '.csv"');
    echo $csv;
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $error = 'El títol és obligatori.';
        } else {
            $prompt = [
                'id'          => $_POST['id'] ?: generateId(),
                'title'       => $title,
                'client_id'   => $_POST['client_id'] ?? '',
                'category'    => $_POST['category'] ?? 'altres',
                'prompt_text' => trim($_POST['prompt_text'] ?? ''),
                'notes'       => trim($_POST['notes'] ?? ''),
            ];
            savePrompt($prompt);
            header('Location: prompts.php?saved=1');
            exit;
        }
    }

    if ($action === 'delete') {
        deletePrompt($_POST['id'] ?? '');
        header('Location: prompts.php?deleted=1');
        exit;
    }
}

$clients   = getClients();
$categories = getPromptCategoryOptions();

$filter_client   = $_GET['client'] ?? '';
$filter_category = $_GET['category'] ?? '';
$prompts = getPrompts($filter_client ?: null);
if ($filter_category) $prompts = array_values(array_filter($prompts, fn($p) => ($p['category'] ?? '') === $filter_category));

// Prompt a editar (si venim amb ?edit=ID)
$edit_prompt = null;
if (!empty($_GET['edit'])) $edit_prompt = getPrompt($_GET['edit']);

$page_title    = 'Prompts';
$page_subtitle  = count($prompts) . ' prompt' . (count($prompts) !== 1 ? 's' : '') . ' guardats';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Prompts · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Prompt guardat.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Prompt eliminat.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filtres + exportar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <select name="client" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Tots els clients</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= htmlspecialchars($c['id']) ?>" <?= $filter_client === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category" style="font-size:.82rem;padding:7px 10px;border:1px solid var(--a-border);border-radius:8px">
                <option value="">Totes les categories</option>
                <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filter_category === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
            <?php if ($filter_client || $filter_category): ?><a href="prompts.php" style="font-size:.8rem;color:#6b7280">Netejar</a><?php endif; ?>
        </form>
        <a href="prompts.php?export_csv=1<?= $filter_client ? '&client=' . urlencode($filter_client) : '' ?>" class="btn btn-sm btn-secondary">⬇️ Exportar CSV</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 420px;gap:24px;align-items:start">

<!-- Llista de prompts -->
<div class="card">
    <div class="card-header">
        <div class="card-title">🧠 Prompts guardats</div>
    </div>
    <?php if (empty($prompts)): ?>
    <div style="padding:40px;text-align:center;color:#6b7280;font-size:.9rem">Cap prompt guardat encara. Crea'n un amb el formulari de la dreta.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Títol</th><th>Client</th><th>Categoria</th><th>Resultats</th><th>Actualitzat</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($prompts as $p):
            $client = !empty($p['client_id']) ? getClient($p['client_id']) : null;
            $n_results = count(getPromptResults($p['id']));
        ?>
        <tr>
            <td><strong><a href="prompt-view.php?id=<?= htmlspecialchars($p['id']) ?>" style="color:inherit;text-decoration:none"><?= htmlspecialchars($p['title']) ?></a></strong></td>
            <td><?= $client ? htmlspecialchars($client['name']) : '<span style="color:#9ca3af">—</span>' ?></td>
            <td><span class="badge badge-gray"><?= htmlspecialchars($categories[$p['category'] ?? ''] ?? $p['category'] ?? '') ?></span></td>
            <td style="text-align:center"><?= $n_results ?></td>
            <td style="color:#9ca3af;font-size:.8rem"><?= !empty($p['updated_at']) ? date('d/m/Y', strtotime($p['updated_at'])) : '—' ?></td>
            <td>
                <div class="td-actions">
                    <a href="prompt-view.php?id=<?= htmlspecialchars($p['id']) ?>" class="btn btn-sm btn-secondary">👁️ Obrir</a>
                    <a href="prompts.php?edit=<?= htmlspecialchars($p['id']) ?>#prompt-form" class="btn btn-sm btn-secondary">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar el prompt «<?= htmlspecialchars(addslashes($p['title'])) ?>» i tot el seu historial de resultats?')">
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
    <?php endif; ?>
</div>

<!-- Formulari crear/editar -->
<div class="card" id="prompt-form">
    <div class="card-header">
        <div class="card-title"><?= $edit_prompt ? '✏️ Editar prompt' : '➕ Nou prompt' ?></div>
    </div>
    <div class="card-body form-grid">
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_prompt['id'] ?? '') ?>">

            <div class="form-group">
                <label>Títol *</label>
                <input type="text" name="title" required placeholder="Ex. Director d'estratègia digital — Dari Trives" value="<?= htmlspecialchars($edit_prompt['title'] ?? '') ?>">
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label>Client</label>
                    <select name="client_id">
                        <option value="">— Sense client assignat —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= htmlspecialchars($c['id']) ?>" <?= ($edit_prompt['client_id'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($edit_prompt['category'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Text del prompt *</label>
                <textarea name="prompt_text" required style="min-height:220px;font-family:ui-monospace,monospace;font-size:.8rem" placeholder="Enganxa ací el prompt complet..."><?= htmlspecialchars($edit_prompt['prompt_text'] ?? '') ?></textarea>
                <p class="hint">Este és el prompt reutilitzable. Un cop guardat, podràs afegir-hi resultats (execucions) des de la fitxa del prompt.</p>
            </div>

            <div class="form-group">
                <label>Notes internes</label>
                <textarea name="notes" style="min-height:60px" placeholder="Notes d'ús, context, versió..."><?= htmlspecialchars($edit_prompt['notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:10px;margin-top:4px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $edit_prompt ? 'Guardar canvis' : 'Crear prompt' ?>
                </button>
                <?php if ($edit_prompt): ?><a href="prompts.php" class="btn btn-secondary">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

</div><!-- /grid -->
</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
</body></html>
