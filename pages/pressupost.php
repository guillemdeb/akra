<?php
// pages/pressupost.php — Sistema de Packs AKRA (màx 2.500€, multilingüe)
require_once '../includes/config.php';

// Funció helper
function gtxt($arr, $lang) {
    return is_array($arr) ? ($arr[$lang] ?? $arr['es'] ?? '') : $arr;
}

// ─── PACKS ────────────────────────────────────────────────────────────────
$packs = [
  'starter' => [
    'price'=>599,'orig'=>799,'color'=>'#52525b','icon'=>'🌱','featured'=>false,'dark'=>false,'badge'=>null,'budget_bucket'=>'500-1500',
    'name'   =>['ca'=>'Starter','es'=>'Starter','en'=>'Starter','fr'=>'Starter','it'=>'Starter'],
    'tagline'=>['ca'=>'Presència online ràpida i efectiva','es'=>'Presencia online rápida y efectiva','en'=>'Fast and effective online presence','fr'=>'Présence en ligne rapide et efficace','it'=>'Presenza online rapida ed efficace'],
    'ideal'  =>['ca'=>'Per a autònoms i negocis locals','es'=>'Para autónomos y negocios locales','en'=>'For freelancers and local businesses','fr'=>'Pour les indépendants et commerces locaux','it'=>'Per autonomi e negozi locali'],
    'inc'=>[
      ['ca'=>'Landing page (1–4 pàgines)','es'=>'Landing page (1–4 páginas)','en'=>'Landing page (1–4 pages)','fr'=>'Landing page (1–4 pages)','it'=>'Landing page (1–4 pagine)'],
      ['ca'=>'Disseny a mida 100%','es'=>'Diseño a medida 100%','en'=>'100% custom design','fr'=>'Design sur mesure 100%','it'=>'Design su misura 100%'],
      ['ca'=>'Formulari de contacte','es'=>'Formulario de contacto','en'=>'Contact form','fr'=>'Formulaire de contact','it'=>'Modulo di contatto'],
      ['ca'=>'SEO bàsic (títols, meta, velocitat)','es'=>'SEO básico (títulos, meta, velocidad)','en'=>'Basic SEO (titles, meta, speed)','fr'=>'SEO de base (titres, meta, vitesse)','it'=>'SEO base (titoli, meta, velocità)'],
      ['ca'=>'Web mòbil (responsive)','es'=>'Web móvil (responsive)','en'=>'Mobile-ready (responsive)','fr'=>'Mobile-ready (responsive)','it'=>'Mobile-ready (responsive)'],
      ['ca'=>'Entrega en 15–20 dies','es'=>'Entrega en 15–20 días','en'=>'Delivered in 15–20 days','fr'=>'Livré en 15–20 jours','it'=>'Consegnato in 15–20 giorni'],
    ],
    'exc'=>[
      ['ca'=>'Blog o CMS','es'=>'Blog o CMS','en'=>'Blog or CMS','fr'=>'Blog ou CMS','it'=>'Blog o CMS'],
      ['ca'=>'Multiidioma','es'=>'Multiidioma','en'=>'Multilingual','fr'=>'Multilingue','it'=>'Multilingua'],
    ],
  ],
  'web' => [
    'price'=>1199,'orig'=>1699,'color'=>'#2563eb','icon'=>'🏢','featured'=>true,'dark'=>false,'budget_bucket'=>'500-1500',
    'badge'=>['ca'=>'Més popular','es'=>'Más popular','en'=>'Most popular','fr'=>'Le plus populaire','it'=>'Più popolare'],
    'name'   =>['ca'=>'Web','es'=>'Web','en'=>'Web','fr'=>'Web','it'=>'Web'],
    'tagline'=>['ca'=>'La web professional que el teu negoci mereix','es'=>'La web profesional que tu negocio merece','en'=>'The professional website your business deserves','fr'=>'Le site professionnel que votre entreprise mérite','it'=>'Il sito professionale che la tua azienda merita'],
    'ideal'  =>['ca'=>'Per a pimes i negocis en creixement','es'=>'Para pymes y negocios en crecimiento','en'=>'For SMEs and growing businesses','fr'=>'Pour les PME et les entreprises en croissance','it'=>'Per PMI e aziende in crescita'],
    'inc'=>[
      ['ca'=>'Web corporativa fins 10 pàgines','es'=>'Web corporativa hasta 10 páginas','en'=>'Corporate website up to 10 pages','fr'=>'Site corporate jusqu\'à 10 pages','it'=>'Sito corporate fino a 10 pagine'],
      ['ca'=>'Disseny a mida 100%','es'=>'Diseño a medida 100%','en'=>'100% custom design','fr'=>'Design sur mesure 100%','it'=>'Design su misura 100%'],
      ['ca'=>'Blog amb gestió d\'articles','es'=>'Blog con gestión de artículos','en'=>'Blog with article management','fr'=>'Blog avec gestion d\'articles','it'=>'Blog con gestione articoli'],
      ['ca'=>'SEO tècnic integrat','es'=>'SEO técnico integrado','en'=>'Integrated technical SEO','fr'=>'SEO technique intégré','it'=>'SEO tecnico integrato'],
      ['ca'=>'Analytics + informe mensual','es'=>'Analytics + informe mensual','en'=>'Analytics + monthly report','fr'=>'Analytics + rapport mensuel','it'=>'Analytics + report mensile'],
      ['ca'=>'Web mòbil (responsive)','es'=>'Web móvil (responsive)','en'=>'Mobile-ready (responsive)','fr'=>'Mobile-ready (responsive)','it'=>'Mobile-ready (responsive)'],
      ['ca'=>'Entrega en 25–35 dies','es'=>'Entrega en 25–35 días','en'=>'Delivered in 25–35 days','fr'=>'Livré en 25–35 jours','it'=>'Consegnato in 25–35 giorni'],
    ],
    'exc'=>[
      ['ca'=>'Panel d\'administració avançat','es'=>'Panel de administración avanzado','en'=>'Advanced admin panel','fr'=>'Panel d\'administration avancé','it'=>'Pannello di amministrazione avanzato'],
      ['ca'=>'Multiidioma','es'=>'Multiidioma','en'=>'Multilingual','fr'=>'Multilingue','it'=>'Multilingua'],
    ],
  ],
  'pro' => [
    'price'=>1899,'orig'=>2799,'color'=>'#7c3aed','icon'=>'🚀','featured'=>false,'dark'=>false,'budget_bucket'=>'1500-3000',
    'badge'=>['ca'=>'Millor valor','es'=>'Mejor valor','en'=>'Best value','fr'=>'Meilleur rapport','it'=>'Miglior rapporto'],
    'name'   =>['ca'=>'Pro','es'=>'Pro','en'=>'Pro','fr'=>'Pro','it'=>'Pro'],
    'tagline'=>['ca'=>'Potència i control total del teu projecte','es'=>'Potencia y control total de tu proyecto','en'=>'Full power and control over your project','fr'=>'Puissance et contrôle total de votre projet','it'=>'Potenza e controllo totale del tuo progetto'],
    'ideal'  =>['ca'=>'Per a empreses amb necessitats avançades','es'=>'Para empresas con necesidades avanzadas','en'=>'For companies with advanced needs','fr'=>'Pour les entreprises avec des besoins avancés','it'=>'Per aziende con esigenze avanzate'],
    'inc'=>[
      ['ca'=>'Web completa fins 20 pàgines','es'=>'Web completa hasta 20 páginas','en'=>'Full website up to 20 pages','fr'=>'Site complet jusqu\'à 20 pages','it'=>'Sito completo fino a 20 pagine'],
      ['ca'=>'Disseny a mida 100%','es'=>'Diseño a medida 100%','en'=>'100% custom design','fr'=>'Design sur mesure 100%','it'=>'Design su misura 100%'],
      ['ca'=>'Blog + CMS propi (sense WordPress)','es'=>'Blog + CMS propio (sin WordPress)','en'=>'Blog + own CMS (no WordPress)','fr'=>'Blog + CMS propre (sans WordPress)','it'=>'Blog + CMS proprio (senza WordPress)'],
      ['ca'=>'Panel d\'administració a mida','es'=>'Panel de administración a medida','en'=>'Custom admin panel','fr'=>'Panel d\'administration sur mesure','it'=>'Pannello di amministrazione su misura'],
      ['ca'=>'Multiidioma (CA + ES + EN)','es'=>'Multiidioma (CA + ES + EN)','en'=>'Multilingual (CA + ES + EN)','fr'=>'Multilingue (CA + ES + EN)','it'=>'Multilingua (CA + ES + EN)'],
      ['ca'=>'SEO avançat + Analytics complet','es'=>'SEO avanzado + Analytics completo','en'=>'Advanced SEO + full Analytics','fr'=>'SEO avancé + Analytics complet','it'=>'SEO avanzato + Analytics completo'],
      ['ca'=>'Velocitat i Core Web Vitals optimitzats','es'=>'Velocidad y Core Web Vitals optimizados','en'=>'Speed and Core Web Vitals optimized','fr'=>'Vitesse et Core Web Vitals optimisés','it'=>'Velocità e Core Web Vitals ottimizzati'],
      ['ca'=>'Entrega en 35–45 dies','es'=>'Entrega en 35–45 días','en'=>'Delivered in 35–45 days','fr'=>'Livré en 35–45 jours','it'=>'Consegnato in 35–45 giorni'],
    ],
    'exc'=>[
      ['ca'=>'Botiga online (e-commerce)','es'=>'Tienda online (e-commerce)','en'=>'Online store (e-commerce)','fr'=>'Boutique en ligne (e-commerce)','it'=>'Negozio online (e-commerce)'],
    ],
  ],
  'total' => [
    'price'=>2499,'orig'=>4199,'color'=>'#f9f9f9','icon'=>'⭐','featured'=>false,'dark'=>true,'budget_bucket'=>'1500-3000',
    'badge'=>['ca'=>'Tot inclòs','es'=>'Todo incluido','en'=>'All included','fr'=>'Tout inclus','it'=>'Tutto incluso'],
    'name'   =>['ca'=>'Total','es'=>'Total','en'=>'Total','fr'=>'Total','it'=>'Total'],
    'tagline'=>['ca'=>'La solució digital completa per al teu negoci','es'=>'La solución digital completa para tu negocio','en'=>'The complete digital solution for your business','fr'=>'La solution numérique complète pour votre entreprise','it'=>'La soluzione digitale completa per la tua azienda'],
    'ideal'  =>['ca'=>'Per a projectes ambiciosos amb tot inclòs','es'=>'Para proyectos ambiciosos con todo incluido','en'=>'For ambitious projects with everything included','fr'=>'Pour les projets ambitieux tout inclus','it'=>'Per progetti ambiziosi con tutto incluso'],
    'inc'=>[
      ['ca'=>'Web completa il·limitada','es'=>'Web completa ilimitada','en'=>'Unlimited full website','fr'=>'Site complet illimité','it'=>'Sito completo illimitato'],
      ['ca'=>'Disseny a mida 100% + branding','es'=>'Diseño a medida 100% + branding','en'=>'100% custom design + branding','fr'=>'Design sur mesure 100% + branding','it'=>'Design su misura 100% + branding'],
      ['ca'=>'Blog + CMS propi (sense WordPress)','es'=>'Blog + CMS propio (sin WordPress)','en'=>'Blog + own CMS (no WordPress)','fr'=>'Blog + CMS propre (sans WordPress)','it'=>'Blog + CMS proprio (senza WordPress)'],
      ['ca'=>'Panel d\'administració complet','es'=>'Panel de administración completo','en'=>'Full admin panel','fr'=>'Panel d\'administration complet','it'=>'Pannello di amministrazione completo'],
      ['ca'=>'Multiidioma (CA + ES + EN + ...)','es'=>'Multiidioma (CA + ES + EN + ...)','en'=>'Multilingual (CA + ES + EN + ...)','fr'=>'Multilingue (CA + ES + EN + ...)','it'=>'Multilingua (CA + ES + EN + ...)'],
      ['ca'=>'E-commerce / botiga online integrada','es'=>'E-commerce / tienda online integrada','en'=>'E-commerce / integrated online store','fr'=>'E-commerce / boutique en ligne intégrée','it'=>'E-commerce / negozio online integrato'],
      ['ca'=>'SEO avançat + Analytics complet','es'=>'SEO avanzado + Analytics completo','en'=>'Advanced SEO + full Analytics','fr'=>'SEO avancé + Analytics complet','it'=>'SEO avanzato + Analytics completo'],
      ['ca'=>'3 mesos de manteniment inclòs','es'=>'3 meses de mantenimiento incluido','en'=>'3 months maintenance included','fr'=>'3 mois de maintenance inclus','it'=>'3 mesi di manutenzione inclusa'],
      ['ca'=>'Suport prioritari 48h','es'=>'Soporte prioritario 48h','en'=>'Priority support 48h','fr'=>'Support prioritaire 48h','it'=>'Supporto prioritario 48h'],
      ['ca'=>'Entrega en 40–55 dies','es'=>'Entrega en 40–55 días','en'=>'Delivered in 40–55 days','fr'=>'Livré en 40–55 jours','it'=>'Consegnato in 40–55 giorni'],
    ],
    'exc'=>[],
  ],
];

