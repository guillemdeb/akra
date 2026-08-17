<?php
// index.php - AKRA Tech Studio | Agència Premium Alacant
require_once 'includes/config.php';
require_once 'admin/includes/core.php';
$page_seo = [
    'title' => 'AKRA Tech Studio · Agència de Màrqueting Digital i Web a Alacant',
    'description' => 'Agència de disseny web, SEO local i màrqueting digital a Alacant. Webs professionals en 45 dies: 15 disseny, 15 programació, 15 perfeccionament.',
    'keywords' => 'agencia marketing digital Alicante, diseño web Alicante, SEO Alicante, desarrollo web Alicante, agencia web Alacant',
    'canonical' => SITE_URL . '/',
];
$services = getServices();
$featured_projects = getProjects(null, true);
$site_cfg = getAdminConfig();
include 'includes/header.php';
?>

<!-- ═══════════════════════════════════════════════════
     HERO — Cridat a l'acció amb SEO local Alacant
═══════════════════════════════════════════════════ -->
<section class="hero" id="inicio" itemscope itemtype="https://schema.org/LocalBusiness">
    <meta itemprop="name" content="AKRA Tech Studio">
    <meta itemprop="telephone" content="<?= CONTACT_PHONE ?>">

    <div class="hero-bg">
        <div class="hero-gradient"></div>
        <div class="hero-grid"></div>
    </div>

    <div class="container hero-container">
        <!-- Columna esquerra: text -->
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge__dot"></span>
                <?= t('hero_badge', 'Agència digital a Alacant') ?>
            </div>

            <h1 class="hero-title">
                <?= t('hero_title_1', 'Webs que') ?>
                <span class="hero-title__accent"> <?= t('hero_title_2', 'venen.') ?></span><br>
                <?= t('hero_title_3', 'Marques que') ?>
                <span class="hero-title__accent"> <?= t('hero_title_4', 'es recorden.') ?></span>
            </h1>

            <p class="hero-subtitle">
                <?= t('hero_subtitle', 'Disseny web professional, SEO local i màrqueting digital per a empreses d\'Alacant i la Costa Blanca. Cada projecte, una oportunitat de créixer.') ?>
            </p>

            <div class="hero-actions">
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary btn--lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    <?= t('hero_cta_primary', 'Parlem del teu projecte') ?>
                </a>
                <a href="#serveis" class="btn btn--ghost btn--lg">
                    <?= t('hero_cta_secondary', 'Veure serveis') ?>
                </a>
            </div>

            <div class="hero-trust">
                <div class="hero-trust__item">
                    <strong data-count="<?= $site_cfg['stat_projects'] ?? 50 ?>"><?= $site_cfg['stat_projects'] ?? 50 ?>+</strong>
                    <span><?= t('hero_stat_projects', 'Projectes lliurats') ?></span>
                </div>
                <div class="hero-trust__sep"></div>
                <div class="hero-trust__item">
                    <strong>45</strong>
                    <span><?= t('hero_stat_days', 'Dies de procés') ?></span>
                </div>
                <div class="hero-trust__sep"></div>
                <div class="hero-trust__item">
                    <strong>5★</strong>
                    <span><?= t('hero_stat_reviews', 'Valoració Google') ?></span>
                </div>
                <div class="hero-trust__sep"></div>
                <div class="hero-trust__item">
                    <strong data-count="<?= $site_cfg['stat_years'] ?? 5 ?>"><?= $site_cfg['stat_years'] ?? 5 ?>+</strong>
                    <span><?= t('hero_stat_years', 'Anys d\'experiència') ?></span>
                </div>
            </div>
        </div>

        <!-- Columna dreta: mockup -->
        <?php
        $hero_img = $site_cfg['hero_image'] ?? '';
        $has_img  = $hero_img && file_exists(__DIR__ . '/' . $hero_img);
        ?>
        <div class="hero-visual">
            <div class="hero-mockup">
                <div class="hero-mockup__screen">
                    <div class="hero-mockup__bar">
                        <span></span><span></span><span></span>
                        <div class="hero-mockup__url">akratechstudio.es</div>
                    </div>
                    <div class="hero-mockup__content">
                        <?php if ($has_img): ?>
                        <img src="<?= htmlspecialchars($hero_img) ?>?v=<?= filemtime(__DIR__ . '/' . $hero_img) ?>"
                             alt="Projecte AKRA Tech Studio"
                             class="hero-mockup__img">
                        <?php else: ?>
                        <!-- Placeholder animat quan no hi ha imatge -->
                        <div class="hero-mockup__placeholder">
                            <div class="hmp-header"></div>
                            <div class="hmp-hero"></div>
                            <div class="hmp-grid">
                                <div class="hmp-card"></div>
                                <div class="hmp-card"></div>
                                <div class="hmp-card"></div>
                            </div>
                            <div class="hmp-bar hmp-bar--long"></div>
                            <div class="hmp-bar hmp-bar--short"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Badges flotants -->
                <div class="hero-badge-float hero-badge-float--tl">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    SEO optimitzat
                </div>
                <div class="hero-badge-float hero-badge-float--br">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    PageSpeed 95+
                </div>
            </div>
        </div>
    </div>

    <div class="hero-scroll"><span></span></div>
