<?php
// pages/proces.php — Com treballem
require_once '../includes/config.php';

$T = [
    'ca' => [
        'title'    => 'Com treballem · Procés de Treball | AKRA Tech Studio Alacant',
        'desc'     => 'Descobreix el procés de treball d\'AKRA Tech Studio. Del primer contacte al llançament en 45 dies. Transparent, clar i sense sorpreses.',
        'h1'       => 'Del primer contacte al llançament',
        'sub'      => 'Un procés clar, en 4 fases. 45 dies. Sense sorpreses.',
        'tag'      => 'Com treballem',
        'phases'   => [
            ['num'=>'01','icon'=>'💬','name'=>'Descoberta','days'=>'Dies 1–3',
             'desc'=>'Primera reunió (presencial o videollamada). Escoltem el teu negoci, objectius i competència. Definim conjuntament l\'abast del projecte i t\'enviem un pressupost detallat en 24h.'],
            ['num'=>'02','icon'=>'🎨','name'=>'Disseny','days'=>'Dies 4–15',
             'desc'=>'Creem el disseny visual del projecte: paleta, tipografia, wireframes i mockups. Tu aproves cada pantalla abans que escrivim una sola línia de codi.'],
            ['num'=>'03','icon'=>'⚙️','name'=>'Desenvolupament','days'=>'Dies 16–38',
             'desc'=>'Programem tot des de zero: HTML, CSS, PHP, JavaScript. Cap plantilla, cap WordPress. El resultat és una web ràpida, segura i exactament com l\'has aprovat.'],
            ['num'=>'04','icon'=>'🚀','name'=>'Llançament','days'=>'Dies 39–45',
             'desc'=>'Tests finals, revisió SEO, configuració del domini i hosting. Llançament. T\'entreguem documentació i formes d\'ús bàsiques. Suport post-llançament inclòs 30 dies.'],
        ],
        'why_title'=> 'Per què 45 dies?',
        'why_desc' => 'Ni massa ràpid ni massa lent. Un termini curt força decisions precipitades; un termini massa llarg allarga innecessàriament. 45 dies permet fer les coses bé sense perdre impuls.',
        'cta'      => 'Parlem del teu projecte',
        'incl_title'=> 'Inclòs en tots els projectes',
        'included' => [
            'Reunions de seguiment setmanals','Accés a esborrany en viu per fer proves',
            'Optimització de velocitat (Core Web Vitals)','SEO tècnic bàsic inclòs',
            'Adaptació mòbil i tablet','Formulari de contacte funcional',
            '30 dies de suport post-llançament','Documentació bàsica d\'ús',
        ],
    ],
    'es' => [
        'title'    => 'Cómo trabajamos · Proceso de Trabajo | AKRA Tech Studio Alicante',
        'desc'     => 'Descubre el proceso de trabajo de AKRA Tech Studio. Del primer contacto al lanzamiento en 45 días. Transparente, claro y sin sorpresas.',
        'h1'       => 'Del primer contacto al lanzamiento',
        'sub'      => 'Un proceso claro, en 4 fases. 45 días. Sin sorpresas.',
        'tag'      => 'Cómo trabajamos',
        'phases'   => [
            ['num'=>'01','icon'=>'💬','name'=>'Descubrimiento','days'=>'Días 1–3',
             'desc'=>'Primera reunión (presencial o videollamada). Escuchamos tu negocio, objetivos y competencia. Definimos conjuntamente el alcance del proyecto y te enviamos un presupuesto detallado en 24h.'],
            ['num'=>'02','icon'=>'🎨','name'=>'Diseño','days'=>'Días 4–15',
             'desc'=>'Creamos el diseño visual del proyecto: paleta, tipografía, wireframes y mockups. Tú apruebas cada pantalla antes de que escribamos una sola línea de código.'],
            ['num'=>'03','icon'=>'⚙️','name'=>'Desarrollo','days'=>'Días 16–38',
             'desc'=>'Programamos todo desde cero: HTML, CSS, PHP, JavaScript. Sin plantillas, sin WordPress. El resultado es una web rápida, segura y exactamente como la has aprobado.'],
            ['num'=>'04','icon'=>'🚀','name'=>'Lanzamiento','days'=>'Días 39–45',
             'desc'=>'Tests finales, revisión SEO, configuración del dominio y hosting. Lanzamiento. Te entregamos documentación y guía de uso básica. Soporte post-lanzamiento incluido 30 días.'],
        ],
        'why_title'=> '¿Por qué 45 días?',
        'why_desc' => 'Ni demasiado rápido ni demasiado lento. Un plazo corto fuerza decisiones precipitadas; un plazo demasiado largo alarga innecesariamente. 45 días permite hacer las cosas bien sin perder impulso.',
        'cta'      => 'Hablemos de tu proyecto',
        'incl_title'=> 'Incluido en todos los proyectos',
        'included' => [
            'Reuniones de seguimiento semanales','Acceso a borrador en vivo para pruebas',
            'Optimización de velocidad (Core Web Vitals)','SEO técnico básico incluido',
            'Adaptación móvil y tablet','Formulario de contacto funcional',
            '30 días de soporte post-lanzamiento','Documentación básica de uso',
        ],
    ],
    'en' => [
        'title'    => 'How We Work · Work Process | AKRA Tech Studio Alicante',
        'desc'     => 'Discover AKRA Tech Studio\'s work process. From first contact to launch in 45 days. Transparent, clear and no surprises.',
        'h1'       => 'From first contact to launch',
        'sub'      => 'A clear process, in 4 phases. 45 days. No surprises.',
        'tag'      => 'How we work',
        'phases'   => [
            ['num'=>'01','icon'=>'💬','name'=>'Discovery','days'=>'Days 1–3',
             'desc'=>'First meeting (in-person or video call). We listen to your business, goals and competition. We jointly define the project scope and send you a detailed quote within 24h.'],
            ['num'=>'02','icon'=>'🎨','name'=>'Design','days'=>'Days 4–15',
             'desc'=>'We create the visual design: palette, typography, wireframes and mockups. You approve every screen before we write a single line of code.'],
            ['num'=>'03','icon'=>'⚙️','name'=>'Development','days'=>'Days 16–38',
             'desc'=>'We code everything from scratch: HTML, CSS, PHP, JavaScript. No templates, no WordPress. The result is a fast, secure website exactly as you approved.'],
            ['num'=>'04','icon'=>'🚀','name'=>'Launch','days'=>'Days 39–45',
             'desc'=>'Final tests, SEO review, domain and hosting setup. Launch. We deliver documentation and basic usage guide. 30 days post-launch support included.'],
        ],
        'why_title'=> 'Why 45 days?',
        'why_desc' => 'Not too fast, not too slow. A short deadline forces rushed decisions; too long unnecessarily drags things out. 45 days allows doing things right without losing momentum.',
        'cta'      => 'Let\'s talk about your project',
        'incl_title'=> 'Included in all projects',
        'included' => [
            'Weekly follow-up meetings','Live draft access for testing',
            'Speed optimisation (Core Web Vitals)','Basic technical SEO included',
            'Mobile and tablet adaptation','Functional contact form',
            '30 days post-launch support','Basic usage documentation',
        ],
    ],
    'fr' => [
        'title'    => 'Comment nous travaillons · Processus | AKRA Tech Studio Alicante',
        'desc'     => 'Découvrez le processus de travail d\'AKRA Tech Studio. Du premier contact au lancement en 45 jours. Transparent, clair et sans surprises.',
        'h1'       => 'Du premier contact au lancement',
        'sub'      => 'Un processus clair, en 4 phases. 45 jours. Sans surprises.',
        'tag'      => 'Comment nous travaillons',
        'phases'   => [
            ['num'=>'01','icon'=>'💬','name'=>'Découverte','days'=>'Jours 1–3',
             'desc'=>'Première réunion (en personne ou appel vidéo). Nous écoutons votre activité, vos objectifs et vos concurrents. Nous définissons ensemble la portée du projet et vous envoyons un devis détaillé en 24h.'],
            ['num'=>'02','icon'=>'🎨','name'=>'Design','days'=>'Jours 4–15',
             'desc'=>'Nous créons le design visuel : palette, typographie, wireframes et maquettes. Vous approuvez chaque écran avant que nous écrivions une seule ligne de code.'],
            ['num'=>'03','icon'=>'⚙️','name'=>'Développement','days'=>'Jours 16–38',
             'desc'=>'Nous codons tout depuis zéro : HTML, CSS, PHP, JavaScript. Pas de templates, pas de WordPress. Le résultat est un site rapide, sécurisé et exactement tel que vous l\'avez approuvé.'],
            ['num'=>'04','icon'=>'🚀','name'=>'Lancement','days'=>'Jours 39–45',
             'desc'=>'Tests finaux, révision SEO, configuration du domaine et hébergement. Lancement. Nous vous remettons la documentation et un guide d\'utilisation. Support post-lancement inclus 30 jours.'],
        ],
        'why_title'=> 'Pourquoi 45 jours ?',
        'why_desc' => 'Ni trop rapide ni trop lent. Un délai court force des décisions précipitées ; un délai trop long s\'étire inutilement. 45 jours permet de bien faire les choses sans perdre l\'élan.',
        'cta'      => 'Parlons de votre projet',
        'incl_title'=> 'Inclus dans tous les projets',
        'included' => [
            'Réunions de suivi hebdomadaires','Accès au brouillon en direct',
            'Optimisation de la vitesse (Core Web Vitals)','SEO technique de base inclus',
            'Adaptation mobile et tablette','Formulaire de contact fonctionnel',
            '30 jours de support post-lancement','Documentation d\'utilisation de base',
        ],
    ],
    'it' => [
        'title'    => 'Come lavoriamo · Processo di Lavoro | AKRA Tech Studio Alicante',
        'desc'     => 'Scopri il processo di lavoro di AKRA Tech Studio. Dal primo contatto al lancio in 45 giorni. Trasparente, chiaro e senza sorprese.',
        'h1'       => 'Dal primo contatto al lancio',
        'sub'      => 'Un processo chiaro, in 4 fasi. 45 giorni. Senza sorprese.',
        'tag'      => 'Come lavoriamo',
        'phases'   => [
            ['num'=>'01','icon'=>'💬','name'=>'Scoperta','days'=>'Giorni 1–3',
             'desc'=>'Primo incontro (di persona o videochiamata). Ascoltiamo il tuo business, i tuoi obiettivi e la concorrenza. Definiamo insieme la portata del progetto e ti inviamo un preventivo dettagliato entro 24h.'],
            ['num'=>'02','icon'=>'🎨','name'=>'Design','days'=>'Giorni 4–15',
             'desc'=>'Creiamo il design visivo: palette, tipografia, wireframe e mockup. Tu approvi ogni schermata prima che scriviamo una sola riga di codice.'],
            ['num'=>'03','icon'=>'⚙️','name'=>'Sviluppo','days'=>'Giorni 16–38',
             'desc'=>'Programmiamo tutto da zero: HTML, CSS, PHP, JavaScript. Nessun template, nessun WordPress. Il risultato è un sito veloce, sicuro ed esattamente come lo hai approvato.'],
            ['num'=>'04','icon'=>'🚀','name'=>'Lancio','days'=>'Giorni 39–45',
             'desc'=>'Test finali, revisione SEO, configurazione del dominio e hosting. Lancio. Ti consegniamo la documentazione e una guida all\'uso base. Supporto post-lancio incluso 30 giorni.'],
        ],
        'why_title'=> 'Perché 45 giorni?',
        'why_desc' => 'Non troppo veloce, non troppo lento. Una scadenza breve forza decisioni affrettate; una troppo lunga allunga inutilmente. 45 giorni permette di fare le cose bene senza perdere slancio.',
        'cta'      => 'Parliamo del tuo progetto',
        'incl_title'=> 'Incluso in tutti i progetti',
        'included' => [
            'Riunioni di follow-up settimanali','Accesso alla bozza live per i test',
            'Ottimizzazione della velocità (Core Web Vitals)','SEO tecnico base incluso',
            'Adattamento mobile e tablet','Modulo di contatto funzionale',
            '30 giorni di supporto post-lancio','Documentazione base d\'uso',
        ],
    ],
];
$cl = $T[$current_lang] ?? $T['es'];