// ─── SERVEIS MENSUALS ─────────────────────────────────────────────────────
$monthly = [
  ['icon'=>'🔍','price'=>199,'min'=>6,
    'name'=>['ca'=>'SEO Local','es'=>'SEO Local','en'=>'Local SEO','fr'=>'SEO Local','it'=>'SEO Locale'],
    'desc'=>['ca'=>'Posicionament a Google a Alacant. Mínim 6 mesos.','es'=>'Posicionamiento en Google en Alicante. Mínimo 6 meses.','en'=>'Google ranking in Alicante. Minimum 6 months.','fr'=>'Positionnement sur Google à Alicante. Minimum 6 mois.','it'=>'Posizionamento su Google ad Alicante. Minimo 6 mesi.'],
  ],
  ['icon'=>'📣','price'=>299,'min'=>3,
    'name'=>['ca'=>'Màrqueting Digital','es'=>'Marketing Digital','en'=>'Digital Marketing','fr'=>'Marketing Digital','it'=>'Marketing Digitale'],
    'desc'=>['ca'=>'Google Ads, Meta Ads i email marketing. Mínim 3 mesos.','es'=>'Google Ads, Meta Ads y email marketing. Mínimo 3 meses.','en'=>'Google Ads, Meta Ads and email marketing. Minimum 3 months.','fr'=>'Google Ads, Meta Ads et email marketing. Minimum 3 mois.','it'=>'Google Ads, Meta Ads ed email marketing. Minimo 3 mesi.'],
  ],
  ['icon'=>'🛡️','price'=>89,'min'=>1,
    'name'=>['ca'=>'Manteniment','es'=>'Mantenimiento','en'=>'Maintenance','fr'=>'Maintenance','it'=>'Manutenzione'],
    'desc'=>['ca'=>'Actualitzacions, còpies de seguretat i suport tècnic mensual.','es'=>'Actualizaciones, copias de seguridad y soporte técnico mensual.','en'=>'Updates, backups and monthly technical support.','fr'=>'Mises à jour, sauvegardes et support technique mensuel.','it'=>'Aggiornamenti, backup e supporto tecnico mensile.'],
  ],
];

