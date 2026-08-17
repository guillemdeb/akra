<?php
require_once 'includes/core.php';
requireLogin();

if (isset($_GET['send_alerts'])) {
    $result = sendDailyAlertsEmail();
    header('Location: dashboard.php?alerts_sent=' . ($result['ok'] ? (!empty($result['sent']) ? '1' : 'none') : '0')); exit;
}

$page_title    = 'Dashboard';
$page_subtitle = 'Visió general del lloc web';

$projects     = getAdminProjects();
$services     = getAdminServices();
$testimonials = getAdminTestimonials();
$messages     = getMessages();
$unread       = count(array_filter($messages, fn($m) => !($m['read'] ?? false)));
$recent_msgs  = array_slice($messages, 0, 5);
$recent_proj  = array_slice($projects, 0, 5);
$crm          = getCrmStats();
$overdue_inv  = getOverdueInvoices();
$followups    = getUpcomingFollowUps(7);
$funnel       = getPipelineFunnel();
$domains_soon = getDomainsExpiringSoon(30);
$fin_global   = getGlobalFinancialStats();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · AKRA Admin</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<?php include 'includes/layout.php'; ?>

<?php if (isset($_GET['alerts_sent'])): ?>
<?php if ($_GET['alerts_sent'] === '1'): ?>
<div class="alert alert-success">✅ Resum diari enviat per email.</div>
<?php elseif ($_GET['alerts_sent'] === 'none'): ?>
<div class="alert alert-success">No hi ha cap avís pendent hui — no calia enviar res.</div>
<?php else: ?>
<div class="alert alert-error">❌ Error enviant l'email. Comprova la configuració SMTP del servidor.</div>
<?php endif; ?>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"><div class="card-title">💰 Resum financer global</div></div>
    <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:6px">Facturat este mes</div>
            <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700"><?= number_format($fin_global['month_billed'], 2, ',', '.') ?> €</div>
            <?php if ($fin_global['month_vs_last_pct'] !== null): ?>
            <div style="font-size:.78rem;color:<?= $fin_global['month_vs_last_pct'] >= 0 ? '#16a34a' : '#dc2626' ?>">
                <?= $fin_global['month_vs_last_pct'] >= 0 ? '▲' : '▼' ?> <?= abs($fin_global['month_vs_last_pct']) ?>% vs mes anterior
            </div>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:6px">Cobrat este mes</div>
            <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:#16a34a"><?= number_format($fin_global['month_paid'], 2, ',', '.') ?> €</div>
        </div>
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:6px">Facturat enguany (<?= date('Y') ?>)</div>
            <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700"><?= number_format($fin_global['year_billed'], 2, ',', '.') ?> €</div>
        </div>
        <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:6px">Pendent de cobrar (total)</div>
            <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:700;color:<?= $fin_global['total_pending'] > 0 ? '#dc2626' : '#16a34a' ?>"><?= number_format($fin_global['total_pending'], 2, ',', '.') ?> €</div>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-icon--gold">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div>
            <div class="stat-num"><?= count($projects) ?></div>
            <div class="stat-label">Projectes totals</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div>
            <div class="stat-num"><?= count($services) ?></div>
            <div class="stat-label">Serveis actius</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-icon--green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        </div>
        <div>
            <div class="stat-num"><?= count($testimonials) ?></div>
            <div class="stat-label">Testimonis</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $unread > 0 ? 'stat-icon--red' : 'stat-icon--gray' ?>" style="background:<?= $unread > 0 ? 'rgba(239,68,68,0.1)' : '#f3f4f6' ?>; color:<?= $unread > 0 ? '#dc2626' : '#9ca3af' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
            <div class="stat-num"><?= $unread ?></div>
            <div class="stat-label">Missatges nous</div>
        </div>
    </div>
</div>

