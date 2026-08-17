<?php
// pages/servei.php?slug=X — Pàgina dedicada per a un servei concret.
// "Pilot" complet per a disseny-web (hero, punts forts i procés fets a mida);
// la resta de serveis ja tenen la seua pàgina pròpia (URL i SEO independents)
// però amb un tractament més senzill fins que se'ls done el mateix mimo.
require_once '../includes/config.php';
require_once '../admin/includes/core.php';

$slug = $_GET['slug'] ?? '';
$service = getServiceBySlug($slug);
if (!$service) { header('Location: ' . pageUrl('serveis')); exit; }

$extended = require '../includes/service-content.php';
$ext_body = $extended[$slug][$current_lang] ?? $extended[$slug]['es'] ?? null;

// ─── Contingut a mida (només per al servei "pilot": disseny-web) ───────────
$pilot = [
    'disseny-web' => [
        'seo' => [
            'ca' => ['Disseny Web a Alacant · Webs que Venen en 45 Dies | AKRA Tech Studio', 'Dissenyem i desenvolupem webs ràpides, modernes i optimitzades per a SEO. Arquitectura SEO-first, Core Web Vitals, disseny responsive. Entrega en 45 dies.'],
            'es' => ['Diseño Web en Alicante · Webs que Venden en 45 Días | AKRA Tech Studio', 'Diseñamos y desarrollamos webs rápidas, modernas y optimizadas para SEO. Arquitectura SEO-first, Core Web Vitals, diseño responsive. Entrega en 45 días.'],
            'en' => ['Web Design in Alicante · Websites That Sell in 45 Days | AKRA Tech Studio', 'We design and build fast, modern, SEO-optimised websites. SEO-first architecture, Core Web Vitals, responsive design. Delivered in 45 days.'],
        ],
        'hero_tag' => ['ca' => 'Disseny i desenvolupament web', 'es' => 'Diseño y desarrollo web', 'en' => 'Web design and development'],
        'hero_title_1' => ['ca' => 'Una web que', 'es' => 'Una web que', 'en' => 'A website that'],
        'hero_title_2' => ['ca' => 'ven de veres.', 'es' => 'vende de verdad.', 'en' => 'actually sells.'],
        'hero_subtitle' => [
            'ca' => 'No fem plantilles. Dissenyem i programem webs ràpides, optimitzades per a SEO des del primer dia, i pensades perquè es convertisquen en el teu millor comercial 24 hores al dia.',
            'es' => 'No hacemos plantillas. Diseñamos y programamos webs rápidas, optimizadas para SEO desde el primer día, y pensadas para que se conviertan en tu mejor comercial 24 horas al día.',
            'en' => "We don't do templates. We design and build fast websites, SEO-optimised from day one, made to become your best salesperson around the clock.",
        ],
        'features' => [
            'ca' => [
                ['Arquitectura SEO-first', 'Estructura, velocitat i contingut pensats per a Google des de la primera línia de codi, no com a afegit final.'],
                ['Disseny responsive mòbil primer', "Es veu i funciona perfecte des del mòbil, on arriba la majoria del teu trànsit real."],
                ['Core Web Vitals optimitzats', 'Test de velocitat PageSpeed superior a 90 abans del lliurament — Google ho premia i els usuaris també.'],
                ['Analytics i Search Console', 'Integrat des del primer dia perquè sàpies exactament qui visita la teua web i des d\'on.'],
                ['SSL, HTTP/2 i formació inclosa', 'Seguretat de sèrie, i et formem perquè pugues gestionar-la tu mateix el dia de després.'],
            ],
            'es' => [
                ['Arquitectura SEO-first', 'Estructura, velocidad y contenido pensados para Google desde la primera línea de código, no como añadido final.'],
                ['Diseño responsive mobile-first', 'Se ve y funciona perfecto desde el móvil, de donde llega la mayoría de tu tráfico real.'],
                ['Core Web Vitals optimizados', 'Test de velocidad PageSpeed superior a 90 antes de la entrega — Google lo premia y los usuarios también.'],
                ['Analytics y Search Console', 'Integrado desde el primer día para que sepas exactamente quién visita tu web y desde dónde.'],
                ['SSL, HTTP/2 y formación incluida', 'Seguridad de serie, y te formamos para que puedas gestionarla tú mismo al día siguiente.'],
            ],
            'en' => [
                ['SEO-first architecture', "Structure, speed and content built for Google from the first line of code, not bolted on at the end."],
                ['Mobile-first responsive design', 'Looks and works perfectly on mobile, where most of your real traffic comes from.'],
                ['Optimised Core Web Vitals', 'PageSpeed score above 90 before delivery — Google rewards it, and so do your visitors.'],
                ['Analytics and Search Console', "Integrated from day one so you know exactly who's visiting and where from."],
                ['SSL, HTTP/2 and training included', "Security as standard, plus we train you to manage it yourself afterwards."],
            ],
        ],
        'cta_title' => ['ca' => 'Parlem del teu projecte de web', 'es' => 'Hablemos de tu proyecto de web', 'en' => "Let's talk about your website project"],
        'cta_subtitle' => [
            'ca' => 'Una primera trucada de 20 minuts, sense compromís, per entendre què necessites de veres.',
            'es' => 'Una primera llamada de 20 minutos, sin compromiso, para entender qué necesitas de verdad.',
            'en' => 'A first 20-minute call, no strings attached, to understand what you actually need.',
        ],
    ],
];
$sc = $pilot[$slug] ?? null;

