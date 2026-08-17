<?php
// pages/projectes.php
require_once '../includes/config.php';
$page_seo = [
    'title'       => 'Projectes · Portfolio de Disseny Web i Marketing Digital | AKRA Tech Studio Alacant',
    'description' => 'Projectes de disseny web, SEO i màrqueting digital d\'AKRA Tech Studio a Alacant. Veus els treballs que hem fet per a empreses de la Costa Blanca.',
    'keywords'    => 'portfolio agencia web Alicante, proyectos diseño web Alicante, casos de exito marketing digital Alicante',
    'canonical'   => SITE_URL . '/pages/projectes.php',
];
$all_projects = getProjects();

// Carrega tipus dinàmics del backend
require_once '../admin/includes/core.php';
$active_types   = getActiveProjectTypes();
$categories = ['all' => ['ca'=>'Tots','es'=>'Todos','en'=>'All','fr'=>'Tous','it'=>'Tutti']];
foreach ($active_types as $t) {
    $categories[$t['id']] = $t['label'];
}
include '../includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="section-header__tag"><?= tr('portfolio_tag') ?></div>
        <h1><?= tr('portfolio_h1') ?></h1>
        <p><?= tr('portfolio_sub') ?></p>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <?php if (empty($all_projects)): ?>
        <!-- ═══ PORTFOLIO PLACEHOLDER ═══════════════════════════════════
             Quan tingues projectes, afegeix-los a $projects_db en config.php
             i aquesta secció s'omplirà automàticament
        ═══════════════════════════════════════════════════════════════ -->
        <div class="portfolio-empty">
            <div class="portfolio-empty__icon">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <h2><?= tr('portfolio_empty_h') ?></h2>
            <p><?= tr('portfolio_empty_p') ?></p>
            <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary btn--lg"><?= tr('portfolio_cta') ?></a>
            
            <!-- Zones reservades per als futurs projectes -->
            <div class="portfolio-grid-placeholder">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="project-placeholder">
                    <div class="project-placeholder__img"></div>
                    <div class="project-placeholder__content">
                        <div class="project-placeholder__bar project-placeholder__bar--title"></div>
                        <div class="project-placeholder__bar project-placeholder__bar--desc"></div>
                        <div class="project-placeholder__bar project-placeholder__bar--desc project-placeholder__bar--short"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- ═══ PORTFOLIO REAL (quan hi hagen projectes) ═══ -->
        <div class="portfolio-filters">
            <?php foreach ($categories as $key => $label): ?>
            <button class="filter-btn <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= $key ?>">
                <?= getTrans($label) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="portfolio-real-grid" id="portfolio-grid">
            <?php foreach ($all_projects as $project): 
                $has_url      = !empty($project['url']);
                $has_gallery  = !empty($project['gallery']);
                $has_demo     = !empty($project['demo_url']);
                $is_design    = $project['category'] === 'design';
                $is_app       = $project['category'] === 'app';
                $show_gallery = $has_gallery && ($is_design || !$has_url);
            ?>
            <div class="project-full-card <?= $is_app ? 'project-full-card--app' : '' ?>" data-category="<?= $project['category'] ?>">
                <div class="project-full-card__media">
                    <?php if (!empty($project['video'])): ?>
                    <iframe src="<?= $project['video'] ?>" frameborder="0" allowfullscreen loading="lazy"></iframe>
                    <?php elseif ($is_app && $has_demo): ?>
                    <!-- App: mostra screenshot amb overlay de demo -->
                    <?php if (!empty($project['thumbnail'])): ?>
                    <img src="../<?= $project['thumbnail'] ?>" alt="<?= getTrans($project['title']) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="app-demo-overlay" onclick="openAppDemo('<?= htmlspecialchars($project['demo_url']) ?>', '<?= htmlspecialchars(getTrans($project['title'])) ?>')">
                        <div class="app-demo-play">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                        <span><?= tr('try_demo') ?></span>
                    </div>
                    <?php elseif ($show_gallery && !empty($project['gallery'][0])): ?>
                    <img src="../<?= $project['gallery'][0] ?>" alt="<?= getTrans($project['title']) ?>" loading="lazy" 
                         class="lightbox-trigger" data-gallery="<?= htmlspecialchars(json_encode($project['gallery'])) ?>" data-index="0" style="cursor:zoom-in">
                    <?php elseif (!empty($project['thumbnail'])): ?>
                    <img src="../<?= $project['thumbnail'] ?>" alt="<?= getTrans($project['title']) ?>" loading="lazy"
                         <?= $show_gallery ? 'class="lightbox-trigger" data-gallery=\''.htmlspecialchars(json_encode($project['gallery'])).'\' data-index="0" style="cursor:zoom-in"' : '' ?>>
                    <?php endif; ?>
                    <div class="project-full-card__status status-<?= $project['status'] ?>">
                        <?= t('project_' . $project['status'], $project['status']) ?>
                    </div>
                    <?php if ($show_gallery && count($project['gallery']) > 1): ?>
                    <div class="gallery-count-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <?= count($project['gallery']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="project-full-card__body">
                    <span class="project-category-tag"><?= getTrans($categories[$project['category']] ?? ['ca' => $project['category']]) ?></span>
                    <h3><?= getTrans($project['title']) ?></h3>
                    <p><?= getTrans($project['description']) ?></p>
                    <?php if (!empty($project['results'])): ?>
                    <div class="project-result">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                        <?= getTrans($project['results']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($project['tech'])): ?>
                    <div class="project-tech">
                        <?php foreach ($project['tech'] as $t_tag): ?>
                        <span><?= $t_tag ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="project-actions">
                        <?php if ($is_app && $has_demo): ?>
                        <button class="btn btn--sm btn--primary demo-btn"
                                onclick="openAppDemo('<?= htmlspecialchars($project['demo_url']) ?>', '<?= htmlspecialchars(getTrans($project['title'])) ?>')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            <?= tr('try_demo') ?>
                        </button>
                        <?php endif; ?>
                        <?php if ($has_url): ?>
                        <a href="<?= $project['url'] ?>" class="btn btn--sm btn--outline" target="_blank" rel="noopener">
                            <?= tr('visit_web') ?> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($show_gallery && count($project['gallery']) > 1): ?>
                        <button class="btn btn--sm btn--ghost lightbox-trigger" 
                                data-gallery="<?= htmlspecialchars(json_encode($project['gallery'])) ?>" data-index="0">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <?= tr('view_gallery') ?> (<?= count($project['gallery']) ?>)
                        </button>
                        <?php elseif ($show_gallery): ?>
                        <button class="btn btn--sm btn--ghost lightbox-trigger"
                                data-gallery="<?= htmlspecialchars(json_encode($project['gallery'])) ?>" data-index="0">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            <?= tr('view_big') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <script>
        // Filtres
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                document.querySelectorAll('.project-full-card').forEach(card => {
                    card.style.display = (filter === 'all' || card.dataset.category === filter) ? 'flex' : 'none';
                });
            });
        });

        // Lightbox
        let lbGallery = [], lbIndex = 0;
        function openLightbox(gallery, index) {
            lbGallery = gallery; lbIndex = index;
            renderLightbox();
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }
        function renderLightbox() {
            document.getElementById('lb-img').src = '../' + lbGallery[lbIndex];
            document.getElementById('lb-counter').textContent = (lbIndex + 1) + ' / ' + lbGallery.length;
            document.getElementById('lb-prev').style.display = lbGallery.length > 1 ? 'flex' : 'none';
            document.getElementById('lb-next').style.display = lbGallery.length > 1 ? 'flex' : 'none';
            const thumbs = document.getElementById('lb-thumbs');
            thumbs.innerHTML = '';
            if (lbGallery.length > 1) {
                lbGallery.forEach((img, i) => {
                    const t = document.createElement('div');
                    t.className = 'lb-thumb' + (i === lbIndex ? ' active' : '');
                    t.innerHTML = '<img src="../' + img + '" loading="lazy">';
                    t.onclick = () => { lbIndex = i; renderLightbox(); };
                    thumbs.appendChild(t);
                });
            }
        }
        function lbNext() { lbIndex = (lbIndex + 1) % lbGallery.length; renderLightbox(); }
        function lbPrev() { lbIndex = (lbIndex - 1 + lbGallery.length) % lbGallery.length; renderLightbox(); }

        document.querySelectorAll('.lightbox-trigger').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                openLightbox(JSON.parse(this.dataset.gallery || '[]'), parseInt(this.dataset.index || 0));
            });
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { closeLightbox(); closeAppDemo(); }
            if (!document.getElementById('lightbox').classList.contains('open')) return;
            if (e.key === 'ArrowRight') lbNext();
            if (e.key === 'ArrowLeft') lbPrev();
        });

        // App Demo Modal
        function openAppDemo(url, title) {
            document.getElementById('app-demo-title').textContent = title;
            document.getElementById('app-demo-iframe').src = url;
            document.getElementById('app-demo-link').href = url;
            document.getElementById('app-demo-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeAppDemo() {
            document.getElementById('app-demo-modal').classList.remove('open');
            document.getElementById('app-demo-iframe').src = '';
            document.body.style.overflow = '';
        }
        </script>

        <!-- LIGHTBOX -->
        <div id="lightbox" class="lightbox" onclick="if(event.target===this)closeLightbox()">
            <button class="lb-close" onclick="closeLightbox()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <button class="lb-nav lb-prev" id="lb-prev" onclick="lbPrev()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <div class="lb-content">
                <img id="lb-img" src="" alt="">
                <div class="lb-counter" id="lb-counter"></div>
                <div class="lb-thumbs" id="lb-thumbs"></div>
            </div>
            <button class="lb-nav lb-next" id="lb-next" onclick="lbNext()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>

        <!-- APP DEMO MODAL -->
        <div id="app-demo-modal" class="app-demo-modal" onclick="if(event.target===this)closeAppDemo()">
            <div class="app-demo-modal__inner">
                <div class="app-demo-modal__bar">
                    <div class="app-demo-modal__bar-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <span class="app-demo-modal__bar-title" id="app-demo-title"></span>
                    <div class="app-demo-modal__bar-actions">
                        <a id="app-demo-link" href="#" target="_blank" rel="noopener" class="app-demo-external" title="Obrir en nova pestanya">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <button class="app-demo-close" onclick="closeAppDemo()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <div class="app-demo-modal__body">
                    <iframe id="app-demo-iframe" src="" allowfullscreen sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-modals"></iframe>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--cta">
    <div class="container">
        <div class="cta-block">
            <div class="section-header__tag"><?= tr('cta_start') ?></div>
            <h2><?= tr('next_project') ?></h2>
            <p><?= tr('next_project_sub') ?></p>
            <div class="cta-block__actions">
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--white btn--lg"><?= tr('cta_quote') ?></a>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<style>
.portfolio-empty { text-align: center; display: flex; flex-direction: column; align-items: center; gap: var(--s-6); }
.portfolio-empty__icon { width: 120px; height: 120px; border: 2px dashed rgba(201,168,76,0.4); border-radius: var(--r-xl); display: flex; align-items: center; justify-content: center; color: var(--c-gold); }
.portfolio-empty h2 { font-family: var(--f-display); font-size: 1.8rem; font-weight: 700; color: var(--c-navy); }
.portfolio-empty p { color: var(--c-muted); max-width: 480px; }

.portfolio-grid-placeholder {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--s-6);
    width: 100%; margin-top: var(--s-8); opacity: 0.4;
}
.project-placeholder { background: var(--c-gray); border-radius: var(--r-lg); overflow: hidden; border: 1px solid var(--c-border); }
.project-placeholder__img { height: 200px; background: linear-gradient(90deg, #e8e8e8 25%, #f0f0f0 50%, #e8e8e8 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.project-placeholder__content { padding: var(--s-4); display: flex; flex-direction: column; gap: var(--s-2); }
.project-placeholder__bar { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #e8e8e8 25%, #f0f0f0 50%, #e8e8e8 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.project-placeholder__bar--title { height: 18px; width: 70%; }
.project-placeholder__bar--desc { width: 100%; }
.project-placeholder__bar--short { width: 55%; }
@keyframes shimmer { to { background-position: -200% 0; } }

.portfolio-filters { display: flex; gap: var(--s-2); flex-wrap: wrap; margin-bottom: var(--s-8); }
.filter-btn { padding: var(--s-2) var(--s-4); border-radius: 100px; border: 1.5px solid var(--c-border); font-size: 0.88rem; font-weight: 600; color: var(--c-text-muted); background: white; cursor: pointer; transition: all var(--t-fast); }
.filter-btn:hover, .filter-btn.active { background: var(--c-primary); color: white; border-color: var(--c-primary); }

.portfolio-real-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--s-6); }
.project-full-card { background: white; border-radius: var(--r-lg); overflow: hidden; box-shadow: var(--shadow-md); flex-direction: column; display: flex; transition: all var(--t-slow); border: 1px solid var(--c-border); }
.project-full-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: var(--c-primary); }
.project-full-card__media { position: relative; height: 240px; overflow: hidden; background: var(--c-surface); }
.project-full-card__media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.project-full-card:hover .project-full-card__media img { transform: scale(1.03); }
.project-full-card__media iframe { width: 100%; height: 100%; border: none; }
.project-full-card__status { position: absolute; top: var(--s-3); right: var(--s-3); background: var(--c-primary); color: white; font-size: 0.72rem; font-weight: 700; padding: 3px 10px; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.04em; }
.project-full-card__body { padding: var(--s-6); flex: 1; display: flex; flex-direction: column; gap: var(--s-3); }
.project-category-tag { font-size: 0.72rem; font-weight: 700; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
.project-full-card__body h3 { font-family: var(--f-display); font-size: 1.1rem; font-weight: 700; color: var(--c-primary); }
.project-full-card__body p { font-size: 0.88rem; color: var(--c-text-muted); line-height: 1.55; flex: 1; }
.project-result { display: flex; align-items: center; gap: var(--s-2); font-size: 0.85rem; font-weight: 600; color: #16a34a; background: #f0fdf4; padding: var(--s-2) var(--s-3); border-radius: var(--r-sm); }
.project-tech { display: flex; flex-wrap: wrap; gap: var(--s-2); }
.project-tech span { font-size: 0.72rem; background: var(--c-surface); color: var(--c-text-muted); padding: 2px 8px; border-radius: 4px; font-weight: 500; }
.project-actions { display: flex; gap: var(--s-2); flex-wrap: wrap; margin-top: auto; padding-top: var(--s-2); }

/* Badge recompte galeria */
.gallery-count-badge {
    position: absolute; bottom: var(--s-3); left: var(--s-3);
    display: flex; align-items: center; gap: 5px;
    background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
    color: white; font-size: 0.75rem; font-weight: 600;
    padding: 4px 10px; border-radius: 100px;
}

/* ── LIGHTBOX ── */
.lightbox {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.95);
    align-items: center; justify-content: center;
    padding: 20px;
}
.lightbox.open { display: flex; animation: lb-in 0.2s ease; }
@keyframes lb-in { from { opacity: 0 } to { opacity: 1 } }

.lb-content {
    display: flex; flex-direction: column; align-items: center;
    max-width: calc(100vw - 160px); max-height: 100vh;
    gap: 16px;
}
.lb-content img {
    max-width: 100%; max-height: calc(100vh - 140px);
    object-fit: contain; border-radius: 8px;
    box-shadow: 0 40px 80px rgba(0,0,0,0.5);
}
.lb-counter {
    color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 500;
    letter-spacing: 0.05em;
}
.lb-thumbs {
    display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;
    max-width: 500px;
}
.lb-thumb {
    width: 56px; height: 42px; border-radius: 4px; overflow: hidden;
    cursor: pointer; opacity: 0.45; transition: opacity 0.15s;
    border: 2px solid transparent;
}
.lb-thumb:hover { opacity: 0.75; }
.lb-thumb.active { opacity: 1; border-color: white; }
.lb-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

.lb-close {
    position: fixed; top: 20px; right: 20px;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.1); color: white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s;
    border: none;
}
.lb-close:hover { background: rgba(255,255,255,0.2); }

