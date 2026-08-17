<!DOCTYPE html>
<html lang="<?= $current_lang ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $meta = getSEO($page_seo ?? []); ?>
    <title><?= htmlspecialchars($meta['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
    <meta name="keywords"    content="<?= htmlspecialchars($meta['keywords']) ?>">
    <meta name="robots"      content="index, follow">
    <meta name="author"      content="AKRA Tech Studio">
    <?php if (!empty($meta['canonical'])): ?>
    <link rel="canonical" href="<?= $meta['canonical'] ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?= htmlspecialchars($meta['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta['description']) ?>">
    <meta property="og:url"         content="<?= SITE_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:locale"      content="<?= $meta['og_locale'] ?? 'ca_ES' ?>">
    <meta property="og:site_name"   content="<?= SITE_NAME ?>">
    <meta property="og:image"       content="<?= SITE_URL ?>/assets/img/og-image.jpg">

    <!-- Geo tags — clau per SEO local -->
    <meta name="geo.region"    content="ES-VC">
    <meta name="geo.placename" content="Alacant, Comunitat Valenciana">

    <!-- Alternate hreflang per multilingüe -->
    <?php foreach (AVAILABLE_LANGS as $lang): ?>
    <link rel="alternate" hreflang="<?= $lang ?>" href="<?= SITE_URL . '?lang=' . $lang ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= SITE_URL ?>">

    <!-- Fonts — Caràcter premium sense sacrificar velocitat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('assets/img/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/img/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('assets/img/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('assets/img/android-chrome-192x192.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= asset('assets/img/android-chrome-512x512.png') ?>">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json"><?= getSchemaLD() ?></script>
</head>

<body>

<!-- ─── NAVBAR ─────────────────────────────────────────────────────────── -->
<header class="navbar" id="navbar" role="banner">
    <div class="nav-container">

        <!-- Logo -->
        <a href="<?= asset('index.php') ?>" class="nav-logo" aria-label="AKRA Tech Studio - Inici">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="AKRA Tech Studio" class="nav-logo__img" width="140" height="40">
        </a>

        <!-- Nav links desktop -->
        <nav class="nav-links" id="nav-links" role="navigation" aria-label="Navegació principal">
            <?php foreach (getNav() as $item): 
                $is_cta       = !empty($item['is_cta']);
                $is_highlight = !empty($item['highlight']);
                $url          = pageUrl($item['slug']);
                $is_active    = $item['slug'] && strpos($_SERVER['REQUEST_URI'], $item['slug']) !== false;
            ?>
            <a href="<?= $url ?>" 
               class="nav-link <?= $is_cta ? 'nav-link--cta' : '' ?> <?= $is_highlight ? 'nav-link--highlight' : '' ?> <?= $is_active ? 'nav-link--active' : '' ?>"
               <?= $is_active ? 'aria-current="page"' : '' ?>>
                <?= getTrans($item['label']) ?>
                <?php if ($is_highlight): ?><span class="nav-link__badge">Gratis</span><?php endif; ?>
            </a>
            <?php endforeach; ?>

            <?php
            $client_area_labels = [
                'ca' => 'Àrea de clients', 'es' => 'Área de clientes', 'en' => 'Client area',
                'fr' => 'Espace clients', 'it' => 'Area clienti',
            ];
            ?>
            <a href="<?= asset('hub/index.php') ?>" class="nav-link nav-link--cta" target="_blank" rel="noopener">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;vertical-align:-2px"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?= htmlspecialchars($client_area_labels[$current_lang] ?? $client_area_labels['ca']) ?>
            </a>
        </nav>

        <!-- Selector d'idioma -->
        <div class="nav-lang" id="nav-lang">
            <button class="nav-lang__btn" aria-haspopup="true" aria-expanded="false" id="lang-toggle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>
                <?= strtoupper($current_lang) ?>
            </button>
            <div class="nav-lang__dropdown" id="lang-dropdown" role="menu">
                <?php 
                $lang_labels = ['ca' => 'Català', 'es' => 'Español', 'en' => 'English', 'fr' => 'Français', 'it' => 'Italiano'];
                foreach (AVAILABLE_LANGS as $lang): 
                ?>
                <a href="<?= getLangUrl($lang) ?>" role="menuitem" class="<?= $lang === $current_lang ? 'active' : '' ?>">
                    <?= $lang_labels[$lang] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hamburger mobile -->
        <button class="nav-hamburger" id="nav-hamburger" aria-label="Obrir menú" aria-expanded="false" aria-controls="nav-links">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main id="main-content">