// ─── SEO ─────────────────────────────────────────────────────────────────
if ($sc) {
    $s = $sc['seo'][$current_lang] ?? $sc['seo']['es'];
    $page_seo = ['title' => $s[0], 'description' => $s[1], 'canonical' => SITE_URL . '/pages/servei.php?slug=' . $slug];
} else {
    $page_seo = [
        'title' => getTrans($service['title']) . ' · AKRA Tech Studio',
        'description' => getTrans($service['desc_short']),
        'canonical' => SITE_URL . '/pages/servei.php?slug=' . $slug,
    ];
}

// ─── Portfolio relacionat ────────────────────────────────────────────────
$category_map = ['disseny-web' => 'web', 'ecommerce' => 'ecommerce', 'disseny-grafic' => 'design'];
$related_category = $category_map[$slug] ?? null;
$related_projects = $related_category ? getProjects($related_category, true) : getProjects(null, true);
if (empty($related_projects)) $related_projects = getProjects($related_category);
$related_projects = array_slice($related_projects, 0, 3);

$testimonials = array_slice(getTestimonials(), 0, 3);

include '../includes/header.php';
?>

<section class="page-hero page-hero--service">
    <div class="container">
        <div class="section-header__tag"><?= htmlspecialchars($sc['hero_tag'][$current_lang] ?? $sc['hero_tag']['es'] ?? getTrans($service['title'])) ?></div>
        <?php if ($sc): ?>
        <h1><?= htmlspecialchars($sc['hero_title_1'][$current_lang] ?? '') ?> <span class="page-hero__accent"><?= htmlspecialchars($sc['hero_title_2'][$current_lang] ?? '') ?></span></h1>
        <p><?= htmlspecialchars($sc['hero_subtitle'][$current_lang] ?? $sc['hero_subtitle']['es']) ?></p>
        <?php else: ?>
        <h1><?= getTrans($service['title']) ?></h1>
        <p><?= getTrans($service['desc_short']) ?></p>
        <?php endif; ?>
        <div class="page-hero__actions">
            <a href="<?= pageUrl('contacte') ?>?servei=<?= $slug ?>" class="btn btn--primary btn--lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                <?= tr('get_quote_svc') ?>
            </a>
            <?php $all_services_label = ['ca' => 'Tots els serveis', 'es' => 'Todos los servicios', 'en' => 'All services', 'fr' => 'Tous les services', 'it' => 'Tutti i servizi']; ?>
            <a href="<?= pageUrl('serveis') ?>" class="btn btn--ghost-white btn--lg"><?= htmlspecialchars($all_services_label[$current_lang] ?? $all_services_label['ca']) ?></a>
        </div>
    </div>
</section>

