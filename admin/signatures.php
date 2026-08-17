<?php
require_once 'includes/core.php';
requireLogin();

$cfg = getAdminConfig();
$page_title    = 'Signatures digitals';
$page_subtitle = 'Genera firmes HTML per a Gmail, Outlook i Apple Mail';

// Guarda configuració de firmes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_sig_config') {
    $sig_cfg = [
        'sig_company'  => sanitize($_POST['sig_company'] ?? 'AKRA Tech Studio'),
        'sig_tagline'  => sanitize($_POST['sig_tagline'] ?? 'Disseny web · SEO · Màrqueting digital'),
        'sig_phone'    => sanitize($_POST['sig_phone']   ?? $cfg['phone'] ?? ''),
        'sig_email'    => sanitize($_POST['sig_email']   ?? $cfg['email'] ?? ''),
        'sig_web'      => sanitize($_POST['sig_web']     ?? $cfg['site_url'] ?? ''),
        'sig_linkedin' => sanitize($_POST['sig_linkedin']?? $cfg['linkedin'] ?? ''),
        'sig_instagram'=> sanitize($_POST['sig_instagram']??$cfg['instagram']?? ''),
        'sig_color'    => preg_replace('/[^#a-fA-F0-9]/', '', $_POST['sig_color'] ?? '#0a0a0a'),
        'sig_accent'   => preg_replace('/[^#a-fA-F0-9]/', '', $_POST['sig_accent'] ?? '#c9a84c'),
        'sig_logo_url' => sanitize($_POST['sig_logo_url'] ?? ''),
    ];
    // Merge into global config
    foreach ($sig_cfg as $k => $v) $cfg[$k] = $v;
    writeData('site_config', $cfg);
    $success = 'Configuració guardada.';
}

// Upload logo per signatures
if (!empty($_FILES['sig_logo']['name'])) {
    $upload = uploadImage($_FILES['sig_logo'], 'sig');
    if ($upload['ok']) {
        $cfg['sig_logo_url'] = SITE_URL . '/' . $upload['path'];
        writeData('site_config', $cfg);
        $success = 'Logo pujat.';
    }
}