// ─── TEXTOS ───────────────────────────────────────────────────────────────
$T=[
 'ca'=>['tag'=>'Preus','title'=>'Tria el teu pack AKRA','subtitle'=>'Com més inclous, més estalvies. Sense sorpreses, sense IVA amagat.','save'=>'Estalvies','incl'=>'Inclou','not_incl'=>'No inclou','cta'=>'Sol·licitar pressupost','monthly_t'=>'Serveis mensuals','monthly_s'=>'Complementa el teu pack amb serveis recurrents','month_sfx'=>'/mes','min'=>'Mínim','months'=>'mesos','iva'=>'Sense IVA','per_item'=>'Com més afegeixes, millor preu per servei','guarantee'=>'45 dies garantits · Codi a mida, mai plantilles · Resposta en 24h','custom_t'=>'Necessites alguna cosa diferent?','custom_s'=>'Projectes especials, apps web, integracions o pressupostos a mida — parlem-ne sense compromís.','custom_cta'=>'Parla amb nosaltres'],
 'es'=>['tag'=>'Precios','title'=>'Elige tu pack AKRA','subtitle'=>'Cuanto más incluyes, más ahorras. Sin sorpresas, sin IVA oculto.','save'=>'Ahorras','incl'=>'Incluye','not_incl'=>'No incluye','cta'=>'Solicitar presupuesto','monthly_t'=>'Servicios mensuales','monthly_s'=>'Complementa tu pack con servicios recurrentes','month_sfx'=>'/mes','min'=>'Mínimo','months'=>'meses','iva'=>'Sin IVA','per_item'=>'Cuanto más añades, mejor precio por servicio','guarantee'=>'45 días garantizados · Código a medida, nunca plantillas · Respuesta en 24h','custom_t'=>'¿Necesitas algo diferente?','custom_s'=>'Proyectos especiales, apps web, integraciones o presupuestos a medida — hablemos sin compromiso.','custom_cta'=>'Habla con nosotros'],
 'en'=>['tag'=>'Pricing','title'=>'Choose your AKRA pack','subtitle'=>'The more you include, the more you save. No surprises, no hidden VAT.','save'=>'You save','incl'=>'Includes','not_incl'=>'Not included','cta'=>'Request a quote','monthly_t'=>'Monthly services','monthly_s'=>'Complement your pack with recurring services','month_sfx'=>'/mo','min'=>'Minimum','months'=>'months','iva'=>'Excl. VAT','per_item'=>'The more you add, the better the price per service','guarantee'=>'45 days guaranteed · Custom code, never templates · Response in 24h','custom_t'=>'Need something different?','custom_s'=>'Special projects, web apps, integrations or custom quotes — let\'s talk with no commitment.','custom_cta'=>'Talk to us'],
 'fr'=>['tag'=>'Tarifs','title'=>'Choisissez votre pack AKRA','subtitle'=>'Plus vous incluez, plus vous économisez. Sans surprises, sans TVA cachée.','save'=>'Vous économisez','incl'=>'Inclut','not_incl'=>'Non inclus','cta'=>'Demander un devis','monthly_t'=>'Services mensuels','monthly_s'=>'Complétez votre pack avec des services récurrents','month_sfx'=>'/mois','min'=>'Minimum','months'=>'mois','iva'=>'Hors TVA','per_item'=>'Plus vous ajoutez, meilleur est le prix par service','guarantee'=>'45 jours garantis · Code sur mesure, jamais de templates · Réponse en 24h','custom_t'=>'Vous avez besoin de quelque chose de différent?','custom_s'=>'Projets spéciaux, applications web, intégrations ou devis sur mesure — parlons-en sans engagement.','custom_cta'=>'Parlez-nous'],
 'it'=>['tag'=>'Prezzi','title'=>'Scegli il tuo pack AKRA','subtitle'=>'Più includi, più risparmi. Senza sorprese, senza IVA nascosta.','save'=>'Risparmi','incl'=>'Include','not_incl'=>'Non incluso','cta'=>'Richiedi un preventivo','monthly_t'=>'Servizi mensili','monthly_s'=>'Complementa il tuo pack con servizi ricorrenti','month_sfx'=>'/mese','min'=>'Minimo','months'=>'mesi','iva'=>'IVA esclusa','per_item'=>'Più aggiungi, migliore è il prezzo per servizio','guarantee'=>'45 giorni garantiti · Codice su misura, mai template · Risposta in 24h','custom_t'=>'Hai bisogno di qualcosa di diverso?','custom_s'=>'Progetti speciali, app web, integrazioni o preventivi su misura — parliamone senza impegno.','custom_cta'=>'Parlaci'],
];
$cl=$T[$current_lang]??$T['es'];
$l=$current_lang;

