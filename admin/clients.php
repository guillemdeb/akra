<?php
require_once 'includes/core.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $existing = !empty($_POST['id']) ? getClient($_POST['id']) : null;

        $logo = $_POST['logo_current'] ?? ($existing['logo'] ?? '');
        if (!empty($_POST['remove_logo'])) {
            if ($logo) { $full = AKRA_ROOT . '/' . $logo; if (file_exists($full)) @unlink($full); }
            $logo = '';
        } elseif (!empty($_FILES['logo']['name'])) {
            $upload = uploadImage($_FILES['logo'], 'clients');
            if ($upload['ok']) {
                if ($logo) { $full = AKRA_ROOT . '/' . $logo; if (file_exists($full)) @unlink($full); }
                $logo = $upload['path'];
            }
        }

        $client = [
            'id'      => $_POST['id'] ?: generateId(),
            'name'    => sanitize($_POST['name'] ?? ''),
            'company' => sanitize($_POST['company'] ?? ''),
            'logo'    => $logo,
            'nif'     => sanitize($_POST['nif'] ?? ''),
            'email'   => sanitize($_POST['email'] ?? ''),
            'phone'   => sanitize($_POST['phone'] ?? ''),
            'address' => sanitize($_POST['address'] ?? ''),
            'city'    => sanitize($_POST['city'] ?? ''),
            'cp'      => sanitize($_POST['cp'] ?? ''),
            'web_actual' => sanitize($_POST['web_actual'] ?? ''),
            'sector'  => sanitize($_POST['sector'] ?? ''),
            'tags'    => $_POST['tags'] ?? [],
            'stage'       => $_POST['stage'] ?? 'lead',
            'lead_source' => $_POST['lead_source'] ?? '',
            'lost_reason' => sanitize($_POST['lost_reason'] ?? ''),
            'notes'   => sanitize($_POST['notes'] ?? ''),
            // Preservem l'accés al Hub (es gestiona amb els seus propis botons, no des d'este formulari)
            'hub_enabled'       => $existing['hub_enabled'] ?? false,
            'hub_password_hash' => $existing['hub_password_hash'] ?? '',
            'hub_lang'          => $existing['hub_lang'] ?? 'ca',
        ];
        saveClient($client);
        header('Location: clients.php?saved=1'); exit;
    }
    if ($action === 'delete') {
        deleteClient($_POST['id']);
        header('Location: clients.php?deleted=1'); exit;
    }
    if ($action === 'save_contact') {
        $prev_contact = !empty($_POST['contact_id']) ? getContact($_POST['contact_id']) : null;
        $contact = [
            'id'        => $_POST['contact_id'] ?: generateId(),
            'client_id' => $_POST['client_id'],
            'date'      => $_POST['date'] ?: date('Y-m-d'),
            'channel'   => $_POST['channel'] ?? 'telefon',
            'direction' => $_POST['direction'] ?? 'jo_client',
            'message'   => sanitize($_POST['message'] ?? ''),
            'response'  => sanitize($_POST['response'] ?? ''),
            'status'    => $_POST['status'] ?? 'pendent',
            'follow_up' => $_POST['follow_up'] ?? '',
        ];
        saveContact($contact);
        advanceClientStage($contact['client_id'], 'contactat');
        // Avisa el client si acabem d'afegir/editar una resposta a un missatge seu
        if ($contact['response'] !== '' && $contact['response'] !== ($prev_contact['response'] ?? '')) {
            notifyClientOfChange($contact['client_id'], 'comm_reply', ['hub_page' => 'comunicacions.php']);
        }
        header('Location: clients.php?id=' . $_POST['client_id'] . '&contact_saved=1'); exit;
    }
    if ($action === 'delete_contact') {
        deleteContact($_POST['contact_id']);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&contact_deleted=1'); exit;
    }

    if ($action === 'save_job') {
        $prev_job = !empty($_POST['job_id']) ? getJob($_POST['job_id']) : null;
        $job = [
            'id'          => $_POST['job_id'] ?: generateId(),
            'client_id'   => $_POST['client_id'],
            'title'       => sanitize($_POST['title'] ?? ''),
            'type'        => $_POST['type'] ?? 'web',
            'status'      => $_POST['status'] ?? 'pressupostat',
            'start_date'  => $_POST['start_date'] ?: date('Y-m-d'),
            'end_date'    => $_POST['end_date'] ?: '',
            'price'       => (float)str_replace(',', '.', $_POST['price'] ?? 0),
            'description' => sanitize($_POST['description'] ?? ''),
        ];
        saveJob($job);
        // Si el treball acaba de passar a "Acabat", enviem l'enquesta de satisfacció al client (una sola vegada).
        if ($job['status'] === 'acabat' && ($prev_job['status'] ?? '') !== 'acabat') {
            sendSatisfactionSurvey($job);
        } elseif ($prev_job && $job['status'] !== ($prev_job['status'] ?? '')) {
            // Per a qualsevol altre canvi d'estat, avisem el client (email i/o WhatsApp segons configuració)
            $st = getJobStatusOptions();
            notifyClientOfChange($job['client_id'], 'job_status', [
                'body_args' => [$job['title'], $st[$job['status']]['label'] ?? $job['status']],
                'hub_page'  => 'treballs.php',
            ]);
        }
        header('Location: clients.php?id=' . $_POST['client_id'] . '&job_saved=1'); exit;
    }
    if ($action === 'delete_job') {
        deleteJob($_POST['job_id']);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&job_deleted=1'); exit;
    }
    if ($action === 'save_time_entry') {
        $entry = [
            'id'        => $_POST['entry_id'] ?: generateId(),
            'job_id'    => $_POST['job_id'],
            'client_id' => $_POST['client_id'],
            'date'      => $_POST['entry_date'] ?: date('Y-m-d'),
            'hours'     => (float)str_replace(',', '.', $_POST['hours'] ?? 0),
            'note'      => sanitize($_POST['entry_note'] ?? ''),
        ];
        saveTimeEntry($entry);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&time_saved=1#jobsCard'); exit;
    }
    if ($action === 'delete_time_entry') {
        deleteTimeEntry($_POST['entry_id']);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&time_deleted=1#jobsCard'); exit;
    }

    if ($action === 'save_domain') {
        $domain = [
            'id'            => $_POST['domain_id'] ?: generateId(),
            'client_id'     => $_POST['client_id'],
            'domain'        => sanitize($_POST['domain'] ?? ''),
            'provider'      => sanitize($_POST['provider'] ?? ''),
            'managed_by'    => $_POST['managed_by'] ?? 'nosaltres',
            'renewal_date'  => $_POST['renewal_date'] ?: '',
            'alert_days'    => (int)($_POST['alert_days'] ?? 30),
            'cost'          => (float)str_replace(',', '.', $_POST['cost'] ?? 0),
            'active'        => isset($_POST['active']),
            'auto_invoice'  => isset($_POST['auto_invoice']),
            'username'      => sanitize($_POST['username'] ?? ''),
            'password_plain'=> $_POST['password'] ?? '', // saveDomain() el xifra i esborra este camp
            'notes'         => sanitize($_POST['domain_notes'] ?? ''),
            // Hosting (opcionalment separat del domini)
            'hosting_provider'      => sanitize($_POST['hosting_provider'] ?? ''),
            'hosting_renewal_date'  => $_POST['hosting_renewal_date'] ?: '',
            'hosting_cost'          => (float)str_replace(',', '.', $_POST['hosting_cost'] ?? 0),
        ];
        // Si l'usuari deixa el camp de contrasenya buit en editar, mantenim l'antiga xifrada.
        if ($domain['password_plain'] === '' && !empty($_POST['domain_id'])) {
            $existing = getDomain($_POST['domain_id']);
            if ($existing) { $domain['password_enc'] = $existing['password_enc'] ?? ''; unset($domain['password_plain']); }
        }
        // Factura de compra del domini/hosting (PDF o imatge, opcional)
        if (!empty($_FILES['invoice_file']['name'])) {
            $upload = uploadDocument($_FILES['invoice_file'], 'domain-invoices');
            if ($upload['ok']) $domain['invoice_file'] = $upload['path'];
        } elseif (!empty($_POST['domain_id'])) {
            $existing = getDomain($_POST['domain_id']);
            if ($existing) $domain['invoice_file'] = $existing['invoice_file'] ?? '';
        }
        saveDomain($domain);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&domain_saved=1#domainsCard'); exit;
    }
    if ($action === 'delete_domain') {
        deleteDomain($_POST['domain_id']);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&domain_deleted=1#domainsCard'); exit;
    }

    if ($action === 'set_hub_password') {
        $custom = trim($_POST['hub_password'] ?? '');
        $plain = setClientHubPassword($_POST['client_id'], $custom !== '' ? $custom : null);
        $_SESSION['hub_password_flash'] = $plain; // es mostra una sola vegada a la següent càrrega
        header('Location: clients.php?id=' . $_POST['client_id'] . '&hub_pw_set=1#hubCard'); exit;
    }
    if ($action === 'disable_hub_access') {
        disableClientHubAccess($_POST['client_id']);
        header('Location: clients.php?id=' . $_POST['client_id'] . '&hub_disabled=1#hubCard'); exit;
    }
    if ($action === 'set_hub_lang') {
        setClientHubLang($_POST['client_id'], $_POST['hub_lang'] ?? 'ca');
        header('Location: clients.php?id=' . $_POST['client_id'] . '&hub_lang_set=1#hubCard'); exit;
    }
}

