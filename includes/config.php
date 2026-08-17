<?php
// includes/config.php — AKRA Tech Studio v2
// Base de dades en PHP + Sistema multilingüe + SEO Local Alacant

if (session_status() === PHP_SESSION_NONE) session_start();

// ─── CONFIGURACIÓ BÀSICA ───────────────────────────────────────────────────
define('SITE_NAME',    'AKRA Tech Studio');
define('SITE_URL',     'https://akratechstudio.es');
define('SITE_SLOGAN',  'Agència Digital Premium a Alacant');
define('DEFAULT_LANG', 'es');
define('AVAILABLE_LANGS', ['ca', 'es', 'en', 'fr', 'it']);

// ─── CONFIGURACIÓ DINÀMICA (llegeix del JSON del backend) ──────────────────
// Permet que els canvis al backend s'apliquen sense tocar fitxers PHP
$_site_config_file = __DIR__ . '/../admin/data/site_config.json';
$_site_cfg = [];
if (file_exists($_site_config_file)) {
    $_site_cfg = json_decode(file_get_contents($_site_config_file), true) ?? [];
}

define('CONTACT_EMAIL',    $_site_cfg['email']     ?? 'hola@akratechstudio.es');
define('CONTACT_PHONE',    $_site_cfg['phone']     ?? '+34 600 000 000');
define('CONTACT_MAPS',     $_site_cfg['maps_url']  ?? 'https://maps.google.com/?q=Alacant');
define('WHATSAPP_NUMBER',        $_site_cfg['whatsapp_number']        ?? '34683279162');
define('WHATSAPP_FLOAT_PUBLIC',  $_site_cfg['whatsapp_float_public']  ?? true);
define('WHATSAPP_FLOAT_MESSAGE', $_site_cfg['whatsapp_float_message'] ?? 'Hola! Tinc una consulta');
define('COOKIE_BANNER_ENABLED',  $_site_cfg['cookie_banner_enabled']  ?? true);
define('COOKIE_CONSENT_DAYS',    (int)($_site_cfg['cookie_consent_days'] ?? 365));
define('SOCIAL_INSTAGRAM', $_site_cfg['instagram'] ?? '#');
define('SOCIAL_LINKEDIN',  $_site_cfg['linkedin']  ?? '#');
define('SOCIAL_FACEBOOK',  $_site_cfg['facebook']  ?? '#');
define('SOCIAL_TIKTOK',    $_site_cfg['tiktok']    ?? '#');
unset($_site_config_file, $_site_cfg);

// ─── GESTIÓ D'IDIOMES ──────────────────────────────────────────────────────
function getCurrentLang() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], AVAILABLE_LANGS)) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], AVAILABLE_LANGS)) {
        return $_SESSION['lang'];
    }
    return DEFAULT_LANG;
}

$current_lang = getCurrentLang();
$translations  = require __DIR__ . "/../lang/{$current_lang}.php";
require_once __DIR__ . '/translations.php';

function t($key, $default = '') {
    global $translations;
    return $translations[$key] ?? ($default ?: $key);
}
function getTrans($arr) {
    global $current_lang;
    if (!is_array($arr)) return $arr;
    return $arr[$current_lang] ?? $arr[DEFAULT_LANG] ?? $arr['es'] ?? '';
}
function getLangUrl($lang) {
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    return $url . '?lang=' . $lang;
}
function sanitize($d) {
    return htmlspecialchars(strip_tags(trim($d)), ENT_QUOTES, 'UTF-8');
}

// Calcula la ruta relativa fins l'arrel del projecte
// Funciona independentment de si estàs a /, /pages/, /admin/, etc.
function rootPath() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    // Troba on és l'arrel (on hi ha index.php principal)
    $root = str_replace('\\', '/', dirname(dirname(__FILE__)));
    $rel  = str_replace($root, '', dirname($script));
    $depth = $rel ? substr_count(trim($rel, '/'), '/') + 1 : 0;
    return $depth > 0 ? str_repeat('../', $depth) : './';
}

// Genera URL d'asset relativa (CSS, JS, imatges)
function asset($path) {
    return rootPath() . ltrim($path, '/');
}