<?php if (!empty($overdue_inv) || !empty($followups)): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">
    <?php if (!empty($overdue_inv)): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚠️ Factures vençudes</div>
            <span class="badge badge-red"><?= count($overdue_inv) ?></span>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>Número</th><th>Client</th><th>Venciment</th><th>Import</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($overdue_inv, 0, 6) as $inv): $cl = getClient($inv['client_id']); ?>
            <tr>
                <td><strong style="font-size:.85rem"><?= htmlspecialchars($inv['number']) ?></strong></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($cl['name'] ?? '—') ?></td>
                <td style="font-size:.8rem;color:#dc2626"><?= date('d/m/Y', strtotime($inv['due_date'])) ?></td>
                <td style="font-weight:700;font-size:.85rem"><?= number_format($inv['total'], 2, ',', '.') ?> €</td>
                <td><a href="invoices.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary">Veure</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($followups)): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">📅 Seguiments pendents</div>
            <span class="badge badge-gold"><?= count($followups) ?></span>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>Data</th><th>Client</th><th>Missatge</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($followups, 0, 6) as $ct): $cl = getClient($ct['client_id']);
                $late = $ct['follow_up'] < date('Y-m-d');
            ?>
            <tr>
                <td style="font-size:.8rem;color:<?= $late ? '#dc2626' : '#6b7280' ?>"><?= date('d/m/Y', strtotime($ct['follow_up'])) ?><?= $late ? ' ⚠️' : '' ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($cl['name'] ?? '—') ?></td>
                <td style="font-size:.8rem;color:#6b7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ct['message'] ?? '') ?></td>
                <td><a href="clients.php?id=<?= $ct['client_id'] ?>#contactsCard" class="btn btn-sm btn-secondary">Veure</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($domains_soon)): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">🌐 Dominis a renovar pròximament</div>
        <span class="badge badge-red"><?= count($domains_soon) ?></span>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Domini</th><th>Client</th><th>Empresa</th><th>Renovació</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($domains_soon as $d): $cl = getClient($d['client_id']);
            $days_left = round((strtotime($d['renewal_date']) - strtotime(date('Y-m-d'))) / 86400);
        ?>
        <tr>
            <td><strong style="font-size:.85rem"><?= htmlspecialchars($d['domain']) ?></strong></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($cl['name'] ?? '—') ?></td>
            <td style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($d['provider'] ?: '—') ?></td>
            <td style="font-size:.8rem;color:<?= $days_left < 0 ? '#dc2626' : '#374151' ?>;white-space:nowrap">
                <?= date('d/m/Y', strtotime($d['renewal_date'])) ?>
                <?= $days_left < 0 ? ' (vençut)' : " ({$days_left} dies)" ?>
            </td>
            <td><a href="clients.php?id=<?= $d['client_id'] ?>#domainsCard" class="btn btn-sm btn-secondary">Veure</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<!-- FUNNEL DE PIPELINE -->
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">📊 Funnel de vendes</div>
        <div style="display:flex;gap:8px">
            <a href="dashboard.php?send_alerts=1" class="btn btn-sm btn-secondary" title="Enviar ara el resum diari per email">✉️ Enviar resum ara</a>
            <a href="pipeline.php" class="btn btn-sm btn-secondary">Veure pipeline</a>
        </div>
    </div>
    <div style="padding:20px 24px">
        <?php
        $stage_opts_dash = getLeadStageOptions();
        $max_funnel = max(1, max($funnel));
        foreach ($stage_opts_dash as $k => $s):
            $count = $funnel[$k] ?? 0;
            $pct = round(($count / $max_funnel) * 100);
        ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
            <div style="width:130px;font-size:.8rem;color:#374151;flex-shrink:0"><?= $s['label'] ?></div>
            <div style="flex:1;background:#f3f4f6;border-radius:6px;height:22px;overflow:hidden">
                <div style="height:100%;width:<?= max(4,$pct) ?>%;background:<?= $s['class'] === 'badge-green' ? '#16a34a' : ($s['class'] === 'badge-red' ? '#dc2626' : 'var(--a-gold)') ?>;border-radius:6px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px">
                    <span style="font-size:.72rem;color:white;font-weight:700"><?= $count ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- CRM: AUDITORIES I PROPOSTES -->
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <div class="card-title">Auditories &amp; gestió comercial</div>
        <a href="audits.php?new=1" class="btn btn-primary btn-sm">+ Nova auditoria</a>
    </div>
    <div class="stats-grid" style="padding:20px">
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            <div><div class="stat-num"><?= $crm['clients'] ?></div><div class="stat-label">Clients totals</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--gold"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
            <div><div class="stat-num"><?= $crm['audits'] ?></div><div class="stat-label">Auditories realitzades</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            <div><div class="stat-num"><?= $crm['sent'] ?></div><div class="stat-label">Pressupostos enviats</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div><div class="stat-num"><?= $crm['accepted'] ?></div><div class="stat-label">Pressupostos acceptats</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 6l-9.5 9.5-5-5L1 18"/></svg></div>
            <div><div class="stat-num"><?= $crm['conversio'] ?>%</div><div class="stat-label">Conversió</div></div>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-bottom:24px;">
    <a href="projects.php?new=1" class="card" style="padding:16px;display:flex;align-items:center;gap:12px;transition:all 0.15s;">
        <div class="stat-icon stat-icon--gold" style="width:38px;height:38px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <div><div style="font-weight:700;font-size:.9rem;color:#1a1f2e">Nou projecte</div><div style="font-size:.78rem;color:#6b7280">Afegir al portfolio</div></div>
    </a>
    <a href="messages.php" class="card" style="padding:16px;display:flex;align-items:center;gap:12px;transition:all 0.15s;">
        <div class="stat-icon stat-icon--blue" style="width:38px;height:38px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div><div style="font-weight:700;font-size:.9rem;color:#1a1f2e">Missatges</div><div style="font-size:.78rem;color:#6b7280"><?= $unread ?> sense llegir</div></div>
    </a>
    <a href="settings.php" class="card" style="padding:16px;display:flex;align-items:center;gap:12px;transition:all 0.15s;">
        <div class="stat-icon stat-icon--green" style="width:38px;height:38px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        </div>
        <div><div style="font-weight:700;font-size:.9rem;color:#1a1f2e">Configuració</div><div style="font-size:.78rem;color:#6b7280">Dades i xarxes socials</div></div>
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
<!-- Últims projectes -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Últims projectes</div>
        <a href="projects.php" class="btn btn-sm btn-secondary">Veure tots</a>
    </div>
    <div class="table-wrap">
    <?php if (empty($recent_proj)): ?>
        <div style="padding:32px;text-align:center;color:#9ca3af;font-size:.88rem;">
            Encara no hi ha projectes. <a href="projects.php?new=1" style="color:#c9a84c;font-weight:600">Afegeix el primer →</a>
        </div>
    <?php else: ?>
    <table>
        <thead><tr><th>Projecte</th><th>Cat.</th><th>Estat</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_proj as $p): ?>
        <tr>
            <td><strong style="font-size:.9rem"><?= htmlspecialchars($p['title']['ca'] ?? $p['title']['es'] ?? '') ?></strong></td>
            <td><span class="badge badge-gray"><?= $p['category'] ?></span></td>
            <td>
                <?php if ($p['status'] === 'active'): ?><span class="badge badge-green">Actiu</span>
                <?php elseif ($p['status'] === 'demo'): ?><span class="badge badge-blue">Demo</span>
                <?php else: ?><span class="badge badge-gray"><?= $p['status'] ?></span><?php endif; ?>
            </td>
            <td><a href="projects.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary btn-icon">✏️</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>

