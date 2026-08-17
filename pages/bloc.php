<?php
// pages/bloc.php — Blog públic d'AKRA Tech Studio
require_once '../includes/config.php';
require_once '../admin/includes/core.php';

$cats      = getCategories();
$post_slug = $_GET['post'] ?? null;
$cat_filter = $_GET['cat'] ?? null;

function catName($cats, $key) {
    global $current_lang;
    $cat = $cats[$key] ?? null;
    if (!$cat) return $key;
    if (is_array($cat)) return $cat[$current_lang] ?? $cat['ca'] ?? $cat['es'] ?? reset($cat);
    return $cat;
}

// ── ARTICLE INDIVIDUAL ───────────────────────────────────────────────────────
if ($post_slug):
    $post = getPost($post_slug);
    if (!$post || !($post['published'] ?? false)) { header('Location: bloc.php'); exit; }
    $body = getTrans($post['body']) ?: ($post['body']['ca'] ?? '');
    $page_seo = [
        'title'       => ($post['seo_title'] ?: getTrans($post['title'])) . ' | AKRA Tech Studio',
        'description' => $post['seo_description'] ?: getTrans($post['excerpt']),
        'canonical'   => SITE_URL . '/pages/bloc.php?post=' . urlencode($post_slug),
    ];
    include '../includes/header.php';
