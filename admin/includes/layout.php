<?php // admin/includes/layout.php — Sidebar + topbar reutilitzables
$unread_msgs = count(getMessages(true));
$current_page = basename($_SERVER['PHP_SELF'], '.php');

function navLink($page, $label, $icon, $current, $badge = 0) {
    $active = ($current === $page) ? ' active' : '';
    $badge_html = $badge > 0 ? "<span class='sidebar-badge'>{$badge}</span>" : '';
    echo "<a href='{$page}.php' class='sidebar-link{$active}'>{$icon}{$label}{$badge_html}</a>";
}

$icons = [
    'dashboard'   => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
    'projects'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    'services'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
    'testimonials'=> '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
    'messages'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    'seo'         => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'settings'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
    'password'    => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>',
    'preview'     => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
    'logout'      => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
];
?>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <svg width="30" height="30" viewBox="0 0 36 36" fill="none">
            <rect width="36" height="36" rx="8" fill="currentColor" fill-opacity="0.15"/>
            <path d="M8 28L18 8L28 28" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M11.5 22H24.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        <div class="sidebar-brand-text">
            AKRA Admin
            <span>Panel v<?= ADMIN_VERSION ?></span>
        </div>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">General</div>
        <?php navLink('dashboard', 'Dashboard', $icons['dashboard'], $current_page) ?>
        <?php navLink('messages', 'Missatges', $icons['messages'], $current_page, $unread_msgs) ?>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Contingut</div>
        <?php navLink('projects', 'Projectes', $icons['projects'], $current_page) ?>
        <a href="project-types.php" class="nav-link <?= $current_page === 'project-types' ? 'active' : '' ?>" style="padding-left:36px;font-size:.82rem;opacity:.8">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            Tipus de projecte
        </a>
        <?php navLink('services', 'Serveis', $icons['services'], $current_page) ?>
        <?php navLink('testimonials', 'Testimonis', $icons['testimonials'], $current_page) ?>
        <?php navLink('blog', 'Blog', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', $current_page) ?>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Suport al client</div>
        <?php $open_ticket_count = countOpenTickets(); ?>
        <a href="tickets.php" class="sidebar-link<?= in_array($current_page, ['tickets', 'ticket-view']) ? ' active' : '' ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 100 6v2a2 2 0 002 2h16a2 2 0 002-2v-2a3 3 0 100-6V7a2 2 0 00-2-2H4a2 2 0 00-2 2v2z"/></svg>🎫 Tiquets<?php if ($open_ticket_count): ?><span class="sidebar-badge"><?= $open_ticket_count ?></span><?php endif; ?></a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Xarxes socials</div>
        <a href="prompts.php" class="sidebar-link<?= in_array($current_page, ['prompts', 'prompt-view']) ? ' active' : '' ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>🧠 Prompts</a>
        <?php navLink('social-calendar', '🗓️ Calendari xarxes', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', $current_page) ?>
    </div>


    <div class="sidebar-section">
        <div class="sidebar-section-label">Facturació</div>
        <?php navLink('invoices', 'Factures', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="8" y1="9" x2="10" y2="9"/></svg>', $current_page) ?>
        <?php $unassigned_payments_count = count(getUnassignedPayments()); ?>
        <a href="payments.php" class="sidebar-link<?= $current_page === 'payments' ? ' active' : '' ?>"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>💳 Pagaments<?php if ($unassigned_payments_count): ?><span class="sidebar-badge"><?= $unassigned_payments_count ?></span><?php endif; ?></a>
        <?php navLink('clients', 'Clients', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>', $current_page) ?>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Comptabilitat</div>
        <?php navLink('comptabilitat', 'Informes i IVA/IRPF', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18.7 8l-5.3 5.3-3-3-4.4 4.4"/></svg>', $current_page) ?>
        <?php navLink('expenses', 'Despeses', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="18" x2="12" y2="18"/></svg>', $current_page) ?>
        <?php navLink('suppliers', 'Proveïdors', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>', $current_page) ?>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Auditories</div>
        <?php navLink('pipeline', '📊 Pipeline comercial', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>', $current_page) ?>
        <?php navLink('audits', 'Auditories web', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>', $current_page) ?>
        <?php navLink('proposals', 'Propostes', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>', $current_page) ?>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-label">Configuració</div>
        <?php navLink('settings', 'Configuració', $icons['settings'], $current_page) ?>
        <?php if (getCurrentUser()['role'] === 'admin'): ?>
        <?php navLink('users', '👥 Usuaris', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>', $current_page) ?>
        <?php endif; ?>
        <?php navLink('seo', 'SEO & Analytics', $icons['seo'], $current_page) ?>
        <?php navLink('backup', 'Còpia de seguretat', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>', $current_page) ?>
        <?php navLink('trash', '🗑️ Paperera', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m5 0V4a2 2 0 012-2h0a2 2 0 012 2v2"/></svg>', $current_page) ?>
        <?php navLink('password', 'Canviar contrasenya', $icons['password'], $current_page) ?>
    </div>

    <div class="sidebar-footer">
        <a href="<?= defined('SITE_URL') ? '' : '../' ?>" target="_blank">
            <?= $icons['preview'] ?> Veure web
        </a>
        <a href="logout.php" style="color: rgba(255,255,255,0.35);">
            <?= $icons['logout'] ?> Tancar sessió
        </a>
    </div>
</aside>
<!-- Dins de <div class="sidebar-section"> de Configuració, afegeix: -->
<?php navLink('seo', 'SEO & Meta Tags', $icons['seo'], $current_page) ?>
<?php navLink('content', 'Contingut', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', $current_page) ?>
<?php navLink('media', 'Mitjans', '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>', $current_page) ?>
<!-- MAIN WRAPPER -->
<div class="admin-main">
<header class="topbar">
    <div>
        <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
        <div class="topbar-subtitle"><?= $page_subtitle ?? '' ?></div>
    </div>
    <form action="search.php" method="GET" style="flex:1;max-width:340px;margin:0 20px">
        <input type="search" name="q" placeholder="🔍 Cerca clients, factures, contactes..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
               style="width:100%;padding:9px 14px;border:1px solid var(--a-border);border-radius:8px;font-size:.85rem;background:var(--a-bg)">
    </form>
    <div class="topbar-actions">
        <span style="font-size:.78rem;color:#9ca3af;margin-right:4px">👤 <?= htmlspecialchars(getCurrentUser()['name']) ?></span>
        <?php if (!empty($topbar_action_url)): ?>
        <a href="<?= $topbar_action_url ?>" class="topbar-btn topbar-btn--primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?= $topbar_action_label ?? 'Nou' ?>
        </a>
        <?php endif; ?>
        <button class="topbar-btn" id="sidebar-toggle" style="display:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
</header>
<div class="page-content">
