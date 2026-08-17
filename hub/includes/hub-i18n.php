<?php
// hub/includes/hub-i18n.php — Traduccions del portal del client (Hub).
// Els TEXTOS que introdueix l'agència (descripcions de treballs, missatges,
// notes de propostes...) NO es tradueixen — es mostren tal com es van escriure.
// Açò només tradueix la interfície (botons, títols, estats) i és independent
// de l'idioma de treball de l'admin, que continua sent en català.

function getHubStrings() {
    return [
    'ca' => [
        'nav_home' => '🏠 Resum', 'nav_comms' => '💬 Comunicacions', 'nav_jobs' => '🛠️ Estat dels treballs',
        'nav_invoices' => '🧾 Factures', 'nav_proposals' => '📄 Propostes', 'nav_logout' => 'Tancar sessió',
        'nav_calendar' => '📅 Calendari',

        'login_title' => 'Portal del client', 'login_email' => 'Email', 'login_password' => 'Contrasenya',
        'login_submit' => 'Entrar', 'login_lost' => 'Has perdut l\'accés? Escriu-nos a',
        'login_error' => 'Email o contrasenya incorrectes.',
        'login_disabled' => 'El teu accés al portal ha sigut desactivat. Contacta amb nosaltres si creus que és un error.',
        'login_lang_label' => 'Idioma',

        'dash_hello' => 'Hola', 'dash_sub' => 'Este és el resum del que tenim entre mans amb',
        'dash_proposals_pending' => 'Tens {n} proposta(es) pendent(s) de revisar.', 'dash_view_proposals' => 'Veure propostes',
        'dash_stat_active_jobs' => 'Treballs actius', 'dash_stat_due' => 'Pendent de pagar',
        'dash_stat_overdue' => 'Factures vençudes', 'dash_stat_total' => 'Total facturat',
        'dash_jobs_title' => '🛠️ Treballs en curs', 'dash_view_all' => 'Veure tots', 'dash_view_all_f' => 'Veure totes',
        'dash_no_active_jobs' => 'No tens cap treball actiu ara mateix.', 'dash_start' => 'Inici',
        'dash_invoices_title' => '🧾 Últimes factures', 'dash_no_invoices' => 'Encara no hi ha factures.',
        'dash_comms_title' => '💬 Última comunicació', 'dash_no_comms' => 'Encara no hi ha comunicacions registrades.',
        'dash_you' => 'Tu', 'dash_us' => 'Nosaltres',
        'dash_contact_title' => '📞 Contacte directe', 'dash_send_msg' => '✍️ Enviar-nos un missatge',

        'comms_title' => 'Comunicacions', 'comms_sub' => 'Historial de contacte amb AKRA Tech Studio.',
        'comms_sent_ok' => '✅ Missatge enviat. Et respondrem el més aviat possible.',
        'comms_write_title' => '✍️ Envia\'ns un missatge', 'comms_placeholder' => 'Escriu ací la teua consulta, petició o comentari...',
        'comms_send' => 'Enviar', 'comms_history' => '🗂️ Historial', 'comms_empty' => 'Encara no hi ha comunicacions registrades.',

        'jobs_title' => 'Estat dels treballs', 'jobs_sub' => 'treball(s) registrat(s) amb tu.',
        'jobs_empty' => 'Encara no tenim cap treball registrat.', 'jobs_rated_ok' => '✅ Gràcies per la teua valoració!',
        'jobs_start_date' => 'Data d\'inici', 'jobs_end_date' => 'Data de fi', 'jobs_hours' => 'Hores dedicades',
        'jobs_your_rating' => 'La teua valoració:', 'jobs_rate_prompt' => '✨ Este treball ja ha acabat — què et va semblar?',
        'jobs_rate_comment' => 'Comentari (opcional)', 'jobs_rate_submit' => 'Enviar valoració',

        'inv_title' => 'Factures', 'inv_due_suffix' => 'pendents de pagar', 'inv_empty' => 'Encara no tens cap factura.',
        'inv_issued' => 'Emesa el', 'inv_due_date' => 'Venciment', 'inv_partial' => 'Pagat parcialment:',
        'inv_pay' => '💳 Pagar', 'inv_pdf' => '⬇️ PDF',

        'prop_title' => 'Propostes', 'prop_sub' => 'Pressupostos i propostes de treball que t\'hem enviat.',
        'prop_done' => '✅ Gràcies per la teua resposta.', 'prop_empty' => 'Encara no tens cap proposta.',
        'prop_pdf' => '⬇️ Descarregar PDF', 'prop_accept' => '✅ Acceptar', 'prop_reject' => '✖️ Rebutjar',
        'prop_confirm_accept' => 'Confirmes que acceptes esta proposta?', 'prop_confirm_reject' => 'Confirmes que rebutges esta proposta?',

        'cal_title' => 'Calendari de xarxes', 'cal_sub' => 'El calendari de contingut planificat per a tu.',
        'cal_empty_month' => 'Cap publicació planificada este mes.', 'cal_today' => 'Avui',
        'cal_months' => ['Gener','Febrer','Març','Abril','Maig','Juny','Juliol','Agost','Setembre','Octubre','Novembre','Desembre'],
        'cal_weekdays' => ['Dl','Dt','Dc','Dj','Dv','Ds','Dg'],

        'nav_tickets' => '🎫 Tiquets',
        'tix_title' => 'Tiquets', 'tix_sub' => 'Incidències, dubtes i peticions que ens has fet arribar.',
        'tix_new' => '➕ Nou tiquet', 'tix_new_subject' => 'Assumpte', 'tix_new_category' => 'Categoria',
        'tix_new_priority' => 'Prioritat', 'tix_new_desc' => 'Descripció', 'tix_new_desc_ph' => 'Explica\'ns el que passa amb el màxim de detall possible...',
        'tix_new_submit' => 'Obrir tiquet', 'tix_empty' => 'Encara no has obert cap tiquet.', 'tix_no_messages' => 'Encara no hi ha cap missatge.',
        'tix_created_ok' => '✅ Tiquet obert. Et respondrem el més aviat possible.',
        'tix_replied_ok' => '✅ Resposta enviada.',
        'tix_you' => 'Tu', 'tix_us' => 'AKRA Tech Studio',
        'tix_reply_ph' => 'Escriu una resposta...', 'tix_reply_submit' => 'Enviar',
        'tix_opened_on' => 'Obert el', 'tix_back' => '← Tornar a Tiquets',
        'tix_cat_incidencia' => 'Incidència / error', 'tix_cat_dubte' => 'Dubte', 'tix_cat_peticio' => 'Petició de canvi',
        'tix_cat_facturacio' => 'Facturació', 'tix_cat_altres' => 'Altres',
        'tix_pri_baixa' => 'Baixa', 'tix_pri_mitjana' => 'Mitjana', 'tix_pri_alta' => 'Alta', 'tix_pri_urgent' => 'Urgent',

        'cal_approval_pending_title' => '📤 Este calendari espera la teua aprovació',
        'cal_approval_deadline'      => 'Si no respons abans del',
        'cal_approval_deadline_end'  => ', es donarà per acceptat automàticament.',
        'cal_approval_accept'        => '✅ Acceptar calendari',
        'cal_approval_request'       => '✏️ Sol·licitar canvis',
        'cal_approval_comment_ph'    => 'Explica quins canvis voldries (opcional)...',
        'cal_approval_send'          => 'Enviar',
        'cal_approval_cancel'        => 'Cancel·lar',
        'cal_approval_accepted'      => '✅ Calendari acceptat. Gràcies!',
        'cal_approval_accepted_auto' => '✅ Calendari acceptat automàticament en no rebre resposta dins del termini.',
        'cal_approval_changes'       => '✏️ Has sol·licitat canvis en este calendari. L\'agència ho revisarà.',
        'cal_approval_your_comment'  => 'El teu comentari:',
    ],
    'es' => [
        'nav_home' => '🏠 Resumen', 'nav_comms' => '💬 Comunicaciones', 'nav_jobs' => '🛠️ Estado de los trabajos',
        'nav_invoices' => '🧾 Facturas', 'nav_proposals' => '📄 Propuestas', 'nav_logout' => 'Cerrar sesión',
        'nav_calendar' => '📅 Calendario',

        'login_title' => 'Portal del cliente', 'login_email' => 'Email', 'login_password' => 'Contraseña',
        'login_submit' => 'Entrar', 'login_lost' => '¿Has perdido el acceso? Escríbenos a',
        'login_error' => 'Email o contraseña incorrectos.',
        'login_disabled' => 'Tu acceso al portal ha sido desactivado. Contacta con nosotros si crees que es un error.',
        'login_lang_label' => 'Idioma',

        'dash_hello' => 'Hola', 'dash_sub' => 'Este es el resumen de lo que tenemos entre manos con',
        'dash_proposals_pending' => 'Tienes {n} propuesta(s) pendiente(s) de revisar.', 'dash_view_proposals' => 'Ver propuestas',
        'dash_stat_active_jobs' => 'Trabajos activos', 'dash_stat_due' => 'Pendiente de pagar',
        'dash_stat_overdue' => 'Facturas vencidas', 'dash_stat_total' => 'Total facturado',
        'dash_jobs_title' => '🛠️ Trabajos en curso', 'dash_view_all' => 'Ver todos', 'dash_view_all_f' => 'Ver todas',
        'dash_no_active_jobs' => 'No tienes ningún trabajo activo ahora mismo.', 'dash_start' => 'Inicio',
        'dash_invoices_title' => '🧾 Últimas facturas', 'dash_no_invoices' => 'Todavía no hay facturas.',
        'dash_comms_title' => '💬 Última comunicación', 'dash_no_comms' => 'Todavía no hay comunicaciones registradas.',
        'dash_you' => 'Tú', 'dash_us' => 'Nosotros',
        'dash_contact_title' => '📞 Contacto directo', 'dash_send_msg' => '✍️ Enviarnos un mensaje',

        'comms_title' => 'Comunicaciones', 'comms_sub' => 'Historial de contacto con AKRA Tech Studio.',
        'comms_sent_ok' => '✅ Mensaje enviado. Te responderemos lo antes posible.',
        'comms_write_title' => '✍️ Envíanos un mensaje', 'comms_placeholder' => 'Escribe aquí tu consulta, petición o comentario...',
        'comms_send' => 'Enviar', 'comms_history' => '🗂️ Historial', 'comms_empty' => 'Todavía no hay comunicaciones registradas.',

        'jobs_title' => 'Estado de los trabajos', 'jobs_sub' => 'trabajo(s) registrado(s) contigo.',
        'jobs_empty' => 'Todavía no tenemos ningún trabajo registrado.', 'jobs_rated_ok' => '✅ ¡Gracias por tu valoración!',
        'jobs_start_date' => 'Fecha de inicio', 'jobs_end_date' => 'Fecha de fin', 'jobs_hours' => 'Horas dedicadas',
        'jobs_your_rating' => 'Tu valoración:', 'jobs_rate_prompt' => '✨ Este trabajo ya ha terminado — ¿qué te ha parecido?',
        'jobs_rate_comment' => 'Comentario (opcional)', 'jobs_rate_submit' => 'Enviar valoración',

        'inv_title' => 'Facturas', 'inv_due_suffix' => 'pendientes de pagar', 'inv_empty' => 'Todavía no tienes ninguna factura.',
        'inv_issued' => 'Emitida el', 'inv_due_date' => 'Vencimiento', 'inv_partial' => 'Pagado parcialmente:',
        'inv_pay' => '💳 Pagar', 'inv_pdf' => '⬇️ PDF',

        'prop_title' => 'Propuestas', 'prop_sub' => 'Presupuestos y propuestas de trabajo que te hemos enviado.',
        'prop_done' => '✅ Gracias por tu respuesta.', 'prop_empty' => 'Todavía no tienes ninguna propuesta.',
        'prop_pdf' => '⬇️ Descargar PDF', 'prop_accept' => '✅ Aceptar', 'prop_reject' => '✖️ Rechazar',
        'prop_confirm_accept' => '¿Confirmas que aceptas esta propuesta?', 'prop_confirm_reject' => '¿Confirmas que rechazas esta propuesta?',

        'cal_title' => 'Calendario de redes', 'cal_sub' => 'El calendario de contenido planificado para ti.',
        'cal_empty_month' => 'Ninguna publicación planificada este mes.', 'cal_today' => 'Hoy',
        'cal_months' => ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        'cal_weekdays' => ['Lu','Ma','Mi','Ju','Vi','Sa','Do'],

        'nav_tickets' => '🎫 Tickets',
        'tix_title' => 'Tickets', 'tix_sub' => 'Incidencias, dudas y peticiones que nos has hecho llegar.',
        'tix_new' => '➕ Nuevo ticket', 'tix_new_subject' => 'Asunto', 'tix_new_category' => 'Categoría',
        'tix_new_priority' => 'Prioridad', 'tix_new_desc' => 'Descripción', 'tix_new_desc_ph' => 'Cuéntanos qué pasa con el máximo detalle posible...',
        'tix_new_submit' => 'Abrir ticket', 'tix_empty' => 'Todavía no has abierto ningún ticket.', 'tix_no_messages' => 'Todavía no hay ningún mensaje.',
        'tix_created_ok' => '✅ Ticket abierto. Te responderemos lo antes posible.',
        'tix_replied_ok' => '✅ Respuesta enviada.',
        'tix_you' => 'Tú', 'tix_us' => 'AKRA Tech Studio',
        'tix_reply_ph' => 'Escribe una respuesta...', 'tix_reply_submit' => 'Enviar',
        'tix_opened_on' => 'Abierto el', 'tix_back' => '← Volver a Tickets',
        'tix_cat_incidencia' => 'Incidencia / error', 'tix_cat_dubte' => 'Duda', 'tix_cat_peticio' => 'Petición de cambio',
        'tix_cat_facturacio' => 'Facturación', 'tix_cat_altres' => 'Otros',
        'tix_pri_baixa' => 'Baja', 'tix_pri_mitjana' => 'Media', 'tix_pri_alta' => 'Alta', 'tix_pri_urgent' => 'Urgente',

        'cal_approval_pending_title' => '📤 Este calendario espera tu aprobación',
        'cal_approval_deadline'      => 'Si no respondes antes del',
        'cal_approval_deadline_end'  => ', se dará por aceptado automáticamente.',
        'cal_approval_accept'        => '✅ Aceptar calendario',
        'cal_approval_request'       => '✏️ Solicitar cambios',
        'cal_approval_comment_ph'    => 'Explica qué cambios te gustaría (opcional)...',
        'cal_approval_send'          => 'Enviar',
        'cal_approval_cancel'        => 'Cancelar',
        'cal_approval_accepted'      => '✅ Calendario aceptado. ¡Gracias!',
        'cal_approval_accepted_auto' => '✅ Calendario aceptado automáticamente al no recibir respuesta dentro del plazo.',
        'cal_approval_changes'       => '✏️ Has solicitado cambios en este calendario. La agencia lo revisará.',
        'cal_approval_your_comment'  => 'Tu comentario:',
    ],
    'en' => [
        'nav_home' => '🏠 Overview', 'nav_comms' => '💬 Messages', 'nav_jobs' => '🛠️ Project status',
        'nav_invoices' => '🧾 Invoices', 'nav_proposals' => '📄 Proposals', 'nav_logout' => 'Log out',
        'nav_calendar' => '📅 Calendar',

        'login_title' => 'Client portal', 'login_email' => 'Email', 'login_password' => 'Password',
        'login_submit' => 'Log in', 'login_lost' => 'Lost your access? Email us at',
        'login_error' => 'Incorrect email or password.',
        'login_disabled' => 'Your portal access has been disabled. Contact us if you think this is a mistake.',
        'login_lang_label' => 'Language',

        'dash_hello' => 'Hi', 'dash_sub' => 'Here\'s a summary of what we\'re working on with',
        'dash_proposals_pending' => 'You have {n} proposal(s) awaiting review.', 'dash_view_proposals' => 'View proposals',
        'dash_stat_active_jobs' => 'Active projects', 'dash_stat_due' => 'Amount due',
        'dash_stat_overdue' => 'Overdue invoices', 'dash_stat_total' => 'Total invoiced',
        'dash_jobs_title' => '🛠️ Projects in progress', 'dash_view_all' => 'View all', 'dash_view_all_f' => 'View all',
        'dash_no_active_jobs' => 'You have no active projects right now.', 'dash_start' => 'Start',
        'dash_invoices_title' => '🧾 Latest invoices', 'dash_no_invoices' => 'No invoices yet.',
        'dash_comms_title' => '💬 Latest message', 'dash_no_comms' => 'No messages recorded yet.',
        'dash_you' => 'You', 'dash_us' => 'Us',
        'dash_contact_title' => '📞 Direct contact', 'dash_send_msg' => '✍️ Send us a message',

        'comms_title' => 'Messages', 'comms_sub' => 'Your contact history with AKRA Tech Studio.',
        'comms_sent_ok' => '✅ Message sent. We\'ll get back to you as soon as possible.',
        'comms_write_title' => '✍️ Send us a message', 'comms_placeholder' => 'Write your question, request or comment here...',
        'comms_send' => 'Send', 'comms_history' => '🗂️ History', 'comms_empty' => 'No messages recorded yet.',

        'jobs_title' => 'Project status', 'jobs_sub' => 'project(s) on record with you.',
        'jobs_empty' => 'We have no projects on record yet.', 'jobs_rated_ok' => '✅ Thanks for your rating!',
        'jobs_start_date' => 'Start date', 'jobs_end_date' => 'End date', 'jobs_hours' => 'Hours logged',
        'jobs_your_rating' => 'Your rating:', 'jobs_rate_prompt' => '✨ This project is finished — how did it go?',
        'jobs_rate_comment' => 'Comment (optional)', 'jobs_rate_submit' => 'Submit rating',

        'inv_title' => 'Invoices', 'inv_due_suffix' => 'due', 'inv_empty' => 'No invoices yet.',
        'inv_issued' => 'Issued on', 'inv_due_date' => 'Due date', 'inv_partial' => 'Partially paid:',
        'inv_pay' => '💳 Pay', 'inv_pdf' => '⬇️ PDF',

        'prop_title' => 'Proposals', 'prop_sub' => 'Quotes and proposals we\'ve sent you.',
        'prop_done' => '✅ Thanks for your response.', 'prop_empty' => 'No proposals yet.',
        'prop_pdf' => '⬇️ Download PDF', 'prop_accept' => '✅ Accept', 'prop_reject' => '✖️ Reject',
        'prop_confirm_accept' => 'Confirm you accept this proposal?', 'prop_confirm_reject' => 'Confirm you reject this proposal?',

        'cal_title' => 'Content calendar', 'cal_sub' => 'Your planned social content calendar.',
        'cal_empty_month' => 'No content planned this month.', 'cal_today' => 'Today',
        'cal_months' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
        'cal_weekdays' => ['Mo','Tu','We','Th','Fr','Sa','Su'],

        'nav_tickets' => '🎫 Tickets',
        'tix_title' => 'Tickets', 'tix_sub' => 'Issues, questions and requests you\'ve sent us.',
        'tix_new' => '➕ New ticket', 'tix_new_subject' => 'Subject', 'tix_new_category' => 'Category',
        'tix_new_priority' => 'Priority', 'tix_new_desc' => 'Description', 'tix_new_desc_ph' => 'Tell us what\'s going on in as much detail as possible...',
        'tix_new_submit' => 'Open ticket', 'tix_empty' => 'You haven\'t opened any tickets yet.', 'tix_no_messages' => 'No messages yet.',
        'tix_created_ok' => '✅ Ticket opened. We\'ll get back to you as soon as possible.',
        'tix_replied_ok' => '✅ Reply sent.',
        'tix_you' => 'You', 'tix_us' => 'AKRA Tech Studio',
        'tix_reply_ph' => 'Write a reply...', 'tix_reply_submit' => 'Send',
        'tix_opened_on' => 'Opened on', 'tix_back' => '← Back to Tickets',
        'tix_cat_incidencia' => 'Issue / bug', 'tix_cat_dubte' => 'Question', 'tix_cat_peticio' => 'Change request',
        'tix_cat_facturacio' => 'Billing', 'tix_cat_altres' => 'Other',
        'tix_pri_baixa' => 'Low', 'tix_pri_mitjana' => 'Medium', 'tix_pri_alta' => 'High', 'tix_pri_urgent' => 'Urgent',

        'cal_approval_pending_title' => '📤 This calendar is waiting for your approval',
        'cal_approval_deadline'      => 'If you don\'t respond before',
        'cal_approval_deadline_end'  => ', it will be automatically approved.',
        'cal_approval_accept'        => '✅ Approve calendar',
        'cal_approval_request'       => '✏️ Request changes',
        'cal_approval_comment_ph'    => 'Tell us what changes you\'d like (optional)...',
        'cal_approval_send'          => 'Send',
        'cal_approval_cancel'        => 'Cancel',
        'cal_approval_accepted'      => '✅ Calendar approved. Thank you!',
        'cal_approval_accepted_auto' => '✅ Calendar automatically approved as no response was received in time.',
        'cal_approval_changes'       => '✏️ You\'ve requested changes to this calendar. We\'ll review it.',
        'cal_approval_your_comment'  => 'Your comment:',
    ],
    ];
}