$page_seo=['title'=>$cl['title'].' | AKRA Tech Studio Alacant','description'=>$cl['subtitle'],'keywords'=>'pack web Alicante, precio diseño web, presupuesto web Alicante','canonical'=>SITE_URL.'/pages/pressupost.php'];
$pq_testimonials = array_slice(getTestimonials(), 0, 3);
$pq_slots = getSlots();
include '../includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="section-header__tag"><?=htmlspecialchars($cl['tag'])?></div>
    <h1><?=htmlspecialchars($cl['title'])?></h1>
    <p><?=htmlspecialchars($cl['subtitle'])?></p>

    <?php if ($pq_slots['show']): ?>
    <div class="pq-urgency<?= $pq_slots['full'] ? ' pq-urgency--full' : '' ?>">
      <span class="pq-urgency__dot"></span>
      <?php if ($pq_slots['full']): ?>
        <?= $l === 'es' ? 'Agenda completa este mes — apúntate a la lista de espera' : ($l === 'en' ? 'Fully booked this month — join the waitlist' : 'Agenda completa este mes — apunta\'t a la llista d\'espera') ?>
      <?php else: ?>
        <?= $l === 'es' ? 'Solo quedan <strong>' . $pq_slots['free'] . '</strong> proyectos nuevos disponibles este mes' : ($l === 'en' ? 'Only <strong>' . $pq_slots['free'] . '</strong> new project slots left this month' : 'Només queden <strong>' . $pq_slots['free'] . '</strong> projectes nous disponibles este mes') ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pq_testimonials)): ?>
    <div class="pq-trust">
      <?php foreach ($pq_testimonials as $ts): ?>
      <div class="pq-trust__item">
        <div class="pq-trust__stars"><?= str_repeat('★', (int)($ts['rating'] ?? 5)) ?></div>
        <p>&ldquo;<?= htmlspecialchars(mb_strimwidth(gtxt($ts['text'], $l), 0, 110, '…')) ?>&rdquo;</p>
        <span><?= htmlspecialchars(gtxt($ts['name'], $l)) ?> · <?= htmlspecialchars(gtxt($ts['company'], $l)) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Banner: com més afegeixes, més econòmic -->
