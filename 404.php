<?php
require_once 'includes/config.php';
http_response_code(404);

$page_seo = [
    'title'       => '404 · Pàgina no trobada | AKRA Tech Studio',
    'description' => 'Aquesta pàgina no existeix.',
    'noindex'     => true,
];

$texts = [
    'ca' => ['title'=>'Pàgina no trobada','sub'=>"Sembla que el que busques s'ha perdut en el codi.", 'home'=>'Tornar a l\'inici', 'contact'=>'Contactar', 'projects'=>'Veure projectes'],
    'es' => ['title'=>'Página no encontrada','sub'=>'Parece que lo que buscas se ha perdido en el código.', 'home'=>'Volver al inicio', 'contact'=>'Contactar', 'projects'=>'Ver proyectos'],
    'en' => ['title'=>'Page not found','sub'=>'Looks like what you were looking for got lost in the code.', 'home'=>'Back to home', 'contact'=>'Contact us', 'projects'=>'View projects'],
    'fr' => ['title'=>'Page introuvable','sub'=>'Ce que vous cherchez semble perdu dans le code.', 'home'=>'Retour à l\'accueil', 'contact'=>'Nous contacter', 'projects'=>'Voir les projets'],
    'it' => ['title'=>'Pagina non trovata','sub'=>'Sembra che quello che cerchi si sia perso nel codice.', 'home'=>'Torna alla home', 'contact'=>'Contattaci', 'projects'=>'Vedi progetti'],
];
$cl = $texts[$current_lang] ?? $texts['es'];

include 'includes/header.php';
?>

<section class="e404">
    <div class="e404__bg" aria-hidden="true">
        <div class="e404__grid"></div>
        <div class="e404__glow"></div>
    </div>
    <div class="container e404__inner">
        <div class="e404__code-wrap" aria-hidden="true">
            <span class="e404__num e404__num--4a">4</span>
            <span class="e404__num e404__num--0">0</span>
            <span class="e404__num e404__num--4b">4</span>
        </div>
        <div class="e404__content">
            <div class="e404__tag">Error 404</div>
            <h1 class="e404__title"><?= htmlspecialchars($cl['title']) ?></h1>
            <p class="e404__sub"><?= htmlspecialchars($cl['sub']) ?></p>
            <div class="e404__actions">
                <a href="<?= SITE_URL ?>" class="btn btn--primary btn--lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <?= htmlspecialchars($cl['home']) ?>
                </a>
                <a href="<?= pageUrl('projectes') ?>" class="btn btn--ghost">
                    <?= htmlspecialchars($cl['projects']) ?>
                </a>
                <a href="<?= pageUrl('contacte') ?>" class="btn btn--ghost">
                    <?= htmlspecialchars($cl['contact']) ?>
                </a>
            </div>
        </div>

        <!-- Terminal animat decoratiu -->
        <div class="e404__terminal" aria-hidden="true">
            <div class="e404__term-bar">
                <span class="dot dot--r"></span>
                <span class="dot dot--y"></span>
                <span class="dot dot--g"></span>
                <span class="e404__term-title">akra ~ error</span>
            </div>
            <div class="e404__term-body">
                <div class="e404__term-line"><span class="t-dim">$</span> <span class="t-cmd">curl</span> <span class="t-str"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/...') ?></span></div>
                <div class="e404__term-line t-err" style="animation-delay:.6s">HTTP/1.1 <strong>404</strong> Not Found</div>
                <div class="e404__term-line" style="animation-delay:1.2s"><span class="t-dim">→</span> Content-Length: 0</div>
                <div class="e404__term-line t-comment" style="animation-delay:1.8s"># La pàgina sol·licitada no existeix</div>
                <div class="e404__term-line" style="animation-delay:2.4s"><span class="t-dim">$</span> <span class="t-cursor">▌</span></div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<style>
/* ── 404 ─────────────────────────────────────────────────── */
.e404 {
    min-height: calc(100vh - 70px);
    display: flex; align-items: center;
    position: relative; overflow: hidden;
    background: #06060a;
    padding: 80px 0;
}