.lb-nav {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(255,255,255,0.08); color: white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background 0.15s;
    flex-shrink: 0; border: none;
    position: fixed;
}
.lb-nav:hover { background: rgba(255,255,255,0.18); }
.lb-prev { left: 20px; top: 50%; transform: translateY(-50%); }
.lb-next { right: 20px; top: 50%; transform: translateY(-50%); }

@media (max-width: 768px) { 
    .portfolio-real-grid, .portfolio-grid-placeholder { grid-template-columns: 1fr; }
    .lb-content { max-width: 100%; }
    .lb-prev { left: 8px; } .lb-next { right: 8px; }
}

/* ── APP DEMO OVERLAY (sobre la imatge de la targeta) ── */
.app-demo-overlay {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 10px;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(2px);
    cursor: pointer;
    transition: background 0.2s;
    color: white;
}
.app-demo-overlay:hover { background: rgba(0,0,0,0.6); }
.app-demo-play {
    width: 64px; height: 64px; border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.6);
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    backdrop-filter: blur(4px);
}
.app-demo-overlay:hover .app-demo-play {
    background: rgba(255,255,255,0.25);
    transform: scale(1.08);
    border-color: white;
}
.app-demo-overlay span {
    font-size: 0.85rem; font-weight: 600; letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* ── APP DEMO MODAL ── */
.app-demo-modal {
    display: none; position: fixed; inset: 0; z-index: 9998;
    background: rgba(0,0,0,0.8);
    align-items: center; justify-content: center;
    padding: 24px;
}
.app-demo-modal.open { display: flex; animation: lb-in 0.2s ease; }
.app-demo-modal__inner {
    width: 100%; max-width: 1100px;
    height: calc(100vh - 80px);
    max-height: 820px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 40px 80px rgba(0,0,0,0.5);
}
/* Barra superior estil navegador */
.app-demo-modal__bar {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px;
    background: #f4f4f5;
    border-bottom: 1px solid #e4e4e7;
    flex-shrink: 0;
}
.app-demo-modal__bar-dots {
    display: flex; gap: 6px; flex-shrink: 0;
}
.app-demo-modal__bar-dots span {
    width: 12px; height: 12px; border-radius: 50%;
    background: #d1d5db;
    display: block;
}
.app-demo-modal__bar-dots span:nth-child(1) { background: #ef4444; }
.app-demo-modal__bar-dots span:nth-child(2) { background: #f59e0b; }
.app-demo-modal__bar-dots span:nth-child(3) { background: #22c55e; }
.app-demo-modal__bar-title {
    flex: 1; text-align: center;
    font-size: 0.85rem; font-weight: 600; color: #52525b;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.app-demo-modal__bar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.app-demo-external {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    color: #71717a; transition: all 0.15s;
}
.app-demo-external:hover { background: #e4e4e7; color: #18181b; }
.app-demo-close {
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    background: none; border: none; color: #71717a;
    cursor: pointer; transition: all 0.15s;
}
.app-demo-close:hover { background: #fee2e2; color: #ef4444; }
.app-demo-modal__body { flex: 1; overflow: hidden; }
.app-demo-modal__body iframe {
    width: 100%; height: 100%; border: none; display: block;
}

@media (max-width: 768px) {
    .app-demo-modal { padding: 12px; }
    .app-demo-modal__inner { height: calc(100vh - 24px); max-height: none; border-radius: 8px; }
}
</style>