<!-- Últims missatges -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Missatges recents <?php if ($unread): ?><span class="sidebar-badge" style="position:static;margin-left:6px"><?= $unread ?></span><?php endif; ?></div>
        <a href="messages.php" class="btn btn-sm btn-secondary">Veure tots</a>
    </div>
    <div class="table-wrap">
    <?php if (empty($messages)): ?>
        <div style="padding:32px;text-align:center;color:#9ca3af;font-size:.88rem;">Encara no hi ha missatges.</div>
    <?php else: ?>
    <table>
        <thead><tr><th>Nom</th><th>Servei</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_msgs as $m): ?>
        <tr>
            <td>
                <strong style="font-size:.9rem"><?= htmlspecialchars($m['name'] ?? '') ?></strong>
                <?php if (!($m['read'] ?? false)): ?><span class="badge badge-red" style="margin-left:6px;font-size:.65rem">Nou</span><?php endif; ?>
                <div style="font-size:.78rem;color:#9ca3af"><?= htmlspecialchars($m['email'] ?? '') ?></div>
            </td>
            <td><span style="font-size:.8rem;color:#6b7280"><?= htmlspecialchars($m['service'] ?? '—') ?></span></td>
            <td style="font-size:.78rem;color:#9ca3af"><?= isset($m['date']) ? date('d/m H:i', strtotime($m['date'])) : '—' ?></td>
            <td><a href="messages.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary btn-icon">👁</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
</div>
</div>

</div></div><!-- /page-content /admin-main -->
<?php include 'includes/admin-footer.php'; ?>
