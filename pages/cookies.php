<?php
require_once '../includes/config.php';
$titles = ['ca'=>'Política de Cookies','es'=>'Política de Cookies','en'=>'Cookie Policy','fr'=>'Politique de Cookies','it'=>'Politica sui Cookie'];
$title = $titles[$current_lang] ?? $titles['es'];
$page_seo = ['title' => $title . ' | AKRA Tech Studio', 'description' => $title . ' de AKRA Tech Studio', 'canonical' => SITE_URL . '/pages/cookies.php'];
include '../includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= $title ?></h1></div></section>
<section class="section section--white"><div class="container"><div class="legal-content">

<?php if ($current_lang === 'es'): ?>
<h2>¿Qué son las cookies?</h2>
<p>Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas una web. Nos ayudan a que la web funcione correctamente y a mejorar tu experiencia.</p>

<h2>Cookies que usamos</h2>
<table class="cookies-table">
  <thead><tr><th>Nombre</th><th>Tipo</th><th>Duración</th><th>Finalidad</th></tr></thead>
  <tbody>
    <tr><td>cookie_consent</td><td>Propia</td><td>1 año</td><td>Guardar tu preferencia de cookies</td></tr>
    <tr><td>lang</td><td>Sesión</td><td>Sesión</td><td>Recordar el idioma seleccionado</td></tr>
    <tr><td>_ga / _gid</td><td>Analítica</td><td>2 años / 24h</td><td>Google Analytics — estadísticas de visitas (solo si aceptas)</td></tr>
  </tbody>
</table>

<h2>¿Cómo gestionar las cookies?</h2>
<p>Puedes aceptar o rechazar cookies no esenciales con el banner que aparece en tu primera visita. También puedes configurar tu navegador para bloquearlas o eliminarlas.</p>

<h2>Más información</h2>
<p>Consulta nuestra <a href="<?= pageUrl('privacitat') ?>">Política de Privacidad</a> o escríbenos a <?= CONTACT_EMAIL ?>.</p>

<?php else: ?>
<h2>Què són les cookies?</h2>
<p>Les cookies són petits fitxers de text que s'emmagatzemen al teu dispositiu quan visites una web. Ens ajuden que la web funcioni correctament i a millorar la teua experiència.</p>

<h2>Cookies que usem</h2>
<table class="cookies-table">
  <thead><tr><th>Nom</th><th>Tipus</th><th>Durada</th><th>Finalitat</th></tr></thead>
  <tbody>
    <tr><td>cookie_consent</td><td>Pròpia</td><td>1 any</td><td>Guardar la teua preferència de cookies</td></tr>
    <tr><td>lang</td><td>Sessió</td><td>Sessió</td><td>Recordar l'idioma seleccionat</td></tr>
    <tr><td>_ga / _gid</td><td>Analítica</td><td>2 anys / 24h</td><td>Google Analytics — estadístiques de visites (només si acceptes)</td></tr>
  </tbody>
</table>

<h2>Com gestionar les cookies?</h2>
<p>Pots acceptar o rebutjar cookies no essencials amb el banner que apareix en la teua primera visita. També pots configurar el navegador per bloquejar-les o eliminar-les.</p>

<h2>Més informació</h2>
<p>Consulta la nostra <a href="<?= pageUrl('privacitat') ?>">Política de Privacitat</a> o escriu-nos a <?= CONTACT_EMAIL ?>.</p>
<?php endif; ?>

</div></div></section>
<?php include '../includes/footer.php'; ?>
<style>
.page-hero{padding:100px 0 60px;background:var(--c-bg);border-bottom:1px solid var(--c-border)}
.page-hero h1{font-family:var(--f-display);font-size:clamp(1.8rem,3vw,2.5rem);font-weight:700;margin:var(--s-2) 0 var(--s-2)}
.legal-content{max-width:720px;margin:0 auto}
.legal-content h2{font-size:1.1rem;font-weight:700;margin:var(--s-5) 0 var(--s-2);padding-top:var(--s-4);border-top:1px solid var(--c-border)}
.legal-content h2:first-child{border-top:none;padding-top:0}
.legal-content p{color:var(--c-text-muted);line-height:1.8;margin-bottom:var(--s-3)}
.legal-content a{color:var(--c-primary);font-weight:600}
.cookies-table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:var(--s-4)}
.cookies-table th{background:var(--c-bg);padding:10px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--c-text-muted)}
.cookies-table td{padding:10px 12px;border-bottom:1px solid var(--c-border);color:var(--c-text-muted)}
</style>