?>
<article class="blog-article" itemscope itemtype="https://schema.org/BlogPosting">
    <?php if (!empty($post['cover'])): ?>
    <div class="blog-article__hero">
        <img src="../<?= htmlspecialchars($post['cover']) ?>" alt="<?= htmlspecialchars(getTrans($post['title'])) ?>" itemprop="image">
    </div>
    <?php endif; ?>
    <div class="container blog-article__container">
        <div class="blog-article__header">
            <div class="blog-article__meta">
                <a href="bloc.php?cat=<?= $post['category'] ?>" class="blog-cat-tag"><?= htmlspecialchars(catName($cats, $post['category'])) ?></a>
                <span class="blog-meta-sep">·</span>
                <time datetime="<?= $post['date'] ?>" itemprop="datePublished"><?= date('d/m/Y', strtotime($post['date'])) ?></time>
                <span class="blog-meta-sep">·</span>
                <span><?= (int)($post['read_mins'] ?? 5) ?> <?= t('blog_read_mins') ?></span>
            </div>
            <h1 itemprop="headline"><?= htmlspecialchars(getTrans($post['title'])) ?></h1>
            <?php if (!empty($post['excerpt'])): ?>
            <p class="blog-article__lead" itemprop="description"><?= htmlspecialchars(getTrans($post['excerpt'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="blog-article__layout">
            <div class="blog-article__body" itemprop="articleBody"><?= $body ?></div>
            <aside class="blog-article__sidebar">
                <div class="blog-sidebar-card">
                    <div class="blog-sidebar-card__inner">
                        <p><?= t('blog_sidebar_help') ?></p>
                        <a href="<?= pageUrl('contacte') ?>" class="btn btn--white btn--sm"><?= t('blog_sidebar_cta') ?></a>
                    </div>
                </div>
                <div class="blog-sidebar-cats">
                    <h4><?= t('blog_sidebar_cats') ?></h4>
                    <?php foreach ($cats as $k => $v): ?>
                    <a href="bloc.php?cat=<?= $k ?>" class="blog-sidebar-cat <?= $k === ($post['category'] ?? '') ? 'active' : '' ?>"><?= htmlspecialchars(catName($cats, $k)) ?></a>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
        <div class="blog-article__footer">
            <a href="bloc.php" class="btn btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <?= t('blog_back') ?>
            </a>
            <div class="blog-share">
                <span><?= t('blog_share') ?></span>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL.'/pages/bloc.php?post='.$post_slug) ?>" target="_blank" rel="noopener" class="blog-share__btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-4 0v7H10v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>LinkedIn
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL.'/pages/bloc.php?post='.$post_slug) ?>&text=<?= urlencode(getTrans($post['title'])) ?>" target="_blank" rel="noopener" class="blog-share__btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>X
                </a>
            </div>
        </div>
    </div>
</article>
<?php

// ── LLISTA D'ARTICLES ─────────────────────────────────────────────────────────
else:
    $all_posts = getPosts(true);
    if ($cat_filter) $all_posts = array_filter($all_posts, fn($p) => $p['category'] === $cat_filter);
    $all_posts = array_values($all_posts);
    $page_seo = [
        'title'       => t('blog_title') . ' | AKRA Tech Studio',
        'description' => t('blog_subtitle'),
        'keywords'    => 'blog diseño web Alicante, SEO local Alicante, marketing digital Costa Blanca',
        'canonical'   => SITE_URL . '/pages/bloc.php',
    ];
    include '../includes/header.php';
?>
<section class="page-hero page-hero--blog">
    <div class="container">
        <div class="section-header__tag"><?= t('blog_tag') ?></div>
        <h1><?= t('blog_title') ?></h1>
        <p><?= t('blog_subtitle') ?></p>
    </div>
</section>

<section class="section section--white">
    <div class="container">
        <div class="blog-filters">
            <a href="bloc.php" class="blog-filter-btn <?= !$cat_filter ? 'active' : '' ?>"><?= t('blog_filter_all') ?></a>
            <?php foreach ($cats as $k => $v): ?>
            <a href="bloc.php?cat=<?= $k ?>" class="blog-filter-btn <?= $cat_filter === $k ? 'active' : '' ?>"><?= htmlspecialchars(catName($cats, $k)) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($all_posts)):
        $teaser_titles = [
            'ca' => ["Quant costa el SEO local a Alacant en 2025?","Per què el teu restaurant no apareix a Google Maps","WordPress vs codi a mida: la veritat que les agències no expliquen","Google Ads vs Meta Ads: quin funciona millor per a PYMES locals?","Com triplicar les trucades d'un negoci local amb SEO","10 eines gratuïtes per analitzar la teua competència online"],
            'es' => ["¿Cuánto cuesta el SEO local en Alicante en 2025?","Por qué tu restaurante no aparece en Google Maps","WordPress vs código a medida: la verdad que las agencias no cuentan","Google Ads vs Meta Ads: ¿cuál funciona mejor para pymes locales?","Cómo triplicar las llamadas de un negocio local con SEO","10 herramientas gratuitas para analizar a tu competencia online"],
            'en' => ["How much does local SEO in Alicante cost in 2025?","Why your restaurant doesn't appear on Google Maps","WordPress vs custom code: the truth agencies don't tell you","Google Ads vs Meta Ads: which works better for local SMBs?","How to triple calls to a local business with SEO","10 free tools to analyze your online competition"],
            'fr' => ["Combien coûte le SEO local à Alicante en 2025 ?","Pourquoi votre restaurant n'apparaît pas sur Google Maps","WordPress vs code sur mesure : la vérité que les agences cachent","Google Ads vs Meta Ads : lequel fonctionne le mieux pour les PME ?","Comment tripler les appels d'un commerce local avec le SEO","10 outils gratuits pour analyser vos concurrents en ligne"],
            'it' => ["Quanto costa il SEO locale ad Alicante nel 2025?","Perché il tuo ristorante non appare su Google Maps","WordPress vs codice su misura: la verità che le agenzie nascondono","Google Ads vs Meta Ads: quale funziona meglio per le PMI locali?","Come triplicare le telefonate di un'attività locale con il SEO","10 strumenti gratuiti per analizzare la concorrenza online"],
        ];
        $teasers = [['cat'=>'seo','mins'=>6],['cat'=>'disseny-web','mins'=>4],['cat'=>'disseny-web','mins'=>7],['cat'=>'marketing','mins'=>5],['cat'=>'negocis','mins'=>5],['cat'=>'eines','mins'=>8]];
        $lang_titles = $teaser_titles[$current_lang] ?? $teaser_titles['ca'];
        ?>
        <div class="blog-empty">
            <div class="blog-empty__icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
            <h3><?= t('blog_empty_title') ?></h3>
            <p><?= t('blog_empty_text') ?></p>
        </div>
        <div class="blog-grid blog-grid--placeholder">
            <?php foreach ($teasers as $i => $ti): ?>
            <div class="blog-card blog-card--teaser">
                <div class="blog-card__cover blog-card__cover--empty"></div>
                <div class="blog-card__body">
                    <span class="blog-cat-tag"><?= htmlspecialchars(catName($cats, $ti['cat'])) ?></span>
                    <h3><?= htmlspecialchars($lang_titles[$i] ?? '') ?></h3>
                    <div class="blog-card__meta">
                        <span><?= $ti['mins'] ?> <?= t('blog_read_mins') ?></span>
                        <span class="blog-card__soon"><?= t('blog_coming_soon') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($all_posts as $i => $p): ?>
            <a href="bloc.php?post=<?= urlencode($p['slug']) ?>" class="blog-card <?= $i === 0 ? 'blog-card--featured' : '' ?>">
                <div class="blog-card__cover" <?= !empty($p['cover']) ? 'style="background-image:url(\'../'.htmlspecialchars($p['cover']).'\')"' : '' ?>>
                    <?php if (empty($p['cover'])): ?><div class="blog-card__cover-fallback"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><?php endif; ?>
                </div>
                <div class="blog-card__body">
                    <div class="blog-card__meta-top">
                        <span class="blog-cat-tag"><?= htmlspecialchars(catName($cats, $p['category'])) ?></span>
                        <?php if ($p['featured'] ?? false): ?><span class="blog-card__badge"><?= t('blog_featured_badge') ?></span><?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars(getTrans($p['title'])) ?></h3>
                    <?php if (!empty($p['excerpt'])): ?><p><?= htmlspecialchars(getTrans($p['excerpt'])) ?></p><?php endif; ?>
                    <div class="blog-card__meta">
                        <time datetime="<?= $p['date'] ?>"><?= date('d/m/Y', strtotime($p['date'])) ?></time>
                        <span>·</span>
                        <span><?= (int)($p['read_mins'] ?? 5) ?> <?= t('blog_read_mins') ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--cta">
    <div class="container">
        <div class="cta-block">
            <div class="section-header__tag"><?= t('blog_cta_tag') ?></div>
            <h2><?= t('blog_cta_title') ?></h2>
            <p><?= t('blog_cta_text') ?></p>
            <div class="cta-block__actions">
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--white btn--lg"><?= t('blog_cta_btn') ?></a>
            </div>
        </div>
    </div>
