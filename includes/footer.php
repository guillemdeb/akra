</main><!-- /main -->

<?php
// Traduccions del footer
$ft = [
    'ca' => [
        'desc'    => "Agència de disseny web, SEO local i màrqueting digital a <strong>Alacant</strong>. Treballem per a empreses de la Costa Blanca, Comunitat Valenciana i tota Espanya.",
        'services'=> 'Serveis',
        'company' => 'Empresa',
        'contact' => 'Contacte',
        'about'   => 'Qui som',
        'projects'=> 'Projectes',
        'process' => 'Com treballem',
        'blog'    => 'Blog SEO',
        'budget'  => 'Pressupost',
        'zones'   => "Zones d'actuació",
        'rights'  => 'Tots els drets reservats',
        'tagline' => 'Agència digital premium a Alacant',
        'privacy' => 'Privacitat',
        'cookies' => 'Cookies',
        'legal'   => 'Avís Legal',
        'cookie_msg' => "Usem cookies per millorar la teua experiència.",
        'cookie_policy' => 'Política de cookies',
        'reject'  => 'Rebutjar',
        'accept'  => 'Acceptar',
        'location'=> "Anem on estàs tu",
    ],
    'es' => [
        'desc'    => "Agencia de diseño web, SEO local y marketing digital en <strong>Alicante</strong>. Trabajamos para empresas de la Costa Blanca, Comunitat Valenciana y toda España.",
        'services'=> 'Servicios',
        'company' => 'Empresa',
        'contact' => 'Contacto',
        'about'   => 'Quiénes somos',
        'projects'=> 'Proyectos',
        'process' => 'Cómo trabajamos',
        'blog'    => 'Blog SEO',
        'budget'  => 'Presupuesto',
        'zones'   => 'Zonas de actuación',
        'rights'  => 'Todos los derechos reservados',
        'tagline' => 'Agencia digital premium en Alicante',
        'privacy' => 'Privacidad',
        'cookies' => 'Cookies',
        'legal'   => 'Aviso Legal',
        'cookie_msg' => 'Usamos cookies para mejorar tu experiencia.',
        'cookie_policy' => 'Política de cookies',
        'reject'  => 'Rechazar',
        'accept'  => 'Aceptar',
        'location'=> 'Vamos donde estás tú',
    ],
    'en' => [
        'desc'    => "Web design, local SEO and digital marketing agency in <strong>Alicante</strong>. We work for businesses across Costa Blanca, Valencia and all Spain.",
        'services'=> 'Services',
        'company' => 'Company',
        'contact' => 'Contact',
        'about'   => 'About us',
        'projects'=> 'Projects',
        'process' => 'How we work',
        'blog'    => 'SEO Blog',
        'budget'  => 'Quote',
        'zones'   => 'Areas we cover',
        'rights'  => 'All rights reserved',
        'tagline' => 'Premium digital agency in Alicante',
        'privacy' => 'Privacy',
        'cookies' => 'Cookies',
        'legal'   => 'Legal Notice',
        'cookie_msg' => 'We use cookies to improve your experience.',
        'cookie_policy' => 'Cookie policy',
        'reject'  => 'Reject',
        'accept'  => 'Accept',
        'location'=> 'We go where you are',
    ],
    'fr' => [
        'desc'    => "Agence de conception web, SEO local et marketing digital à <strong>Alicante</strong>. Nous travaillons pour les entreprises de la Costa Blanca, Valence et toute l'Espagne.",
        'services'=> 'Services',
        'company' => 'Entreprise',
        'contact' => 'Contact',
        'about'   => 'Qui sommes-nous',
        'projects'=> 'Projets',
        'process' => 'Comment nous travaillons',
        'blog'    => 'Blog SEO',
        'budget'  => 'Devis',
        'zones'   => "Zones d'intervention",
        'rights'  => 'Tous droits réservés',
        'tagline' => 'Agence digitale premium à Alicante',
        'privacy' => 'Confidentialité',
        'cookies' => 'Cookies',
        'legal'   => 'Mentions légales',
        'cookie_msg' => 'Nous utilisons des cookies pour améliorer votre expérience.',
        'cookie_policy' => 'Politique de cookies',
        'reject'  => 'Refuser',
        'accept'  => 'Accepter',
        'location'=> 'Nous allons où vous êtes',
    ],
    'it' => [
        'desc'    => "Agenzia di web design, SEO locale e marketing digitale ad <strong>Alicante</strong>. Lavoriamo per aziende della Costa Blanca, Valenciana e tutta la Spagna.",
        'services'=> 'Servizi',
        'company' => 'Azienda',
        'contact' => 'Contatti',
        'about'   => 'Chi siamo',
        'projects'=> 'Progetti',
        'process' => 'Come lavoriamo',
        'blog'    => 'Blog SEO',
        'budget'  => 'Preventivo',
        'zones'   => 'Zone di intervento',
        'rights'  => 'Tutti i diritti riservati',
        'tagline' => 'Agenzia digitale premium ad Alicante',
        'privacy' => 'Privacy',
        'cookies' => 'Cookie',
        'legal'   => 'Note legali',
        'cookie_msg' => 'Utilizziamo cookie per migliorare la tua esperienza.',
        'cookie_policy' => 'Politica sui cookie',
        'reject'  => 'Rifiuta',
        'accept'  => 'Accetta',
        'location'=> 'Andiamo dove sei tu',
    ],
];
$f = $ft[$current_lang] ?? $ft['es'];
?>