<section class="section section--white" style="padding-top:32px;padding-bottom:0">
<div class="container">
  <div class="pq-banner">
    <div class="pq-banner__msg">
      <span>📈</span>
      <span><?=htmlspecialchars($cl['per_item'])?></span>
    </div>
    <div class="pq-steps">
      <?php
      $steps=[['Starter',599,6],['Web',1199,7],['Pro',1899,8],['Total',2499,10]];
      foreach($steps as $i=>[$sn,$sp,$si]):
        $ppi=round($sp/$si);
        $best=$i===3;
      ?>
      <div class="pq-step<?=$best?' pq-step--best':''?>">
        <span class="pq-step__name"><?=$sn?></span>
        <span class="pq-step__ppi">~<?=$ppi?>€<small>/serv</small></span>
      </div>
      <?php if($i<3): ?>
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</section>

<!-- GRID DE PACKS -->
<section class="section section--white">
<div class="container">
  <div class="pq-grid">
  <?php foreach($packs as $id=>$pk):
    $saving=$pk['orig']-$pk['price'];
    $pct=round($saving/$pk['orig']*100);
  ?>
    <div class="pq-card<?=$pk['featured']?' pq-card--featured':''?><?=$pk['dark']?' pq-card--dark':''?>">

      <?php if($pk['badge']): ?>
      <div class="pq-badge" style="background:<?=($pk['dark']?'#fff':$pk['color'])?>; color:<?=($pk['dark']?$pk['color']:'#fff')?>"><?=htmlspecialchars(gtxt($pk['badge'],$l))?></div>
      <?php endif; ?>

      <div class="pq-head">
        <div class="pq-icon"><?=$pk['icon']?></div>
        <div class="pq-name" style="color:<?=htmlspecialchars($pk['color'])?>"><?=htmlspecialchars(gtxt($pk['name'],$l))?></div>
        <p class="pq-tagline"><?=htmlspecialchars(gtxt($pk['tagline'],$l))?></p>
      </div>

      <div class="pq-price-box">
        <div class="pq-orig"><?=number_format($pk['orig'],0,',','.')?>€</div>
        <div class="pq-price"><?=number_format($pk['price'],0,',','.')?>€</div>
        <div class="pq-saving"><?=htmlspecialchars($cl['save'])?> <strong><?=number_format($saving,0,',','.')?>€</strong> (<?=$pct?>%)</div>
        <div class="pq-iva"><?=htmlspecialchars($cl['iva'])?></div>
      </div>

      <p class="pq-ideal"><?=htmlspecialchars(gtxt($pk['ideal'],$l))?></p>

      <div class="pq-features">
        <div class="pq-label"><?=htmlspecialchars($cl['incl'])?></div>
        <ul>
          <?php foreach($pk['inc'] as $f): ?>
          <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?=htmlspecialchars(gtxt($f,$l))?></li>
          <?php endforeach; ?>
        </ul>
        <?php if(!empty($pk['exc'])): ?>
        <div class="pq-label pq-label--no" style="margin-top:12px"><?=htmlspecialchars($cl['not_incl'])?></div>
        <ul class="pq-exc">
          <?php foreach($pk['exc'] as $f): ?>
          <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><?=htmlspecialchars(gtxt($f,$l))?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>

      <a href="<?=pageUrl('contacte')?>?pack=<?=$id?>&budget=<?=urlencode($pk['budget_bucket'])?>" class="btn pq-cta<?=($pk['featured']||$pk['dark'])?' btn--primary':' btn--ghost'?>">
        <?=htmlspecialchars($cl['cta'])?>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>
  <?php endforeach; ?>
  </div>
