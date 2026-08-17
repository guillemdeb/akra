<?php
require_once '../includes/config.php';
$titles = ['ca'=>'Avís Legal','es'=>'Aviso Legal','en'=>'Legal Notice','fr'=>'Mentions Légales','it'=>'Note Legali'];
$title = $titles[$current_lang] ?? $titles['es'];
$page_seo = ['title' => $title . ' | AKRA Tech Studio', 'description' => $title . ' de AKRA Tech Studio', 'canonical' => SITE_URL . '/pages/avis-legal.php'];
include '../includes/header.php';
?>
<section class="page-hero"><div class="container"><h1><?= $title ?></h1></div></section>
<section class="section section--white"><div class="container"><div class="legal-content">

<?php if ($current_lang === 'es'): ?>
<h2>Titular del sitio web</h2>
<p><strong>AKRA Tech Studio</strong><br>Email: <?= CONTACT_EMAIL ?><br>Teléfono: <?= CONTACT_PHONE ?></p>

<h2>Condiciones de uso</h2>
<p>El acceso y uso de este sitio web implica la aceptación de las presentes condiciones. AKRA Tech Studio se reserva el derecho a modificar el contenido del sitio sin previo aviso.</p>

<h2>Propiedad intelectual</h2>
<p>Todos los contenidos de este sitio (textos, imágenes, diseño, código) son propiedad de AKRA Tech Studio o de sus respectivos titulares. Queda prohibida su reproducción sin autorización expresa.</p>

<h2>Responsabilidad</h2>
<p>AKRA Tech Studio no se hace responsable de los daños derivados del uso del sitio web ni de los enlaces externos que pueda contener.</p>

<h2>Legislación aplicable</h2>
<p>Este aviso legal se rige por la legislación española. Para cualquier controversia, las partes se someten a los juzgados de Alicante.</p>

<?php else: ?>
<h2>Titular del lloc web</h2>
<p><strong>AKRA Tech Studio</strong><br>Email: <?= CONTACT_EMAIL ?><br>Telèfon: <?= CONTACT_PHONE ?></p>

<h2>Condicions d'ús</h2>
<p>L'accés i ús d'aquest lloc web implica l'acceptació de les presents condicions. AKRA Tech Studio es reserva el dret a modificar el contingut del lloc sense preavís.</p>

<h2>Propietat intel·lectual</h2>
<p>Tots els continguts d'aquest lloc (textos, imatges, disseny, codi) són propietat d'AKRA Tech Studio o dels seus respectius titulars. Queda prohibida la seva reproducció sense autorització expressa.</p>

<h2>Responsabilitat</h2>
<p>AKRA Tech Studio no es fa responsable dels danys derivats de l'ús del lloc web ni dels enllaços externs que pugui contenir.</p>

<h2>Legislació aplicable</h2>
<p>Aquest avís legal es regeix per la legislació espanyola. Per a qualsevol controvèrsia, les parts se sotmeten als jutjats d'Alacant.</p>
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
</style>
