<?php
require_once '../includes/config.php';
require_once '../admin/includes/core.php';

$T = [
    'es' => [
        'seo_title' => 'Contacto · Presupuesto Gratuito Diseño Web y SEO Alicante | AKRA Tech Studio',
        'seo_desc'  => 'Contacta con AKRA Tech Studio en Alicante. Presupuesto gratuito para diseño web, SEO local o marketing digital. Respuesta garantizada en menos de 24 horas.',
        'tag'       => 'Contacto',
        'h1'        => 'Hablemos de tu proyecto',
        'sub'       => 'Primera consulta gratuita y sin compromiso. Te atendemos en menos de 24 horas.',
        'sent_h'    => '¡Mensaje enviado!',
        'sent_p'    => 'Nos pondremos en contacto en menos de 24 horas. Gracias por confiar en AKRA.',
        'error'     => 'Revisa los campos obligatorios (*) e inténtalo de nuevo.',
        'label_name'=> 'Nombre y apellidos *',
        'label_email'=> 'Email *',
        'label_phone'=> 'Teléfono',
        'label_co'  => 'Empresa',
        'label_svc' => 'Servicio que te interesa',
        'sel_svc'   => 'Selecciona un servicio',
        'other_svc' => 'Otros / Consulta general',
        'label_bud' => 'Presupuesto estimado',
        'bud_undef' => 'Sin definir',
        'bud_5k'    => 'Más de 5.000€',
        'label_msg' => 'Cuéntanos tu proyecto *',
        'ph_msg'    => 'Describe brevemente lo que necesitas, tu sector, objetivos...',
        'ph_co'     => 'Tu empresa',
        'privacy'   => 'He leído y acepto la',
        'privacy_lnk'=> 'Política de Privacidad',
        'submit'    => 'Enviar solicitud',
        'note'      => '* Campos obligatorios. Respuesta garantizada en menos de 24h laborables.',
        'info_h'    => 'Información de contacto',
        'meet_h'    => 'Dónde nos reunimos',
        'meet_p'    => 'En tu oficina, tu local o donde prefieras',
        'meet_sub'  => 'Tú no te muevas — vamos nosotros',
        'phone_h'   => 'Teléfono',
        'email_h'   => 'Email',
        'hours_h'   => 'Horario',
        'hours_v'   => 'Lu – Vi: 9:00 – 18:00',
        'promise_h' => 'Nuestra promesa',
        'promise'   => ['Respuesta en menos de 24h','Primera consulta gratuita','Propuesta personalizada sin compromiso','Hablas directamente con quien hará tu proyecto'],
        'slots_free'=> 'plazas disponibles',
        'slots_max' => 'Aceptamos un máximo de',
        'slots_qual'=> 'proyectos simultáneos para garantizar la calidad.',
        'slots_full'=> '🔴 Completos este mes',
        'slots_wait'=> 'Puedes contactarnos para la lista de espera del mes siguiente.',
    ],
    'ca' => [
        'seo_title' => 'Contacte · Pressupost Gratuït Disseny Web i SEO Alacant | AKRA Tech Studio',
        'seo_desc'  => 'Contacta amb AKRA Tech Studio a Alacant. Pressupost gratuït per a disseny web, SEO local o màrqueting digital. Resposta garantida en menys de 24 hores.',
        'tag'       => 'Contacte',
        'h1'        => 'Parlem del teu projecte',
        'sub'       => 'Primera consulta gratuïta i sense compromís. T\'atenem en menys de 24 hores.',
        'sent_h'    => 'Missatge enviat!',
        'sent_p'    => 'Et contactarem en menys de 24 hores. Gràcies per confiar en AKRA.',
        'error'     => 'Revisa els camps obligatoris (*) i torna-ho a intentar.',
        'label_name'=> 'Nom i cognoms *',
        'label_email'=> 'Email *',
        'label_phone'=> 'Telèfon',
        'label_co'  => 'Empresa',
        'label_svc' => 'Servei que t\'interessa',
        'sel_svc'   => 'Selecciona un servei',
        'other_svc' => 'Altres / Consulta general',
        'label_bud' => 'Pressupost estimat',
        'bud_undef' => 'Sense definir',
        'bud_5k'    => 'Més de 5.000€',
        'label_msg' => 'Explica\'ns el teu projecte *',
        'ph_msg'    => 'Descriu breument el que necessites, el teu sector, objectius...',
        'ph_co'     => 'La teua empresa',
        'privacy'   => 'He llegit i accepto la',
        'privacy_lnk'=> 'Política de Privacitat',
        'submit'    => 'Enviar sol·licitud',
        'note'      => '* Camps obligatoris. Resposta garantida en menys de 24h laborables.',
        'info_h'    => 'Informació de contacte',
        'meet_h'    => 'On ens reunim',
        'meet_p'    => 'A la teua oficina, el teu local o on preferisques',
        'meet_sub'  => 'Tu no te mogues — anem nosaltres',
        'phone_h'   => 'Telèfon',
        'email_h'   => 'Email',
        'hours_h'   => 'Horari',
        'hours_v'   => 'Dl – Dv: 9:00 – 18:00',
        'promise_h' => 'La nostra promesa',
        'promise'   => ['Resposta en menys de 24h','Primera consulta gratuïta','Proposta personalitzada sense compromís','Parles directament amb qui farà el teu projecte'],
        'slots_free'=> 'places disponibles',
        'slots_max' => 'Acceptem un màxim de',
        'slots_qual'=> 'projectes simultanis per garantir la qualitat.',
        'slots_full'=> '🔴 Complets aquest mes',
        'slots_wait'=> 'Pots contactar-nos per a la llista d\'espera del mes que ve.',
    ],
    'en' => [
        'seo_title' => 'Contact · Free Quote Web Design & SEO Alicante | AKRA Tech Studio',
        'seo_desc'  => 'Contact AKRA Tech Studio in Alicante. Free quote for web design, local SEO or digital marketing. Response guaranteed within 24 hours.',
        'tag'       => 'Contact',
        'h1'        => 'Let\'s talk about your project',
        'sub'       => 'First consultation free and no commitment. We\'ll get back to you within 24 hours.',
        'sent_h'    => 'Message sent!',
        'sent_p'    => 'We\'ll contact you within 24 hours. Thanks for trusting AKRA.',
        'error'     => 'Please check the required fields (*) and try again.',
        'label_name'=> 'Full name *',
        'label_email'=> 'Email *',
        'label_phone'=> 'Phone',
        'label_co'  => 'Company',
        'label_svc' => 'Service you\'re interested in',
        'sel_svc'   => 'Select a service',
        'other_svc' => 'Other / General enquiry',
        'label_bud' => 'Estimated budget',
        'bud_undef' => 'Not defined',
        'bud_5k'    => 'More than €5,000',
        'label_msg' => 'Tell us about your project *',
        'ph_msg'    => 'Briefly describe what you need, your sector, goals...',
        'ph_co'     => 'Your company',
        'privacy'   => 'I have read and accept the',
        'privacy_lnk'=> 'Privacy Policy',
        'submit'    => 'Send request',
        'note'      => '* Required fields. Response guaranteed within 24 business hours.',
        'info_h'    => 'Contact information',
        'meet_h'    => 'Where we meet',
        'meet_p'    => 'At your office, your premises or wherever you prefer',
        'meet_sub'  => 'You stay put — we come to you',
        'phone_h'   => 'Phone',
        'email_h'   => 'Email',
        'hours_h'   => 'Hours',
        'hours_v'   => 'Mon – Fri: 9:00 – 18:00',
        'promise_h' => 'Our promise',
        'promise'   => ['Response within 24h','First consultation free','Personalised proposal, no commitment','You speak directly with the person who will build your project'],
        'slots_free'=> 'spots available',
        'slots_max' => 'We take a maximum of',
        'slots_qual'=> 'simultaneous projects to guarantee quality.',
        'slots_full'=> '🔴 Fully booked this month',
        'slots_wait'=> 'Contact us to join the waiting list for next month.',
    ],
    'fr' => [
        'seo_title' => 'Contact · Devis Gratuit Création Web & SEO Alicante | AKRA Tech Studio',
        'seo_desc'  => 'Contactez AKRA Tech Studio à Alicante. Devis gratuit pour la création web, SEO local ou marketing digital. Réponse garantie en moins de 24 heures.',
        'tag'       => 'Contact',
        'h1'        => 'Parlons de votre projet',
        'sub'       => 'Première consultation gratuite et sans engagement. Nous vous répondons en moins de 24 heures.',
        'sent_h'    => 'Message envoyé !',
        'sent_p'    => 'Nous vous contacterons en moins de 24 heures. Merci de faire confiance à AKRA.',
        'error'     => 'Veuillez vérifier les champs obligatoires (*) et réessayer.',
        'label_name'=> 'Nom et prénom *',
        'label_email'=> 'Email *',
        'label_phone'=> 'Téléphone',
        'label_co'  => 'Entreprise',
        'label_svc' => 'Service qui vous intéresse',
        'sel_svc'   => 'Sélectionnez un service',
        'other_svc' => 'Autre / Demande générale',
        'label_bud' => 'Budget estimé',
        'bud_undef' => 'Non défini',
        'bud_5k'    => 'Plus de 5 000€',
        'label_msg' => 'Décrivez-nous votre projet *',
        'ph_msg'    => 'Décrivez brièvement ce dont vous avez besoin, votre secteur, vos objectifs...',
        'ph_co'     => 'Votre entreprise',
        'privacy'   => 'J\'ai lu et accepte la',
        'privacy_lnk'=> 'Politique de confidentialité',
        'submit'    => 'Envoyer la demande',
        'note'      => '* Champs obligatoires. Réponse garantie en moins de 24h ouvrables.',
        'info_h'    => 'Informations de contact',
        'meet_h'    => 'Où nous nous retrouvons',
        'meet_p'    => 'Dans votre bureau, vos locaux ou où vous préférez',
        'meet_sub'  => 'Ne bougez pas — c\'est nous qui venons',
        'phone_h'   => 'Téléphone',
        'email_h'   => 'Email',
        'hours_h'   => 'Horaires',
        'hours_v'   => 'Lu – Ve : 9h00 – 18h00',
        'promise_h' => 'Notre promesse',
        'promise'   => ['Réponse en moins de 24h','Première consultation gratuite','Proposition personnalisée sans engagement','Vous parlez directement avec la personne qui réalisera votre projet'],
        'slots_free'=> 'places disponibles',
        'slots_max' => 'Nous acceptons un maximum de',
        'slots_qual'=> 'projets simultanés pour garantir la qualité.',
        'slots_full'=> '🔴 Complets ce mois-ci',
        'slots_wait'=> 'Contactez-nous pour la liste d\'attente du mois prochain.',
    ],
    'it' => [
        'seo_title' => 'Contatti · Preventivo Gratuito Web Design & SEO Alicante | AKRA Tech Studio',
        'seo_desc'  => 'Contatta AKRA Tech Studio ad Alicante. Preventivo gratuito per web design, SEO locale o marketing digitale. Risposta garantita entro 24 ore.',
        'tag'       => 'Contatti',
        'h1'        => 'Parliamo del tuo progetto',
        'sub'       => 'Prima consulenza gratuita e senza impegno. Ti risponderemo entro 24 ore.',
        'sent_h'    => 'Messaggio inviato!',
        'sent_p'    => 'Ti contatteremo entro 24 ore. Grazie per aver scelto AKRA.',
        'error'     => 'Controlla i campi obbligatori (*) e riprova.',
        'label_name'=> 'Nome e cognome *',
        'label_email'=> 'Email *',
        'label_phone'=> 'Telefono',
        'label_co'  => 'Azienda',
        'label_svc' => 'Servizio che ti interessa',
        'sel_svc'   => 'Seleziona un servizio',
        'other_svc' => 'Altro / Richiesta generale',
        'label_bud' => 'Budget stimato',
        'bud_undef' => 'Non definito',
        'bud_5k'    => 'Più di 5.000€',
        'label_msg' => 'Raccontaci il tuo progetto *',
        'ph_msg'    => 'Descrivi brevemente cosa ti serve, il tuo settore, gli obiettivi...',
        'ph_co'     => 'La tua azienda',
        'privacy'   => 'Ho letto e accetto la',
        'privacy_lnk'=> 'Informativa sulla Privacy',
        'submit'    => 'Invia richiesta',
        'note'      => '* Campi obbligatori. Risposta garantita entro 24 ore lavorative.',
        'info_h'    => 'Informazioni di contatto',
        'meet_h'    => 'Dove ci incontriamo',
        'meet_p'    => 'Nel tuo ufficio, nella tua sede o dove preferisci',
        'meet_sub'  => 'Non ti muovere — veniamo noi',
        'phone_h'   => 'Telefono',
        'email_h'   => 'Email',
        'hours_h'   => 'Orario',
        'hours_v'   => 'Lu – Ve: 9:00 – 18:00',
        'promise_h' => 'La nostra promessa',
        'promise'   => ['Risposta entro 24h','Prima consulenza gratuita','Proposta personalizzata senza impegno','Parli direttamente con chi realizzerà il tuo progetto'],
        'slots_free'=> 'posti disponibili',
        'slots_max' => 'Accettiamo un massimo di',
        'slots_qual'=> 'progetti simultanei per garantire la qualità.',
        'slots_full'=> '🔴 Completi questo mese',
        'slots_wait'=> 'Contattaci per la lista d\'attesa del mese prossimo.',
    ],
];
$cl = $T[$current_lang] ?? $T['es'];