</section>


<!-- ═══════════════════════════════════════════════════
     SERVEIS — Des de base de dades
═══════════════════════════════════════════════════ -->
<section class="section section--gray" id="serveis">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('services_tag', 'El que fem') ?></div>
            <h2><?= t('services_title', 'Serveis digitals per a empreses d\'Alacant') ?></h2>
            <p><?= t('services_subtitle', 'Solucions a mida que fan créixer el teu negoci localment i a escala nacional.') ?></p>
        </div>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <a href="<?= pageUrl('servei') ?>?slug=<?= $service['slug'] ?>" class="service-card">
                <div class="service-card__icon">
                    <?= $service['icon_svg'] ?>
                </div>
                <div class="service-card__content">
                    <h3><?= getTrans($service['title']) ?></h3>
                    <p><?= getTrans($service['desc_short']) ?></p>
                    <span class="service-card__cta">
                        <?= t('more_info', 'Saber més') ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </span>
                </div>
                <?php if (!empty($service['highlight'])): ?>
                <div class="service-card__badge"><?= getTrans($service['highlight']) ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     SEO LOCAL — Bloc d'autoritat geogràfica (clau per ranking)
═══════════════════════════════════════════════════ -->
<section class="section section--white section--local" itemscope itemtype="https://schema.org/LocalBusiness">
    <div class="container">
        <div class="local-grid">
            <div class="local-content">
                <div class="section-header__tag"><?= t('local_tag', 'Agència local') ?></div>
                <h2 itemprop="name"><?= t('local_title', 'La teua agència digital a Alacant') ?></h2>
                <p itemprop="description"><?= t('local_desc', 'Treballem des d\'Alacant per a tota la Costa Blanca, la Comunitat Valenciana i Espanya. Coneixem el mercat local: Benidorm, Elx, Torrevella, Dénia, Altea, Calp. Ens reunim contigo si cal. Resultats, no promeses.') ?></p>
                
                <div class="local-points">
                    <div class="local-point">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/><line x1="4.22" y1="4.22" x2="19.78" y2="19.78" stroke-width="0"/><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        <div>
                            <strong><?= t('local_p1_title', 'Anem on tu estàs') ?></strong>
                            <span><?= t('local_p1_desc', 'Videollamada, reunió presencial o a les teues instal·lacions. Tu decideixes com treballem.') ?></span>
                        </div>
                    </div>
                    <div class="local-point">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.39 1.2 2 2 0 012.36 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.68-1.68a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg>
                        <div>
                            <strong><?= t('local_p2_title') ?></strong>
                            <span itemprop="telephone"><?= CONTACT_PHONE ?></span>
                        </div>
                    </div>
                    <div class="local-point">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <div>
                            <strong><?= t('local_p3_title', 'SEO Local especialitzat') ?></strong>
                            <span><?= t('local_p3_desc', 'Posicionament a Google Maps i buscadors per al mercat alacantí') ?></span>
                        </div>
                    </div>
                    <div class="local-point">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <strong><?= t('local_p4_title', 'Resposta en 24h') ?></strong>
                            <span><?= t('local_p4_desc', 'Sempre hi serem quan ens necessite') ?></span>
                        </div>
                    </div>
                </div>

                <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary">
                    <?= t('local_cta', 'Parlem — sense compromís') ?>
                </a>
            </div>

            <div class="local-visual">
                <div class="local-map-card">
                    <div class="local-map-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        <span><?= t('remote_work', 'Hi som on ens necessites') ?></span>
                        <p>Costa Blanca · Comunitat Valenciana · Espanya</p>
                    </div>
                    <div class="local-map-zones">
                        <?php 
                        $zones = ['Alacant', 'Benidorm', 'Elx', 'Torrevella', 'Dénia', 'Altea', 'Calp', 'Villena', 'Alcoi'];
                        foreach($zones as $z): ?>
                        <span><?= $z ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     PER QUÈ AKRA — Diferenciadors reals, no genèrics
