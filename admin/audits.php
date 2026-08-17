<?php
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = $_POST['id'] ?: generateId();
        $audit = [
            'id'        => $id,
            'client_id' => $_POST['client_id'] ?? '',
            'date'      => $_POST['date'] ?? date('Y-m-d'),
            'url'       => sanitize($_POST['url'] ?? ''),
            'lang'      => in_array($_POST['lang'] ?? '', ['ca', 'es']) ? $_POST['lang'] : 'ca',
            'cms'       => $_POST['cms'] ?? '',
            'estado'    => $_POST['estado'] ?? 'pendiente',
            'problemas_detectados' => $_POST['problemas'] ?? [],

            // 1. Resum executiu
            'valoracio_general' => (int)($_POST['valoracio_general'] ?? 5),
            'fortaleses'  => sanitize($_POST['fortaleses'] ?? ''),
            'debilitats'  => sanitize($_POST['debilitats'] ?? ''),
            'prioritats'  => sanitize($_POST['prioritats'] ?? ''),

            // 2-10. Àrees amb puntuació + notes
            'score_disseny' => (int)($_POST['score_disseny'] ?? 5), 'notes_disseny' => sanitize($_POST['notes_disseny'] ?? ''),
            'score_ux'      => (int)($_POST['score_ux'] ?? 5),      'notes_ux'      => sanitize($_POST['notes_ux'] ?? ''),
            'punts_abandonament' => sanitize($_POST['punts_abandonament'] ?? ''),
            'score_mobile'  => (int)($_POST['score_mobile'] ?? 5), 'notes_mobile'  => sanitize($_POST['notes_mobile'] ?? ''),
            'score_velocitat' => (int)($_POST['score_velocitat'] ?? 5), 'notes_velocitat' => sanitize($_POST['notes_velocitat'] ?? ''),
            'score_seo'     => (int)($_POST['score_seo'] ?? 5),     'notes_seo'     => sanitize($_POST['notes_seo'] ?? ''),
            'score_contingut' => (int)($_POST['score_contingut'] ?? 5), 'notes_contingut' => sanitize($_POST['notes_contingut'] ?? ''),
            'score_accessibilitat' => (int)($_POST['score_accessibilitat'] ?? 5), 'notes_accessibilitat' => sanitize($_POST['notes_accessibilitat'] ?? ''),
            'score_seguretat' => (int)($_POST['score_seguretat'] ?? 5), 'notes_seguretat' => sanitize($_POST['notes_seguretat'] ?? ''),
            'score_conversio' => (int)($_POST['score_conversio'] ?? 5), 'notes_conversio' => sanitize($_POST['notes_conversio'] ?? ''),
            'score_imatge_corporativa' => (int)($_POST['score_imatge_corporativa'] ?? 5),

            // 11. Competència
            'notes_competencia' => sanitize($_POST['notes_competencia'] ?? ''),

            // 12. Pla d'acció
            'accions_critiques'    => sanitize($_POST['accions_critiques'] ?? ''),
            'accions_importants'   => sanitize($_POST['accions_importants'] ?? ''),
            'accions_recomanables' => sanitize($_POST['accions_recomanables'] ?? ''),
            'accions_creixement'   => sanitize($_POST['accions_creixement'] ?? ''),

            // 13. Taula resum (Problema | Impacte | Dificultat | Prioritat | Solució)
            'taula_resum' => sanitize($_POST['taula_resum'] ?? ''),

            // 14. Conclusió professional
            'conclusio' => sanitize($_POST['conclusio'] ?? ''),
        ];
        saveAudit($audit);
        advanceClientStage($audit['client_id'], 'auditoria');
        header('Location: audits.php?saved=1&id=' . $id); exit;
    }

    if ($action === 'delete') {
        deleteAudit($_POST['id']);
        header('Location: audits.php?deleted=1'); exit;
    }

    if ($action === 'grant_access') {
        $client = getClient($_POST['client_id'] ?? '');
        $cred = generateAuditAccess($_POST['id'], $client['name'] ?? '');
        $_SESSION['new_credentials'] = $cred;
        header('Location: audits.php?id=' . $_POST['id'] . '&access=1'); exit;
    }
    if ($action === 'regenerate_password') {
        $pwd = regenerateAuditPassword($_POST['id']);
        $_SESSION['new_credentials'] = ['password' => $pwd, 'regenerated' => true];
        header('Location: audits.php?id=' . $_POST['id'] . '&access=1'); exit;
    }
    if ($action === 'toggle_access') {
        toggleAuditAccess($_POST['id'], $_POST['enabled'] === '1');
        header('Location: audits.php?id=' . $_POST['id']); exit;
    }
}