$edit_id = $_GET['id'] ?? null;
$edit    = $edit_id ? getClient($edit_id) : null;
if ($edit_id && !$edit) { header('Location: clients.php'); exit; }

$clients = getClients();
$tag_opts = getClientTagOptions();
$stage_opts_list = getLeadStageOptions();
$tag_filter = $_GET['tag'] ?? '';
$stage_filter = $_GET['stage'] ?? '';
if ($tag_filter) $clients = array_values(array_filter($clients, fn($c) => in_array($tag_filter, $c['tags'] ?? [])));
if ($stage_filter) $clients = array_values(array_filter($clients, fn($c) => ($c['stage'] ?? 'lead') === $stage_filter));
$page_title    = $edit_id ? 'Editar client' : 'Clients';
$page_subtitle = $edit_id ? ($edit['name'] ?? '') : count($clients) . ' clients';
$topbar_action_url   = 'clients.php?new=1';
$topbar_action_label = 'Nou client';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?> · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.inv-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 20px; }
.inv-stat { background: white; border: 1px solid var(--a-border); border-radius: 10px; padding: 16px 20px; }
.inv-stat__label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--a-muted); margin-bottom: 6px; }
.inv-stat__value { font-family: 'Syne',sans-serif; font-size: 1.6rem; font-weight: 700; color: var(--a-navy); letter-spacing: -.03em; }
.inv-stat__value--green  { color: #16a34a; }
.inv-stat__value--red    { color: #dc2626; }
.inv-stat__value--gold   { color: var(--a-gold); }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Client guardat.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Client eliminat.</div><?php endif; ?>
<?php if (isset($_GET['contact_saved'])): ?><div class="alert alert-success">✅ Contacte guardat.</div><?php endif; ?>
<?php if (isset($_GET['contact_deleted'])): ?><div class="alert alert-success">Contacte eliminat.</div><?php endif; ?>
<?php if (isset($_GET['job_saved'])): ?><div class="alert alert-success">✅ Treball guardat.</div><?php endif; ?>
<?php if (isset($_GET['job_deleted'])): ?><div class="alert alert-success">Treball eliminat.</div><?php endif; ?>
<?php if (isset($_GET['time_saved'])): ?><div class="alert alert-success">✅ Hores registrades.</div><?php endif; ?>
<?php if (isset($_GET['time_deleted'])): ?><div class="alert alert-success">Registre d'hores eliminat.</div><?php endif; ?>
<?php if (isset($_GET['domain_saved'])): ?><div class="alert alert-success">✅ Domini guardat.</div><?php endif; ?>
<?php if (isset($_GET['domain_deleted'])): ?><div class="alert alert-success">Domini eliminat.</div><?php endif; ?>

<?php if (!$edit_id && !isset($_GET['new'])): ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Tots els clients</div>
        <div style="display:flex;gap:8px">
            <a href="pipeline.php" class="btn btn-sm btn-secondary">📊 Vista pipeline</a>
            <a href="clients.php?new=1" class="btn btn-primary btn-sm">+ Nou client</a>
        </div>
    </div>
    <div class="no-print" style="display:flex;flex-wrap:wrap;gap:8px;padding:14px 22px;border-bottom:1px solid #eee">
        <a href="clients.php" class="badge <?= $stage_filter === '' ? 'badge-blue' : 'badge-gray' ?>" style="text-decoration:none">Totes les fases</a>
        <?php foreach ($stage_opts_list as $k => $s): ?>
        <a href="clients.php?stage=<?= $k ?>" class="badge <?= $stage_filter === $k ? $s['class'] : 'badge-gray' ?>" style="text-decoration:none"><?= $s['label'] ?></a>
        <?php endforeach; ?>
    </div>
    <div class="no-print" style="display:flex;flex-wrap:wrap;gap:8px;padding:14px 22px;border-bottom:1px solid #eee">
        <a href="clients.php" class="badge <?= $tag_filter === '' ? 'badge-blue' : 'badge-gray' ?>" style="text-decoration:none">Totes les etiquetes</a>
        <?php foreach ($tag_opts as $k => $t): ?>
        <a href="clients.php?tag=<?= $k ?>" class="badge <?= $tag_filter === $k ? $t['class'] : 'badge-gray' ?>" style="text-decoration:none"><?= $t['label'] ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($clients)): ?>
    <div style="padding:48px;text-align:center">
        <div style="font-size:3rem;margin-bottom:12px">👤</div>
        <h3 style="font-family:'Syne',sans-serif;margin-bottom:8px">Cap client encara</h3>
        <p style="color:#6b7280;margin-bottom:20px">Afegeix clients per poder generar factures.</p>
        <a href="clients.php?new=1" class="btn btn-primary">+ Afegir primer client</a>
    </div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Nom / Empresa</th><th>Etiquetes</th><th>NIF</th><th>Email</th><th>Telèfon</th><th>Factures</th><th>Auditories</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($clients as $c):
            $n_inv = count(array_filter(getInvoices(), fn($i) => $i['client_id'] === $c['id']));
            $n_aud = count(getAudits($c['id']));
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <?php if (!empty($c['logo'])): ?>
                    <img src="../<?= htmlspecialchars($c['logo']) ?>" style="width:32px;height:32px;object-fit:contain;border-radius:6px;border:1px solid var(--a-border);background:#fff;flex-shrink:0">
                    <?php endif; ?>
                    <div>
                        <strong><?= htmlspecialchars($c['name']) ?></strong>
                        <span class="badge <?= $stage_opts_list[$c['stage'] ?? 'lead']['class'] ?>" style="font-size:.65rem;margin-left:6px"><?= $stage_opts_list[$c['stage'] ?? 'lead']['label'] ?></span>
                        <?php if (!empty($c['hub_enabled'])): ?><span class="badge badge-blue" style="font-size:.65rem;margin-left:4px" title="Té accés al portal del client">🔐 Hub</span><?php endif; ?>
                        <?php if (!empty($c['company'])): ?>
                        <div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($c['company']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <?php foreach (($c['tags'] ?? []) as $tg): if (!isset($tag_opts[$tg])) continue; ?>
                <span class="badge <?= $tag_opts[$tg]['class'] ?>" style="font-size:.68rem;margin-right:3px"><?= $tag_opts[$tg]['label'] ?></span>
                <?php endforeach; ?>
            </td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($c['nif'] ?? '—') ?></td>
            <td><a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="font-size:.82rem;color:var(--a-gold)"><?= htmlspecialchars($c['email'] ?? '—') ?></a></td>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
            <td><a href="invoices.php?client=<?= $c['id'] ?>" style="font-size:.82rem;font-weight:600"><?= $n_inv ?> factura<?= $n_inv !== 1 ? 's' : '' ?></a></td>
            <td><a href="audits.php?client=<?= $c['id'] ?>" style="font-size:.82rem;font-weight:600"><?= $n_aud ?> auditoria<?= $n_aud !== 1 ? 'es' : '' ?></a></td>
            <td>
                <div class="td-actions">
                    <a href="clients.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                    <a href="audits.php?new=1&client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Nova auditoria">🔍</a>
                    <a href="invoices.php?new=1&client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Nova factura">🧾</a>
                    <form method="POST" onsubmit="return confirm('Eliminar client?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
$c = $edit ?? ['id'=>'','name'=>'','company'=>'','nif'=>'','email'=>'','phone'=>'','address'=>'','city'=>'','cp'=>'','web_actual'=>'','sector'=>'','tags'=>[],'stage'=>'lead','lead_source'=>'','lost_reason'=>'','notes'=>''];
$tag_opts_form = getClientTagOptions();
$stage_opts    = getLeadStageOptions();
$source_opts   = getLeadSourceOptions();
$lost_opts     = getLostReasonOptions();
$c_audits   = $c['id'] ? getAudits($c['id']) : [];
$c_props    = $c['id'] ? getProposals($c['id']) : [];
$c_contacts = $c['id'] ? getContacts($c['id']) : [];
$channels   = getContactChannelOptions();
$statuses   = getContactStatusOptions();
$directions = getContactDirectionOptions();
$edit_contact_id = $_GET['contact_id'] ?? null;
$edit_contact = $edit_contact_id ? getContact($edit_contact_id) : null;

$c_jobs = $c['id'] ? getJobs($c['id']) : [];
$job_type_opts = getJobTypeOptions();
$job_status_opts = getJobStatusOptions();
$edit_job_id = $_GET['job_id'] ?? null;
$edit_job = $edit_job_id ? getJob($edit_job_id) : null;

$c_domains = $c['id'] ? getDomains($c['id']) : [];
$domain_manager_opts = getDomainManagerOptions();
$edit_domain_id = $_GET['domain_id'] ?? null;
$edit_domain = $edit_domain_id ? getDomain($edit_domain_id) : null;
?>
<form method="POST" class="form-grid" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
    <input type="hidden" name="logo_current" value="<?= htmlspecialchars($c['logo'] ?? '') ?>">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px">
    <div class="form-grid">
        <div class="card">
            <div class="card-header"><div class="card-title">Dades personals / empresa</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Logotip del client</label>
                    <div style="display:flex;align-items:center;gap:14px">
                        <label class="img-upload" for="logo" id="logo-label" style="width:84px;height:84px;flex-shrink:0">
                            <?php if (!empty($c['logo'])): ?>
                            <img src="../<?= htmlspecialchars($c['logo']) ?>" class="img-preview" id="logo-preview" style="object-fit:contain;background:#fff">
                            <?php else: ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <?php endif; ?>
                        </label>
                        <input type="file" name="logo" id="logo" accept="image/*" style="display:none" onchange="previewLogo(this)">
                        <div style="font-size:.8rem;color:#6b7280">
                            JPG, PNG, WebP o SVG · màx 5MB<br>Es mostra a la fitxa i, si té accés, al Hub del client.
                            <?php if (!empty($c['logo'])): ?><br><label style="display:flex;align-items:center;gap:5px;font-weight:400;margin-top:4px"><input type="checkbox" name="remove_logo" value="1"> Eliminar logotip actual</label><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group"><label>Nom complet *</label><input type="text" name="name" value="<?= htmlspecialchars($c['name']) ?>" required placeholder="Joan Garcia Pérez"></div>
                    <div class="form-group"><label>Empresa (opcional)</label><input type="text" name="company" value="<?= htmlspecialchars($c['company']) ?>" placeholder="Empresa SL"></div>
                </div>
                <div class="form-row-2">
                    <div class="form-group"><label>NIF / CIF</label><input type="text" name="nif" value="<?= htmlspecialchars($c['nif']) ?>" placeholder="12345678A"></div>
                    <div class="form-group"><label>Telèfon</label><input type="tel" name="phone" value="<?= htmlspecialchars($c['phone']) ?>" placeholder="+34 600 000 000"></div>
                </div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($c['email']) ?>" placeholder="client@empresa.es"></div>
                <div class="form-row-2">
                    <div class="form-group"><label>Web actual</label><input type="text" name="web_actual" value="<?= htmlspecialchars($c['web_actual'] ?? '') ?>" placeholder="https://empresa.es"></div>
                    <div class="form-group"><label>Sector</label><input type="text" name="sector" value="<?= htmlspecialchars($c['sector'] ?? '') ?>" placeholder="Hostaleria, retail, salut..."></div>
                    <div class="form-group">
                        <label>Etiquetes</label>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;padding-top:4px">
                            <?php foreach ($tag_opts_form as $k => $t): ?>
                            <label style="display:flex;align-items:center;gap:5px;font-size:.82rem;font-weight:400;color:#374151">
                                <input type="checkbox" name="tags[]" value="<?= $k ?>" <?= in_array($k, $c['tags'] ?? []) ? 'checked' : '' ?>> <?= $t['label'] ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title">Adreça de facturació</div></div>
            <div class="card-body form-grid">
                <div class="form-group"><label>Adreça</label><input type="text" name="address" value="<?= htmlspecialchars($c['address']) ?>" placeholder="Carrer Exemple, 12, 1r"></div>
                <div class="form-row-2">
                    <div class="form-group"><label>Codi postal</label><input type="text" name="cp" value="<?= htmlspecialchars($c['cp']) ?>" placeholder="03001"></div>
                    <div class="form-group"><label>Població</label><input type="text" name="city" value="<?= htmlspecialchars($c['city']) ?>" placeholder="Alacant"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-grid">
        <div class="card">
            <div class="card-header"><div class="card-title">📊 Fase comercial</div></div>
            <div class="card-body form-grid">
                <div class="form-group">
                    <label>Fase</label>
                    <select name="stage" id="stage-select" onchange="document.getElementById('lost-reason-field').style.display=(this.value==='perdut')?'block':'none'">
                        <?php foreach ($stage_opts as $k => $s): ?>
                        <option value="<?= $k ?>" <?= ($c['stage'] ?? 'lead') === $k ? 'selected' : '' ?>><?= $s['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint">Un "lead" és un possible client encara sense factures. Quan li factures per primera vegada, passa sol a "Guanyat".</p>
                </div>
                <div class="form-group">
                    <label>Origen</label>
                    <select name="lead_source">
                        <option value="">— Sense especificar —</option>
                        <?php foreach ($source_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($c['lead_source'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="lost-reason-field" style="display:<?= ($c['stage'] ?? '') === 'perdut' ? 'block' : 'none' ?>">
                    <label>Motiu de la pèrdua</label>
                    <select name="lost_reason">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($lost_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($c['lost_reason'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><div class="card-title">Notes internes</div></div>
            <div class="card-body">
                <textarea name="notes" rows="4" placeholder="Notes sobre el client, preferències, historial..."><?= htmlspecialchars($c['notes']) ?></textarea>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary" style="flex:1">Guardar client</button>
            <a href="clients.php" class="btn btn-secondary">Cancel·lar</a>
        </div>
    </div>
    </div>
</form>

<?php if ($c['id']):
    $fin = getClientFinancialSummary($c['id']);
?>
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">Resum financer</div>
        <a href="invoices.php?client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Veure factures</a>
    </div>
    <div class="inv-stats" style="grid-template-columns:repeat(4,1fr);padding:20px">
        <div class="inv-stat">
            <div class="inv-stat__label">Total facturat</div>
            <div class="inv-stat__value"><?= number_format($fin['total'], 2, ',', '.') ?> €</div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat__label">Cobrat</div>
            <div class="inv-stat__value inv-stat__value--green"><?= number_format($fin['paid'], 2, ',', '.') ?> €</div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat__label">Pendent de cobrar</div>
            <div class="inv-stat__value <?= $fin['due'] > 0 ? 'inv-stat__value--red' : 'inv-stat__value--green' ?>"><?= number_format($fin['due'], 2, ',', '.') ?> €</div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat__label">Factures vençudes</div>
            <div class="inv-stat__value <?= $fin['overdue'] > 0 ? 'inv-stat__value--red' : '' ?>"><?= $fin['overdue'] ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($c['id']):
    $hub_pw_flash = $_SESSION['hub_password_flash'] ?? null;
    unset($_SESSION['hub_password_flash']);
?>
<div class="card" style="margin-top:20px" id="hubCard">
    <div class="card-header">
        <div class="card-title">🔐 Accés al portal del client (Hub)</div>
        <span class="badge <?= !empty($c['hub_enabled']) ? 'badge-green' : 'badge-gray' ?>"><?= !empty($c['hub_enabled']) ? 'Actiu' : 'Desactivat' ?></span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['hub_pw_set']) && $hub_pw_flash): ?>
        <div class="alert alert-success" style="margin-bottom:16px">
            ✅ Accés activat. Contrasenya generada: <strong style="font-family:ui-monospace,monospace;font-size:1rem;letter-spacing:.5px"><?= htmlspecialchars($hub_pw_flash) ?></strong>
            <div class="hint" style="margin-top:4px">Guarda-la ara — no es tornarà a mostrar. Passa-la-hi al client junt amb l'email d'accés.</div>
        </div>
        <?php elseif (isset($_GET['hub_pw_set'])): ?>
        <div class="alert alert-success" style="margin-bottom:16px">✅ Contrasenya actualitzada.</div>
        <?php endif; ?>
        <?php if (isset($_GET['hub_disabled'])): ?>
        <div class="alert alert-success" style="margin-bottom:16px">Accés al Hub desactivat per a este client.</div>
        <?php endif; ?>

        <?php if (empty($c['email'])): ?>
        <p class="hint">Cal que el client tinga un email guardat a la fitxa (a dalt) per poder accedir al Hub — s'utilitza com a usuari d'inici de sessió.</p>
        <?php else: ?>
        <p style="font-size:.85rem;color:#374151;margin-bottom:14px">
            URL del portal: <strong>akratechstudio.es/hub</strong> · Usuari: <strong><?= htmlspecialchars($c['email']) ?></strong>
        </p>
        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-end">
            <form method="POST" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                <input type="hidden" name="action" value="set_hub_password">
                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                <div class="form-group" style="margin:0">
                    <label style="font-size:.72rem">Contrasenya (opcional — deixa-la en blanc per generar-ne una)</label>
                    <input type="text" name="hub_password" placeholder="Generar automàticament..." style="font-size:.82rem">
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><?= !empty($c['hub_enabled']) ? '🔄 Canviar contrasenya' : '✅ Activar accés' ?></button>
            </form>
            <?php if (!empty($c['hub_enabled'])): ?>
            <form method="POST" onsubmit="return confirm('El client ja no podrà entrar al Hub. Confirmes?')">
                <input type="hidden" name="action" value="disable_hub_access">
                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">🚫 Desactivar accés</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['hub_lang_set'])): ?>
        <div class="alert alert-success" style="margin-top:14px">✅ Llengua del portal actualitzada.</div>
        <?php endif; ?>
        <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--a-border)">
            <form method="POST" style="display:flex;gap:8px;align-items:flex-end">
                <input type="hidden" name="action" value="set_hub_lang">
                <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                <div class="form-group" style="margin:0">
                    <label style="font-size:.72rem">Llengua del portal del client</label>
                    <select name="hub_lang" style="font-size:.82rem">
                        <?php foreach (getHubLangOptions() as $key => $label): ?>
                        <option value="<?= $key ?>" <?= getClientHubLang($c) === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-secondary">Guardar llengua</button>
            </form>
            <p class="hint" style="margin-top:6px">El client també pot canviar-la ell mateix des del selector del Hub — en eixe cas quedarà reflectida ací automàticament.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<?php if ($c['id']): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">🛠️ Treballs</div>
        <a href="jobs-print.php?client=<?= $c['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">🖨️ Imprimir treballs</a>
    </div>
    <div class="card-body form-grid" style="border-bottom:1px solid #eee;padding-bottom:20px;margin-bottom:4px">
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="save_job">
            <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($edit_job['id'] ?? '') ?>">
            <div class="form-group"><label>Títol del treball *</label><input type="text" name="title" value="<?= htmlspecialchars($edit_job['title'] ?? '') ?>" required placeholder="Ex: Web corporativa, SEO trimestral..."></div>
            <div class="form-row-2">
                <div class="form-group"><label>Tipus</label>
                    <select name="type">
                        <?php foreach ($job_type_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($edit_job['type'] ?? 'web') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Estat</label>
                    <select name="status">
                        <?php foreach ($job_status_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($edit_job['status'] ?? 'pressupostat') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Data d'inici</label><input type="date" name="start_date" value="<?= htmlspecialchars($edit_job['start_date'] ?? date('Y-m-d')) ?>"></div>
                <div class="form-group"><label>Data fi (buit si en curs)</label><input type="date" name="end_date" value="<?= htmlspecialchars($edit_job['end_date'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Import (opcional)</label><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($edit_job['price'] ?? '') ?>" placeholder="Ex: 800.00"></div>
            <div class="form-group"><label>Descripció / notes</label><textarea name="description" rows="2" placeholder="Detalls, abast, entregables..."><?= htmlspecialchars($edit_job['description'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_job ? 'Actualitzar treball' : '+ Afegir treball' ?></button>
                <?php if ($edit_job): ?><a href="clients.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
    <?php if (empty($c_jobs)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Cap treball registrat encara amb este client.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Treball</th><th>Tipus</th><th>Inici</th><th>Fi</th><th>Import</th><th>Hores</th><th>Estat</th><th>Valoració</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($c_jobs as $j): $js = $job_status_opts[$j['status'] ?? 'pressupostat']; $sat = getSatisfaction($j['id']); ?>
        <tr>
            <td><strong style="font-size:.85rem"><?= htmlspecialchars($j['title']) ?></strong></td>
            <td style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($job_type_opts[$j['type']] ?? $j['type']) ?></td>
            <td style="font-size:.8rem;color:#6b7280;white-space:nowrap"><?= !empty($j['start_date']) ? date('d/m/Y', strtotime($j['start_date'])) : '—' ?></td>
            <td style="font-size:.8rem;color:#6b7280;white-space:nowrap"><?= !empty($j['end_date']) ? date('d/m/Y', strtotime($j['end_date'])) : '—' ?></td>
            <td style="font-size:.82rem"><?= $j['price'] > 0 ? number_format($j['price'], 2, ',', '.') . ' €' : '—' ?></td>
            <td style="font-size:.82rem;color:#6b7280"><?= getJobTotalHours($j['id']) > 0 ? getJobTotalHours($j['id']) . ' h' : '—' ?></td>
            <td><span class="badge <?= $js['class'] ?>"><?= $js['label'] ?></span></td>
            <td style="font-size:.85rem;white-space:nowrap"><?= $sat ? str_repeat('⭐', $sat['rating']) : '—' ?></td>
            <td class="td-actions">
                <a href="clients.php?id=<?= $c['id'] ?>&job_id=<?= $j['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                <form method="POST" onsubmit="return confirm('Eliminar este treball?')" style="display:inline">
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="job_id" value="<?= $j['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <!-- Registre d'hores -->
    <?php if (!empty($c_jobs)): ?>
    <div style="border-top:1px solid #eee;padding:18px 22px">
        <p style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:10px">⏱️ Registre d'hores</p>
        <form method="POST" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin-bottom:14px">
            <input type="hidden" name="action" value="save_time_entry">
            <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
            <div>
                <label style="font-size:.72rem;color:#9ca3af;display:block">Treball</label>
                <select name="job_id" required style="font-size:.82rem">
                    <?php foreach ($c_jobs as $j): ?><option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label style="font-size:.72rem;color:#9ca3af;display:block">Data</label><input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" style="font-size:.82rem"></div>
            <div><label style="font-size:.72rem;color:#9ca3af;display:block">Hores</label><input type="number" step="0.25" min="0.25" name="hours" required placeholder="Ex: 2.5" style="font-size:.82rem;width:90px"></div>
            <div style="flex:1;min-width:150px"><label style="font-size:.72rem;color:#9ca3af;display:block">Nota</label><input type="text" name="entry_note" placeholder="Què s'ha fet..." style="font-size:.82rem;width:100%"></div>
            <button type="submit" class="btn btn-sm btn-primary">+ Afegir</button>
        </form>
        <?php
        $all_entries = [];
        foreach ($c_jobs as $j) foreach (getTimeEntries($j['id']) as $e) { $e['job_title'] = $j['title']; $all_entries[] = $e; }
        usort($all_entries, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        ?>
        <?php if (!empty($all_entries)): ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Data</th><th>Treball</th><th>Hores</th><th>Nota</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($all_entries as $e): ?>
            <tr>
                <td style="font-size:.78rem;color:#6b7280;white-space:nowrap"><?= date('d/m/Y', strtotime($e['date'])) ?></td>
                <td style="font-size:.8rem"><?= htmlspecialchars($e['job_title']) ?></td>
                <td style="font-size:.8rem;font-weight:700"><?= $e['hours'] ?> h</td>
                <td style="font-size:.78rem;color:#6b7280"><?= htmlspecialchars($e['note'] ?: '—') ?></td>
                <td class="td-actions">
                    <form method="POST" onsubmit="return confirm('Eliminar este registre?')">
                        <input type="hidden" name="action" value="delete_time_entry">
                        <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="entry_id" value="<?= $e['id'] ?>">
                        <button class="btn btn-sm btn-danger">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($c['id']):
    $today = date('Y-m-d');
?>
<div class="card" style="margin-top:20px" id="domainsCard">
    <div class="card-header">
        <div class="card-title">🌐 Dominis</div>
    </div>
    <div class="card-body form-grid" style="border-bottom:1px solid #eee;padding-bottom:20px;margin-bottom:4px">
        <form method="POST" class="form-grid" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_domain">
            <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
            <input type="hidden" name="domain_id" value="<?= htmlspecialchars($edit_domain['id'] ?? '') ?>">
            <div class="form-row-2">
                <div class="form-group"><label>Domini *</label><input type="text" name="domain" value="<?= htmlspecialchars($edit_domain['domain'] ?? '') ?>" required placeholder="empresa-client.es"></div>
                <div class="form-group"><label>Empresa / registrador</label><input type="text" name="provider" value="<?= htmlspecialchars($edit_domain['provider'] ?? '') ?>" placeholder="Ex: OVH, GoDaddy, Arsys, Namecheap..."></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Qui el gestiona</label>
                    <select name="managed_by">
                        <?php foreach ($domain_manager_opts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= ($edit_domain['managed_by'] ?? 'nosaltres') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Data de renovació (domini)</label><input type="date" name="renewal_date" value="<?= htmlspecialchars($edit_domain['renewal_date'] ?? '') ?>"></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Avisar-me amb quants dies d'antelació</label><input type="number" name="alert_days" min="1" value="<?= htmlspecialchars($edit_domain['alert_days'] ?? 30) ?>"></div>
                <div class="form-group"><label>Cost anual del domini (€)</label><input type="number" step="0.01" name="cost" value="<?= htmlspecialchars($edit_domain['cost'] ?? '') ?>" placeholder="Ex: 12.99"></div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400">
                    <input type="checkbox" name="active" value="1" <?= ($edit_domain['active'] ?? true) ? 'checked' : '' ?>> Domini actiu (desmarca si ja no s'usa, sense esborrar l'historial)
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400">
                    <input type="checkbox" name="auto_invoice" value="1" <?= !empty($edit_domain['auto_invoice']) ? 'checked' : '' ?>> 🧾 Facturar automàticament la renovació (genera una factura esborrany quan toque, amb el cost indicat dalt)
                </label>
            </div>

            <div style="border-top:1px solid #eee;padding-top:14px;margin-top:6px">
                <p style="font-size:.78rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">🖥️ Hosting (opcional, si va separat del domini)</p>
                <div class="form-row-2">
                    <div class="form-group"><label>Empresa d'hosting</label><input type="text" name="hosting_provider" value="<?= htmlspecialchars($edit_domain['hosting_provider'] ?? '') ?>" placeholder="Ex: SiteGround, Webempresa..."></div>
                    <div class="form-group"><label>Data de renovació (hosting)</label><input type="date" name="hosting_renewal_date" value="<?= htmlspecialchars($edit_domain['hosting_renewal_date'] ?? '') ?>"></div>
                </div>
                <div class="form-group"><label>Cost anual de l'hosting (€)</label><input type="number" step="0.01" name="hosting_cost" value="<?= htmlspecialchars($edit_domain['hosting_cost'] ?? '') ?>" placeholder="Ex: 89.00"></div>
            </div>

            <div class="form-row-2">
                <div class="form-group"><label>Usuari d'accés</label><input type="text" name="username" value="<?= htmlspecialchars($edit_domain['username'] ?? '') ?>" autocomplete="off"></div>
                <div class="form-group">
                    <label>Contrasenya <?= $edit_domain ? '(deixa-la en blanc per no canviar-la)' : '' ?></label>
                    <div style="display:flex;gap:6px">
                        <input type="password" name="password" id="domain-pass-input" placeholder="<?= $edit_domain && !empty($edit_domain['password_enc']) ? '••••••••' : '' ?>" autocomplete="new-password" style="flex:1">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="const i=document.getElementById('domain-pass-input'); i.type = i.type==='password' ? 'text' : 'password';">👁️</button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Factura de compra (PDF o imatge, opcional)</label>
                <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($edit_domain['invoice_file'])): ?>
                <p class="hint">Ja hi ha una factura pujada: <a href="../<?= htmlspecialchars($edit_domain['invoice_file']) ?>" target="_blank">veure-la</a> (puja'n una altra per substituir-la).</p>
                <?php endif; ?>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="domain_notes" rows="2" placeholder="Ex: hosting inclòs, DNS gestionat a Cloudflare..."><?= htmlspecialchars($edit_domain['notes'] ?? '') ?></textarea></div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary btn-sm"><?= $edit_domain ? 'Actualitzar domini' : '+ Afegir domini' ?></button>
                <?php if ($edit_domain): ?><a href="clients.php?id=<?= $c['id'] ?>#domainsCard" class="btn btn-secondary btn-sm">Cancel·lar</a><?php endif; ?>
            </div>
        </form>
    </div>
    <?php if (empty($c_domains)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Cap domini registrat encara amb este client.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Domini</th><th>Empresa</th><th>Gestiona</th><th>Renovació</th><th>Hosting</th><th>Cost/any</th><th>Usuari</th><th>Contrasenya</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($c_domains as $d):
            $days_left = !empty($d['renewal_date']) ? (strtotime($d['renewal_date']) - strtotime($today)) / 86400 : null;
            $soon = $days_left !== null && $days_left <= ($d['alert_days'] ?? 30);
            $host_days_left = !empty($d['hosting_renewal_date']) ? (strtotime($d['hosting_renewal_date']) - strtotime($today)) / 86400 : null;
            $host_soon = $host_days_left !== null && $host_days_left <= ($d['alert_days'] ?? 30);
            $pass_plain = !empty($d['password_enc']) ? decryptSecret($d['password_enc']) : '';
            $is_active = $d['active'] ?? true;
            $total_cost = (float)($d['cost'] ?? 0) + (float)($d['hosting_cost'] ?? 0);
        ?>
        <tr style="<?= $is_active ? '' : 'opacity:.5' ?>">
            <td>
                <strong style="font-size:.85rem"><?= htmlspecialchars($d['domain']) ?></strong>
                <?php if (!$is_active): ?><div><span class="badge badge-gray" style="font-size:.65rem">Inactiu</span></div><?php endif; ?>
                <?php if (!empty($d['invoice_file'])): ?><div><a href="../<?= htmlspecialchars($d['invoice_file']) ?>" target="_blank" style="font-size:.72rem;color:#6b7280">📄 factura</a></div><?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($d['provider'] ?: '—') ?></td>
            <td style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($domain_manager_opts[$d['managed_by'] ?? 'nosaltres'] ?? '') ?></td>
            <td style="font-size:.8rem;white-space:nowrap">
                <?php if (!empty($d['renewal_date'])): ?>
                <span class="<?= $soon ? 'badge badge-red' : '' ?>" style="<?= $soon ? '' : 'color:#6b7280' ?>">
                    <?= date('d/m/Y', strtotime($d['renewal_date'])) ?><?= $soon ? ' ⚠️' : '' ?>
                </span>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:.8rem;white-space:nowrap">
                <?php if (!empty($d['hosting_provider']) || !empty($d['hosting_renewal_date'])): ?>
                    <div style="color:#6b7280"><?= htmlspecialchars($d['hosting_provider'] ?: '—') ?></div>
                    <?php if (!empty($d['hosting_renewal_date'])): ?>
                    <span class="<?= $host_soon ? 'badge badge-red' : '' ?>" style="<?= $host_soon ? '' : 'color:#9ca3af' ?>;font-size:.75rem">
                        <?= date('d/m/Y', strtotime($d['hosting_renewal_date'])) ?><?= $host_soon ? ' ⚠️' : '' ?>
                    </span>
                    <?php endif; ?>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:#6b7280"><?= $total_cost > 0 ? number_format($total_cost, 2, ',', '.') . ' €' : '—' ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($d['username'] ?: '—') ?></td>
            <td style="font-size:.82rem">
                <?php if ($pass_plain !== ''): ?>
                <span class="dom-pass" data-pass="<?= htmlspecialchars($pass_plain) ?>" style="font-family:monospace">••••••••</span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="const s=this.previousElementSibling; s.textContent = s.textContent==='••••••••' ? s.dataset.pass : '••••••••';">👁️</button>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td class="td-actions">
                <a href="clients.php?id=<?= $c['id'] ?>&domain_id=<?= $d['id'] ?>#domainsCard" class="btn btn-sm btn-secondary">✏️</a>
                <form method="POST" onsubmit="return confirm('Eliminar este domini?')" style="display:inline">
                    <input type="hidden" name="action" value="delete_domain">
                    <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="domain_id" value="<?= $d['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p style="padding:12px 22px;font-size:.75rem;color:#9ca3af">🔒 Les contrasenyes es guarden xifrades al servidor. Encara així, evita compartir captures d'esta pantalla.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($c['id']): ?>
<div class="card" style="margin-top:20px" id="contactsCard">
    <div class="card-header">
        <div class="card-title">Historial de contactes</div>
    </div>
    <div class="card-body form-grid no-print" style="border-bottom:1px solid #eee;padding-bottom:20px;margin-bottom:4px">
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="save_contact">
            <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($edit_contact['id'] ?? '') ?>">
            <div class="form-row-2">
                <div class="form-group"><label>Data del contacte *</label><input type="date" name="date" value="<?= htmlspecialchars($edit_contact['date'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group"><label>Qui inicia el missatge</label>
                    <select name="direction">
                        <?php foreach ($directions as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($edit_contact['direction'] ?? 'jo_client') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Mitjà emprat</label>
                    <select name="channel">
                        <?php foreach ($channels as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($edit_contact['channel'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Estat</label>
                    <select name="status">
                        <?php foreach ($statuses as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($edit_contact['status'] ?? 'pendent') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Missatge (qui inicia diu...)</label><textarea name="message" rows="2" placeholder="Ex: Se li ha proposat el pressupost de la web nova..."><?= htmlspecialchars($edit_contact['message'] ?? '') ?></textarea></div>
            <div class="form-group"><label>Resposta rebuda</label><textarea name="response" rows="2" placeholder="Ex: Diu que ho ha de consultar amb el soci..."><?= htmlspecialchars($edit_contact['response'] ?? '') ?></textarea></div>
            <div class="form-row-2">
                <div class="form-group"><label>Proper seguiment (opcional)</label><input type="date" name="follow_up" value="<?= htmlspecialchars($edit_contact['follow_up'] ?? '') ?>"></div>
                <div class="form-group" style="display:flex;align-items:flex-end;gap:10px">
                    <button type="submit" class="btn btn-primary btn-sm"><?= $edit_contact ? 'Actualitzar contacte' : '+ Afegir contacte' ?></button>
                    <?php if ($edit_contact): ?><a href="clients.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Cancel·lar</a><?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($c_contacts)): ?>
    <div class="no-print" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:14px 22px;border-bottom:1px solid #eee">
        <input type="search" id="contactSearch" placeholder="🔍 Cerca en els missatges (text, mitjà, estat...)" style="flex:1;min-width:220px;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem">
        <label style="font-size:.8rem;color:#6b7280;display:flex;align-items:center;gap:6px;white-space:nowrap">
            <input type="checkbox" id="selectAllContacts"> Seleccionar visibles
        </label>
        <button type="button" class="btn btn-sm btn-secondary" onclick="printContacts(false)">🖨️ Imprimir tot</button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="printContacts(true)">🖨️ Imprimir seleccionats</button>
    </div>
    <div id="contactsNoResults" style="display:none;padding:24px;color:#9ca3af;font-size:.85rem">Cap contacte coincideix amb la cerca.</div>
    <?php endif; ?>

    <?php if (empty($c_contacts)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Cap contacte registrat encara amb este client.</div>
    <?php else: ?>
    <div class="table-wrap"><table id="contactsTable">
        <thead><tr>
            <th class="no-print" style="width:28px"></th>
            <th>Data</th><th>Direcció</th><th>Mitjà</th><th>Missatge</th><th>Resposta</th><th>Estat</th><th>Seguiment</th><th class="no-print">Accions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($c_contacts as $ct):
            $cst = contactStatusLabel($ct['status'] ?? 'pendent');
            $dir = contactDirectionLabel($ct['direction'] ?? 'jo_client');
            $searchable = mb_strtolower(implode(' ', [
                $ct['message'] ?? '', $ct['response'] ?? '', $channels[$ct['channel']] ?? '', $cst['label'], $dir['label'], $ct['date'] ?? ''
            ]));
        ?>
        <tr class="contact-row" data-search="<?= htmlspecialchars($searchable) ?>">
            <td class="no-print"><input type="checkbox" class="contact-check"></td>
            <td style="font-size:.82rem;color:#6b7280;white-space:nowrap"><?= htmlspecialchars($ct['date']) ?></td>
            <td><span class="badge <?= $dir['class'] ?>"><?= $dir['icon'] ?> <?= $dir['label'] ?></span></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($channels[$ct['channel']] ?? $ct['channel']) ?></td>
            <td style="font-size:.82rem;max-width:220px"><?= nl2br(htmlspecialchars($ct['message'] ?? '')) ?></td>
            <td style="font-size:.82rem;max-width:220px"><?= nl2br(htmlspecialchars($ct['response'] ?? '')) ?></td>
            <td><span class="badge <?= $cst['class'] ?>"><?= $cst['label'] ?></span></td>
            <td style="font-size:.82rem;color:#6b7280;white-space:nowrap"><?= htmlspecialchars($ct['follow_up'] ?? '—') ?></td>
            <td class="td-actions no-print">
                <a href="clients.php?id=<?= $c['id'] ?>&contact_id=<?= $ct['id'] ?>#contactsCard" class="btn btn-sm btn-secondary">✏️</a>
                <form method="POST" onsubmit="return confirm('Eliminar este contacte?')">
                    <input type="hidden" name="action" value="delete_contact">
                    <input type="hidden" name="client_id" value="<?= $c['id'] ?>">
                    <input type="hidden" name="contact_id" value="<?= $ct['id'] ?>">
                    <button class="btn btn-sm btn-danger">🗑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<style>
#contactsTable .badge-blue { white-space:nowrap }
@media print {
    body * { visibility: hidden; }
    #contactsCard, #contactsCard * { visibility: visible; }
    #contactsCard { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; }
    .no-print, .no-print * { display: none !important; }
    .contact-row.print-hide { display: none !important; }
}
</style>
<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const label = document.getElementById('logo-label');
            label.innerHTML = '<img src="' + e.target.result + '" class="img-preview" id="logo-preview" style="object-fit:contain;background:#fff">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
(function(){
    var search = document.getElementById('contactSearch');
    var rows = document.querySelectorAll('#contactsTable .contact-row');
    var noResults = document.getElementById('contactsNoResults');
    var selectAll = document.getElementById('selectAllContacts');

    function applySearch() {
        if (!search) return;
        var q = search.value.trim().toLowerCase();
        var visibleCount = 0;
        rows.forEach(function(row){
            var match = !q || row.getAttribute('data-search').indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (noResults) noResults.style.display = (q && visibleCount === 0) ? 'block' : 'none';
    }
    if (search) search.addEventListener('input', applySearch);

    if (selectAll) {
        selectAll.addEventListener('change', function(){
            rows.forEach(function(row){
                if (row.style.display !== 'none') {
                    var cb = row.querySelector('.contact-check');
                    if (cb) cb.checked = selectAll.checked;
                }
            });
        });
    }

    window.printContacts = function(onlySelected) {
        rows.forEach(function(row){ row.classList.remove('print-hide'); });
        if (onlySelected) {
            var anyChecked = false;
            rows.forEach(function(row){
                var cb = row.querySelector('.contact-check');
                if (cb && cb.checked) anyChecked = true;
            });
            if (!anyChecked) { alert('Selecciona almenys un contacte per imprimir, o usa "Imprimir tot".'); return; }
            rows.forEach(function(row){
                var cb = row.querySelector('.contact-check');
                if (!cb || !cb.checked) row.classList.add('print-hide');
            });
        }
        window.print();
    };
    window.addEventListener('afterprint', function(){
        rows.forEach(function(row){ row.classList.remove('print-hide'); });
    });
})();
</script>
<?php endif; ?>

<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">Historial d'auditories</div>
        <a href="audits.php?new=1&client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">+ Nova auditoria</a>
    </div>
    <?php if (empty($c_audits)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Cap auditoria realitzada encara a este client.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Data</th><th>Puntuació</th><th>Estat</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($c_audits as $a): $avg = auditScoreAvg($a); $lvl = auditScoreLabel($avg); $st = auditStatusLabel($a['estado'] ?? 'pendiente'); ?>
        <tr>
            <td style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($a['date']) ?></td>
            <td><span class="badge <?= $lvl['class'] ?>"><?= $avg ?>/10</span></td>
            <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            <td class="td-actions">
                <a href="audit-report.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">📄</a>
                <a href="audits.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">Propostes comercials</div>
        <a href="proposals.php?new=1&client=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">+ Nova proposta</a>
    </div>
    <?php if (empty($c_props)): ?>
        <div style="padding:24px;color:#9ca3af;font-size:.85rem">Cap proposta enviada encara a este client.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Tipus</th><th>Preu</th><th>Estat</th><th>Accions</th></tr></thead>
        <tbody>
        <?php foreach ($c_props as $pr): $stp = proposalStatusLabel($pr['status'] ?? 'borrador'); $tp = getProposalTypeOptions(); ?>
        <tr>
            <td style="font-size:.85rem"><?= htmlspecialchars($tp[$pr['type']] ?? $pr['type']) ?></td>
            <td style="font-weight:700;font-family:'Syne',sans-serif"><?= number_format($pr['price'], 0, ',', '.') ?> €</td>
            <td><span class="badge <?= $stp['class'] ?>"><?= $stp['label'] ?></span></td>
            <td class="td-actions"><a href="proposals.php?id=<?= $pr['id'] ?>" class="btn btn-sm btn-secondary">✏️</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>

<?php endif; ?>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