</section>

<?php endif; ?>
<?php include '../includes/footer.php'; ?>

<style>
.page-hero{padding:100px 0 60px;background:var(--c-bg);border-bottom:1px solid var(--c-border)}
.page-hero h1{font-family:var(--f-display);font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;margin:var(--s-2) 0 var(--s-3)}
.page-hero p{color:var(--c-text-muted);font-size:1.05rem;max-width:560px;line-height:1.7}
.blog-filters{display:flex;gap:var(--s-2);flex-wrap:wrap;margin-bottom:var(--s-8)}
.blog-filter-btn{padding:var(--s-2) var(--s-4);border-radius:100px;border:1.5px solid var(--c-border);font-size:.88rem;font-weight:600;color:var(--c-text-muted);background:white;cursor:pointer;transition:all var(--t-fast);text-decoration:none}
.blog-filter-btn:hover,.blog-filter-btn.active{background:var(--c-primary);color:white;border-color:var(--c-primary)}
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--s-6)}
.blog-grid--placeholder .blog-card--teaser{opacity:.6;pointer-events:none}
.blog-card{background:white;border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--c-border);text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:all var(--t-slow);box-shadow:var(--shadow-sm)}
.blog-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--c-primary)}
.blog-card--featured{grid-column:span 2}.blog-card--featured .blog-card__cover{height:280px}.blog-card--featured h3{font-size:1.3rem}
.blog-card__cover{height:200px;background:var(--c-surface) center/cover no-repeat;flex-shrink:0;position:relative}
.blog-card__cover--empty{background:var(--c-surface)}
.blog-card__cover-fallback{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--c-border)}
.blog-card__body{padding:var(--s-5);display:flex;flex-direction:column;gap:var(--s-2);flex:1}
.blog-card__meta-top{display:flex;align-items:center;justify-content:space-between}
.blog-card h3{font-family:var(--f-display);font-size:1.05rem;font-weight:700;color:var(--c-primary);line-height:1.35;letter-spacing:-.01em}
.blog-card p{font-size:.88rem;color:var(--c-text-muted);line-height:1.55;flex:1}
.blog-card__meta{display:flex;align-items:center;gap:var(--s-2);font-size:.78rem;color:var(--c-text-muted);margin-top:auto;padding-top:var(--s-2)}
.blog-card__badge{font-size:.68rem;font-weight:700;background:var(--c-primary);color:white;padding:2px 8px;border-radius:100px;text-transform:uppercase;letter-spacing:.04em}
.blog-card__soon{font-size:.75rem;font-weight:600;color:var(--c-text-muted);font-style:italic;margin-left:auto}
.blog-cat-tag{font-size:.72rem;font-weight:700;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.06em;text-decoration:none;transition:color var(--t-fast)}
.blog-cat-tag:hover{color:var(--c-primary)}.blog-meta-sep{color:var(--c-border)}
.blog-empty{text-align:center;max-width:480px;margin:0 auto var(--s-12);display:flex;flex-direction:column;align-items:center;gap:var(--s-4)}
.blog-empty__icon{width:96px;height:96px;border:2px dashed var(--c-border);border-radius:var(--r-xl);display:flex;align-items:center;justify-content:center;color:var(--c-text-muted)}
.blog-empty h3{font-family:var(--f-display);font-size:1.4rem;font-weight:700;color:var(--c-primary)}
.blog-empty p{color:var(--c-text-muted);font-size:.95rem;line-height:1.65}
.blog-article__hero{height:420px;overflow:hidden}.blog-article__hero img{width:100%;height:100%;object-fit:cover}
.blog-article__container{max-width:1100px;padding-top:var(--s-12);padding-bottom:var(--s-16)}
.blog-article__header{max-width:760px;margin-bottom:var(--s-10)}
.blog-article__meta{display:flex;align-items:center;gap:var(--s-2);font-size:.82rem;color:var(--c-text-muted);margin-bottom:var(--s-4);flex-wrap:wrap}
.blog-article__header h1{font-family:var(--f-display);font-size:clamp(1.8rem,4vw,2.8rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;line-height:1.2;margin-bottom:var(--s-4)}
.blog-article__lead{font-size:1.15rem;color:var(--c-text-sec);line-height:1.7;border-left:3px solid var(--c-border);padding-left:var(--s-4)}
.blog-article__layout{display:grid;grid-template-columns:1fr 260px;gap:var(--s-12);align-items:start}
.blog-article__body{font-size:1rem;line-height:1.8;color:var(--c-text)}
.blog-article__body h2{font-family:var(--f-display);font-size:1.5rem;font-weight:700;color:var(--c-primary);margin:2em 0 .6em}
.blog-article__body h3{font-family:var(--f-display);font-size:1.2rem;font-weight:600;color:var(--c-primary);margin:1.6em 0 .5em}
.blog-article__body p{margin-bottom:1.2em}.blog-article__body ul,.blog-article__body ol{margin:.8em 0 1.2em 1.5em}.blog-article__body li{margin-bottom:.4em}
.blog-article__body blockquote{border-left:4px solid var(--c-primary);padding:var(--s-4) var(--s-6);margin:1.5em 0;background:var(--c-bg);border-radius:0 var(--r-md) var(--r-md) 0;font-style:italic;color:var(--c-text-sec)}
.blog-article__body a{color:var(--c-primary);text-decoration:underline}.blog-article__body strong{font-weight:700;color:var(--c-primary)}
.blog-article__sidebar{position:sticky;top:90px;display:flex;flex-direction:column;gap:var(--s-6)}
.blog-sidebar-card{background:var(--c-primary);border-radius:var(--r-lg);overflow:hidden}
.blog-sidebar-card__inner{padding:var(--s-6);color:white;display:flex;flex-direction:column;gap:var(--s-4)}
.blog-sidebar-card__inner p{font-size:.9rem;line-height:1.6;color:rgba(255,255,255,.8)}
.blog-sidebar-cats{background:var(--c-bg);border:1px solid var(--c-border);border-radius:var(--r-lg);padding:var(--s-5)}
.blog-sidebar-cats h4{font-family:var(--f-display);font-size:.75rem;font-weight:700;color:var(--c-primary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:var(--s-3)}
.blog-sidebar-cat{display:block;padding:var(--s-2) var(--s-3);font-size:.88rem;color:var(--c-text-muted);border-radius:var(--r-sm);transition:all var(--t-fast);text-decoration:none}
.blog-sidebar-cat:hover,.blog-sidebar-cat.active{background:var(--c-primary);color:white}
.blog-article__footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--s-4);margin-top:var(--s-12);padding-top:var(--s-8);border-top:1px solid var(--c-border)}
.blog-share{display:flex;align-items:center;gap:var(--s-3);font-size:.85rem;color:var(--c-text-muted)}
.blog-share__btn{display:flex;align-items:center;gap:var(--s-2);font-size:.82rem;font-weight:600;color:var(--c-text-sec);border:1px solid var(--c-border);padding:var(--s-2) var(--s-3);border-radius:var(--r-sm);text-decoration:none;transition:all var(--t-fast)}
.blog-share__btn:hover{background:var(--c-primary);color:white;border-color:var(--c-primary)}
@media(max-width:1024px){.blog-article__layout{grid-template-columns:1fr}.blog-article__sidebar{position:static}}
@media(max-width:768px){.blog-grid{grid-template-columns:1fr}.blog-card--featured{grid-column:span 1}.blog-article__hero{height:260px}}
</style>