// Valors actuals amb defaults
$sc = $cfg['sig_company']   ?? 'AKRA Tech Studio';
$st = $cfg['sig_tagline']   ?? 'Disseny web · SEO · Màrqueting digital';
$sp = $cfg['sig_phone']     ?? ($cfg['phone'] ?? '+34 600 000 000');
$se = $cfg['sig_email']     ?? ($cfg['email'] ?? 'hola@akratechstudio.es');
$sw = $cfg['sig_web']       ?? ($cfg['site_url'] ?? 'https://akratechstudio.es');
$sl = $cfg['sig_linkedin']  ?? ($cfg['linkedin'] ?? '');
$si = $cfg['sig_instagram'] ?? ($cfg['instagram'] ?? '');
$scolor  = $cfg['sig_color']  ?? '#0a0a0a';
$saccent = $cfg['sig_accent'] ?? '#c9a84c';
$slogo   = $cfg['sig_logo_url'] ?? (SITE_URL . '/assets/img/logo.png');
?>
<!DOCTYPE html><html lang="ca"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Signatures · AKRA Admin</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
.sig-wrap { display:grid; grid-template-columns:360px 1fr; gap:24px; align-items:start; }
.sig-templates { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.sig-tab { padding:8px 18px; border-radius:8px; border:1.5px solid #e4e4e7; font-size:.82rem; font-weight:600; cursor:pointer; background:#fff; color:#3f3f46; transition:all .15s; }
.sig-tab.active { background:#0a0a0a; color:#fff; border-color:#0a0a0a; }
.sig-preview-card { background:#fff; border:1px solid #e4e4e7; border-radius:16px; overflow:hidden; }
.sig-preview-header { background:#f9fafb; border-bottom:1px solid #e4e4e7; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; }
.sig-preview-header span { font-size:.78rem; color:#6b7280; font-weight:500; }
.sig-preview-body { padding:32px; min-height:200px; background:#fff; }
.sig-copy-btns { display:flex; gap:10px; padding:16px 20px; background:#f9fafb; border-top:1px solid #e4e4e7; }
.sig-hint { font-size:.78rem; color:#6b7280; padding:12px 20px 16px; }
.color-row { display:flex; gap:12px; align-items:end; }
.color-row .form-group { flex:1; }
input[type=color] { width:100%; height:40px; border:1px solid #e4e4e7; border-radius:8px; cursor:pointer; padding:2px; }
.sig-person-fields { background:#f9fafb; border-radius:10px; padding:16px; margin-bottom:16px; border:1px solid #e4e4e7; }
.person-row { display:flex; gap:10px; align-items:center; margin-bottom:10px; }
.person-row:last-child { margin-bottom:0; }
.person-row input { flex:1; }
.btn-del-person { background:none; border:none; cursor:pointer; color:#ef4444; font-size:1.1rem; padding:4px; }
.add-person-btn { font-size:.82rem; color:#c9a84c; background:none; border:1px dashed #c9a84c; padding:6px 14px; border-radius:8px; cursor:pointer; font-weight:600; }
</style>
</head><body>
<?php include 'includes/layout.php'; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="sig-wrap">

<!-- ── PANELL ESQUERRA: Configuració ── -->
<div>
<form method="POST" enctype="multipart/form-data" class="form-grid">
<input type="hidden" name="action" value="save_sig_config">

<div class="card">
    <div class="card-header"><div class="card-title">🏢 Empresa</div></div>
    <div class="card-body form-grid">
        <div class="form-group">
            <label>Nom de l'empresa</label>
            <input type="text" name="sig_company" id="sc" value="<?= htmlspecialchars($sc) ?>">
        </div>
        <div class="form-group">
            <label>Tagline / especialitat</label>
            <input type="text" name="sig_tagline" id="st" value="<?= htmlspecialchars($st) ?>">
        </div>
        <div class="form-group">
            <label>Telèfon</label>
            <input type="tel" name="sig_phone" id="sp" value="<?= htmlspecialchars($sp) ?>">
        </div>
        <div class="form-group">
            <label>Email de contacte</label>
            <input type="email" name="sig_email" id="se" value="<?= htmlspecialchars($se) ?>">
        </div>
        <div class="form-group">
            <label>Web</label>
            <input type="url" name="sig_web" id="sw" value="<?= htmlspecialchars($sw) ?>">
        </div>
        <div class="form-group">
            <label>LinkedIn URL</label>
            <input type="url" name="sig_linkedin" id="sl" value="<?= htmlspecialchars($sl) ?>">
        </div>
        <div class="form-group">
            <label>Instagram URL</label>
            <input type="url" name="sig_instagram" id="si" value="<?= htmlspecialchars($si) ?>">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">🎨 Colors i logo</div></div>
    <div class="card-body form-grid">
        <div class="color-row">
            <div class="form-group">
                <label>Color principal</label>
                <input type="color" name="sig_color" id="scolor" value="<?= htmlspecialchars($scolor) ?>">
            </div>
            <div class="form-group">
                <label>Color accent</label>
                <input type="color" name="sig_accent" id="saccent" value="<?= htmlspecialchars($saccent) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>URL logo (absoluta per a emails)</label>
            <input type="url" name="sig_logo_url" id="slogo" value="<?= htmlspecialchars($slogo) ?>">
            <p class="hint">Ha de ser una URL pública (https://). El logo s'incrusta per referència en emails HTML.</p>
        </div>
        <div class="form-group">
            <label>O puja un logo nou</label>
            <input type="file" name="sig_logo" accept="image/png,image/jpeg,image/webp">
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary" style="width:100%">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    Guardar configuració
</button>
</form>

<!-- Persones de l'equip (local, no es guarda al servidor — per a la firma personal) -->
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">👤 Persona (firma personal)</div>
        <span style="font-size:.75rem;color:#9ca3af">Només per a la previsualització local</span>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" id="pname" placeholder="Joan Garcia" oninput="updatePreview()">
            </div>
            <div class="form-group">
                <label>Càrrec / rol</label>
                <input type="text" id="prole" placeholder="Desenvolupador web · AKRA" oninput="updatePreview()">
            </div>
        </div>
    </div>
</div>
</div>

<!-- ── PANELL DRET: Preview + plantilles ── -->
<div>
    <div class="sig-templates">
        <button class="sig-tab active" onclick="setTemplate('minimal',this)">Mínima</button>
        <button class="sig-tab" onclick="setTemplate('standard',this)">Estàndard</button>
        <button class="sig-tab" onclick="setTemplate('branded',this)">Branded</button>
    </div>

    <div class="sig-preview-card">
        <div class="sig-preview-header">
            <span>📧 Previsualització — com es veu al destinatari</span>
            <span id="tpl-label" style="font-size:.72rem;background:#f3f4f6;padding:3px 10px;border-radius:100px">Template: Mínima</span>
        </div>
        <div class="sig-preview-body" id="sig-preview-body">
            <!-- Render dinàmic via JS -->
        </div>
        <div class="sig-hint">
            ⚠️ Les signatures HTML van sempre amb taules i estils inline. No s'aplica cap framework extern.
        </div>
        <div class="sig-copy-btns">
            <button class="btn btn-primary" onclick="copyHTML()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                Copiar HTML
            </button>
            <button class="btn btn-secondary" onclick="copyForGmail()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/></svg>
                Copiar per Gmail (render)
            </button>
            <button class="btn btn-secondary" onclick="downloadHTML()">⬇ Descarregar .html</button>
        </div>
    </div>

    <!-- Instruccions -->
    <div class="card" style="margin-top:20px">
        <div class="card-header"><div class="card-title">📖 Com instal·lar la firma</div></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
            <details open>
                <summary style="font-weight:600;cursor:pointer;padding:8px 0;font-size:.88rem">Gmail</summary>
                <ol style="font-size:.82rem;color:#6b7280;line-height:1.8;margin:8px 0 0 16px">
                    <li>Clica <strong>Copiar per Gmail (render)</strong></li>
                    <li>Obre Gmail → ⚙️ Configuració → Veure tota la configuració</li>
                    <li>Baixa fins a <strong>Firma</strong> → Crea una firma nova</li>
                    <li>Pega (Ctrl+V) directament al quadre de text</li>
                    <li>Desa els canvis</li>
                </ol>
            </details>
            <details>
                <summary style="font-weight:600;cursor:pointer;padding:8px 0;font-size:.88rem">Outlook / Windows</summary>
                <ol style="font-size:.82rem;color:#6b7280;line-height:1.8;margin:8px 0 0 16px">
                    <li>Clica <strong>Descarregar .html</strong></li>
                    <li>Obre Outlook → Fitxer → Opcions → Correu → Firmes</li>
                    <li>Crea una firma nova → clica <strong>Editar firma</strong></li>
                    <li>Obre el fitxer .html amb el Bloc de Notes, copia el contingut</li>
                    <li>A Outlook pega'l (usarà el format HTML)</li>
                </ol>
            </details>
            <details>
                <summary style="font-weight:600;cursor:pointer;padding:8px 0;font-size:.88rem">Apple Mail (macOS)</summary>
                <ol style="font-size:.82rem;color:#6b7280;line-height:1.8;margin:8px 0 0 16px">
                    <li>Descarrega el .html i obre'l al navegador</li>
                    <li>Selecciona tot (Cmd+A) i copia (Cmd+C)</li>
                    <li>Obre Mail → Preferències → Firmes</li>
                    <li>Crea una firma nova i pega (Cmd+V)</li>
                </ol>
            </details>
        </div>
    </div>
</div>

</div><!-- /sig-wrap -->
</div></div>

<?php include 'includes/admin-footer.php'; ?>

<script>
// ── Dades des del PHP ──────────────────────────────────────────────────────
const D = {
    company:   document.getElementById('sc')?.value     || 'AKRA Tech Studio',
    tagline:   document.getElementById('st')?.value     || 'Disseny web · SEO · Màrqueting digital',
    phone:     document.getElementById('sp')?.value     || '',
    email:     document.getElementById('se')?.value     || '',
    web:       document.getElementById('sw')?.value     || '',
    linkedin:  document.getElementById('sl')?.value     || '',
    instagram: document.getElementById('si')?.value     || '',
    color:     document.getElementById('scolor')?.value || '#0a0a0a',
    accent:    document.getElementById('saccent')?.value|| '#c9a84c',
    logo:      document.getElementById('slogo')?.value  || '',
};

let currentTemplate = 'minimal';

// Sincronitza canvis dels inputs en temps real
['sc','st','sp','se','sw','sl','si','scolor','saccent','slogo'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => {
        const map = {sc:'company',st:'tagline',sp:'phone',se:'email',sw:'web',sl:'linkedin',si:'instagram',scolor:'color',saccent:'accent',slogo:'logo'};
        D[map[id]] = el.value;
        updatePreview();
    });
});

function getData() {
    return {
        ...D,
        name: document.getElementById('pname')?.value || '',
        role: document.getElementById('prole')?.value || '',
    };
}

// ── Templates ──────────────────────────────────────────────────────────────
function buildMinimal(d) {
    const name = d.name ? `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:15px;font-weight:700;color:${d.color};letter-spacing:-.3px;margin-bottom:2px">${esc(d.name)}</div>` : '';
    const role = d.role ? `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:12px;color:#6b7280;margin-bottom:10px">${esc(d.role)}</div>` : '';
    const socials = buildSocials(d, 'light');
    return `<table cellpadding="0" cellspacing="0" border="0" style="font-family:'Helvetica Neue',Arial,sans-serif;">
  <tr><td style="padding-right:18px;border-right:3px solid ${d.accent};vertical-align:top">
    ${name}${role}
    <div style="font-size:13px;font-weight:700;color:${d.color};margin-bottom:1px">${esc(d.company)}</div>
    <div style="font-size:11px;color:#9ca3af;margin-bottom:8px">${esc(d.tagline)}</div>
    ${buildContact(d, '12')}
    ${socials}
  </td></tr>
</table>`;
}

function buildStandard(d) {
    const name = d.name ? `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:700;color:${d.color};letter-spacing:-.3px">${esc(d.name)}</div>` : '';
    const role = d.role ? `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:12px;color:${d.accent};font-weight:600;margin-bottom:6px">${esc(d.role)}</div>` : '';
    const logoHtml = d.logo ? `<img src="${esc(d.logo)}" width="120" height="auto" alt="${esc(d.company)}" style="display:block;margin-bottom:10px">` : `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:18px;font-weight:800;color:${d.color};letter-spacing:-.04em;margin-bottom:10px">${esc(d.company)}</div>`;

    return `<table cellpadding="0" cellspacing="0" border="0" style="font-family:'Helvetica Neue',Arial,sans-serif;border-top:2px solid ${d.accent};padding-top:14px">
  <tr>
    <td style="padding-right:20px;border-right:1px solid #e4e4e7;vertical-align:top;min-width:160px">
      ${logoHtml}
      <div style="font-size:11px;color:#9ca3af">${esc(d.tagline)}</div>
    </td>
    <td style="padding-left:20px;vertical-align:top">
      ${name}${role}
      ${buildContact(d, '13')}
      <div style="margin-top:8px">${buildSocials(d, 'dark')}</div>
    </td>
  </tr>
</table>`;
}

function buildBranded(d) {
    const name = d.name ? `<span style="font-size:15px;font-weight:700;color:#fff">${esc(d.name)}</span>` : '';
    const role = d.role ? `<div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:1px;margin-bottom:10px">${esc(d.role)}</div>` : '<div style="margin-bottom:10px"></div>';
    const logoHtml = d.logo ? `<img src="${esc(d.logo)}" width="110" height="auto" alt="${esc(d.company)}" style="display:block;margin-bottom:8px;filter:brightness(0) invert(1)">` : `<div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:17px;font-weight:800;color:#fff;letter-spacing:-.04em;margin-bottom:8px">${esc(d.company)}</div>`;

    return `<table cellpadding="0" cellspacing="0" border="0" style="font-family:'Helvetica Neue',Arial,sans-serif;background:${d.color};border-radius:12px;overflow:hidden">
  <tr>
    <td style="padding:20px 24px;border-right:1px solid rgba(255,255,255,.1);min-width:170px;vertical-align:top">
      ${logoHtml}
      <div style="font-size:10px;color:rgba(255,255,255,.45);letter-spacing:.06em;text-transform:uppercase">${esc(d.tagline)}</div>
    </td>
    <td style="padding:20px 24px;vertical-align:top">
      ${name}${role}
      ${buildContact(d, '12', true)}
      <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.1)">${buildSocials(d, 'inverse')}</div>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="background:${d.accent};padding:8px 24px">
      <a href="${esc(d.web)}" style="font-size:11px;font-weight:700;color:${d.color};text-decoration:none;letter-spacing:.04em;text-transform:uppercase">${esc(d.web.replace('https://','').replace('http://',''))}</a>
    </td>
  </tr>
</table>`;
}

function buildContact(d, size, inv=false) {
    const c = inv ? 'rgba(255,255,255,.7)' : '#4b5563';
    const ca = inv ? '#fff' : d.color;
    let html = '';
    if (d.phone) html += `<div style="font-size:${size}px;color:${c};margin-bottom:3px">📞 <a href="tel:${esc(d.phone)}" style="color:${ca};text-decoration:none">${esc(d.phone)}</a></div>`;
    if (d.email) html += `<div style="font-size:${size}px;color:${c};margin-bottom:3px">✉️ <a href="mailto:${esc(d.email)}" style="color:${ca};text-decoration:none">${esc(d.email)}</a></div>`;
    if (d.web)   html += `<div style="font-size:${size}px;color:${c}">🌐 <a href="${esc(d.web)}" style="color:${ca};text-decoration:none">${esc(d.web.replace('https://',''))}</a></div>`;
    return html;
}

function buildSocials(d, mode) {
    const links = [];
    if (d.linkedin)  links.push({url: d.linkedin,  label: 'LinkedIn'});
    if (d.instagram) links.push({url: d.instagram, label: 'Instagram'});
    if (!links.length) return '';
    const colors = { light: '#9ca3af', dark: '#6b7280', inverse: 'rgba(255,255,255,.55)' };
    const c = colors[mode];
    return `<div style="margin-top:6px">` + links.map(l =>
        `<a href="${esc(l.url)}" style="font-size:11px;font-weight:600;color:${c};text-decoration:none;margin-right:10px;letter-spacing:.04em">${l.label} ↗</a>`
    ).join('') + `</div>`;
}

function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Render ─────────────────────────────────────────────────────────────────
function updatePreview() {
    const d = getData();
    let html = '';
    if (currentTemplate === 'minimal')   html = buildMinimal(d);
    if (currentTemplate === 'standard')  html = buildStandard(d);
    if (currentTemplate === 'branded')   html = buildBranded(d);
    document.getElementById('sig-preview-body').innerHTML = html;
}

function setTemplate(tpl, btn) {
    currentTemplate = tpl;
    document.querySelectorAll('.sig-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const labels = {minimal:'Mínima', standard:'Estàndard', branded:'Branded'};
    document.getElementById('tpl-label').textContent = 'Template: ' + labels[tpl];
    updatePreview();
}

function getSignatureHTML() {
    const d = getData();
    if (currentTemplate === 'minimal')  return buildMinimal(d);
    if (currentTemplate === 'standard') return buildStandard(d);
    if (currentTemplate === 'branded')  return buildBranded(d);
}

async function copyHTML() {
    try {
        await navigator.clipboard.writeText(getSignatureHTML());
        showToast('✅ HTML copiat al portapapers');
    } catch(e) {
        fallbackCopy(getSignatureHTML());
    }
}

// Per a Gmail: copia el render visual, no el codi
function copyForGmail() {
    const preview = document.getElementById('sig-preview-body');
    const range = document.createRange();
    range.selectNodeContents(preview);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
    try {
        document.execCommand('copy');
        sel.removeAllRanges();
        showToast('✅ Firma copiada! Pega-la a Gmail.');
    } catch(e) {
        showToast('❌ Error en copiar. Usa el botó "Copiar HTML".');
    }
}

function downloadHTML() {
    const d = getData();
    const full = `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Firma ${esc(d.name || d.company)}</title></head>
<body style="margin:40px;font-family:Helvetica Neue,Arial,sans-serif;background:#f9f9f9">
<p style="font-size:11px;color:#9ca3af;margin-bottom:24px">Firma digital — ${esc(d.company)}</p>
${getSignatureHTML()}
<p style="font-size:10px;color:#d1d5db;margin-top:32px">Generada amb AKRA Admin · ${new Date().toLocaleDateString('ca')}</p>
</body></html>`;
    const blob = new Blob([full], {type:'text/html'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `firma-akra-${currentTemplate}-${Date.now()}.html`;
    a.click();
}

function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position='fixed'; ta.style.opacity='0';
    document.body.appendChild(ta); ta.select();
    document.execCommand('copy'); document.body.removeChild(ta);
    showToast('✅ HTML copiat!');
}

function showToast(msg) {
    let t = document.getElementById('sig-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'sig-toast';
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1a1a2e;color:#fff;padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:opacity .3s';
        document.body.appendChild(t);
    }
    t.textContent = msg; t.style.opacity = '1';
    setTimeout(() => t.style.opacity = '0', 2800);
}

// Init
updatePreview();
</script>
