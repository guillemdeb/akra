<?php
require_once '../includes/config.php';
require_once '../admin/includes/core.php';
$seo = [
    'es' => ['Nosotros · AKRA, el atelier digital de Alicante', 'AKRA Tech Studio es un atelier digital: máximo 5 proyectos simultáneos, código a medida y atención exclusiva. Fundados sobre Akra Leuka, la antigua Alicante.'],
    'ca' => ['Nosaltres · AKRA, l\'atelier digital d\'Alacant',  'AKRA Tech Studio és un atelier digital: màxim 5 projectes simultanis, codi a mida i atenció exclusiva. Fundats sobre Akra Leuka, l\'antiga Alacant.'],
    'en' => ['About · AKRA, Alicante\'s Digital Atelier',        'AKRA Tech Studio is a digital atelier: maximum 5 simultaneous projects, custom code and exclusive attention. Founded on Akra Leuka, ancient Alicante.'],
    'fr' => ['À propos · AKRA, l\'atelier digital d\'Alicante',  'AKRA Tech Studio est un atelier digital : maximum 5 projets simultanés, code sur mesure et attention exclusive. Fondé sur Akra Leuka, l\'ancienne Alicante.'],
    'it' => ['Chi siamo · AKRA, l\'atelier digitale di Alicante', 'AKRA Tech Studio è un atelier digitale: massimo 5 progetti simultanei, codice su misura e attenzione esclusiva. Fondato su Akra Leuka, l\'antica Alicante.'],
];
$s = $seo[$current_lang] ?? $seo['es'];
$page_seo = ['title' => $s[0], 'description' => $s[1], 'canonical' => SITE_URL . '/pages/nosaltres.php'];
$slots_total = getAdminConfig()['slots_total'] ?? 5;
include '../includes/header.php';
?>

