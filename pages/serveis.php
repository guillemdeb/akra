<?php
require_once '../includes/config.php';
$seo = [
    'es' => ['Servicios · Diseño Web, SEO Local y Marketing Digital Alicante | AKRA Tech Studio',
             'Todos los servicios de AKRA Tech Studio en Alicante: diseño web profesional, SEO local, Google Ads, redes sociales, branding y e-commerce para la Costa Blanca.'],
    'ca' => ['Serveis · Disseny Web, SEO Local i Màrqueting Digital a Alacant | AKRA Tech Studio',
             'Tots els serveis d\'AKRA Tech Studio a Alacant: disseny web professional, SEO local, Google Ads, gestió xarxes socials, branding i e-commerce per a la Costa Blanca.'],
    'en' => ['Services · Web Design, Local SEO and Digital Marketing Alicante | AKRA Tech Studio',
             'All AKRA Tech Studio services in Alicante: professional web design, local SEO, Google Ads, social media, branding and e-commerce for the Costa Blanca.'],
    'fr' => ['Services · Création Web, SEO Local et Marketing Digital Alicante | AKRA Tech Studio',
             'Tous les services d\'AKRA Tech Studio à Alicante : création web professionnelle, SEO local, Google Ads, réseaux sociaux, branding et e-commerce pour la Costa Blanca.'],
    'it' => ['Servizi · Web Design, SEO Locale e Marketing Digitale Alicante | AKRA Tech Studio',
             'Tutti i servizi di AKRA Tech Studio ad Alicante: web design professionale, SEO locale, Google Ads, social media, branding ed e-commerce per la Costa Blanca.'],
];
$s = $seo[$current_lang] ?? $seo['es'];
$page_seo = ['title' => $s[0], 'description' => $s[1], 'canonical' => SITE_URL . '/pages/serveis.php'];
$services = getServices();
include '../includes/header.php';

$extended = require '../includes/service-content.php';
?>
<section class="page-hero">
    <div class="container">
        <div class="section-header__tag"><?= tr('services_tag') ?></div>
        <h1><?= tr('services_h1') ?></h1>
        <p><?= tr('services_sub') ?></p>
    </div>
</section>
<section class="section section--white">
    <div class="container">
        <?php foreach ($services as $service): ?>
        <div class="service-full" id="<?= $service['slug'] ?>">
            <div class="service-full__header">
                <div class="service-full__icon"><?= $service['icon_svg'] ?></div>
                <div>
                    <h2><?= getTrans($service['title']) ?></h2>
                    <?php if ($service['highlight']): ?>
                    <span class="service-badge"><?= getTrans($service['highlight']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="service-full__body">
                <p class="service-full__lead"><?= getTrans($service['desc_short']) ?></p>
                <?php $ext = $extended[$service['slug']][$current_lang] ?? $extended[$service['slug']]['es'] ?? null; ?>
                <?php if ($ext): ?>
                <div class="service-full__extended"><?= $ext ?></div>
                <?php endif; ?>
            </div>
            <div class="service-full__cta">
                <a href="<?= pageUrl('servei') ?>?slug=<?= $service['slug'] ?>" class="btn btn--outline">
                    <?= t('more_info', 'Saber més') ?>
                </a>
                <a href="<?= pageUrl('contacte') ?>?servei=<?= $service['slug'] ?>" class="btn btn--primary">
                    <?= tr('get_quote_svc') ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include '../includes/footer.php'; ?>
<style>
.page-hero{background:var(--c-navy);padding:120px 0 80px;text-align:center}
.page-hero .section-header__tag{margin-bottom:var(--s-3)}
.page-hero h1{font-family:var(--f-display);font-size:clamp(2rem,4vw,3rem);font-weight:800;color:white;letter-spacing:-.02em;margin-bottom:var(--s-4)}
.page-hero p{font-size:1.05rem;color:rgba(255,255,255,.6);max-width:600px;margin:0 auto}
.service-full{padding:var(--s-12) 0;border-bottom:1px solid var(--c-border)}
.service-full:last-child{border-bottom:none}
.service-full__header{display:flex;align-items:center;gap:var(--s-4);margin-bottom:var(--s-6)}
.service-full__icon{width:64px;height:64px;background:var(--c-navy);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;color:var(--c-gold);flex-shrink:0}
.service-full__header h2{font-family:var(--f-display);font-size:1.8rem;font-weight:800;color:var(--c-navy);letter-spacing:-.02em}
.service-badge{display:inline-block;background:var(--c-gold);color:var(--c-navy);font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:3px 10px;border-radius:100px;margin-top:var(--s-1)}
.service-full__lead{font-size:1.05rem;color:var(--c-muted);margin-bottom:var(--s-4);line-height:1.7}
.service-full__extended p{color:var(--c-text);font-size:.95rem;line-height:1.75;margin-bottom:var(--s-3)}
.service-full__extended strong{color:var(--c-navy)}
.service-full__cta{margin-top:var(--s-6)}
</style>