<?php if ($sc): ?>
<section class="section section--dark">
    <div class="container">
        <div class="differentiators">
            <?php foreach ($sc['features'][$current_lang] ?? $sc['features']['es'] as $i => $f): ?>
            <div class="diff-item">
                <div class="diff-item__num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
                <div class="diff-item__content">
                    <h3><?= htmlspecialchars($f[0]) ?></h3>
                    <p><?= htmlspecialchars($f[1]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($ext_body): ?>
<section class="section section--white">
    <div class="container container--narrow">
        <div class="service-full__extended"><?= $ext_body ?></div>
    </div>
</section>
<?php endif; ?>

<?php if ($slug === 'disseny-web'): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('process_tag', 'Com treballem') ?></div>
            <h2><?= t('process_title', 'Del primer contacte al llançament') ?></h2>
            <p><?= t('process_subtitle', 'Un procés clar en 45 dies. 15 de disseny, 15 de desenvolupament, 15 de perfeccionament. Sense sorpreses.') ?></p>
        </div>
        <div class="timeline-breakdown">
            <div class="timeline-phase">
                <strong>15 dies</strong>
                <span><?= t('phase_design', 'Disseny UX/UI') ?></span>
                <small><?= t('phase_design_desc', 'Wireframes, prototips i validació visual amb el teu equip') ?></small>
            </div>
            <div class="timeline-phase">
                <strong>15 dies</strong>
                <span><?= t('phase_dev', 'Desenvolupament') ?></span>
                <small><?= t('phase_dev_desc', 'Codi net, responsive, optimitzat per a velocitat i SEO') ?></small>
            </div>
            <div class="timeline-phase">
                <strong>15 dies</strong>
                <span><?= t('phase_launch', 'Testatge i llançament') ?></span>
                <small><?= t('phase_launch_desc', 'Revisions, SEO tècnic, velocitat, formació i suport') ?></small>
            </div>
        </div>
        <a href="<?= pageUrl('proces') ?>" class="btn btn--outline btn--lg" style="margin-top:var(--s-6)"><?= t('process_more', 'Veure procés complet') ?></a>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($related_projects)): ?>
<section class="section section--white" id="portfolio">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('projects_tag', 'El nostre treball') ?></div>
            <h2><?= t('projects_title', 'Projectes destacats') ?></h2>
        </div>
        <div class="portfolio-grid">
            <?php foreach ($related_projects as $project): ?>
            <div class="project-card">
                <div class="project-card__media" style="background-image:url('<?= $project['thumbnail'] ?>')">
                    <div class="project-card__status project-card__status--<?= $project['status'] ?>"><?= t('project_' . $project['status'], $project['status']) ?></div>
                </div>
                <div class="project-card__body">
                    <h3><?= getTrans($project['title']) ?></h3>
                    <p><?= getTrans($project['description']) ?></p>
                    <?php if (!empty($project['url'])): ?>
                    <a href="<?= $project['url'] ?>" class="btn btn--sm btn--outline" target="_blank" rel="noopener"><?= t('visit_website', 'Visitar web') ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($testimonials)): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('testimonials_tag', 'Clients') ?></div>
            <h2><?= t('testimonials_title', 'El que diuen de nosaltres') ?></h2>
        </div>
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $t_item): ?>
            <div class="testimonial-card">
                <div class="testimonial-card__stars">★★★★★</div>
                <p>"<?= getTrans($t_item['text']) ?>"</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar"><?= substr(getTrans($t_item['name']), 0, 1) ?></div>
                    <div><strong><?= getTrans($t_item['name']) ?></strong><span><?= getTrans($t_item['company']) ?></span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section section--cta">
    <div class="container">
        <div class="cta-block">
            <div class="section-header__tag" style="background: rgba(255,255,255,0.1); color: white;"><?= tr('get_quote_svc') ?></div>
            <h2><?= $sc ? htmlspecialchars($sc['cta_title'][$current_lang] ?? $sc['cta_title']['es']) : t('cta_title') ?></h2>
            <p><?= $sc ? htmlspecialchars($sc['cta_subtitle'][$current_lang] ?? $sc['cta_subtitle']['es']) : t('cta_subtitle') ?></p>
            <div class="cta-block__actions">
                <a href="<?= pageUrl('contacte') ?>?servei=<?= $slug ?>" class="btn btn--white btn--lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    <?= t('cta_button') ?>
                </a>
                <a href="tel:<?= str_replace(' ', '', CONTACT_PHONE) ?>" class="btn btn--ghost-white btn--lg"><?= CONTACT_PHONE ?></a>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
<style>
.page-hero--service{background:var(--c-primary);padding:120px 0 70px;text-align:center;position:relative;overflow:hidden}
.page-hero--service h1{font-family:var(--f-display);font-size:clamp(2.1rem,4.2vw,3.2rem);font-weight:800;color:white;letter-spacing:-.02em;margin:var(--s-3) 0 var(--s-4);line-height:1.15}
.page-hero__accent{color:var(--c-accent-light)}
.page-hero--service p{font-size:1.08rem;color:rgba(255,255,255,.65);max-width:640px;margin:0 auto var(--s-6)}
.page-hero__actions{display:flex;gap:var(--s-3);justify-content:center;flex-wrap:wrap}
.container--narrow{max-width:760px}
.service-full__extended p{color:var(--c-text);font-size:1rem;line-height:1.8;margin-bottom:var(--s-4)}
.service-full__extended strong{color:var(--c-primary)}
</style>