<section class="about-hero">
    <div class="container">
        <div class="about-hero__inner">
            <div class="about-hero__text">
                <div class="section-header__tag"><?= tr('about_tag') ?></div>
                <h1><?= tr('about_h1_1') ?><br><span class="about-hero__accent"><?= tr('about_h1_2') ?></span></h1>
                <p class="about-hero__lead"><?= tr('about_lead') ?></p>
                <div class="about-hero__tag">
                    <span class="about-atelier-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?= tr('about_atelier') ?> <?= $slots_total ?> <?= tr('about_atelier2') ?>
                    </span>
                </div>
            </div>
            <div class="about-hero__emblem">
                <div class="about-emblem">
                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="96" stroke="currentColor" stroke-width="1" stroke-dasharray="4 6" opacity="0.2"/>
                        <circle cx="100" cy="100" r="72" stroke="currentColor" stroke-width="1" opacity="0.15"/>
                        <path d="M100 38L142 138H58L100 38Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" fill="none"/>
                        <line x1="72" y1="112" x2="128" y2="112" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        <circle cx="100" cy="100" r="3" fill="currentColor" opacity="0.4"/>
                        <text font-family="'DM Sans', sans-serif" font-size="10" font-weight="600" letter-spacing="6" fill="currentColor" opacity="0.3">
                            <textPath href="#circle-path">AKRA LEUKA · ALACANT · EST. 2020 · </textPath>
                        </text>
                        <defs><path id="circle-path" d="M100,20 a80,80 0 1,1 -0.1,0"/></defs>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <div class="about-origin">
            <div class="about-origin__timeline">
                <div class="about-timeline-item"><div class="about-timeline-dot"></div><div class="about-timeline-year">~250 aC</div></div>
                <div class="about-timeline-line"></div>
                <div class="about-timeline-item"><div class="about-timeline-dot"></div><div class="about-timeline-year">2020</div></div>
                <div class="about-timeline-line about-timeline-line--dash"></div>
                <div class="about-timeline-item"><div class="about-timeline-dot about-timeline-dot--now"></div><div class="about-timeline-year"><?= tr('about_now') ?></div></div>
            </div>
            <div class="about-origin__content">
                <div class="section-header__tag"><?= tr('origin_tag') ?></div>
                <h2><?= tr('origin_h2') ?></h2>
                <div class="about-origin__body">
                    <p><?= tr('origin_p1') ?></p>
                    <p><?= tr('origin_p2') ?></p>
                    <p><?= tr('origin_p3') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--primary">
    <div class="container">
        <div class="about-atelier">
            <div class="about-atelier__text">
                <div class="section-header__tag" style="background:rgba(255,255,255,0.12);color:rgba(255,255,255,0.7)"><?= tr('atelier_tag') ?></div>
                <h2><?= tr('atelier_h2') ?></h2>
                <p><?= tr('atelier_p1') ?></p>
                <p><?= sprintf(tr('atelier_p2'), $slots_total) ?></p>
                <p><?= tr('atelier_p3') ?></p>
            </div>
            <div class="about-atelier__pillars">
                <?php
                $pillars = [
                    [tr('pillar1_t'), tr('pillar1_p')],
                    [tr('pillar2_t'), sprintf(tr('pillar2_p'), $slots_total)],
                    [tr('pillar3_t'), tr('pillar3_p')],
                    [tr('pillar4_t'), tr('pillar4_p')],
                ];
                foreach ($pillars as $p): ?>
                <div class="atelier-pillar">
                    <div class="atelier-pillar__num">✦</div>
                    <strong><?= htmlspecialchars($p[0]) ?></strong>
                    <span><?= htmlspecialchars($p[1]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section--gray">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= tr('problems_tag') ?></div>
            <h2><?= tr('problems_h2') ?></h2>
        </div>
        <div class="about-problems">
            <?php
            $problems = [
                ['prob1_b','prob1_a'],['prob2_b','prob2_a'],['prob3_b','prob3_a'],['prob4_b','prob4_a'],
            ];
            foreach ($problems as $pb): ?>
            <div class="about-problem-card">
                <div class="about-problem-card__before"><span><?= tr('before') ?></span> <?= tr($pb[0]) ?></div>
                <div class="about-problem-card__after"><span><?= tr('akra') ?></span> <?= tr($pb[1]) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= tr('values_tag') ?></div>
            <h2><?= tr('values_h2') ?></h2>
            <p><?= tr('values_sub') ?></p>
        </div>
        <div class="about-values">
            <?php
            $values = [
                ['01','shield','val1_h','val1_p','val1_proof',
                 '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
                ['02','code','val2_h','val2_p','val2_proof',
                 '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
                ['03','people','val3_h','val3_p','val3_proof',
                 '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
                ['04','chart','val4_h','val4_p','val4_proof',
                 '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>'],
            ];
            foreach ($values as $v): ?>
            <div class="about-value">
                <div class="about-value__number"><?= $v[0] ?></div>
                <div class="about-value__content">
                    <div class="about-value__icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $v[5] ?></svg></div>
                    <h3><?= tr($v[2]) ?></h3>
                    <p><?= tr($v[3]) ?></p>
                    <div class="about-value__proof">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <?= tr($v[4]) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--gray">
    <div class="container">
        <div class="about-manifesto">
            <div class="about-manifesto__quote">
                <svg class="about-manifesto__mark" viewBox="0 0 60 45" fill="currentColor"><path d="M0 45V27.273C0 10.909 9.091 2.727 27.273 0L30 5.455C21.818 7.273 17.727 12.727 16.364 18.182H27.273V45H0zm32.727 0V27.273C32.727 10.909 41.818 2.727 60 0l2.727 5.455C54.545 7.273 50.455 12.727 49.091 18.182H60V45H32.727z"/></svg>
                <blockquote><?= tr('manifesto') ?></blockquote>
                <cite>— AKRA Tech Studio, Alacant</cite>
            </div>
        </div>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <div class="section-header">
            <div class="section-header__tag"><?= tr('stats_tag') ?></div>
            <h2><?= tr('stats_h2') ?></h2>
        </div>
        <div class="about-stats">
            <?php
            $stats = [
                ['50+','stat1_l','stat1_d',50],
                ['5+', 'stat2_l','stat2_d',null],
                ['45', 'stat3_l','stat3_d',null],
                ['5★', 'stat4_l','stat4_d',null],
            ];
            foreach ($stats as $st): ?>
            <div class="about-stat">
                <div class="about-stat__num" <?= $st[3] ? 'data-count="'.$st[3].'"' : '' ?>><?= $st[0] ?></div>
                <div class="about-stat__label"><?= tr($st[1]) ?></div>
                <div class="about-stat__desc"><?= tr($st[2]) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--cta">
    <div class="container">
        <div class="cta-block">
            <div class="section-header__tag"><?= tr('cta_start') ?></div>
            <h2><?= tr('competition') ?></h2>
            <p><?= tr('free_consult') ?></p>
            <div class="cta-block__actions">
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--white btn--lg"><?= tr('cta_quote') ?></a>
                <a href="<?= pageUrl('pressupost') ?>" class="btn btn--ghost-white btn--lg"><?= tr('cta_calc') ?></a>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
<style>
.about-hero{padding:100px 0 80px;background:var(--c-bg);border-bottom:1px solid var(--c-border);overflow:hidden}
.about-hero__inner{display:grid;grid-template-columns:1fr auto;gap:var(--s-12);align-items:center}
.about-hero__text{max-width:600px}
.about-hero__text h1{font-family:var(--f-display);font-size:clamp(2.4rem,5vw,4rem);font-weight:700;line-height:1.1;letter-spacing:-.03em;color:var(--c-primary);margin:var(--s-3) 0 var(--s-5)}
.about-hero__accent{color:transparent;-webkit-text-stroke:2px var(--c-primary)}
.about-hero__lead{font-size:1.1rem;color:var(--c-text-sec);line-height:1.75;max-width:520px}
.about-hero__lead em{font-style:italic;color:var(--c-primary)}
.about-emblem{width:220px;height:220px;color:var(--c-primary);opacity:.7;flex-shrink:0}
.about-emblem svg{width:100%;height:100%}
.about-origin{display:grid;grid-template-columns:140px 1fr;gap:var(--s-12);align-items:start}
.about-origin__timeline{display:flex;flex-direction:column;align-items:center;padding-top:48px;gap:0}
.about-timeline-item{display:flex;flex-direction:column;align-items:center;gap:var(--s-2)}
.about-timeline-dot{width:14px;height:14px;border-radius:50%;background:var(--c-border);border:2px solid var(--c-text-muted);flex-shrink:0}
.about-timeline-dot--now{background:var(--c-primary);border-color:var(--c-primary);box-shadow:0 0 0 4px rgba(0,0,0,.08)}
.about-timeline-year{font-size:.75rem;font-weight:700;color:var(--c-text-muted);letter-spacing:.04em}
.about-timeline-line{width:2px;height:52px;background:var(--c-border);margin:var(--s-1) 0}
.about-timeline-line--dash{background:repeating-linear-gradient(to bottom,var(--c-border) 0,var(--c-border) 4px,transparent 4px,transparent 10px)}
.about-origin__content h2{font-family:var(--f-display);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;margin:var(--s-3) 0 var(--s-6)}
.about-origin__body{display:flex;flex-direction:column;gap:var(--s-4)}
.about-origin__body p{font-size:1rem;line-height:1.8;color:var(--c-text-sec)}
.about-origin__body em{font-style:italic;color:var(--c-primary)}
.about-origin__body strong{font-weight:700;color:var(--c-primary)}
.about-atelier{display:grid;grid-template-columns:1fr 1fr;gap:var(--s-12);align-items:start}
.about-atelier__text h2{font-family:var(--f-display);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:700;color:white;letter-spacing:-.02em;margin:var(--s-3) 0 var(--s-5)}
.about-atelier__text p{font-size:.95rem;color:rgba(255,255,255,.7);line-height:1.8;margin-bottom:var(--s-4)}
.about-atelier__text strong{color:white}
.about-atelier__pillars{display:flex;flex-direction:column;gap:var(--s-4)}
.atelier-pillar{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:var(--r-lg);padding:var(--s-5);display:flex;flex-direction:column;gap:var(--s-2)}
.atelier-pillar__num{font-size:1rem;color:var(--c-gold)}
.atelier-pillar strong{font-size:.95rem;font-weight:700;color:white}
.atelier-pillar span{font-size:.85rem;color:rgba(255,255,255,.6);line-height:1.6}
.about-problems{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--s-4);margin-top:var(--s-8)}
.about-problem-card{background:white;border-radius:var(--r-lg);border:1px solid var(--c-border);overflow:hidden}
.about-problem-card__before,.about-problem-card__after{padding:var(--s-4) var(--s-5);font-size:.9rem;line-height:1.6}
.about-problem-card__before{background:#fafafa;border-bottom:1px solid var(--c-border);color:var(--c-text-muted)}
.about-problem-card__after{color:var(--c-text)}
.about-problem-card__before span{font-weight:700;color:#ef4444;margin-right:4px}
.about-problem-card__after span{font-weight:700;color:#16a34a;margin-right:4px}
.about-values{display:flex;flex-direction:column;gap:0;margin-top:var(--s-10)}
.about-value{display:grid;grid-template-columns:80px 1fr;gap:var(--s-6);padding:var(--s-8) 0;border-bottom:1px solid var(--c-border)}
.about-value:last-child{border-bottom:none}
.about-value__number{font-family:var(--f-display);font-size:3rem;font-weight:700;color:var(--c-border);line-height:1;letter-spacing:-.04em;padding-top:4px}
.about-value__content{display:flex;flex-direction:column;gap:var(--s-3)}
.about-value__icon{width:52px;height:52px;border-radius:var(--r-md);background:var(--c-surface);border:1px solid var(--c-border);display:flex;align-items:center;justify-content:center;color:var(--c-primary)}
.about-value__content h3{font-family:var(--f-display);font-size:1.3rem;font-weight:700;color:var(--c-primary);letter-spacing:-.01em}
.about-value__content p{font-size:.95rem;color:var(--c-text-sec);line-height:1.75;max-width:620px}
.about-value__proof{display:inline-flex;align-items:center;gap:var(--s-2);font-size:.8rem;font-weight:600;color:#16a34a;background:#f0fdf4;padding:var(--s-2) var(--s-3);border-radius:var(--r-sm);width:fit-content}
.about-manifesto{max-width:760px;margin:0 auto;text-align:center}
.about-manifesto__quote{position:relative;padding:var(--s-6) var(--s-8)}
.about-manifesto__mark{width:48px;height:36px;color:var(--c-border);margin-bottom:var(--s-5);display:block;margin-left:auto;margin-right:auto}
.about-manifesto__quote blockquote{font-family:var(--f-display);font-size:clamp(1.2rem,2.5vw,1.6rem);font-weight:600;color:var(--c-primary);line-height:1.5;letter-spacing:-.01em;margin-bottom:var(--s-5);font-style:normal}
.about-manifesto__quote cite{font-size:.85rem;color:var(--c-text-muted);font-style:normal}
.about-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--s-6);margin-top:var(--s-8)}
.about-stat{padding:var(--s-6);background:var(--c-bg);border-radius:var(--r-lg);border:1px solid var(--c-border);text-align:center}
.about-stat__num{font-family:var(--f-display);font-size:2.8rem;font-weight:700;color:var(--c-primary);letter-spacing:-.04em;line-height:1;margin-bottom:var(--s-2)}
.about-stat__label{font-size:.85rem;font-weight:700;color:var(--c-text-sec);text-transform:uppercase;letter-spacing:.05em;margin-bottom:var(--s-2)}
.about-stat__desc{font-size:.8rem;color:var(--c-text-muted);line-height:1.55}
.btn--ghost-white{background:transparent;color:rgba(255,255,255,.8);border:1.5px solid rgba(255,255,255,.3)}
.btn--ghost-white:hover{background:rgba(255,255,255,.1);color:white;border-color:rgba(255,255,255,.6)}
@media(max-width:1024px){.about-stats{grid-template-columns:repeat(2,1fr)}.about-problems{grid-template-columns:1fr}.about-atelier{grid-template-columns:1fr}}
@media(max-width:768px){.about-hero__inner{grid-template-columns:1fr}.about-emblem{display:none}.about-origin{grid-template-columns:1fr}.about-origin__timeline{flex-direction:row;padding-top:0;padding-bottom:var(--s-6)}.about-timeline-line{width:40px;height:2px;margin:0 var(--s-1)}.about-timeline-line--dash{background:repeating-linear-gradient(to right,var(--c-border) 0,var(--c-border) 4px,transparent 4px,transparent 10px)}.about-value{grid-template-columns:1fr;gap:var(--s-3)}.about-value__number{font-size:2rem}.about-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.about-stats{grid-template-columns:1fr}}
</style>