$page_seo = [
    'title'       => $cl['title'],
    'description' => $cl['desc'],
    'canonical'   => SITE_URL . '/pages/proces.php',
];
include '../includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="section-header__tag"><?= htmlspecialchars($cl['tag']) ?></div>
        <h1><?= htmlspecialchars($cl['h1']) ?></h1>
        <p><?= htmlspecialchars($cl['sub']) ?></p>
    </div>
</section>

<section class="section section--white">
<div class="container">

    <!-- Fases -->
    <div class="process-phases">
        <?php foreach ($cl['phases'] as $i => $phase): ?>
        <div class="process-phase <?= $i % 2 === 1 ? 'process-phase--alt' : '' ?>">
            <div class="process-phase__num"><?= $phase['num'] ?></div>
            <div class="process-phase__content">
                <div class="process-phase__header">
                    <span class="process-phase__icon"><?= $phase['icon'] ?></span>
                    <div>
                        <h2><?= htmlspecialchars($phase['name']) ?></h2>
                        <span class="process-phase__days"><?= htmlspecialchars($phase['days']) ?></span>
                    </div>
                </div>
                <p><?= htmlspecialchars($phase['desc']) ?></p>
            </div>
            <?php if ($i < count($cl['phases']) - 1): ?>
            <div class="process-phase__arrow">↓</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Per què 45 dies -->
    <div class="process-why">
        <h3><?= htmlspecialchars($cl['why_title']) ?></h3>
        <p><?= htmlspecialchars($cl['why_desc']) ?></p>
    </div>

    <!-- Inclòs en tots -->
    <div class="process-included">
        <h3><?= htmlspecialchars($cl['incl_title']) ?></h3>
        <div class="process-included__grid">
            <?php foreach ($cl['included'] as $item): ?>
            <div class="process-included__item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= htmlspecialchars($item) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CTA -->
    <div style="text-align:center; margin-top: var(--s-8)">
        <a href="<?= pageUrl('contacte') ?>" class="btn btn--primary btn--lg">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            <?= htmlspecialchars($cl['cta']) ?>
        </a>
    </div>