═══════════════════════════════════════════════════ -->
<section class="section section--dark">
    <div class="container">
        <div class="section-header section-header--light">
            <div class="section-header__tag"><?= t('why_tag', 'Per què nosaltres') ?></div>
            <h2><?= t('why_title', 'No som una agència més d\'Alacant') ?></h2>
            <p><?= t('why_subtitle', 'Treballem com si el teu negoci fos el nostre. Resultats mesurables, comunicació honesta.') ?></p>
        </div>

        <div class="differentiators">
            <div class="diff-item">
                <div class="diff-item__num">01</div>
                <div class="diff-item__content">
                    <h3><?= t('diff_1_title', 'Codí net i escalable') ?></h3>
                    <p><?= t('diff_1_desc', 'No usem plantilles pre-fabricades. Cada línia de codi és teua, optimitzada per a velocitat i SEO. El teu web creixerà amb el teu negoci sense necessitat de refundar.') ?></p>
                </div>
            </div>
            
            <div class="diff-item">
                <div class="diff-item__num">02</div>
                <div class="diff-item__content">
                    <h3><?= t('diff_2_title', 'SEO des del dia 1') ?></h3>
                    <p><?= t('diff_2_desc', 'No és un extra que afegim al final. Estructura, velocitat i contingut optimitzats per a Google des de la primera reunió.') ?></p>
                </div>
            </div>
            
            <div class="diff-item">
                <div class="diff-item__num">03</div>
                <div class="diff-item__content">
                    <h3><?= t('diff_3_title', '45 dies ben invertits') ?></h3>
                    <p><?= t('diff_3_desc', 'El mercat està ple de webs barates en 48 hores que ningú troba a Google. Nosaltres invertim 45 dies: 15 en disseny UX, 15 en programació robusta, 15 en testatge i SEO. Resultat: una web que et fa vendre durant anys.') ?></p>
                </div>
            </div>
            
            <div class="diff-item">
                <div class="diff-item__num">04</div>
                <div class="diff-item__content">
                    <h3><?= t('diff_4_title', 'Formació inclosa') ?></h3>
                    <p><?= t('diff_4_desc', 'No et deixem amb un manual de 100 pàgines. Et formem personalment a gestionar la teua web. I si prefereixes que ho fem nosaltres, tenim plans de manteniment transparents.') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     PROCÉS — Transparent, genera confiança amb 45 dies destacats