/* Grid de fons */
.e404__bg { position: absolute; inset: 0; pointer-events: none; }
.e404__grid {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(201,168,76,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(201,168,76,.06) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 40%, transparent 100%);
}
.e404__glow {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 60% 50% at 50% 0%, rgba(201,168,76,.12) 0%, transparent 70%);
}

/* Inner layout */
.e404__inner {
    position: relative; z-index: 1;
    display: grid;
    grid-template-rows: auto auto auto;
    justify-items: center;
    gap: 32px;
    text-align: center;
}

/* Xifres grans */
.e404__code-wrap {
    display: flex; align-items: center; justify-content: center;
    line-height: 1; user-select: none;
}
.e404__num {
    font-family: 'Syne', sans-serif;
    font-size: clamp(120px, 22vw, 240px);
    font-weight: 800;
    letter-spacing: -.04em;
    background: linear-gradient(160deg, #fff 30%, rgba(255,255,255,.15) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: floatNum 6s ease-in-out infinite;
}
.e404__num--4a { animation-delay: 0s; }
.e404__num--0  {
    animation-delay: .3s;
    background: linear-gradient(160deg, #c9a84c 0%, rgba(201,168,76,.3) 100%);
    -webkit-background-clip: text; background-clip: text;
    font-size: clamp(100px, 18vw, 200px);
}
.e404__num--4b { animation-delay: .6s; }

@keyframes floatNum {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-10px); }
}

/* Contingut */
.e404__content { max-width: 520px; }
.e404__tag {
    display: inline-block;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .12em; text-transform: uppercase;
    color: #c9a84c;
    border: 1px solid rgba(201,168,76,.3);
    padding: 4px 14px; border-radius: 100px;
    margin-bottom: 16px;
}
.e404__title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 700; color: #fff;
    letter-spacing: -.03em; margin-bottom: 12px;
}
.e404__sub {
    font-size: 1rem; color: rgba(255,255,255,.5);
    line-height: 1.65; margin-bottom: 32px;
}
.e404__actions {
    display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;
}
.e404__actions .btn--ghost {
    border-color: rgba(255,255,255,.2);
    color: rgba(255,255,255,.65);
}
.e404__actions .btn--ghost:hover {
    border-color: rgba(255,255,255,.5);
    color: #fff; background: rgba(255,255,255,.06);
}

/* Terminal */
.e404__terminal {
    width: 100%; max-width: 480px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px;
    overflow: hidden;
    font-family: 'Menlo', 'Consolas', monospace;
    font-size: .78rem;
}
.e404__term-bar {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.05);
    padding: 10px 14px;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.dot {
    width: 10px; height: 10px; border-radius: 50%;
}
.dot--r { background: #ff5f57; }
.dot--y { background: #febc2e; }
.dot--g { background: #28c840; }
.e404__term-title {
    margin-left: 8px;
    font-size: .72rem; color: rgba(255,255,255,.3);
    letter-spacing: .04em;
}
.e404__term-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 6px; }
.e404__term-line {
    color: rgba(255,255,255,.55);
    animation: termFade .4s ease both;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.t-dim   { color: rgba(255,255,255,.2); }
.t-cmd   { color: #7dd3fc; }
.t-str   { color: #86efac; }
.t-err   { color: #f87171; }
.t-comment { color: rgba(255,255,255,.25); font-style: italic; }
.t-cursor {
    color: #c9a84c;
    animation: blink .9s step-end infinite;
}
@keyframes termFade { from { opacity:0; transform: translateX(-6px); } to { opacity:1; transform:none; } }
@keyframes blink    { 0%,100%{opacity:1} 50%{opacity:0} }

@media (max-width: 600px) {
    .e404__actions { flex-direction: column; align-items: center; }
    .e404__actions .btn { width: 100%; max-width: 280px; justify-content: center; }
    .e404__terminal { font-size: .7rem; }
}
</style>