// Genera URL relativa a una pàgina
function pageUrl($slug) {
    $root = rootPath();
    if (!$slug) return $root . 'index.php';
    return $root . 'pages/' . $slug . '.php';
}

// ─── SEO LOCAL ALACANT ─────────────────────────────────────────────────────
// Estructurant les keywords principals per les que la competència no ranka bé
$seo_global = [
    'ca' => [
        'title'       => 'AKRA Tech Studio · Agència Disseny Web i Màrqueting Digital a Alacant',
        'description' => 'Agència de disseny web professional, SEO local i màrqueting digital a Alacant. Webs que venen per a empreses de la Costa Blanca. Pressupost gratuït en 24h.',
        'keywords'    => 'agencia web Alacant, disseny web Alicante, SEO local Alicante, marketing digital Alicante, agencia marketing Alacant, diseño web Costa Blanca',
        'og_locale'   => 'ca_ES',
    ],
    'es' => [
        'title'       => 'AKRA Tech Studio · Agencia de Diseño Web y Marketing Digital en Alicante',
        'description' => 'Agencia de diseño web profesional, SEO local y marketing digital en Alicante. Webs que venden para empresas de la Costa Blanca. Presupuesto gratuito en 24h.',
        'keywords'    => 'agencia diseño web Alicante, SEO Alicante, marketing digital Alicante, desarrollo web Alicante, agencia marketing Costa Blanca, diseño web profesional Alicante',
        'og_locale'   => 'es_ES',
    ],
    'en' => [
        'title'       => 'AKRA Tech Studio · Web Design & Digital Marketing Agency in Alicante',
        'description' => 'Professional web design, local SEO and digital marketing agency in Alicante, Spain. Websites that sell for Costa Blanca businesses. Free quote in 24h.',
        'keywords'    => 'web design Alicante, digital marketing Alicante, SEO agency Alicante Spain, Costa Blanca web agency',
        'og_locale'   => 'en_GB',
    ],
    'fr' => [
        'title'       => 'AKRA Tech Studio · Agence Web & Marketing Digital à Alicante',
        'description' => 'Agence de création web, SEO local et marketing digital à Alicante. Devis gratuit sous 24h.',
        'keywords'    => 'agence web Alicante, marketing digital Alicante, création site web Costa Blanca',
        'og_locale'   => 'fr_FR',
    ],
    'it' => [
        'title'       => 'AKRA Tech Studio · Agenzia Web & Marketing Digitale ad Alicante',
        'description' => 'Agenzia di design web, SEO locale e marketing digitale ad Alicante. Preventivo gratuito entro 24h.',
        'keywords'    => 'agenzia web Alicante, marketing digitale Alicante, sito web Costa Blanca',
        'og_locale'   => 'it_IT',
    ],
];

function getSEO($page_seo = []) {
    global $seo_global, $current_lang;
    $base = $seo_global[$current_lang] ?? $seo_global[DEFAULT_LANG];
    return array_merge($base, $page_seo);
}

