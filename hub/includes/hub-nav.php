<?php
// hub/includes/hub-nav.php — Capçalera i navegació del portal, un cop autenticat.
// Espera que la pàgina que l'inclou ja tinga definides $client i $lang.
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$unread_comms = count(array_filter(getContacts($client['id']), fn($c) => ($c['direction'] ?? '') === 'jo_client' && empty($c['read_by_client'])));
$pending_proposals = count(array_filter(getProposals($client['id']), fn($p) => ($p['status'] ?? '') === 'enviada'));
$pending_calendar_approval = getCalendarApproval($client['id'], date('Y-m'));
$pending_calendar_approval = ($pending_calendar_approval && $pending_calendar_approval['status'] === 'pendent') ? 1 : 0;
$hub_langs = getHubLangOptions();
?>
<div class="hub-topbar">
    <div class="hub-topbar-inner">
        <div class="hub-brand"><span class="hub-brand-dot"></span> AKRA TECH STUDIO</div>
        <div style="display:flex;align-items:center;gap:16px">
            <?php if (!empty($client['logo'])): ?>
            <img src="../<?= htmlspecialchars($client['logo']) ?>" style="height:26px;width:auto;max-width:110px;object-fit:contain;background:#fff;border-radius:5px;padding:3px 6px">
            <?php endif; ?>
            <span class="hub-client-name"><?= htmlspecialchars($client['company'] ?: $client['name']) ?></span>
            <div class="hub-lang-switch">
                <?php foreach ($hub_langs as $key => $label): ?>
                <a href="set-lang.php?lang=<?= $key ?>&return_to=<?= urlencode($current_page . '.php') ?>" class="<?= $lang === $key ? 'active' : '' ?>" title="<?= htmlspecialchars($label) ?>"><?= strtoupper($key) ?></a>
                <?php endforeach; ?>
            </div>
            <a href="logout.php" class="hub-logout"><?= htmlspecialchars(hubT('nav_logout', $lang)) ?></a>
        </div>
    </div>
</div>
<div class="hub-nav">
    <div class="hub-nav-inner">
        <a href="index.php" class="hub-nav-link <?= $current_page === 'index' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_home', $lang)) ?></a>
        <a href="comunicacions.php" class="hub-nav-link <?= $current_page === 'comunicacions' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_comms', $lang)) ?> <?php if ($unread_comms): ?><span class="hub-nav-badge"><?= $unread_comms ?></span><?php endif; ?></a>
        <?php $open_tickets = countOpenTickets($client['id']); ?>
        <a href="tickets.php" class="hub-nav-link <?= in_array($current_page, ['tickets', 'ticket-view']) ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_tickets', $lang)) ?> <?php if ($open_tickets): ?><span class="hub-nav-badge"><?= $open_tickets ?></span><?php endif; ?></a>
        <a href="treballs.php" class="hub-nav-link <?= $current_page === 'treballs' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_jobs', $lang)) ?></a>
        <a href="calendari.php" class="hub-nav-link <?= $current_page === 'calendari' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_calendar', $lang)) ?> <?php if ($pending_calendar_approval): ?><span class="hub-nav-badge">1</span><?php endif; ?></a>
        <a href="factures.php" class="hub-nav-link <?= $current_page === 'factures' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_invoices', $lang)) ?></a>
        <a href="propostes.php" class="hub-nav-link <?= $current_page === 'propostes' ? 'active' : '' ?>"><?= htmlspecialchars(hubT('nav_proposals', $lang)) ?> <?php if ($pending_proposals): ?><span class="hub-nav-badge"><?= $pending_proposals ?></span><?php endif; ?></a>
    </div>
</div>

<?php $hub_cfg = getAdminConfig(); if (!empty($hub_cfg['whatsapp_number']) && !empty($hub_cfg['whatsapp_float_hub'])): ?>
<a href="https://wa.me/<?= htmlspecialchars($hub_cfg['whatsapp_number']) ?>?text=<?= rawurlencode($hub_cfg['whatsapp_float_message'] ?? 'Hola!') ?>" target="_blank" rel="noopener" class="hub-wa-float" aria-label="WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12.01 0C5.377 0 0 5.373 0 12c0 2.121.553 4.113 1.523 5.845L0 24l6.328-1.492A11.943 11.943 0 0012.01 24C18.643 24 24 18.627 24 12S18.643 0 12.01 0zm0 21.783a9.72 9.72 0 01-4.955-1.354l-.356-.211-3.68.867.9-3.638-.232-.373A9.706 9.706 0 012.24 12c0-5.385 4.383-9.783 9.77-9.783 5.385 0 9.769 4.398 9.769 9.783 0 5.386-4.384 9.783-9.769 9.783z"/></svg>
</a>
<?php endif; ?>
