<?php
// admin/settings.php — Configuració general del lloc web
require_once 'includes/core.php';
requireLogin();

$saved = false;
$upload_error = '';
$upload_debug = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ─── Pujada d'imatge del hero ─────────────────────────────────────────
    $hero_image = getAdminConfig()['hero_image'] ?? '';

    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $err_code = $_FILES['hero_image']['error'];
        if ($err_code !== UPLOAD_ERR_OK) {
            $err_msgs = [
                UPLOAD_ERR_INI_SIZE   => 'Fitxer massa gran (límit php.ini: upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE  => 'Fitxer massa gran (límit del formulari).',
                UPLOAD_ERR_PARTIAL    => 'Pujada incompleta. Torna-ho a intentar.',
                UPLOAD_ERR_NO_TMP_DIR => 'No existeix la carpeta temporal de PHP.',
                UPLOAD_ERR_CANT_WRITE => 'No s\'ha pogut escriure a la carpeta temporal.',
                UPLOAD_ERR_EXTENSION  => 'Una extensió de PHP ha blocat la pujada.',
            ];
            $upload_error = $err_msgs[$err_code] ?? "Error de pujada codi $err_code.";
        } elseif (!empty($_FILES['hero_image']['tmp_name'])) {
            $tmp  = $_FILES['hero_image']['tmp_name'];
            $name = $_FILES['hero_image']['name'];

            // Detecta extensió per nom si finfo no funciona
            $ext_map = ['jpg'=>'jpg','jpeg'=>'jpg','png'=>'png','webp'=>'webp','gif'=>'gif'];
            $name_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $ext = $ext_map[$name_ext] ?? '';

            // Intenta finfo per MIME (més segur)
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                $mime_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if (isset($mime_map[$mime])) $ext = $mime_map[$mime];
                else { $upload_error = "Format no permès ($mime). Usa JPG, PNG o WebP."; }
            }

            if (!$upload_error && $ext) {
                $dest_dir  = AKRA_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
                $dest_file = $dest_dir . 'hero-mockup.' . $ext;

                // Comprova que la carpeta existeix
                if (!is_dir($dest_dir)) {
                    @mkdir($dest_dir, 0755, true);
                }

                if (!is_writable($dest_dir)) {
                    $upload_error = 'La carpeta <code>assets/img/</code> no té permisos d\'escriptura. A WAMP: clic dret → Propietats → Seguretat → dona permís d\'escriptura a IUSR.';
                } else {
                    // Elimina versions anteriors
                    foreach (glob($dest_dir . 'hero-mockup.*') as $old) @unlink($old);

                    if (move_uploaded_file($tmp, $dest_file)) {
                        $hero_image = 'assets/img/hero-mockup.' . $ext;
                    } else {
                        $upload_error = 'move_uploaded_file ha fallat. Ruta destí: <code>' . htmlspecialchars($dest_file) . '</code>';
                    }
                }
            } elseif (!$upload_error) {
                $upload_error = 'Extensió no reconeguda (' . htmlspecialchars($name_ext) . '). Usa JPG, PNG o WebP.';
            }
        }
    }

    $cfg = [
        'site_name'    => sanitize($_POST['site_name'] ?? ''),
        'site_url'     => sanitize($_POST['site_url'] ?? ''),
        'phone'        => sanitize($_POST['phone'] ?? ''),
        'email'        => sanitize($_POST['email'] ?? ''),
        'address'      => sanitize($_POST['address'] ?? ''),
        'maps_url'     => sanitize($_POST['maps_url'] ?? ''),
        'instagram'    => sanitize($_POST['instagram'] ?? '#'),
        'linkedin'     => sanitize($_POST['linkedin'] ?? '#'),
        'facebook'     => sanitize($_POST['facebook'] ?? '#'),
        'tiktok'       => sanitize($_POST['tiktok'] ?? '#'),
        'ga_id'        => sanitize($_POST['ga_id'] ?? ''),
        'gtm_id'       => sanitize($_POST['gtm_id'] ?? ''),
        'hero_title_1' => sanitize($_POST['hero_title_1'] ?? ''),
        'hero_title_2' => sanitize($_POST['hero_title_2'] ?? ''),
        'hero_title_3' => sanitize($_POST['hero_title_3'] ?? ''),
        'hero_title_4' => sanitize($_POST['hero_title_4'] ?? ''),
        'hero_subtitle'=> sanitize($_POST['hero_subtitle'] ?? ''),
        'hero_image'   => $hero_image,
        'stat_projects'=> sanitize($_POST['stat_projects'] ?? '50'),
        'stat_years'   => sanitize($_POST['stat_years'] ?? '5'),
        'maintenance'  => isset($_POST['maintenance']),
        'slots_total'  => max(1, (int)($_POST['slots_total'] ?? 5)),
        'slots_used'   => max(0, (int)($_POST['slots_used']  ?? 0)),
        'slots_show'   => isset($_POST['slots_show']),
        'invoice_nif'     => sanitize($_POST['invoice_nif'] ?? ''),
        'invoice_address' => sanitize($_POST['invoice_address'] ?? ''),
        'invoice_payment' => sanitize($_POST['invoice_payment'] ?? ''),
        'payment_link'    => sanitize($_POST['payment_link'] ?? ''),
        'whatsapp_number'        => preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? ''),
        'whatsapp_float_public'  => isset($_POST['whatsapp_float_public']),
        'whatsapp_float_hub'     => isset($_POST['whatsapp_float_hub']),
        'whatsapp_float_message' => sanitize($_POST['whatsapp_float_message'] ?? ''),
        'notify_client_email'    => isset($_POST['notify_client_email']),
        'wa_notify_provider'     => in_array($_POST['wa_notify_provider'] ?? '', ['', 'twilio', 'meta']) ? $_POST['wa_notify_provider'] : '',
        'wa_notify_twilio_sid'   => sanitize($_POST['wa_notify_twilio_sid'] ?? ''),
        'wa_notify_twilio_token' => sanitize($_POST['wa_notify_twilio_token'] ?? ''),
        'wa_notify_twilio_from'  => preg_replace('/[^0-9]/', '', $_POST['wa_notify_twilio_from'] ?? ''),
        'wa_notify_meta_token'    => sanitize($_POST['wa_notify_meta_token'] ?? ''),
        'wa_notify_meta_phone_id' => sanitize($_POST['wa_notify_meta_phone_id'] ?? ''),
        'cookie_banner_enabled'   => isset($_POST['cookie_banner_enabled']),
        'cookie_consent_days'     => max(1, (int)($_POST['cookie_consent_days'] ?? 365)),
        'auto_backup_enabled'        => isset($_POST['auto_backup_enabled']),
        'auto_backup_retention_days' => max(1, (int)($_POST['auto_backup_retention_days'] ?? 30)),
        'auto_backup_sections'       => array_values(array_intersect($_POST['auto_backup_sections'] ?? [], array_keys(getBackupSections()))),
    ];
    saveAdminConfig($cfg);
    $verify = getAdminConfig();
    $saved = ($verify['phone'] === $cfg['phone'] && $verify['email'] === $cfg['email']);
}