</div>
</section>

<!-- SERVEIS MENSUALS -->
<section class="section section--grey">
<div class="container">
  <div class="section-header">
    <h2><?=htmlspecialchars($cl['monthly_t'])?></h2>
    <p><?=htmlspecialchars($cl['monthly_s'])?></p>
  </div>
  <div class="pq-monthly">
  <?php foreach($monthly as $ms): ?>
    <div class="pq-month-card">
      <div class="pq-month-icon"><?=$ms['icon']?></div>
      <div class="pq-month-info">
        <strong><?=htmlspecialchars(gtxt($ms['name'],$l))?></strong>
        <p><?=htmlspecialchars(gtxt($ms['desc'],$l))?></p>
      </div>
      <div class="pq-month-price"><?=$ms['price']?>€<span><?=htmlspecialchars($cl['month_sfx'])?></span></div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
</section>

<!-- GARANTIA + BLOC CUSTOM -->
<section class="section section--white">
<div class="container">
  <div class="pq-guarantee">
    <?php foreach(explode('·',$cl['guarantee']) as $g): ?>
    <div class="pq-guarantee__item">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      <?=htmlspecialchars(trim($g))?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="pq-custom">
    <div>
      <h3><?=htmlspecialchars($cl['custom_t'])?></h3>
      <p><?=htmlspecialchars($cl['custom_s'])?></p>
    </div>
    <a href="<?=pageUrl('contacte')?>" class="btn btn--primary btn--lg">
      <?=htmlspecialchars($cl['custom_cta'])?>
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
  </div>
</div>
</section>

<!-- CTA FIXA MÒBIL -->
<div class="pq-sticky-cta">
  <a href="<?=pageUrl('contacte')?>" class="btn btn--primary">
    <?=htmlspecialchars($cl['cta'])?>
  </a>
</div>