$edit_id = $_GET['id'] ?? null;
$edit    = $edit_id ? getAudit($edit_id) : null;
if ($edit_id && !$edit) { header('Location: audits.php'); exit; }

$preselect_client = $_GET['client'] ?? '';
$clients      = getClients();
$audits       = getAudits();
$cms_opts     = getAuditCmsOptions();
$prob_opts    = getAuditProblemOptions();
$status_opts  = getAuditStatusOptions();
$score_cats   = getAuditScoreCategories();
$action_buckets = getAuditActionBuckets();

$new_credentials = $_SESSION['new_credentials'] ?? null;
unset($_SESSION['new_credentials']);

$page_title    = $edit_id ? 'Auditoria' : 'Auditories web';
$page_subtitle = $edit_id ? '' : count($audits) . ' auditories realitzades';
$topbar_action_url   = 'audits.php?new=1';
$topbar_action_label = '+ Nova auditoria';
$show_form = $edit_id || isset($_GET['new']);
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.score-row { display:flex; align-items:center; gap:14px; }
.score-row input[type=range] { flex:1; accent-color:#2563eb; }
.score-val { width:34px; text-align:center; font-weight:700; font-family:'Syne',sans-serif; color:#2563eb; }
.checklist-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:10px; }
.checklist-item { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; cursor:pointer; font-size:.85rem; }
.checklist-item:has(input:checked) { border-color:#2563eb; background:rgba(37,99,235,.06); }
.checklist-item input { accent-color:#2563eb; }
.cms-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
.cms-item { display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; cursor:pointer; font-size:.85rem; }
.cms-item:has(input:checked) { border-color:#0f172a; background:rgba(15,23,42,.04); font-weight:600; }
.audit-section-num { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:6px; background:#0f172a; color:#fff; font-size:.72rem; font-weight:800; font-family:'Syne',sans-serif; margin-right:8px; }
.audit-area-grid { display:grid; grid-template-columns:1fr 2fr; gap:18px; align-items:start; }
@media(max-width:760px){ .audit-area-grid{ grid-template-columns:1fr; } }
.help-text { font-size:.76rem; color:#9ca3af; margin-top:4px; }
.access-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:18px 20px; }
.access-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 0; border-bottom:1px dashed #bbf7d0; font-size:.85rem; }
.access-row:last-child { border-bottom:none; }
.access-row code { background:#fff; border:1px solid #d1fae5; padding:4px 10px; border-radius:6px; font-size:.82rem; user-select:all; }
.credentials-alert { background:#0f172a; color:#fff; border-radius:14px; padding:20px 24px; margin-bottom:20px; }
.credentials-alert .cr-row { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px 0; font-size:.9rem; }
.credentials-alert code { background:rgba(255,255,255,.12); padding:5px 12px; border-radius:6px; font-size:.95rem; font-weight:700; user-select:all; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Auditoria guardada. <a href="audit-report.php?id=<?= htmlspecialchars($_GET['id'] ?? '') ?>" target="_blank">Veure informe intern →</a></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Auditoria eliminada.</div><?php endif; ?>

<?php if ($new_credentials): ?>
<div class="credentials-alert">
    <strong style="font-family:'Syne',sans-serif">🔐 Credencials generades — copia-les ara, la contrasenya no es tornarà a mostrar</strong>
    <?php if (!empty($new_credentials['username'])): ?>
    <div class="cr-row"><span>Usuari:</span> <code><?= htmlspecialchars($new_credentials['username']) ?></code></div>
    <?php endif; ?>
    <div class="cr-row"><span>Contrasenya:</span> <code><?= htmlspecialchars($new_credentials['password']) ?></code></div>
</div>
<?php endif; ?>

<?php if (!$show_form): ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Totes les auditories</div>
        <a href="audits.php?new=1" class="btn btn-primary btn-sm">+ Nova auditoria</a>
    </div>
    <?php if (empty($audits)): ?>
    <div style="padding:48px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">🔍</div>
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:8px">Cap auditoria encara</h3>
        <p style="color:#6b7280;margin-bottom:20px">Realitza la primera auditoria professional amb un client.</p>
        <a href="audits.php?new=1" class="btn btn-primary">+ Fer primera auditoria</a>
    </div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Client</th><th>Web</th><th>Data</th><th>Nota global</th><th>Estat</th><th>Accés client</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($audits as $a):
            $c = getClient($a['client_id']);
            $avg = auditScoreAvg($a);
            $lvl = auditScoreLabel($avg);
            $st  = auditStatusLabel($a['estado'] ?? 'pendiente');
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($c['name'] ?? '— client eliminat —') ?></strong>
                <?php if (!empty($c['company'])): ?><div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($c['company']) ?></div><?php endif; ?>
            </td>
            <td style="font-size:.78rem;color:#6b7280;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($a['url'] ?? '—') ?></td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($a['date']) ?></td>
            <td><span class="badge <?= $lvl['class'] ?>"><?= $avg ?>/10 · <?= $lvl['label'] ?></span></td>
            <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            <td>
                <?php if (!empty($a['access_token']) && !empty($a['access_enabled'])): ?>
                    <span class="badge badge-green">🔓 Activat</span>
                <?php elseif (!empty($a['access_token'])): ?>
                    <span class="badge badge-gray">🔒 Desactivat</span>
                <?php else: ?>
                    <span class="badge badge-gray">Sense generar</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="td-actions">
                    <a href="audit-report.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary" target="_blank" title="Informe intern">📄</a>
                    <a href="audits.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary" title="Editar">✏️</a>
                    <form method="POST" onsubmit="return confirm('Eliminar esta auditoria i les seues propostes?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<?php else:
$a = $edit ?? array_merge(
    ['id'=>'','client_id'=>$preselect_client,'date'=>date('Y-m-d'),'url'=>'','cms'=>'','estado'=>'pendiente','problemas_detectados'=>[]],
    array_fill_keys(array_map(fn($k)=>"score_$k", array_keys($score_cats)), 5)
);
?>
<form method="POST" class="form-grid">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($a['id']) ?>">

    <div class="card">
        <div class="card-header"><div class="card-title">Dades generals</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Client *</label>
                    <select name="client_id" required>
                        <option value="">— Selecciona client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $a['client_id'] === $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?><?= $c['company'] ? ' · ' . htmlspecialchars($c['company']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($clients)): ?><small style="color:#dc2626">No hi ha clients. <a href="clients.php?new=1">Crea'n un primer</a>.</small><?php endif; ?>
                </div>
                <div class="form-group"><label>Data de l'auditoria</label><input type="date" name="date" value="<?= htmlspecialchars($a['date']) ?>"></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Web analitzada (URL)</label><input type="text" name="url" value="<?= htmlspecialchars($a['url'] ?? '') ?>" placeholder="https://exemple.es"></div>
                <div class="form-group">
                    <label>Estat de l'auditoria</label>
                    <select name="estado">
                        <?php foreach ($status_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($a['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Idioma de l'informe pel client</label>
                <select name="lang">
                    <?php foreach (getAuditLangOptions() as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($a['lang'] ?? 'ca') === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="help-text">Tu sempre veus l'informe intern en valencià; el client veu la versió en l'idioma que trie ací.</small>
            </div>
            <div class="form-group">
                <label>Diagnòstic ràpid — CMS detectat</label>
                <div class="cms-grid">
                    <?php foreach ($cms_opts as $k => $v): ?>
                    <label class="cms-item"><input type="radio" name="cms" value="<?= $k ?>" <?= ($a['cms'] ?? '') === $k ? 'checked' : '' ?>> <?= $v ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Diagnòstic ràpid — problemes evidents</label>
                <div class="checklist-grid">
                    <?php foreach ($prob_opts as $k => $v): $checked = in_array($k, $a['problemas_detectados'] ?? []); ?>
                    <label class="checklist-item"><input type="checkbox" name="problemas[]" value="<?= $k ?>" <?= $checked ? 'checked' : '' ?>> <?= $v ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($a['id']): ?>
    <div class="card">
        <div class="card-header"><div class="card-title">🔐 Accés privat del client</div></div>
        <div class="card-body">
            <?php if (empty($a['access_token'])): ?>
                <p style="font-size:.85rem;color:#6b7280;margin-bottom:14px">Encara no s'ha generat accés. En generar-lo, obtindràs un enllaç i unes credencials úniques d'esta auditoria per compartir amb el client — només ell i tu podreu veure l'informe.</p>
                <button type="submit" form="grant-access-form" class="btn btn-primary btn-sm">Generar accés privat</button>
            <?php else: ?>
                <div class="access-box">
                    <div class="access-row"><span>Enllaç privat</span> <code><?= htmlspecialchars(auditPublicUrl($a)) ?></code></div>
                    <div class="access-row"><span>Usuari</span> <code><?= htmlspecialchars($a['access_username']) ?></code></div>
                    <div class="access-row"><span>Contrasenya</span> <span style="color:#6b7280;font-size:.8rem">•••••••• (regenera-la si l'has perduda)</span></div>
                    <div class="access-row"><span>Estat</span> <span class="badge <?= !empty($a['access_enabled']) ? 'badge-green' : 'badge-gray' ?>"><?= !empty($a['access_enabled']) ? 'Activat' : 'Desactivat' ?></span></div>
                </div>
                <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                    <button type="submit" form="regen-pwd-form" class="btn btn-sm btn-secondary" onclick="return confirm('Es generarà una contrasenya nova. La contrasenya anterior deixarà de funcionar.')">🔁 Regenerar contrasenya</button>
                    <button type="submit" form="toggle-access-form" class="btn btn-sm btn-secondary"><?= !empty($a['access_enabled']) ? '🔒 Desactivar accés' : '🔓 Activar accés' ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 1. RESUM EXECUTIU -->
    <div class="card">
        <div class="card-header"><div class="card-title"><span class="audit-section-num">1</span>Resum executiu</div></div>
        <div class="card-body form-grid">
            <div class="form-group">
                <label>Valoració general (0-10)</label>
                <div class="score-row">
                    <input type="range" min="0" max="10" name="valoracio_general" value="<?= (int)($a['valoracio_general'] ?? 5) ?>" oninput="this.nextElementSibling.textContent=this.value">
                    <span class="score-val"><?= (int)($a['valoracio_general'] ?? 5) ?></span>
                </div>
            </div>
            <div class="form-group"><label>Principals fortaleses <span class="help-text">(una per línia)</span></label><textarea name="fortaleses" rows="3" placeholder="Marca visualment consistent&#10;Bon temps de resposta del servidor"><?= htmlspecialchars($a['fortaleses'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Principals debilitats <span class="help-text">(una per línia)</span></label><textarea name="debilitats" rows="3" placeholder="Sense HTTPS&#10;Temps de càrrega superior a 5s"><?= htmlspecialchars($a['debilitats'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Prioritats immediates <span class="help-text">(una per línia)</span></label><textarea name="prioritats" rows="3"><?= htmlspecialchars($a['prioritats'] ?? '') ?></textarea></div>
        </div>
    </div>

    <?php
    // Helper inline per pintar seccions "puntuació + notes" sense repetir marcatge
    function auditAreaCard($num, $title, $score_key, $score_val, $notes_key, $notes_val, $notes_placeholder = '', $extra = '') {
        ob_start(); ?>
        <div class="card">
            <div class="card-header"><div class="card-title"><span class="audit-section-num"><?= $num ?></span><?= htmlspecialchars($title) ?></div></div>
            <div class="card-body">
                <div class="audit-area-grid">
                    <div class="form-group">
                        <label>Puntuació (0-10)</label>
                        <div class="score-row">
                            <input type="range" min="0" max="10" name="score_<?= $score_key ?>" value="<?= (int)$score_val ?>" oninput="this.nextElementSibling.textContent=this.value">
                            <span class="score-val"><?= (int)$score_val ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Anàlisi i recomanacions</label>
                        <textarea name="notes_<?= $notes_key ?>" rows="4" placeholder="<?= htmlspecialchars($notes_placeholder) ?>"><?= htmlspecialchars($notes_val ?? '') ?></textarea>
                    </div>
                </div>
                <?= $extra ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    echo auditAreaCard(2, 'Primera impressió', 'disseny', $a['score_disseny'] ?? 5, 'disseny', $a['notes_disseny'] ?? '',
        'Aspecte professional, confiança, claredat del missatge, identitat de marca, jerarquia visual, imatges, tipografia, colors, botons...');

    $ux_extra = '<div class="form-group" style="margin-top:14px"><label>Possibles punts d\'abandonament <span class="help-text">(una per línia)</span></label>'
        . '<textarea name="punts_abandonament" rows="2">' . htmlspecialchars($a['punts_abandonament'] ?? '') . '</textarea></div>';
    echo auditAreaCard(3, "Experiència d'usuari (UX)", 'ux', $a['score_ux'] ?? 5, 'ux', $a['notes_ux'] ?? '',
        'Navegació, menús, arquitectura de la informació, formularis, procés de compra, CTA, flux de navegació...', $ux_extra);

    echo auditAreaCard(4, 'Adaptació a mòbil', 'mobile', $a['score_mobile'] ?? 5, 'mobile', $a['notes_mobile'] ?? '',
        'Responsive, grandària de botons, llegibilitat, velocitat, facilitat d\'ús amb el dit...');

    echo auditAreaCard(5, 'Velocitat', 'velocitat', $a['score_velocitat'] ?? 5, 'velocitat', $a['notes_velocitat'] ?? '',
        'Temps de càrrega, imatges, CSS, JavaScript, caché, compressió, fonts, lazy loading...');

    echo auditAreaCard(6, 'SEO', 'seo', $a['score_seo'] ?? 5, 'seo', $a['notes_seo'] ?? '',
        'Títol, meta description, H1-H6, URLs, sitemap, robots.txt, alt d\'imatges, contingut duplicat, enllaços, paraules clau, dades estructurades, Core Web Vitals...');

    echo auditAreaCard(7, 'Contingut', 'contingut', $a['score_contingut'] ?? 5, 'contingut', $a['notes_contingut'] ?? '',
        'Qualitat dels textos, ortografia, persuasió, longitud, valor aportat, CTA, blog, actualització...');

    echo auditAreaCard(8, 'Accessibilitat (WCAG)', 'accessibilitat', $a['score_accessibilitat'] ?? 5, 'accessibilitat', $a['notes_accessibilitat'] ?? '',
        'Contrast, text alternatiu, navegació amb teclat, etiquetes, formularis, grandària del text...');

    echo auditAreaCard(9, 'Seguretat', 'seguretat', $a['score_seguretat'] ?? 5, 'seguretat', $a['notes_seguretat'] ?? '',
        'HTTPS, SSL, capçaleres de seguretat, vulnerabilitats, cookies, RGPD, política de privacitat, avís legal...');

    echo auditAreaCard(10, 'Rendiment comercial', 'conversio', $a['score_conversio'] ?? 5, 'conversio', $a['notes_conversio'] ?? '',
        'Confiança, credibilitat, prova social, testimonis, casos d\'èxit, diferenciació, conversió, captació de clients...');
    ?>

    <!-- 11. COMPETÈNCIA -->
    <div class="card">
        <div class="card-header"><div class="card-title"><span class="audit-section-num">11</span>Competència</div></div>
        <div class="card-body form-grid">
            <div class="form-group"><label>Què fa millor la competència i oportunitats de diferenciació</label><textarea name="notes_competencia" rows="4"><?= htmlspecialchars($a['notes_competencia'] ?? '') ?></textarea></div>
        </div>
    </div>

    <!-- 12. PLA D'ACCIÓ -->
    <div class="card">
        <div class="card-header"><div class="card-title"><span class="audit-section-num">12</span>Pla d'acció</div></div>
        <div class="card-body form-grid">
            <?php foreach ($action_buckets as $key => $b): ?>
            <div class="form-group">
                <label><span class="badge <?= $b['class'] ?>"><?= htmlspecialchars($b['label']) ?></span> <span class="help-text"><?= htmlspecialchars($b['sub']) ?> — una acció per línia</span></label>
                <textarea name="<?= $key ?>" rows="3"><?= htmlspecialchars($a[$key] ?? '') ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 13. TAULA RESUM -->
    <div class="card">
        <div class="card-header"><div class="card-title"><span class="audit-section-num">13</span>Taula resum</div></div>
        <div class="card-body form-grid">
            <div class="form-group">
                <label>Una fila per línia, camps separats per "<code>|</code>": <span class="help-text">Problema | Impacte | Dificultat | Prioritat | Solució</span></label>
                <textarea name="taula_resum" rows="5" placeholder="Web sense HTTPS | Alt | Baixa | Crítica | Instal·lar certificat SSL"><?= htmlspecialchars($a['taula_resum'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- 14. VALORACIÓ FINAL -->
    <div class="card">
        <div class="card-header"><div class="card-title"><span class="audit-section-num">14</span>Valoració final</div></div>
        <div class="card-body form-grid">
            <p class="help-text">Reutilitza les puntuacions ja assignades a cada secció + la imatge corporativa. La nota global es calcula automàticament.</p>
            <div class="form-group">
                <label>Imatge corporativa (0-10)</label>
                <div class="score-row">
                    <input type="range" min="0" max="10" name="score_imatge_corporativa" value="<?= (int)($a['score_imatge_corporativa'] ?? 5) ?>" oninput="this.nextElementSibling.textContent=this.value">
                    <span class="score-val"><?= (int)($a['score_imatge_corporativa'] ?? 5) ?></span>
                </div>
            </div>
            <div class="form-group"><label>Conclusió professional <span class="help-text">(~300 paraules: quines accions tindran més impacte)</span></label><textarea name="conclusio" rows="7"><?= htmlspecialchars($a['conclusio'] ?? '') ?></textarea></div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar auditoria</button>
        <a href="audits.php" class="btn btn-secondary">Cancel·lar</a>
    </div>
</form>

<?php if ($a['id']): ?>
<form id="grant-access-form" method="POST"><input type="hidden" name="action" value="grant_access"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="client_id" value="<?= htmlspecialchars($a['client_id']) ?>"></form>
<form id="regen-pwd-form" method="POST"><input type="hidden" name="action" value="regenerate_password"><input type="hidden" name="id" value="<?= $a['id'] ?>"></form>
<form id="toggle-access-form" method="POST"><input type="hidden" name="action" value="toggle_access"><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="enabled" value="<?= !empty($a['access_enabled']) ? '0' : '1' ?>"></form>
<?php endif; ?>

<?php endif; ?>

</div></div>
<?php include 'includes/admin-footer.php'; ?>