═══════════════════════════════════════════════════ -->
<section class="section section--white">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('process_tag', 'Com treballem') ?></div>
            <h2><?= t('process_title', 'Del primer contacte al llançament') ?></h2>
            <p><?= t('process_subtitle', 'Un procés clar en 45 dies. 15 de disseny, 15 de desenvolupament, 15 de perfeccionament. Sense sorpreses.') ?></p>
        </div>

        <div class="process-track">
            <div class="process-step">
                <div class="process-step__num">01</div>
                <div class="process-step__body">
                    <div class="process-step__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </div>
                    <h3><?= t('step_1_title', 'Descobriment') ?></h3>
                    <p><?= t('step_1_desc', 'Analitzem el teu negoci, competència i objectius.') ?></p>
                    <span class="process-step__time">3-5 dies</span>
                </div>
            </div>
            
            <div class="process-step">
                <div class="process-step__num">02</div>
                <div class="process-step__body">
                    <div class="process-step__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                    </div>
                    <h3><?= t('step_2_title', 'Disseny UX/UI') ?></h3>
                    <p><?= t('step_2_desc', 'Wireframes, prototips interactius i disseny visual.') ?></p>
                    <span class="process-step__time">15 dies</span>
                </div>
            </div>
            
            <div class="process-step">
                <div class="process-step__num">03</div>
                <div class="process-step__body">
                    <div class="process-step__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <h3><?= t('step_3_title', 'Desenvolupament') ?></h3>
                    <p><?= t('step_3_desc', 'Programació robusta, responsive i optimitzada.') ?></p>
                    <span class="process-step__time">15 dies</span>
                </div>
            </div>
            
            <div class="process-step">
                <div class="process-step__num">04</div>
                <div class="process-step__body">
                    <div class="process-step__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3><?= t('step_4_title', 'Llançament') ?></h3>
                    <p><?= t('step_4_desc', 'Testatge, SEO final, formació i posada en marxa.') ?></p>
                    <span class="process-step__time">12-15 dies</span>
                </div>
            </div>
        </div>

        <!-- Timeline Detail - Venent els 45 dies -->
        <div class="timeline-detail">
            <h4><?= t('timeline_title', 'Per què 45 dies?') ?></h4>
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
            
            <p class="timeline-value">
                <?= t('timeline_value', 'No fem webs en 48 hores. Fem webs que funcionen durant anys.') ?>
            </p>
        </div>

        <div class="process-cta">
            <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary btn--lg">
                <?= t('process_cta', 'Comença el teu projecte de 45 dies') ?>
            </a>
            <a href="<?= pageUrl('proces') ?>" class="btn btn--outline btn--lg">
                <?= t('process_more', 'Veure procés complet') ?>
            </a>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     PROJECTES — Espai reservat per al portfolio