</div>
</section>

<?php include '../includes/footer.php'; ?>

<style>
.page-hero { padding: 100px 0 60px; background: var(--c-bg); border-bottom: 1px solid var(--c-border); }
.page-hero h1 { font-family: var(--f-display); font-size: clamp(1.8rem,3.5vw,2.8rem); font-weight: 700; color: var(--c-primary); letter-spacing:-.02em; margin: var(--s-2) 0 var(--s-3); }
.page-hero p  { color: var(--c-text-muted); font-size: 1.05rem; max-width: 540px; }

.process-phases { max-width: 720px; margin: 0 auto var(--s-8); }
.process-phase { position: relative; display: grid; grid-template-columns: 80px 1fr; gap: var(--s-5); align-items: start; padding: var(--s-6) 0; border-bottom: 1px solid var(--c-border); }
.process-phase:last-child { border-bottom: none; }
.process-phase__num { font-family: var(--f-display); font-size: 3.5rem; font-weight: 800; color: var(--c-border); line-height: 1; padding-top: 4px; }
.process-phase__header { display: flex; align-items: center; gap: var(--s-3); margin-bottom: var(--s-3); }
.process-phase__icon { font-size: 2rem; }
.process-phase__header h2 { font-size: 1.3rem; font-weight: 700; margin: 0; }
.process-phase__days { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--c-text-muted); display: block; margin-top: 2px; }
.process-phase__content p { color: var(--c-text-muted); line-height: 1.75; }
.process-phase__arrow { display: none; }

.process-why { background: var(--c-bg); border-radius: 12px; padding: var(--s-6); max-width: 720px; margin: 0 auto var(--s-6); }
.process-why h3 { font-family: var(--f-display); font-size: 1.2rem; font-weight: 700; margin-bottom: var(--s-3); }
.process-why p  { color: var(--c-text-muted); line-height: 1.75; }

.process-included { max-width: 720px; margin: 0 auto; }
.process-included h3 { font-family: var(--f-display); font-size: 1.2rem; font-weight: 700; margin-bottom: var(--s-4); }
.process-included__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: var(--s-3); }
.process-included__item { display: flex; align-items: center; gap: var(--s-2); font-size: 14px; color: var(--c-text); }
.process-included__item svg { color: #22c55e; flex-shrink: 0; }
</style>
