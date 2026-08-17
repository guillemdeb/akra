<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

$jobs        = getJobs($client['id']);
$active_jobs = array_values(array_filter($jobs, fn($j) => !in_array($j['status'] ?? '', ['acabat', 'cancelat'])));
$fin         = getClientFinancialSummary($client['id']);
$invoices    = array_slice(getInvoices($client['id']), 0, 3);
$contacts    = array_slice(getContacts($client['id']), 0, 3);
$proposals   = array_values(array_filter(getProposals($client['id']), fn($p) => ($p['status'] ?? '') === 'enviada'));
$job_status  = getJobStatusOptions();
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('nav_home', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('dash_hello', $lang)) ?>, <?= htmlspecialchars(explode(' ', $client['name'])[0] ?: $client['name']) ?> 👋</h1>
    <p class="hub-page-subtitle"><?= htmlspecialchars(hubT('dash_sub', $lang)) ?> <?= htmlspecialchars($client['company'] ?: $client['name']) ?>.</p>

    <?php if (!empty($proposals)): ?>
    <div class="hub-alert" style="background:rgba(201,168,76,.14);border:1px solid rgba(201,168,76,.3);color:#7a5c0e;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <span>📄 <?= htmlspecialchars(str_replace('{n}', count($proposals), hubT('dash_proposals_pending', $lang))) ?></span>
        <a href="propostes.php" class="hub-btn hub-btn--sm hub-btn--gold"><?= htmlspecialchars(hubT('dash_view_proposals', $lang)) ?></a>
    </div>
    <?php endif; ?>

    <div class="hub-stats">
        <div class="hub-stat">
            <div class="hub-stat-label"><?= htmlspecialchars(hubT('dash_stat_active_jobs', $lang)) ?></div>
            <div class="hub-stat-value"><?= count($active_jobs) ?></div>
        </div>
        <div class="hub-stat">
            <div class="hub-stat-label"><?= htmlspecialchars(hubT('dash_stat_due', $lang)) ?></div>
            <div class="hub-stat-value <?= $fin['due'] > 0 ? 'hub-stat-value--red' : 'hub-stat-value--green' ?>"><?= number_format($fin['due'], 2, ',', '.') ?> €</div>
        </div>
        <div class="hub-stat">
            <div class="hub-stat-label"><?= htmlspecialchars(hubT('dash_stat_overdue', $lang)) ?></div>
            <div class="hub-stat-value <?= $fin['overdue'] > 0 ? 'hub-stat-value--red' : '' ?>"><?= $fin['overdue'] ?></div>
        </div>
        <div class="hub-stat">
            <div class="hub-stat-label"><?= htmlspecialchars(hubT('dash_stat_total', $lang)) ?></div>
            <div class="hub-stat-value"><?= number_format($fin['total'], 2, ',', '.') ?> €</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px" class="hub-dash-grid">
        <div>
            <div class="hub-card">
                <div class="hub-card-header">
                    <div class="hub-card-title"><?= htmlspecialchars(hubT('dash_jobs_title', $lang)) ?></div>
                    <a href="treballs.php" class="hub-btn hub-btn--sm"><?= htmlspecialchars(hubT('dash_view_all', $lang)) ?></a>
                </div>
                <?php if (empty($active_jobs)): ?>
                <div class="hub-empty"><?= htmlspecialchars(hubT('dash_no_active_jobs', $lang)) ?></div>
                <?php else: foreach (array_slice($active_jobs, 0, 4) as $j): $st = $job_status[$j['status'] ?? 'pressupostat'] ?? $job_status['pressupostat']; ?>
                <div class="hub-row">
                    <div class="hub-row-main">
                        <div class="hub-row-title"><?= htmlspecialchars($j['title'] ?? 'Treball') ?></div>
                        <div class="hub-row-sub"><?= htmlspecialchars(hubT('dash_start', $lang)) ?>: <?= !empty($j['start_date']) ? date('d/m/Y', strtotime($j['start_date'])) : '—' ?></div>
                    </div>
                    <div class="hub-row-side"><span class="hub-badge hub-badge--<?= str_replace('badge-', '', $st['class']) ?>"><?= htmlspecialchars(hubTStatus($st['label'], $lang)) ?></span></div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="hub-card">
                <div class="hub-card-header">
                    <div class="hub-card-title"><?= htmlspecialchars(hubT('dash_invoices_title', $lang)) ?></div>
                    <a href="factures.php" class="hub-btn hub-btn--sm"><?= htmlspecialchars(hubT('dash_view_all_f', $lang)) ?></a>
                </div>
                <?php if (empty($invoices)): ?>
                <div class="hub-empty"><?= htmlspecialchars(hubT('dash_no_invoices', $lang)) ?></div>
                <?php else: foreach ($invoices as $inv): $sl = invoiceStatusLabel($inv['status'] ?? 'draft'); ?>
                <div class="hub-row">
                    <div class="hub-row-main">
                        <div class="hub-row-title"><?= htmlspecialchars($inv['number'] ?? '') ?></div>
                        <div class="hub-row-sub"><?= !empty($inv['date']) ? date('d/m/Y', strtotime($inv['date'])) : '' ?></div>
                    </div>
                    <div class="hub-row-side">
                        <div class="hub-row-amount"><?= number_format($inv['total'] ?? 0, 2, ',', '.') ?> €</div>
                        <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $sl['class']) ?>"><?= htmlspecialchars(hubTStatus($sl['text'], $lang)) ?></span>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div>
            <div class="hub-card">
                <div class="hub-card-header">
                    <div class="hub-card-title"><?= htmlspecialchars(hubT('dash_comms_title', $lang)) ?></div>
                    <a href="comunicacions.php" class="hub-btn hub-btn--sm"><?= htmlspecialchars(hubT('dash_view_all', $lang)) ?></a>
                </div>
                <?php if (empty($contacts)): ?>
                <div class="hub-empty"><?= htmlspecialchars(hubT('dash_no_comms', $lang)) ?></div>
                <?php else: foreach ($contacts as $c): ?>
                <div class="hub-comm">
                    <div class="hub-comm-top">
                        <span class="hub-comm-meta"><?= !empty($c['date']) ? date('d/m/Y', strtotime($c['date'])) : '' ?> · <?= ($c['direction'] ?? '') === 'client_jo' ? htmlspecialchars(hubT('dash_you', $lang)) : htmlspecialchars(hubT('dash_us', $lang)) ?></span>
                    </div>
                    <div class="hub-comm-body"><?= htmlspecialchars(mb_strimwidth($c['message'] ?? '', 0, 140, '…')) ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="hub-card">
                <div class="hub-card-header"><div class="hub-card-title"><?= htmlspecialchars(hubT('dash_contact_title', $lang)) ?></div></div>
                <div class="hub-card-body" style="font-size:.86rem;line-height:1.9">
                    <div>✉️ <a href="mailto:hola@akratechstudio.es" style="color:var(--h-text);font-weight:600">hola@akratechstudio.es</a></div>
                    <div style="margin-top:6px"><a href="comunicacions.php" class="hub-btn hub-btn--sm hub-btn--gold" style="margin-top:6px"><?= htmlspecialchars(hubT('dash_send_msg', $lang)) ?></a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>@media (max-width: 820px) { .hub-dash-grid { grid-template-columns: 1fr !important; } }</style>
</body></html>