$cfg = getAdminConfig();
$page_title    = 'Configuració';
$page_subtitle = 'Dades del negoci, xarxes socials i hero';
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configuració · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if ($saved): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Configuració guardada correctament.</div>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Error en guardar. Comprova que la carpeta <code>admin/data/</code> tinga permisos d'escriptura (chmod 755).</div>
<?php endif; ?>
<?php if ($upload_error): ?>
<div class="alert alert-error"><?= $upload_error ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-grid">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
<div class="form-grid">
    <!-- Dades del negoci -->
    <div class="card">
        <div class="card-header"><div class="card-title">🏢 Dades del negoci</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Nom del lloc web</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($cfg['site_name']) ?>">
                </div>
                <div class="form-group">
                    <label>URL del domini</label>
                    <input type="url" name="site_url" value="<?= htmlspecialchars($cfg['site_url']) ?>" placeholder="https://akratechstudio.es">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Telèfon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($cfg['phone']) ?>" placeholder="+34 600 000 000">
                </div>
                <div class="form-group">
                    <label>Email de contacte</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cfg['email']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Adreça física</label>
                <input type="text" name="address" value="<?= htmlspecialchars($cfg['address']) ?>">
            </div>
            <div class="form-group">
                <label>URL Google Maps</label>
                <input type="url" name="maps_url" value="<?= htmlspecialchars($cfg['maps_url']) ?>" placeholder="https://maps.google.com/?q=...">
            </div>
        </div>
    </div>

    <!-- Xarxes socials -->
    <div class="card">
        <div class="card-header"><div class="card-title">📱 Xarxes socials</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group"><label>Instagram</label><input type="url" name="instagram" value="<?= htmlspecialchars($cfg['instagram']) ?>" placeholder="https://instagram.com/..."></div>
                <div class="form-group"><label>LinkedIn</label><input type="url" name="linkedin" value="<?= htmlspecialchars($cfg['linkedin']) ?>" placeholder="https://linkedin.com/..."></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Facebook</label><input type="url" name="facebook" value="<?= htmlspecialchars($cfg['facebook']) ?>" placeholder="https://facebook.com/..."></div>
                <div class="form-group"><label>TikTok</label><input type="url" name="tiktok" value="<?= htmlspecialchars($cfg['tiktok']) ?>" placeholder="https://tiktok.com/..."></div>
            </div>
        </div>
    </div>

    <!-- Manteniment -->
    <div class="card">
        <div class="card-header"><div class="card-title">⚙️ Mode manteniment</div></div>
        <div class="card-body">
            <div class="toggle-wrap">
                <label class="toggle"><input type="checkbox" name="maintenance" <?= $cfg['maintenance'] ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                <span class="toggle-label">Activar mode manteniment (el lloc web mostrarà un missatge)</span>
            </div>
        </div>
    </div>

    <!-- Places disponibles -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🎯 Places disponibles aquest mes</div>
            <span style="font-size:.78rem;color:#6b7280">Apareix al hero i a la pàgina de contacte</span>
        </div>
        <div class="card-body form-grid">
            <div class="toggle-wrap" style="margin-bottom:var(--s-4)">
                <label class="toggle"><input type="checkbox" name="slots_show" <?= ($cfg['slots_show'] ?? true) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                <span class="toggle-label">Mostrar indicador de places a la web</span>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Places totals per mes</label>
                    <input type="number" name="slots_total" min="1" max="20" value="<?= (int)($cfg['slots_total'] ?? 5) ?>">
                    <p class="hint">Màxim de projectes que acceptes simultàniament.</p>
                </div>
                <div class="form-group">
                    <label>Places ja ocupades</label>
                    <input type="number" name="slots_used" min="0" max="20" value="<?= (int)($cfg['slots_used'] ?? 0) ?>">
                    <p class="hint">Actualitza quan comences un projecte nou.</p>
                </div>
            </div>
            <?php
            $s = getSlots();
            $free = $s['free'];
            $color = $free === 0 ? '#ef4444' : ($free <= 1 ? '#f59e0b' : '#22c55e');
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--a-bg);border-radius:10px;border:1px solid var(--a-border)">
                <div style="display:flex;gap:6px">
                    <?php for ($i = 0; $i < $s['total']; $i++): ?>
                    <span style="width:14px;height:14px;border-radius:50%;background:<?= $i < $free ? $color : '#e4e4e7' ?>;display:block;<?= $i < $free ? 'box-shadow:0 0 6px '.$color.'55' : '' ?>"></span>
                    <?php endfor; ?>
                </div>
                <span style="font-size:.88rem;font-weight:600;color:var(--a-text)">
                    <?= $free > 0 ? "{$free} de {$s['total']} places lliures" : '🔴 Complets aquest mes' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Dades de facturació -->
    <div class="card">
        <div class="card-header"><div class="card-title">🧾 Dades per a les factures</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group">
                    <label>NIF / DNI autònom</label>
                    <input type="text" name="invoice_nif" value="<?= htmlspecialchars($cfg['invoice_nif'] ?? '') ?>" placeholder="12345678A">
                </div>
                <div class="form-group">
                    <label>Adreça fiscal</label>
                    <input type="text" name="invoice_address" value="<?= htmlspecialchars($cfg['invoice_address'] ?? '') ?>" placeholder="Carrer, número, CP, Alacant">
                </div>
            </div>
            <div class="form-group">
                <label>Text predeterminat de pagament (apareix a totes les factures)</label>
                <textarea name="invoice_payment" rows="3" placeholder="Transferència bancària&#10;IBAN: ES00 0000 0000 00 0000000000&#10;Titular: AKRA Tech Studio"><?= htmlspecialchars($cfg['invoice_payment'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Enllaç de pagament online (opcional)</label>
                <input type="text" name="payment_link" value="<?= htmlspecialchars($cfg['payment_link'] ?? '') ?>" placeholder="Ex: enllaç de pagament de Bizum Empresa, Stripe Payment Link, Redsys...">
                <p class="hint">Si l'omplis, apareixerà un botó "Pagar ara" a l'email de les factures. Ha de ser un enllaç que ja genere el teu banc o la teua passarel·la de pagament (esta aplicació no processa pagaments directament).</p>
            </div>
        </div>
    </div>

    <!-- WhatsApp i avisos al client -->
    <div class="card">
        <div class="card-header"><div class="card-title">💬 WhatsApp i avisos al client</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Número de WhatsApp (botó flotant)</label>
                    <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($cfg['whatsapp_number'] ?? '') ?>" placeholder="34600000000">
                    <p class="hint">Format internacional, sense "+" ni espais.</p>
                </div>
                <div class="form-group">
                    <label>Missatge predeterminat del botó</label>
                    <input type="text" name="whatsapp_float_message" value="<?= htmlspecialchars($cfg['whatsapp_float_message'] ?? '') ?>" placeholder="Hola! Tinc una consulta">
                </div>
            </div>
            <div class="form-group" style="margin:0">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="whatsapp_float_public" <?= !empty($cfg['whatsapp_float_public']) ? 'checked' : '' ?>> Mostrar botó flotant a la web pública</label>
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;margin-top:6px"><input type="checkbox" name="whatsapp_float_hub" <?= !empty($cfg['whatsapp_float_hub']) ? 'checked' : '' ?>> Mostrar botó flotant al Hub del client</label>
            </div>

            <div style="border-top:1px solid var(--a-border);margin:8px 0"></div>

            <div class="form-group" style="margin:0">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="notify_client_email" <?= !empty($cfg['notify_client_email']) ? 'checked' : '' ?>> Avisar el client per email quan hi haja un canvi (treball, factura, proposta)</label>
                <p class="hint">Reutilitza el mateix sistema d'enviament que ja fas servir per a factures i propostes.</p>
            </div>

            <div style="border-top:1px solid var(--a-border);margin:8px 0"></div>

            <div class="form-group">
                <label>Avisos automàtics per WhatsApp (opcional)</label>
                <select name="wa_notify_provider" id="wa_provider" onchange="document.getElementById('wa-twilio-fields').style.display=this.value==='twilio'?'grid':'none';document.getElementById('wa-meta-fields').style.display=this.value==='meta'?'grid':'none'">
                    <option value="" <?= empty($cfg['wa_notify_provider']) ? 'selected' : '' ?>>Desactivat (encara no connectat)</option>
                    <option value="twilio" <?= ($cfg['wa_notify_provider'] ?? '') === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                    <option value="meta" <?= ($cfg['wa_notify_provider'] ?? '') === 'meta' ? 'selected' : '' ?>>Meta Cloud API (WhatsApp Business)</option>
                </select>
                <p class="hint">Mentre no trie'ns un proveïdor i afegim les seues claus, els avisos de canvis només s'enviaran per email.</p>
            </div>
            <div id="wa-twilio-fields" class="form-grid" style="display:<?= ($cfg['wa_notify_provider'] ?? '') === 'twilio' ? 'grid' : 'none' ?>;gap:14px;padding:14px;background:var(--a-bg);border-radius:10px">
                <div class="form-row-2">
                    <div class="form-group" style="margin:0"><label>Twilio Account SID</label><input type="text" name="wa_notify_twilio_sid" value="<?= htmlspecialchars($cfg['wa_notify_twilio_sid'] ?? '') ?>"></div>
                    <div class="form-group" style="margin:0"><label>Twilio Auth Token</label><input type="password" name="wa_notify_twilio_token" value="<?= htmlspecialchars($cfg['wa_notify_twilio_token'] ?? '') ?>"></div>
                </div>
                <div class="form-group" style="margin:0"><label>Número de WhatsApp de Twilio (remitent)</label><input type="text" name="wa_notify_twilio_from" value="<?= htmlspecialchars($cfg['wa_notify_twilio_from'] ?? '') ?>" placeholder="14155238886"></div>
            </div>
            <div id="wa-meta-fields" class="form-grid" style="display:<?= ($cfg['wa_notify_provider'] ?? '') === 'meta' ? 'grid' : 'none' ?>;gap:14px;padding:14px;background:var(--a-bg);border-radius:10px">
                <div class="form-group" style="margin:0"><label>Token d'accés permanent</label><input type="password" name="wa_notify_meta_token" value="<?= htmlspecialchars($cfg['wa_notify_meta_token'] ?? '') ?>"></div>
                <div class="form-group" style="margin:0"><label>Phone Number ID</label><input type="text" name="wa_notify_meta_phone_id" value="<?= htmlspecialchars($cfg['wa_notify_meta_phone_id'] ?? '') ?>"></div>
            </div>
        </div>
    </div>

    <!-- Banner de cookies -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🍪 Banner de consentiment de cookies</div>
            <span class="badge <?= !empty($cfg['cookie_banner_enabled']) ? 'badge-green' : 'badge-gray' ?>"><?= !empty($cfg['cookie_banner_enabled']) ? 'Actiu' : 'Desactivat' ?></span>
        </div>
        <div class="card-body form-grid">
            <div class="form-group" style="margin:0">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="cookie_banner_enabled" <?= !empty($cfg['cookie_banner_enabled']) ? 'checked' : '' ?>> Mostrar el banner de cookies a la web pública</label>
                <p class="hint">
                    Apareix a baix de tot en la primera visita, amb botons Acceptar/Rebutjar, i ja està traduït als 5 idiomes del lloc.
                    Enllaça amb les pàgines de <a href="../pages/cookies.php" target="_blank">Política de Cookies</a> i
                    <a href="../pages/privacitat.php" target="_blank">Privacitat</a>, que ja existeixen i estan publicades.
                    Si el desactives, cap visitant el veurà — útil nomes si gestiones el consentiment per un altre mitjà.
                </p>
            </div>
            <div class="form-group">
                <label>Caducitat del consentiment (dies)</label>
                <input type="number" name="cookie_consent_days" min="1" max="730" value="<?= (int)($cfg['cookie_consent_days'] ?? 365) ?>" style="max-width:140px">
                <p class="hint">Passat este temps des que el visitant va acceptar o rebutjar, se li tornarà a mostrar el banner. Recomanat: 365 dies (1 any), tal com marca el RGPD/AEPD. Si canvies este número, s'aplica a partir d'eixe moment — no afecta el compte arrere dels consentiments ja donats.</p>
            </div>
        </div>
    </div>

    <!-- Còpies de seguretat automàtiques -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">💾 Còpies de seguretat automàtiques</div>
            <a href="backup.php" class="btn btn-sm btn-secondary">Anar a Còpia de seguretat →</a>
        </div>
        <div class="card-body form-grid">
            <div class="form-group" style="margin:0">
                <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="auto_backup_enabled" <?= !empty($cfg['auto_backup_enabled']) ? 'checked' : '' ?>> Activar còpies de seguretat automàtiques</label>
                <p class="hint">Cal, a més, programar <code>admin/cron_backup.php</code> perquè s'execute periòdicament (mira la pàgina de Còpia de seguretat per a les instruccions exactes) — este interruptor només decideix si eixa execució fa alguna cosa o no.</p>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Retenció (dies)</label>
                    <input type="number" name="auto_backup_retention_days" min="1" max="365" value="<?= (int)($cfg['auto_backup_retention_days'] ?? 30) ?>">
                    <p class="hint">Les còpies automàtiques més antigues que açò s'esborren soles a cada execució.</p>
                </div>
            </div>
            <div class="form-group">
                <label>Seccions a incloure a la còpia automàtica</label>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;font-size:.82rem">
                    <?php $auto_sections = $cfg['auto_backup_sections'] ?? []; foreach (getBackupSections() as $key => $s): ?>
                    <label style="display:flex;align-items:center;gap:7px;font-weight:400">
                        <input type="checkbox" name="auto_backup_sections[]" value="<?= $key ?>" <?= in_array($key, $auto_sections) ? 'checked' : '' ?>> <?= htmlspecialchars($s['label']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="hint">Si no marques cap secció, la còpia automàtica inclourà TOTES les dades (recomanat).</p>
            </div>
        </div>
    </div>
</div>

<div class="form-grid">
    <!-- Hero section -->
    <div class="card">
        <div class="card-header"><div class="card-title">🦸 Secció Hero</div></div>
        <div class="card-body form-grid">
            <p style="font-size:.82rem;color:#6b7280;margin-bottom:4px">El titular apareix com: "[Títol 1] <strong style="color:#c9a84c">[Accentuat 1]</strong> [Títol 3] <strong style="color:#c9a84c">[Accentuat 2]</strong>"</p>
            <div class="form-row-2">
                <div class="form-group"><label>Títol 1</label><input type="text" name="hero_title_1" value="<?= htmlspecialchars($cfg['hero_title_1']) ?>" placeholder="Webs que"></div>
                <div class="form-group"><label>Accentuat 1 (or)</label><input type="text" name="hero_title_2" value="<?= htmlspecialchars($cfg['hero_title_2']) ?>" placeholder="venen."></div>
            </div>
            <div class="form-row-2">
                <div class="form-group"><label>Títol 3</label><input type="text" name="hero_title_3" value="<?= htmlspecialchars($cfg['hero_title_3']) ?>" placeholder="Marques que"></div>
                <div class="form-group"><label>Accentuat 2 (or)</label><input type="text" name="hero_title_4" value="<?= htmlspecialchars($cfg['hero_title_4']) ?>" placeholder="es recorden."></div>
            </div>
            <div class="form-group">
                <label>Subtítol del hero</label>
                <textarea name="hero_subtitle" rows="2"><?= htmlspecialchars($cfg['hero_subtitle']) ?></textarea>
            </div>

            <!-- Imatge del mockup hero -->
            <div class="form-group">
                <label>Imatge del mockup (apareix a la dreta del hero)</label>
                <?php $hi = $cfg['hero_image'] ?? ''; ?>
                <?php if ($hi && file_exists(AKRA_ROOT . '/' . $hi)): ?>
                <div style="margin-bottom:12px;display:flex;align-items:center;gap:12px">
                    <img src="<?= htmlspecialchars('../' . $hi) ?>?v=<?= filemtime(AKRA_ROOT . '/' . $hi) ?>"
                         alt="Hero actual" style="height:80px;border-radius:8px;border:1px solid var(--a-border);object-fit:cover">
                    <div>
                        <div style="font-size:.82rem;color:#22c55e;font-weight:600">✓ Imatge activa</div>
                        <div style="font-size:.75rem;color:#9ca3af"><?= htmlspecialchars($hi) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div style="margin-bottom:12px;padding:10px 14px;background:var(--a-bg);border-radius:8px;font-size:.82rem;color:#9ca3af">
                    Cap imatge pujada — el hero mostrarà un element gràfic per defecte.
                </div>
                <?php endif; ?>
                <div class="upload-drop" id="hero-drop">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/></svg>
                    <span>Arrossega una imatge o <label for="hero_image_input" style="color:var(--a-accent);cursor:pointer;text-decoration:underline">tria fitxer</label></span>
                    <span style="font-size:.75rem;color:#9ca3af">JPG, PNG o WebP · Recomanat 1200×800px · Màx 5MB</span>
                    <input type="file" id="hero_image_input" name="hero_image" accept="image/jpeg,image/png,image/webp" style="display:none">
                </div>
                <div id="hero-preview" style="display:none;margin-top:10px">
                    <img id="hero-preview-img" style="max-height:120px;border-radius:8px;border:1px solid var(--a-border)">
                    <span id="hero-preview-name" style="font-size:.78rem;color:#6b7280;display:block;margin-top:4px"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats hero -->
    <div class="card">
        <div class="card-header"><div class="card-title">📊 Comptadors del Hero</div></div>
        <div class="card-body form-grid">
            <div class="form-row-2">
                <div class="form-group"><label>Nº de projectes</label><input type="number" name="stat_projects" value="<?= htmlspecialchars($cfg['stat_projects']) ?>"></div>
                <div class="form-group"><label>Anys d'experiència</label><input type="number" name="stat_years" value="<?= htmlspecialchars($cfg['stat_years']) ?>"></div>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="card">
        <div class="card-header"><div class="card-title">📈 Analytics i Tracking</div></div>
        <div class="card-body form-grid">
            <div class="form-group">
                <label>Google Analytics 4 ID</label>
                <input type="text" name="ga_id" value="<?= htmlspecialchars($cfg['ga_id']) ?>" placeholder="G-XXXXXXXXXX">
                <p class="hint">Deixa buit per no activar. S'inserirà automàticament al header.</p>
            </div>
            <div class="form-group">
                <label>Google Tag Manager ID</label>
                <input type="text" name="gtm_id" value="<?= htmlspecialchars($cfg['gtm_id']) ?>" placeholder="GTM-XXXXXXX">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Guardar i sincronitzar amb el frontend
    </button>
</div>
</div>
</form>
</div></div>
<?php include 'includes/admin-footer.php'; ?>
<script>
// Preview imatge hero
const input   = document.getElementById('hero_image_input');
const preview = document.getElementById('hero-preview');
const preImg  = document.getElementById('hero-preview-img');
const preName = document.getElementById('hero-preview-name');
const drop    = document.getElementById('hero-drop');

function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
        preImg.src = e.target.result;
        preName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

input?.addEventListener('change', () => showPreview(input.files[0]));

drop?.addEventListener('dragover',  e => { e.preventDefault(); drop.classList.add('dragover'); });
drop?.addEventListener('dragleave', () => drop.classList.remove('dragover'));
drop?.addEventListener('drop', e => {
    e.preventDefault();
    drop.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    }
});
</script>