<?php include '../includes/footer.php'; ?>

<style>
/* PAGE HERO */
.page-hero{padding:100px 0 60px;background:var(--c-bg);border-bottom:1px solid var(--c-border)}
.page-hero h1{font-family:var(--f-display);font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;margin:var(--s-2) 0 var(--s-3)}
.page-hero p{color:var(--c-text-muted);font-size:1.05rem;max-width:560px;line-height:1.7}

/* URGENCY BAR */
.pq-urgency{display:inline-flex;align-items:center;gap:9px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:.82rem;font-weight:600;padding:8px 16px;border-radius:100px;margin-top:20px}
.pq-urgency strong{font-weight:800}
.pq-urgency__dot{width:7px;height:7px;border-radius:50%;background:#f97316;animation:pq-pulse 1.6s ease-in-out infinite}
.pq-urgency--full{background:#f4f4f5;border-color:var(--c-border);color:var(--c-text-muted)}
.pq-urgency--full .pq-urgency__dot{background:#a1a1aa;animation:none}
@keyframes pq-pulse{0%,100%{opacity:1}50%{opacity:.35}}

/* SOCIAL PROOF STRIP (page-hero) */
.pq-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:32px;max-width:900px}
.pq-trust__item{background:#fff;border:1px solid var(--c-border);border-radius:14px;padding:16px 18px}
.pq-trust__stars{color:#f59e0b;font-size:.8rem;letter-spacing:1px;margin-bottom:6px}
.pq-trust__item p{font-size:.82rem;color:var(--c-text-sec);line-height:1.5;margin:0 0 8px}
.pq-trust__item span{font-size:.72rem;font-weight:700;color:var(--c-primary)}

/* STICKY MOBILE CTA */
.pq-sticky-cta{display:none}
@media(max-width:720px){
  .pq-sticky-cta{display:block;position:fixed;left:0;right:0;bottom:0;z-index:80;padding:12px 16px;background:#fff;border-top:1px solid var(--c-border);box-shadow:0 -6px 20px rgba(0,0,0,.08)}
  .pq-sticky-cta .btn{width:100%;justify-content:center}
  .pq-trust{grid-template-columns:1fr}
}

/* SAVINGS BANNER */
.pq-banner{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:14px 22px;margin-bottom:0}
.pq-banner__msg{display:flex;align-items:center;gap:10px;font-size:.88rem;font-weight:600;color:#166534}
.pq-steps{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pq-step{display:flex;flex-direction:column;align-items:center;background:#fff;border:1px solid #d1fae5;border-radius:10px;padding:5px 14px;gap:1px;min-width:72px}
.pq-step--best{background:#dcfce7;border-color:#86efac}
.pq-step__name{font-size:.65rem;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:.06em}
.pq-step__ppi{font-size:.88rem;font-weight:800;color:#14532d}
.pq-step__ppi small{font-size:.62rem;font-weight:500;color:#22c55e}

/* GRID PACKS */
.pq-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;align-items:start}

/* PACK CARD */
.pq-card{position:relative;background:#fff;border:1.5px solid var(--c-border);border-radius:20px;padding:30px 20px 22px;display:flex;flex-direction:column;gap:14px;transition:box-shadow .2s,transform .2s}
.pq-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-3px)}
.pq-card--featured{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12),var(--shadow-lg);transform:translateY(-6px)}
.pq-card--dark{background:#0a0a0a;border-color:#0a0a0a;color:#fff}
.pq-card--dark .pq-tagline,.pq-card--dark .pq-ideal,.pq-card--dark .pq-iva,.pq-card--dark .pq-orig,.pq-card--dark .pq-saving,.pq-card--dark .pq-label--no,.pq-card--dark .pq-exc li{color:#a1a1aa}
.pq-card--dark .pq-price{color:#fff}
.pq-card--dark .pq-price-box{background:rgba(255,255,255,.08)}
.pq-card--dark .pq-ideal{background:rgba(255,255,255,.07)}
.pq-card--dark .pq-features li{color:#d4d4d8}
.pq-card--dark .pq-features li svg{color:#4ade80}
.pq-card--dark .pq-exc li svg{color:#f87171}
.pq-card--dark .pq-label{color:#a1a1aa}

/* Badge */
.pq-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:4px 14px;border-radius:20px;white-space:nowrap}

/* Head */
.pq-head{text-align:center}
.pq-icon{font-size:2rem;margin-bottom:4px}
.pq-name{font-family:var(--f-display);font-size:1.5rem;font-weight:800;letter-spacing:-.02em}
.pq-tagline{font-size:.8rem;color:var(--c-text-muted);line-height:1.5;margin:4px 0 0}

/* Price */
.pq-price-box{text-align:center;background:var(--c-surface);border-radius:14px;padding:16px 10px}
.pq-orig{font-size:.82rem;color:var(--c-text-muted);text-decoration:line-through;margin-bottom:2px}
.pq-price{font-family:var(--f-display);font-size:2.5rem;font-weight:800;color:var(--c-primary);letter-spacing:-.03em;line-height:1}
.pq-saving{font-size:.76rem;font-weight:600;color:#16a34a;margin-top:5px}
.pq-saving strong{font-size:.84rem}
.pq-iva{font-size:.68rem;color:var(--c-text-muted);margin-top:3px}

/* Ideal */
.pq-ideal{font-size:.77rem;color:var(--c-text-muted);background:var(--c-surface);border-radius:10px;padding:8px 12px;text-align:center;line-height:1.5;margin:0}

/* Features */
.pq-features{flex:1}
.pq-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--c-text-muted);margin-bottom:8px}
.pq-features ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:7px}
.pq-features li{display:flex;align-items:flex-start;gap:8px;font-size:.8rem;color:var(--c-text-sec);line-height:1.4}
.pq-features li svg{flex-shrink:0;color:#16a34a;margin-top:2px}
.pq-exc li{color:var(--c-text-muted)!important}
.pq-exc li svg{color:#dc2626!important}

/* CTA */
.pq-cta{width:100%;justify-content:center;margin-top:auto}
.pq-card--featured .pq-cta{background:#2563eb!important;border-color:#2563eb!important}
.pq-card--featured .pq-cta:hover{background:#1d4ed8!important}
.pq-card--dark .pq-cta.btn--primary{background:#fff!important;color:#0a0a0a!important;border-color:#fff!important}
.pq-card--dark .pq-cta.btn--primary:hover{background:#f4f4f5!important}

/* MONTHLY */
.section--grey{background:var(--c-bg)}
.pq-monthly{display:flex;flex-direction:column;gap:12px;max-width:780px;margin:0 auto}
.pq-month-card{display:flex;align-items:center;gap:16px;background:#fff;border:1px solid var(--c-border);border-radius:14px;padding:18px 22px;transition:box-shadow .2s}
.pq-month-card:hover{box-shadow:var(--shadow-md)}
.pq-month-icon{font-size:1.8rem;flex-shrink:0}
.pq-month-info{flex:1}
.pq-month-info strong{display:block;font-size:.92rem;font-weight:700;color:var(--c-primary);margin-bottom:2px}
.pq-month-info p{font-size:.78rem;color:var(--c-text-muted);margin:0;line-height:1.5}
.pq-month-price{font-family:var(--f-display);font-size:1.55rem;font-weight:800;color:var(--c-primary);text-align:right;flex-shrink:0;white-space:nowrap}
.pq-month-price span{font-size:.72rem;font-weight:500;color:var(--c-text-muted)}

/* GUARANTEE */
.pq-guarantee{display:flex;justify-content:center;flex-wrap:wrap;gap:20px;padding:18px 24px;background:var(--c-surface);border-radius:14px;margin-bottom:36px}
.pq-guarantee__item{display:flex;align-items:center;gap:8px;font-size:.82rem;font-weight:600;color:var(--c-text-sec)}

/* CUSTOM CTA */
.pq-custom{display:flex;align-items:center;justify-content:space-between;gap:24px;background:var(--c-primary);color:#fff;border-radius:20px;padding:36px 40px;flex-wrap:wrap}
.pq-custom h3{font-family:var(--f-display);font-size:1.4rem;font-weight:700;margin-bottom:8px;margin-top:0}
.pq-custom p{font-size:.88rem;opacity:.7;line-height:1.6;max-width:500px;margin:0}
.pq-custom .btn--primary{background:#fff!important;color:var(--c-primary)!important;border-color:#fff!important;flex-shrink:0}
.pq-custom .btn--primary:hover{background:#f4f4f5!important}

/* SECTION HEADER */
.section-header{text-align:center;margin-bottom:var(--s-8)}
.section-header h2{font-family:var(--f-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;margin-bottom:var(--s-2)}
.section-header p{font-size:.92rem;color:var(--c-text-muted);max-width:480px;margin:0 auto}

/* RESPONSIVE */
@media(max-width:1100px){.pq-grid{grid-template-columns:repeat(2,1fr)}.pq-card--featured{transform:none}}
@media(max-width:640px){
  .pq-grid{grid-template-columns:1fr}
  .pq-steps{display:none}
  .pq-custom{flex-direction:column;text-align:center;padding:28px 20px}
  .pq-month-card{flex-wrap:wrap}
  .pq-month-price{width:100%;text-align:left}
}
</style>