// ─── SCHEMA.ORG JSON-LD (clau per SEO local) ──────────────────────────────
function getSchemaLD() {
    return json_encode([
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "@id" => SITE_URL . "/#business",
        "name" => SITE_NAME,
        "description" => "Agència de disseny web, SEO local i màrqueting digital a Alacant",
        "url" => SITE_URL,
        "telephone" => CONTACT_PHONE,
        "email" => CONTACT_EMAIL,

        "areaServed" => [
            ["@type" => "City", "name" => "Alacant"],
            ["@type" => "City", "name" => "Benidorm"],
            ["@type" => "City", "name" => "Elx"],
            ["@type" => "City", "name" => "Torrevella"],
            ["@type" => "City", "name" => "Dénia"],
            ["@type" => "City", "name" => "Altea"],
            ["@type" => "AdministrativeArea", "name" => "Comunitat Valenciana"],
        ],
        "serviceType" => [
            "Disseny Web Professional",
            "SEO Local Alacant",
            "Màrqueting Digital",
            "Disseny Gràfic",
            "Gestió Xarxes Socials",
            "Google Ads",
            "E-commerce"
        ],
        "priceRange" => "€€",
        "openingHoursSpecification" => [
            ["@type" => "OpeningHoursSpecification", "dayOfWeek" => ["Monday","Tuesday","Wednesday","Thursday","Friday"], "opens" => "09:00", "closes" => "18:00"]
        ],
        "sameAs" => [SOCIAL_INSTAGRAM, SOCIAL_LINKEDIN, SOCIAL_FACEBOOK],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — SERVEIS
//  Per afegir/editar serveis: modifica l'array $services_db
// ═══════════════════════════════════════════════════════════════════════════
$services_db = [
    [
        'id'   => 1,
        'slug' => 'disseny-web',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'title' => [
            'ca' => 'Disseny Web Professional',
            'es' => 'Diseño Web Profesional',
            'en' => 'Professional Web Design',
            'fr' => 'Conception Web Professionnelle',
            'it' => 'Web Design Professionale',
        ],
        'desc_short' => [
            'ca' => 'Webs modernes, ràpides i optimitzades per convertir visitants en clients. SEO-first des del primer dia.',
            'es' => 'Webs modernas, rápidas y optimizadas para convertir visitantes en clientes. SEO-first desde el primer día.',
            'en' => 'Modern, fast websites optimized to convert visitors into clients. SEO-first from day one.',
            'fr' => 'Sites web modernes, rapides et optimisés pour convertir les visiteurs en clients.',
            'it' => 'Siti web moderni, veloci e ottimizzati per convertire i visitatori in clienti.',
        ],
        'highlight' => ['ca' => 'Més demanat', 'es' => 'Más solicitado', 'en' => 'Most requested'],
        'order' => 1,
        'active' => true,
    ],
    [
        'id'   => 2,
        'slug' => 'seo-local',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v3l2 2"/></svg>',
        'title' => [
            'ca' => 'SEO Local a Alacant',
            'es' => 'SEO Local en Alicante',
            'en' => 'Local SEO in Alicante',
            'fr' => 'SEO Local à Alicante',
            'it' => 'SEO Locale ad Alicante',
        ],
        'desc_short' => [
            'ca' => 'Apareix el primer a Google quan els teus clients busquen el que ofereixes. Google Maps, fitxa GMB i keywords locals.',
            'es' => 'Aparece primero en Google cuando tus clientes buscan lo que ofreces. Google Maps, ficha GMB y keywords locales.',
            'en' => 'Appear first on Google when your clients search for what you offer. Google Maps, GMB listing and local keywords.',
            'fr' => 'Apparaissez en premier sur Google. Google Maps, fiche GMB et mots-clés locaux.',
            'it' => 'Appari primo su Google. Google Maps, scheda GMB e parole chiave locali.',
        ],
        'highlight' => ['ca' => 'Resultats visibles', 'es' => 'Resultados visibles'],
        'order' => 2,
        'active' => true,
    ],
    [
        'id'   => 3,
        'slug' => 'marketing-digital',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'title' => [
            'ca' => 'Màrqueting Digital',
            'es' => 'Marketing Digital',
            'en' => 'Digital Marketing',
            'fr' => 'Marketing Digital',
            'it' => 'Marketing Digitale',
        ],
        'desc_short' => [
            'ca' => 'Estratègies de màrqueting digital que generen resultats mesurables: Google Ads, Meta Ads, email marketing i més.',
            'es' => 'Estrategias de marketing digital que generan resultados medibles: Google Ads, Meta Ads, email marketing y más.',
            'en' => 'Digital marketing strategies that generate measurable results: Google Ads, Meta Ads, email marketing and more.',
            'fr' => 'Stratégies marketing qui génèrent des résultats mesurables.',
            'it' => 'Strategie di marketing che generano risultati misurabili.',
        ],
        'highlight' => null,
        'order' => 3,
        'active' => true,
    ],
    [
        'id'   => 4,
        'slug' => 'disseny-grafic',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>',
        'title' => [
            'ca' => 'Disseny Gràfic i Branding',
            'es' => 'Diseño Gráfico y Branding',
            'en' => 'Graphic Design & Branding',
            'fr' => 'Design Graphique & Branding',
            'it' => 'Design Grafico & Branding',
        ],
        'desc_short' => [
            'ca' => 'Identitat visual que fa destacar la teua marca. Logotips, cartells, materials corporatius i imatge de marca completa.',
            'es' => 'Identidad visual que hace destacar tu marca. Logotipos, carteles, materiales corporativos e imagen de marca completa.',
            'en' => 'Visual identity that makes your brand stand out. Logos, posters, corporate materials and complete brand image.',
            'fr' => 'Identité visuelle qui fait ressortir votre marque.',
            'it' => 'Identità visiva che fa risaltare il tuo brand.',
        ],
        'highlight' => null,
        'order' => 4,
        'active' => true,
    ],
    [
        'id'   => 5,
        'slug' => 'xarxes-socials',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'title' => [
            'ca' => 'Xarxes Socials',
            'es' => 'Redes Sociales',
            'en' => 'Social Media',
            'fr' => 'Réseaux Sociaux',
            'it' => 'Social Media',
        ],
        'desc_short' => [
            'ca' => 'Gestió professional de xarxes socials que connecta amb la teua audiència i fa créixer la teva comunitat de clients.',
            'es' => 'Gestión profesional de redes sociales que conecta con tu audiencia y hace crecer tu comunidad de clientes.',
            'en' => 'Professional social media management that connects with your audience and grows your client community.',
            'fr' => 'Gestion professionnelle des réseaux sociaux.',
            'it' => 'Gestione professionale dei social media.',
        ],
        'highlight' => null,
        'order' => 5,
        'active' => true,
    ],
    [
        'id'   => 6,
        'slug' => 'ecommerce',
        'icon_svg' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>',
        'title' => [
            'ca' => 'Botiga Online (E-commerce)',
            'es' => 'Tienda Online (E-commerce)',
            'en' => 'Online Store (E-commerce)',
            'fr' => 'Boutique en Ligne (E-commerce)',
            'it' => 'Negozio Online (E-commerce)',
        ],
        'desc_short' => [
            'ca' => 'Botigues online completes que venen les 24h del dia. WooCommerce, Shopify o solució a mida per al teu sector.',
            'es' => 'Tiendas online completas que venden las 24h del día. WooCommerce, Shopify o solución a medida para tu sector.',
            'en' => 'Complete online stores that sell 24/7. WooCommerce, Shopify or custom solution for your sector.',
            'fr' => 'Boutiques en ligne complètes qui vendent 24h/24.',
            'it' => 'Negozi online completi che vendono 24 ore su 24.',
        ],
        'highlight' => null,
        'order' => 6,
        'active' => true,
    ],
];

function getServices($active_only = true) {
    global $services_db;
    $s = $active_only ? array_filter($services_db, fn($s) => $s['active']) : $services_db;
    usort($s, fn($a, $b) => $a['order'] <=> $b['order']);
    return $s;
}
function getServiceBySlug($slug) {
    global $services_db;
    foreach ($services_db as $s) if ($s['slug'] === $slug) return $s;
    return null;
}


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — DIFERENCIADORS ("Per què AKRA")
// ═══════════════════════════════════════════════════════════════════════════
$differentiators_db = [
    [
        'num' => '01',
        'title' => ['ca' => 'Entregem en 4 setmanes', 'es' => 'Entregamos en 4 semanas', 'en' => 'We deliver in 4 weeks'],
        'desc'  => ['ca' => 'Cap excusa, cap retard. Tenim un procés provat que garanteix els terminis. Si no cumplim, el pressupost és teu.', 'es' => 'Sin excusas, sin retrasos. Tenemos un proceso probado que garantiza los plazos. Si no cumplimos, el presupuesto es tuyo.', 'en' => 'No excuses, no delays. We have a proven process that guarantees deadlines.'],
    ],
    [
        'num' => '02',
        'title' => ['ca' => 'SEO-first des del dia 1', 'es' => 'SEO-first desde el día 1', 'en' => 'SEO-first from day 1'],
        'desc'  => ['ca' => 'Cada web que fem ja inclou SEO tècnic bàsic. No és un extra: és la nostra manera de treballar.', 'es' => 'Cada web que hacemos ya incluye SEO técnico básico. No es un extra: es nuestra forma de trabajar.', 'en' => 'Every website we build already includes basic technical SEO. It\'s not an extra: it\'s our way of working.'],
    ],
    [
        'num' => '03',
        'title' => ['ca' => 'Parles amb qui treballa el teu projecte', 'es' => 'Hablas con quien trabaja tu proyecto', 'en' => 'You talk to who works on your project'],
        'desc'  => ['ca' => 'Sense intermediaris, sense account managers. La persona que dissenya la teua web és la mateixa amb qui parles.', 'es' => 'Sin intermediarios, sin account managers. La persona que diseña tu web es la misma con quien hablas.', 'en' => 'No middlemen, no account managers. The person designing your website is the same one you talk to.'],
    ],
    [
        'num' => '04',
        'title' => ['ca' => 'Resultats, no tràfic buit', 'es' => 'Resultados, no tráfico vacío', 'en' => 'Results, not empty traffic'],
        'desc'  => ['ca' => 'Ens interessa que les teues conversions augmenten, no les visites. Mesurem, optimitzem i informem cada mes.', 'es' => 'Nos interesa que tus conversiones aumenten, no las visitas. Medimos, optimizamos e informamos cada mes.', 'en' => 'We care about increasing your conversions, not visits. We measure, optimise and report every month.'],
    ],
];
function getDifferentiators() { global $differentiators_db; return $differentiators_db; }


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — PROCÉS
// ═══════════════════════════════════════════════════════════════════════════
$process_steps_db = [
    [
        'icon_svg' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
        'title' => ['ca' => 'Descobriment', 'es' => 'Descubrimiento', 'en' => 'Discovery', 'fr' => 'Découverte', 'it' => 'Scoperta'],
        'desc'  => ['ca' => 'Fem una videotrucada o reunió presencial per entendre el teu negoci, els teus objectius i la teva competència.', 'es' => 'Hacemos una videollamada o reunión presencial para entender tu negocio, tus objetivos y tu competencia.', 'en' => 'We have a video call or in-person meeting to understand your business, goals and competition.'],
        'time'  => ['ca' => 'Dia 1 · Gratuït', 'es' => 'Día 1 · Gratuito', 'en' => 'Day 1 · Free'],
    ],
    [
        'icon_svg' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
        'title' => ['ca' => 'Proposta', 'es' => 'Propuesta', 'en' => 'Proposal', 'fr' => 'Proposition', 'it' => 'Proposta'],
        'desc'  => ['ca' => 'T\'enviem una proposta detallada amb pressupost, terminis i entregables clars. Sense lletra menuda.', 'es' => 'Te enviamos una propuesta detallada con presupuesto, plazos y entregables claros. Sin letra pequeña.', 'en' => 'We send you a detailed proposal with budget, deadlines and clear deliverables. No fine print.'],
        'time'  => ['ca' => 'Dia 2-3', 'es' => 'Día 2-3', 'en' => 'Day 2-3'],
    ],
    [
        'icon_svg' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
        'title' => ['ca' => 'Disseny i Desenvolupament', 'es' => 'Diseño y Desarrollo', 'en' => 'Design & Development', 'fr' => 'Conception et Développement', 'it' => 'Design e Sviluppo'],
        'desc'  => ['ca' => 'Dissenyem i construïm el teu projecte. T\'anem mostrant avenços per a que validis cada fase.', 'es' => 'Diseñamos y construimos tu proyecto. Te mostramos avances para que valides cada fase.', 'en' => 'We design and build your project. We show you progress so you validate each phase.'],
        'time'  => ['ca' => 'Setmanes 2-4', 'es' => 'Semanas 2-4', 'en' => 'Weeks 2-4'],
    ],
    [
        'icon_svg' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'title' => ['ca' => 'Llançament i Suport', 'es' => 'Lanzamiento y Soporte', 'en' => 'Launch & Support', 'fr' => 'Lancement et Support', 'it' => 'Lancio e Supporto'],
        'desc'  => ['ca' => 'Llançament en viu amb formació inclosa. Suport post-llançament durant 30 dies sense cost addicional.', 'es' => 'Lanzamiento en vivo con formación incluida. Soporte post-lanzamiento durante 30 días sin coste adicional.', 'en' => 'Live launch with included training. Post-launch support for 30 days at no extra cost.'],
        'time'  => ['ca' => 'Setmana 4 · + 30 dies suport', 'es' => 'Semana 4 · + 30 días soporte', 'en' => 'Week 4 · + 30 days support'],
    ],
];
function getProcessSteps() { global $process_steps_db; return $process_steps_db; }


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — TESTIMONIS
//  Afegeix els teus clients reals ací. Els placeholders serveixen fins que en
//  tingues. Activa/desactiva amb 'active' => true/false
// ═══════════════════════════════════════════════════════════════════════════
$testimonials_db = [
    [
        'id'   => 1,
        'name' => ['ca' => 'Maria García', 'es' => 'Maria García'],
        'company' => ['ca' => 'Clínica Dental Alacant', 'es' => 'Clínica Dental Alicante'],
        'text' => [
            'ca' => 'Des que AKRA ens va redissenyar la web i va optimitzar el SEO local, hem triplicat les trucades mensuals. Professionals de veritat.',
            'es' => 'Desde que AKRA nos rediseñó la web y optimizó el SEO local, hemos triplicado las llamadas mensuales. Profesionales de verdad.',
            'en' => 'Since AKRA redesigned our website and optimised local SEO, we\'ve tripled our monthly calls. Real professionals.',
        ],
        'rating' => 5,
        'active' => true,
    ],
    [
        'id'   => 2,
        'name' => ['ca' => 'Joan Martínez', 'es' => 'Juan Martínez'],
        'company' => ['ca' => 'Restaurant Sa Cuina · Benidorm', 'es' => 'Restaurante Sa Cuina · Benidorm'],
        'text' => [
            'ca' => 'La nostra botiga online va facturar 40.000€ el primer any. AKRA no és una agència, és un soci de negoci.',
            'es' => 'Nuestra tienda online facturó 40.000€ el primer año. AKRA no es una agencia, es un socio de negocio.',
            'en' => 'Our online store turned over €40,000 in the first year. AKRA is not an agency, it\'s a business partner.',
        ],
        'rating' => 5,
        'active' => true,
    ],
    [
        'id'   => 3,
        'name' => ['ca' => 'Laura Sánchez', 'es' => 'Laura Sánchez'],
        'company' => ['ca' => 'Immobiliària Costa Blanca', 'es' => 'Inmobiliaria Costa Blanca'],
        'text' => [
            'ca' => 'Molt professional, ràpid i sempre disponible. Recomane AKRA a qualsevol empresa de la zona.',
            'es' => 'Muy profesional, rápido y siempre disponible. Recomiendo AKRA a cualquier empresa de la zona.',
            'en' => 'Very professional, fast and always available. I recommend AKRA to any company in the area.',
        ],
        'rating' => 5,
        'active' => true,
    ],
];
function getTestimonials($active_only = true) {
    global $testimonials_db;
    return $active_only ? array_values(array_filter($testimonials_db, fn($t) => $t['active'])) : $testimonials_db;
}


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — PROJECTES / PORTFOLIO
//  Afegeix els teus projectes reals ací. featured=true → apareix a inici
// ═══════════════════════════════════════════════════════════════════════════
$projects_db = [
    [
        'id' => '1',
        'slug' => 'el-portic-revista-cultural',
        'category' => 'web',
        'status' => 'active',
        'featured' => true,
        'title' => ['ca' => 'El Pòrtic — Revista Cultural Digital', 'es' => 'El Pòrtic — Revista Cultural Digital'],
        'description' => ['ca' => 'Revista cultural digital independent editada a Alacant. Plataforma editorial completa amb gestió d\'articles, categories, pàgines d\'autor, newsletter, RSS, gestió de cookies RGPD i panell d\'administració. ISSN 3101-5492.', 'es' => 'Revista cultural digital independiente editada en Alicante. Plataforma editorial completa con gestión de artículos, categorías, páginas de autor, newsletter, RSS, cookies RGPD y panel de administración. ISSN 3101-5492.'],
        'results' => ['ca' => 'CMS editorial a mida · 6 seccions · ISSN 3101-5492', 'es' => 'CMS editorial a medida · 6 secciones · ISSN 3101-5492'],
        'thumbnail' => 'assets/img/projects/elportic.jpg',
        'url' => 'https://www.elportic.cat',
        'video' => null,
        'tech' => ['PHP','MySQL','CMS propi','Newsletter','RSS','RGPD','SEO'],
        'date' => '2026-01',
        'active' => true,
    ],
    [
        'id' => '3',
        'slug' => 'kmysetas-ecommerce-samarretes',
        'category' => 'ecommerce',
        'status' => 'active',
        'featured' => true,
        'title' => ['ca' => 'Kmysetas — E-commerce de Samarretes Personalitzades', 'es' => 'Kmysetas — E-commerce de Camisetas Personalizadas'],
        'description' => ['ca' => 'Botiga online completa de samarretes, sudadores i accessoris personalitzats amb dissenyador gràfic integrat. Catàleg amb talles XS–XXXL, carret, pasarela de pagament segura, gestió de comandes i 5 idiomes. Actiu des de 2005.', 'es' => 'Tienda online completa de camisetas, sudaderas y accesorios personalizados con diseñador gráfico integrado. Catálogo con tallas XS–XXXL, carrito, pasarela de pago segura y 5 idiomas. Activo desde 2005.'],
        'results' => ['ca' => 'E-commerce · Dissenyador online · 5 idiomes · Des de 2005', 'es' => 'E-commerce · Diseñador online · 5 idiomas · Desde 2005'],
        'thumbnail' => 'assets/img/projects/kmysetas.jpg',
        'url' => 'https://www.kmysetas.com',
        'video' => null,
        'tech' => ['PHP','MySQL','JavaScript','E-commerce','Dissenyador online','Pasarela pagament','5 idiomes'],
        'date' => '2024-11',
        'active' => true,
    ],
    [
        'id' => '1772206189_0dcd1cd8',
        'slug' => 'cartell-de-halloween-per-al-ceip-azorin-d-alacant',
        'category' => 'design',
        'status' => 'active',
        'featured' => false,
        'title' => ['ca' => 'Cartell de Halloween per al CEIP AZORIN d&#039;Alacant', 'es' => 'Cartel de Halloween CEIP AZORIN Alicante'],
        'description' => ['ca' => 'Cartell per a la festa de Halloween del CEIP Azorin d&#039;Alacant per l&#039;AMPA amb joc de colors i temàtica de por i infantil', 'es' => 'Cartel para la fiesta de Halloween del CEIP Azorin de Alicante organizada por  el AMPA con juego de colores y temática de miedo e infantil'],
        'results' => ['ca' => '', 'es' => ''],
        'thumbnail' => '',
        'url' => '',
        'video' => null,
        'tech' => ['Illustrator','Photoshop'],
        'date' => '2025-09',
        'active' => true,
    ],
    [
        'id' => '1773407882_80094076',
        'slug' => 'Barris',
        'category' => 'app',
        'status' => 'active',
        'featured' => true,
        'title' => ['ca' => 'Barris. Recursos urbans', 'es' => 'Barrios. recursos Urbanos'],
        'description' => ['ca' => 'App per notificar  mancances als barris d&#039;Alacant', 'es' => 'App para notificar  carencias en los barrios de Alicante'],
        'results' => ['ca' => '', 'es' => ''],
        'thumbnail' => 'admin/uploads/projects/1773407882_a3d84c14.png',
        'url' => 'https://akratechstudio.es/barris',
        'video' => null,
        'tech' => ['Html','css','js','python'],
        'date' => '2026-03',
        'active' => true,
    ],
    [
        'id' => '1777530879_5bb7c646',
        'slug' => 'Dari Trives',
        'category' => 'web',
        'status' => 'active',
        'featured' => true,
        'title' => ['ca' => 'Pàgina oficial de Dari Trives. poeta valencià', 'es' => 'Página oficial de Dari Trives. poeta valencià'],
        'description' => ['ca' => 'pàgina oficial del poeta Dari Trives on es pot comprar la seua obra a més de conéixer l&amp;amp;#039;autor.', 'es' => 'pàgina oficial del poeta Dari Trives donde se puede comprar  su obra además  de conocer al autor.'],
        'results' => ['ca' => '', 'es' => ''],
        'thumbnail' => 'admin/uploads/projects/1779268232_036499fe.png',
        'url' => 'https://www.daritrives.cat',
        'video' => null,
        'tech' => ['PHP','JS','html','Mysql'],
        'date' => '2026-03',
        'active' => true,
    ],
];

function getProjects($category = null, $featured_only = false) {
    global $projects_db;
    $p = $projects_db;
    if ($category)      $p = array_filter($p, fn($x) => $x['category'] === $category);
    if ($featured_only) $p = array_filter($p, fn($x) => $x['featured'] === true);
    return array_values($p);
}
function getProject($slug) {
    global $projects_db;
    foreach ($projects_db as $p) if ($p['slug'] === $slug) return $p;
    return null;
}


// ═══════════════════════════════════════════════════════════════════════════
//  BASE DE DADES — PÀGINES INTERNES (nav)
// ═══════════════════════════════════════════════════════════════════════════
$nav_items = [
    ['slug' => '',           'label' => ['ca' => 'Inici',      'es' => 'Inicio',    'en' => 'Home'],      'file' => 'index.php'],
    ['slug' => 'serveis',    'label' => ['ca' => 'Serveis',    'es' => 'Servicios', 'en' => 'Services'],  'file' => 'pages/serveis.php'],
    ['slug' => 'projectes',  'label' => ['ca' => 'Projectes',  'es' => 'Proyectos', 'en' => 'Projects'],  'file' => 'pages/projectes.php'],
    ['slug' => 'nosaltres',  'label' => ['ca' => 'Nosaltres',  'es' => 'Nosotros',  'en' => 'About'],     'file' => 'pages/nosaltres.php'],
    ['slug' => 'bloc',       'label' => ['ca' => 'Bloc',       'es' => 'Blog',      'en' => 'Blog'],      'file' => 'pages/bloc.php'],
    ['slug' => 'pressupost', 'label' => ['ca' => 'Pressupost', 'es' => 'Presupuesto','en' => 'Quote'],    'file' => 'pages/pressupost.php', 'highlight' => true],
    ['slug' => 'contacte',   'label' => ['ca' => 'Contacte',   'es' => 'Contacto',  'en' => 'Contact'],   'file' => 'pages/contacte.php', 'is_cta' => true],
    // Pàgines no al nav principal però accessibles via pageUrl()
    ['slug' => 'proces',     'label' => ['ca' => 'Com treballem', 'es' => 'Cómo trabajamos', 'en' => 'How we work'], 'file' => 'pages/proces.php',     'nav' => false],
    ['slug' => 'privacitat', 'label' => ['ca' => 'Privacitat', 'es' => 'Privacidad', 'en' => 'Privacy'],  'file' => 'pages/privacitat.php', 'nav' => false],
    ['slug' => 'cookies',    'label' => ['ca' => 'Cookies',    'es' => 'Cookies',   'en' => 'Cookies'],   'file' => 'pages/cookies.php',    'nav' => false],
    ['slug' => 'avis-legal', 'label' => ['ca' => 'Avís Legal', 'es' => 'Aviso Legal','en' => 'Legal'],    'file' => 'pages/avis-legal.php', 'nav' => false],
];
function getNav() {
    global $nav_items;
    return array_filter($nav_items, fn($i) => ($i['nav'] ?? true) !== false);
}

// ═══════════════════════════════════════════════════════════════════════════
//  PLACES DISPONIBLES — llegeix des del JSON del backend
// ═══════════════════════════════════════════════════════════════════════════
function getSlots() {
    $data_file = __DIR__ . '/../admin/data/site_config.json';
    $cfg = file_exists($data_file) ? (json_decode(file_get_contents($data_file), true) ?? []) : [];
    $total = (int)($cfg['slots_total'] ?? 5);
    $used  = (int)($cfg['slots_used']  ?? 0);
    $free  = max(0, $total - $used);
    return [
        'total'  => $total,
        'used'   => $used,
        'free'   => $free,
        'show'   => (bool)($cfg['slots_show'] ?? true),
        'full'   => $free === 0,
    ];
}
