<?php
require_once '../includes/config.php';
$titles = ['ca'=>'Política de Privacitat','es'=>'Política de Privacidad','en'=>'Privacy Policy','fr'=>'Politique de Confidentialité','it'=>'Informativa sulla Privacy'];
$title = $titles[$current_lang] ?? $titles['es'];
$page_seo = ['title' => $title . ' | AKRA Tech Studio', 'description' => $title . ' de AKRA Tech Studio', 'canonical' => SITE_URL . '/pages/privacitat.php'];
include '../includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= $title ?></h1><p><?= SITE_URL ?></p></div></section>
<section class="section section--white"><div class="container"><div class="legal-content">

<?php if ($current_lang === 'es'): ?>
<h2>Responsable del tratamiento</h2>
<p><strong>AKRA Tech Studio</strong> · <?= CONTACT_EMAIL ?> · <?= CONTACT_PHONE ?></p>

<h2>Datos que recogemos</h2>
<p>Recogemos únicamente los datos que nos proporcionas voluntariamente a través del formulario de contacto: nombre, email y mensaje. No recogemos datos sensibles ni realizamos perfilado automático.</p>

<h2>Finalidad y base legal</h2>
<p>Los datos se usan exclusivamente para responderte y gestionar la relación comercial. Base legal: consentimiento expreso del interesado (art. 6.1.a RGPD).</p>

<h2>Conservación</h2>
<p>Conservamos los datos el tiempo necesario para atender tu consulta y, si hay relación contractual, durante el plazo legalmente exigido.</p>

<h2>Derechos</h2>
<p>Tienes derecho a acceder, rectificar, suprimir, oponerte y solicitar la portabilidad de tus datos. Escríbenos a <?= CONTACT_EMAIL ?>.</p>

<h2>Cookies</h2>
<p>Consulta nuestra <a href="<?= pageUrl('cookies') ?>">Política de Cookies</a>.</p>

<?php else: ?>
<h2>Responsable del tractament</h2>
<p><strong>AKRA Tech Studio</strong> · <?= CONTACT_EMAIL ?> · <?= CONTACT_PHONE ?></p>

<h2>Dades que recollim</h2>
<p>Recollim únicament les dades que ens proporciones voluntàriament a través del formulari de contacte: nom, email i missatge. No recollim dades sensibles ni fem perfilat automàtic.</p>

<h2>Finalitat i base legal</h2>
<p>Les dades s'utilitzen exclusivament per respondre't i gestionar la relació comercial. Base legal: consentiment exprés de l'interessat (art. 6.1.a RGPD).</p>

<h2>Conservació</h2>
<p>Conservem les dades el temps necessari per atendre la teua consulta i, si hi ha relació contractual, durant el termini legalment exigit.</p>

<h2>Drets</h2>
<p>Tens dret a accedir, rectificar, suprimir, oposar-te i sol·licitar la portabilitat de les teues dades. Escriu-nos a <?= CONTACT_EMAIL ?>.</p>

<h2>Cookies</h2>
<p>Consulta la nostra <a href="<?= pageUrl('cookies') ?>">Política de Cookies</a>.</p>
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
</style>