$page_seo = [
    'title'    => $cl['seo_title'],
    'description' => $cl['seo_desc'],
    'canonical'   => SITE_URL . '/pages/contacte.php',
];
$services = getServices();
$prefill_service = isset($_GET['servei']) ? sanitize($_GET['servei']) : '';
$prefill_budget  = isset($_GET['budget']) ? sanitize($_GET['budget']) : '';
$pack_names = ['starter' => 'Starter', 'web' => 'Web', 'pro' => 'Pro', 'total' => 'Total'];
$prefill_pack = isset($_GET['pack']) && isset($pack_names[$_GET['pack']]) ? $_GET['pack'] : '';
$prefill_message = $prefill_pack
    ? ($current_lang === 'es'
        ? "Estoy interesado/a en el pack {$pack_names[$prefill_pack]}. "
        : ($current_lang === 'en'
            ? "I'm interested in the {$pack_names[$prefill_pack]} pack. "
            : "Estic interessat/da en el pack {$pack_names[$prefill_pack]}. "))
    : '';
include '../includes/header.php';

$form_success = $form_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name']    ?? '');
    $email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $message = sanitize($_POST['message'] ?? '');
    $phone   = sanitize($_POST['phone']   ?? '');
    $company = sanitize($_POST['company'] ?? '');
    $service = sanitize($_POST['service'] ?? '');
    $budget  = sanitize($_POST['budget']  ?? '');

    if ($name && $email && $message) {
        // ── 1. Guarda al backend (admin/data/messages.json) ──────────────
        saveMessage([
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'company' => $company,
            'service' => $service,
            'budget'  => $budget,
            'message' => $message,
            'lang'    => $current_lang,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        // ── 2. Intent d'enviament per email (opcional, no bloqueja) ──────
        $to      = CONTACT_EMAIL;
        $subject = "[AKRA Web] Nou missatge de $name";
        $body    = "Nou missatge rebut des del formulari de contacte.\n\n"
                 . "Nom: $name\n"
                 . "Email: $email\n"
                 . ($phone   ? "Telèfon: $phone\n"   : '')
                 . ($company ? "Empresa: $company\n" : '')
                 . ($service ? "Servei: $service\n"  : '')
                 . ($budget  ? "Pressupost: $budget\n": '')
                 . "\nMissatge:\n$message\n";
        $headers = "From: noreply@akratechstudio.es\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
        @mail($to, $subject, $body, $headers); // @mail per no bloquejar si SMTP no configurat

        $form_success = true;
    } else {
        $form_error = true;
    }
}
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
        <div class="contact-grid">

            <div class="contact-form-wrap">
                <?php if ($form_success): ?>
                <div class="form-success">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                    <h3><?= htmlspecialchars($cl['sent_h']) ?></h3>
                    <p><?= htmlspecialchars($cl['sent_p']) ?></p>
                </div>
                <?php else: ?>
                <?php if ($form_error): ?>
                <div class="form-error"><?= htmlspecialchars($cl['error']) ?></div>
                <?php endif; ?>
                <form class="contact-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name"><?= htmlspecialchars($cl['label_name']) ?></label>
                            <input type="text" id="name" name="name" required placeholder="Joan García">
                        </div>
                        <div class="form-group">
                            <label for="email"><?= htmlspecialchars($cl['label_email']) ?></label>
                            <input type="email" id="email" name="email" required placeholder="joan@empresa.es">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone"><?= htmlspecialchars($cl['label_phone']) ?></label>
                            <input type="tel" id="phone" name="phone" placeholder="+34 600 000 000">
                        </div>
                        <div class="form-group">
                            <label for="company"><?= htmlspecialchars($cl['label_co']) ?></label>
                            <input type="text" id="company" name="company" placeholder="<?= htmlspecialchars($cl['ph_co']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service"><?= htmlspecialchars($cl['label_svc']) ?></label>
                            <select id="service" name="service">
                                <option value=""><?= htmlspecialchars($cl['sel_svc']) ?></option>
                                <?php foreach ($services as $s): ?>
                                <option value="<?= $s['slug'] ?>" <?= $prefill_service === $s['slug'] ? 'selected' : '' ?>><?= getTrans($s['title']) ?></option>
                                <?php endforeach; ?>
                                <option value="altres"><?= htmlspecialchars($cl['other_svc']) ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="budget"><?= htmlspecialchars($cl['label_bud']) ?></label>
                            <select id="budget" name="budget">
                                <option value=""><?= htmlspecialchars($cl['bud_undef']) ?></option>
                                <option value="500-1500" <?= $prefill_budget === '500-1500' ? 'selected' : '' ?>>500€ – 1.500€</option>
                                <option value="1500-3000" <?= $prefill_budget === '1500-3000' ? 'selected' : '' ?>>1.500€ – 3.000€</option>
                                <option value="3000-5000" <?= $prefill_budget === '3000-5000' ? 'selected' : '' ?>>3.000€ – 5.000€</option>
                                <option value="5000+" <?= $prefill_budget === '5000+' ? 'selected' : '' ?>><?= htmlspecialchars($cl['bud_5k']) ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message"><?= htmlspecialchars($cl['label_msg']) ?></label>
                        <textarea id="message" name="message" rows="5" required placeholder="<?= htmlspecialchars($cl['ph_msg']) ?>"><?= htmlspecialchars($prefill_message) ?></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="privacy" name="privacy" required>
                        <label for="privacy"><?= htmlspecialchars($cl['privacy']) ?> <a href="<?= pageUrl('privacitat') ?>"><?= htmlspecialchars($cl['privacy_lnk']) ?></a> *</label>
                    </div>
                    <button type="submit" class="btn btn--primary btn--lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <?= htmlspecialchars($cl['submit']) ?>
                    </button>
                    <p class="form-note"><?= htmlspecialchars($cl['note']) ?></p>
                </form>
                <?php endif; ?>
            </div>

            <div class="contact-info">
                <h3><?= htmlspecialchars($cl['info_h']) ?></h3>
                <div class="contact-info__items">
                    <div class="contact-info__item">
                        <div class="contact-info__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                        <div>
                            <strong><?= htmlspecialchars($cl['meet_h']) ?></strong>
                            <span><?= htmlspecialchars($cl['meet_p']) ?></span>
                            <span class="contact-info__sub"><?= htmlspecialchars($cl['meet_sub']) ?></span>
                        </div>
                    </div>
                    <div class="contact-info__item">
                        <div class="contact-info__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.39 1.2 2 2 0 012.36 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.18 6.18l1.68-1.68a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg></div>
                        <div>
                            <strong><?= htmlspecialchars($cl['phone_h']) ?></strong>
                            <a href="tel:<?= str_replace(' ', '', CONTACT_PHONE) ?>"><?= CONTACT_PHONE ?></a>
                        </div>
                    </div>
                    <div class="contact-info__item">
                        <div class="contact-info__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                        <div>
                            <strong><?= htmlspecialchars($cl['email_h']) ?></strong>
                            <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
                        </div>
                    </div>
                    <div class="contact-info__item">
                        <div class="contact-info__icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div>
                            <strong><?= htmlspecialchars($cl['hours_h']) ?></strong>
                            <span><?= htmlspecialchars($cl['hours_v']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="contact-promise">
                    <?php $slots = getSlots(); if ($slots['show']): ?>
                    <div class="slots-widget" style="margin-bottom: var(--s-5)">
                        <div class="slots-widget__pips">
                            <?php for ($i = 0; $i < $slots['total']; $i++): ?>
                            <span class="slots-widget__pip <?= $i < $slots['free'] ? 'free' : 'taken' ?>"></span>
                            <?php endfor; ?>
                        </div>
                        <div class="slots-widget__text">
                            <?php if (!$slots['full']): ?>
                            <strong><?= $slots['free'] ?> <?= htmlspecialchars($cl['slots_free']) ?></strong>
                            <span><?= htmlspecialchars($cl['slots_max']) ?> <?= $slots['total'] ?> <?= htmlspecialchars($cl['slots_qual']) ?></span>
                            <?php else: ?>
                            <strong><?= htmlspecialchars($cl['slots_full']) ?></strong>
                            <span><?= htmlspecialchars($cl['slots_wait']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <h4><?= htmlspecialchars($cl['promise_h']) ?></h4>
                    <ul>
                        <?php foreach ($cl['promise'] as $p): ?>
                        <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($p) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
<style>
.page-hero{padding:100px 0 60px;background:var(--c-bg);border-bottom:1px solid var(--c-border)}
.page-hero h1{font-family:var(--f-display);font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:var(--c-primary);letter-spacing:-.02em;margin:var(--s-2) 0 var(--s-3)}
.page-hero p{color:var(--c-text-muted);font-size:1.05rem;max-width:540px}
.contact-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:var(--s-12);align-items:start}
.contact-form-wrap{background:var(--c-gray);border-radius:var(--r-xl);padding:var(--s-8);border:1px solid var(--c-border)}
.contact-form{display:flex;flex-direction:column;gap:var(--s-4)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:var(--s-4)}
.form-group{display:flex;flex-direction:column;gap:var(--s-2)}
.form-group label{font-size:.88rem;font-weight:600;color:var(--c-navy)}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.7rem 1rem;border:1.5px solid var(--c-border);border-radius:var(--r-md);font-family:var(--f-body);font-size:.95rem;background:white;color:var(--c-text);transition:border-color var(--t-fast);outline:none}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--c-gold)}
.form-group textarea{resize:vertical;min-height:120px}
.form-check{display:flex;align-items:flex-start;gap:var(--s-2);font-size:.88rem;color:var(--c-muted)}
.form-check input{margin-top:2px;accent-color:var(--c-gold)}
.form-check a{color:var(--c-navy);text-decoration:underline}
.form-note{font-size:.78rem;color:var(--c-muted);margin-top:var(--s-1)}
.form-success{text-align:center;padding:var(--s-12);display:flex;flex-direction:column;align-items:center;gap:var(--s-4)}
.form-success svg{color:var(--c-gold)}
.form-success h3{font-family:var(--f-display);font-size:1.5rem;font-weight:700;color:var(--c-navy)}
.form-error{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:var(--s-3) var(--s-4);border-radius:var(--r-md);font-size:.88rem;margin-bottom:var(--s-4)}
.contact-info h3{font-family:var(--f-display);font-size:1.3rem;font-weight:700;color:var(--c-navy);margin-bottom:var(--s-6)}
.contact-info__items{display:flex;flex-direction:column;gap:var(--s-4);margin-bottom:var(--s-8)}
.contact-info__item{display:flex;gap:var(--s-4);align-items:flex-start}
.contact-info__icon{width:40px;height:40px;background:var(--c-navy);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;color:var(--c-gold);flex-shrink:0}
.contact-info__item div{display:flex;flex-direction:column;gap:4px}
.contact-info__item strong{font-size:.85rem;font-weight:700;color:var(--c-navy)}
.contact-info__item span,.contact-info__item a{font-size:.9rem;color:var(--c-muted)}
.contact-info__item a:hover{color:var(--c-gold)}
.contact-info__sub{font-size:.82rem;color:var(--c-text-muted);margin-top:2px}
.contact-promise{background:var(--c-navy);border-radius:var(--r-lg);padding:var(--s-6)}
.contact-promise h4{font-family:var(--f-display);font-size:.9rem;font-weight:700;color:var(--c-gold);text-transform:uppercase;letter-spacing:.06em;margin-bottom:var(--s-4)}
.contact-promise ul{display:flex;flex-direction:column;gap:var(--s-3)}
.contact-promise ul li{display:flex;align-items:center;gap:var(--s-2);font-size:.88rem;color:rgba(255,255,255,.75)}
.contact-promise ul li svg{color:var(--c-gold);flex-shrink:0}
@media(max-width:768px){.contact-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}}
</style>