function hubT($key, $lang = 'ca') {
    $strings = getHubStrings();
    $lang = array_key_exists($lang, $strings) ? $lang : 'ca';
    return $strings[$lang][$key] ?? ($strings['ca'][$key] ?? $key);
}

// Com hubT() però per a valors que són un array (llistes de mesos/dies de la setmana).
function hubTArr($key, $lang = 'ca') {
    $strings = getHubStrings();
    $lang = array_key_exists($lang, $strings) ? $lang : 'ca';
    return $strings[$lang][$key] ?? ($strings['ca'][$key] ?? []);
}

// Tradueix una etiqueta dinàmica (estat de treball/factura/proposta, canal de
// contacte, tipus de treball/proposta...) que arriba en català des de core.php.
function hubTStatus($catalan_text, $lang = 'ca') {
    if ($lang === 'ca') return $catalan_text;
    static $map = [
        'es' => [
            'Pressupostat' => 'Presupuestado', 'En curs' => 'En curso', 'En pausa' => 'En pausa',
            'Acabat' => 'Terminado', 'Cancel·lat' => 'Cancelado', 'Cancel·lada' => 'Cancelada',
            'Esborrany' => 'Borrador', 'Enviada' => 'Enviada', 'Cobrada' => 'Cobrada', 'Vençuda' => 'Vencida',
            'Acceptada' => 'Aceptada', 'Rebutjada' => 'Rechazada',
            'Disseny/desenvolupament web' => 'Diseño/desarrollo web', 'SEO' => 'SEO', 'Manteniment' => 'Mantenimiento',
            'Disseny gràfic' => 'Diseño gráfico', 'Marketing/xarxes' => 'Marketing/redes', 'Altres' => 'Otros',
            'Optimització WordPress' => 'Optimización WordPress', 'Migració híbrida' => 'Migración híbrida',
            'Plataforma a mida' => 'Plataforma a medida', 'Redisseny complet' => 'Rediseño completo',
            'Telèfon' => 'Teléfono', 'Email' => 'Email', 'WhatsApp' => 'WhatsApp', 'Presencial' => 'Presencial',
            'Videotrucada' => 'Videollamada', 'Portal del client (Hub)' => 'Portal del cliente (Hub)',
            'Obert' => 'Abierto', 'En procés' => 'En proceso', 'Resolt' => 'Resuelto', 'Tancat' => 'Cerrado',
        ],
        'en' => [
            'Pressupostat' => 'Quoted', 'En curs' => 'In progress', 'En pausa' => 'On hold',
            'Acabat' => 'Completed', 'Cancel·lat' => 'Cancelled', 'Cancel·lada' => 'Cancelled',
            'Esborrany' => 'Draft', 'Enviada' => 'Sent', 'Cobrada' => 'Paid', 'Vençuda' => 'Overdue',
            'Acceptada' => 'Accepted', 'Rebutjada' => 'Rejected',
            'Disseny/desenvolupament web' => 'Web design/development', 'SEO' => 'SEO', 'Manteniment' => 'Maintenance',
            'Disseny gràfic' => 'Graphic design', 'Marketing/xarxes' => 'Marketing/social', 'Altres' => 'Other',
            'Optimització WordPress' => 'WordPress optimisation', 'Migració híbrida' => 'Hybrid migration',
            'Plataforma a mida' => 'Custom platform', 'Redisseny complet' => 'Full redesign',
            'Telèfon' => 'Phone', 'Email' => 'Email', 'WhatsApp' => 'WhatsApp', 'Presencial' => 'In person',
            'Videotrucada' => 'Video call', 'Portal del client (Hub)' => 'Client portal (Hub)',
            'Obert' => 'Open', 'En procés' => 'In progress', 'Resolt' => 'Resolved', 'Tancat' => 'Closed',
        ],
    ];
    return $map[$lang][$catalan_text] ?? $catalan_text;
}