═══════════════════════════════════════════════════ -->
<section class="section section--gray" id="portfolio">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('projects_tag', 'El nostre treball') ?></div>
            <h2><?= t('projects_title', 'Projectes destacats') ?></h2>
            <p><?= t('projects_subtitle', 'Resultats reals per a empreses reals. Cada projecte: 45 dies de treball rigorós.') ?></p>
        </div>

        <?php if (empty($featured_projects)): ?>
        <!-- PLACEHOLDER — S'omplirà des de la base de dades quan hi haja projectes -->
        <div class="portfolio-coming-soon">
            <div class="portfolio-coming-soon__inner">
                <div class="portfolio-coming-soon__icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <h3><?= t('portfolio_soon_title', 'Portfolio en construcció') ?></h3>
                <p><?= t('portfolio_soon_desc', 'Estem documentant els nostres millors projectes. Cada un d\'ells va requerir 45 dies de treball intens. Mentrestant, contacta\'ns per veure exemples de treball anterior.') ?></p>
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary">
                    <?= t('portfolio_soon_cta', 'Veure treball anterior') ?>
                </a>
            </div>
            <!-- Espai per a 3 targetes de projecte quan estiguen llestes -->
            <div class="portfolio-grid portfolio-grid--placeholder">
                <div class="project-card project-card--placeholder"></div>
                <div class="project-card project-card--placeholder"></div>
                <div class="project-card project-card--placeholder"></div>
            </div>
        </div>
        <?php else: ?>
        <div class="portfolio-grid">
            <?php foreach($featured_projects as $project): ?>
            <div class="project-card">
                <?php if (!empty($project['video'])): ?>
                <div class="project-card__media project-card__media--video">
                    <iframe src="<?= $project['video'] ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>
                </div>
                <?php else: ?>
                <div class="project-card__media" style="background-image:url('<?= $project['thumbnail'] ?>')">
                    <div class="project-card__status project-card__status--<?= $project['status'] ?>">
                        <?= t('project_' . $project['status'], $project['status']) ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="project-card__body">
                    <h3><?= getTrans($project['title']) ?></h3>
                    <p><?= getTrans($project['description']) ?></p>
                    <?php if (!empty($project['url'])): ?>
                    <a href="<?= $project['url'] ?>" class="btn btn--sm btn--outline" target="_blank" rel="noopener">
                        <?= t('visit_website', 'Visitar web') ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="section-footer">
            <a href="<?= pageUrl('projectes') ?>" class="btn btn--outline btn--lg">
                <?= t('projects_cta', 'Veure tots els projectes') ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     TESTIMONIALS — Prova social (clau per conversió)
═══════════════════════════════════════════════════ -->
<section class="section section--white">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= t('testimonials_tag', 'Clients') ?></div>
            <h2><?= t('testimonials_title', 'El que diuen de nosaltres') ?></h2>
        </div>

        <div class="testimonials-grid">
            <?php $testimonials = getTestimonials(); foreach($testimonials as $t_item): ?>
            <div class="testimonial-card" itemscope itemtype="https://schema.org/Review">
                <div class="testimonial-card__stars">★★★★★</div>
                <p itemprop="reviewBody">"<?= getTrans($t_item['text']) ?>"</p>
                <div class="testimonial-card__author" itemscope itemtype="https://schema.org/Person">
                    <div class="testimonial-card__avatar"><?= substr(getTrans($t_item['name']), 0, 1) ?></div>
                    <div>
                        <strong itemprop="name"><?= getTrans($t_item['name']) ?></strong>
                        <span><?= getTrans($t_item['company']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ═══════════════════════════════════════════════════
     CTA FINAL — Conversió màxima amb 45 dies
═══════════════════════════════════════════════════ -->
<section class="section section--cta">
    <div class="container">
        <div class="cta-block">
            <div class="section-header__tag" style="background: rgba(255,255,255,0.1); color: white;"><?= t('cta_tag') ?></div>
            <h2><?= t('cta_title') ?></h2>
            <?php $slots = getSlots(); if ($slots['show']): ?>
            <div class="cta-slots">
                <?php if (!$slots['full']): ?>
                <div class="cta-slots__pips">
                    <?php for ($i = 0; $i < $slots['total']; $i++): ?>
                    <span class="cta-slots__pip <?= $i < $slots['free'] ? 'free' : 'taken' ?>"></span>
                    <?php endfor; ?>
                </div>
                <p><?= sprintf(t('slots_available'), '<strong>' . $slots['free'] . '</strong>') ?></p>
                <?php else: ?>
                <p><?= t('slots_full') ?></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p><?= t('cta_subtitle') ?></p>
            <?php endif; ?>
            <div class="cta-block__actions">
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--white btn--lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    <?= $slots['full'] ? t('slots_waitlist') : t('cta_button') ?>
                </a>
                <a href="tel:<?= str_replace(' ', '', CONTACT_PHONE) ?>" class="btn btn--ghost-white btn--lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.39 1.2 2 2 0 012.36 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.68-1.68a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg>
                    <?= t('cta_phone') ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>