<!-- ─── FOOTER ──────────────────────────────────────────────────────────── -->
<footer class="footer" role="contentinfo" itemscope itemtype="https://schema.org/LocalBusiness">
    <div class="container">
        <div class="footer-grid">

            <!-- Columna 1: Brand -->
            <div class="footer-brand">
                <a href="<?= asset('index.php') ?>" class="nav-logo nav-logo--footer" aria-label="AKRA Tech Studio">
                    <img src="<?= asset('assets/img/logob.png') ?>" alt="AKRA Tech Studio" class="nav-logo__img nav-logo__img--footer" width="140" height="40">
                </a>
                <p class="footer-brand__desc" itemprop="description"><?= $f['desc'] ?></p>
                <div class="footer-social">
                    <a href="<?= SOCIAL_INSTAGRAM ?>" aria-label="Instagram" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                    </a>
                    <a href="<?= SOCIAL_LINKEDIN ?>" aria-label="LinkedIn" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <a href="<?= SOCIAL_FACEBOOK ?>" aria-label="Facebook" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Columna 2: Serveis -->
            <div class="footer-col">
                <h4><?= $f['services'] ?></h4>
                <ul>
                    <?php foreach(getServices() as $s): ?>
                    <li><a href="<?= asset('pages/serveis.php') ?>#<?= $s['slug'] ?>"><?= getTrans($s['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Columna 3: Empresa -->
            <div class="footer-col">
                <h4><?= $f['company'] ?></h4>
                <ul>
                    <li><a href="<?= asset('pages/nosaltres.php') ?>"><?= $f['about'] ?></a></li>
                    <li><a href="<?= asset('pages/projectes.php') ?>"><?= $f['projects'] ?></a></li>
                    <li><a href="<?= asset('pages/proces.php') ?>"><?= $f['process'] ?></a></li>
                    <li><a href="<?= asset('pages/bloc.php') ?>"><?= $f['blog'] ?></a></li>
                    <li><a href="<?= asset('pages/pressupost.php') ?>"><?= $f['budget'] ?></a></li>
                    <li><a href="<?= asset('pages/contacte.php') ?>"><?= $f['contact'] ?></a></li>
                </ul>
            </div>

            <!-- Columna 4: Contacte -->
            <div class="footer-col footer-col--contact">
                <h4><?= $f['contact'] ?></h4>
                <div class="footer-contact-list">
                    <a href="tel:<?= str_replace(' ', '', CONTACT_PHONE) ?>" class="footer-contact-item" itemprop="telephone">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.39 1.2 2 2 0 012.36 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.68-1.68a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg>
                        <?= CONTACT_PHONE ?>
                    </a>
                    <a href="mailto:<?= CONTACT_EMAIL ?>" class="footer-contact-item" itemprop="email">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <?= CONTACT_EMAIL ?>
                    </a>
                </div>
                <div class="footer-zones">
                    <strong><?= $f['zones'] ?>:</strong>
                    Alacant · Benidorm · Elx · Torrevella · Dénia · Altea · Costa Blanca
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <span itemprop="name">AKRA Tech Studio</span>. <?= $f['rights'] ?>. · <em><?= $f['tagline'] ?></em></p>
            <div class="footer-legal">
                <a href="<?= asset('pages/privacitat.php') ?>"><?= $f['privacy'] ?></a>
                <a href="<?= asset('pages/cookies.php') ?>"><?= $f['cookies'] ?></a>
                <a href="<?= asset('pages/avis-legal.php') ?>"><?= $f['legal'] ?></a>
            </div>
        </div>
    </div>
</footer>

<!-- ─── COOKIE BANNER ───────────────────────────────────────────────────── -->
<?php if (COOKIE_BANNER_ENABLED): ?>
<div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="<?= htmlspecialchars($f['cookie_policy']) ?>" aria-hidden="true" data-consent-days="<?= COOKIE_CONSENT_DAYS ?>">
    <div class="cookie-banner__inner">
        <div class="cookie-banner__text">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <p><?= $f['cookie_msg'] ?> <a href="<?= asset('pages/cookies.php') ?>"><?= $f['cookie_policy'] ?></a></p>
        </div>
        <div class="cookie-banner__actions">
            <button class="btn btn--sm btn--ghost" id="cookie-reject"><?= $f['reject'] ?></button>
            <button class="btn btn--sm btn--primary" id="cookie-accept"><?= $f['accept'] ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─── JS ─────────────────────────────────────────────────────────────── -->
<script src="<?= asset('assets/js/main.js') ?>" defer></script>

<?php if (!empty(WHATSAPP_NUMBER) && WHATSAPP_FLOAT_PUBLIC): ?>
<a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=<?= rawurlencode(WHATSAPP_FLOAT_MESSAGE) ?>" target="_blank" rel="noopener" class="wa-float-btn" aria-label="WhatsApp">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.01 0C5.377 0 0 5.373 0 12c0 2.121.553 4.113 1.523 5.845L0 24l6.328-1.492A11.943 11.943 0 0012.01 24C18.643 24 24 18.627 24 12S18.643 0 12.01 0zm0 21.783a9.72 9.72 0 01-4.955-1.354l-.356-.211-3.68.867.9-3.638-.232-.373A9.706 9.706 0 012.24 12c0-5.385 4.383-9.783 9.77-9.783 5.385 0 9.769 4.398 9.769 9.783 0 5.386-4.384 9.783-9.769 9.783z"/></svg>
</a>
<style>
.wa-float-btn { position: fixed; right: 22px; bottom: 22px; width: 58px; height: 58px; border-radius: 50%; background: #25d366; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(0,0,0,.25); z-index: 999; transition: transform .15s; }
.wa-float-btn:hover { transform: scale(1.08); }
@media (max-width: 600px) { .wa-float-btn { right: 16px; bottom: 16px; width: 52px; height: 52px; } }
</style>
<?php endif; ?>
</body>
</html>
