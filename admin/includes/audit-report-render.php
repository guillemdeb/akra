<?php
// admin/includes/audit-report-render.php
// Plantilla compartida de l'informe d'auditoria (14 seccions), amb idioma.
// Variables esperades: $audit, $client, $proposals (array), $view ('admin'|'client')
// L'idioma es fixa per l'admin a cada auditoria ($audit['lang']); la vista
// intern sempre es queda en valencià perquè és l'idioma de treball de Dari.
if (!isset($audit) || !isset($client)) { return; }
$proposals   = $proposals ?? [];
$view        = $view ?? 'admin';
$lang        = $view === 'client' ? ($audit['lang'] ?? 'ca') : 'ca';
$score_cats  = getAuditScoreCategories($lang);
$cms_opts    = getAuditCmsOptions($lang);
$type_opts   = getProposalTypeOptions();
$action_buckets = getAuditActionBuckets($lang);
$table_rows  = parseAuditTable($audit['taula_resum'] ?? '');
$global_avg  = auditScoreAvg($audit);
$global_lvl  = auditScoreLabel($global_avg);
$t = fn($key) => auditT($key, $lang);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Informe-Auditoria-<?= htmlspecialchars(preg_replace('/[^A-Za-z0-9]+/', '-', $client['company'] ?: $client['name'])) ?>-<?= htmlspecialchars($audit['date']) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root { --ink:#0F172A; --ink2:#1E293B; --accent:#2563EB; --paper:#F8FAFC; --border:#E2E8F0; --muted:#64748B; }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{font-family:'DM Sans','Helvetica Neue',Arial,sans-serif;font-size:13.5px;color:var(--ink);background:#e8ecf1}
.toolbar{position:fixed;top:0;left:0;right:0;height:56px;background:var(--ink);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:100}
.toolbar__logo{font-family:'Syne',sans-serif;font-weight:800;font-size:15px;letter-spacing:-.02em}
.toolbar__actions{display:flex;gap:10px}
.toolbar__btn{background:var(--accent);color:#fff;border:none;padding:9px 16px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.toolbar__btn--ghost{background:rgba(255,255,255,.1);color:#fff}
.sheet{max-width:900px;margin:88px auto 60px;background:#fff;padding:56px 60px;border-radius:4px;box-shadow:0 8px 30px rgba(15,23,42,.12)}
.head{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:28px;border-bottom:3px solid var(--ink);margin-bottom:32px}
.brand{font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--ink);letter-spacing:-.02em}
.brand small{display:block;font-family:'DM Sans',sans-serif;font-weight:500;font-size:.7rem;color:var(--accent);letter-spacing:.08em;text-transform:uppercase;margin-top:4px}
.head-meta{text-align:right;font-size:.8rem;color:var(--muted)}
.head-meta strong{color:var(--ink)}
h1.doc-title{font-family:'Syne',sans-serif;font-size:1.6rem;margin-bottom:4px;color:var(--ink)}
.doc-sub{color:var(--muted);margin-bottom:28px;font-size:.9rem}
.client-box{background:var(--paper);border:1px solid var(--border);border-radius:10px;padding:18px 22px;margin-bottom:32px;display:grid;grid-template-columns:1fr 1fr;gap:6px 24px;font-size:.85rem}
.client-box div span{color:var(--muted)}
section{margin-bottom:34px}
h2.sec-title{font-family:'Syne',sans-serif;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
h2.sec-title .sn{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:5px;background:var(--ink);color:#fff;font-size:.66rem}
h3.sub-title{font-family:'Syne',sans-serif;font-size:.92rem;color:var(--ink);margin:16px 0 8px}
p.body-text{line-height:1.7;color:var(--ink2);white-space:pre-line}
.score-mini{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border-radius:8px;padding:6px 14px;font-family:'Syne',sans-serif;font-weight:800;font-size:.95rem;float:right}
.score-mini span{color:var(--accent)}
ul.report-list{list-style:none}
ul.report-list li{position:relative;padding-left:20px;margin-bottom:9px;line-height:1.6;color:var(--ink2)}
ul.report-list li::before{content:'';position:absolute;left:0;top:8px;width:7px;height:7px;border-radius:50%;background:var(--accent)}
.badge-level{display:inline-block;padding:4px 14px;border-radius:100px;font-size:.75rem;font-weight:700;background:var(--accent);color:#fff}
.action-block{border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:12px}
.action-block h4{font-family:'Syne',sans-serif;font-size:.82rem;margin-bottom:2px}
.action-block .sub{font-size:.72rem;color:var(--muted);margin-bottom:10px}
.action-block--crit{border-left:4px solid #dc2626}
.action-block--imp{border-left:4px solid #d97706}
.action-block--rec{border-left:4px solid var(--accent)}
.action-block--gro{border-left:4px solid #16a34a}
table.data-table{width:100%;border-collapse:collapse;margin-top:8px}
table.data-table th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:8px 10px;border-bottom:2px solid var(--ink)}
table.data-table td{padding:10px;border-bottom:1px solid var(--border);font-size:.8rem;vertical-align:top}
table.prop-table .price{font-family:'Syne',sans-serif;font-weight:800;color:var(--accent);text-align:right}
.priority-pill{display:inline-block;padding:2px 9px;border-radius:100px;font-size:.68rem;font-weight:700}
.priority-pill--critica,.priority-pill--crítica{background:#fee2e2;color:#991b1b}
.priority-pill--alta{background:#ffedd5;color:#9a3412}
.priority-pill--mitjana,.priority-pill--mitja,.priority-pill--media{background:#dbeafe;color:#1e40af}
.priority-pill--baixa,.priority-pill--baja{background:#dcfce7;color:#166534}
.final-scores{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
.final-score{background:var(--paper);border:1px solid var(--border);border-left:4px solid var(--border);border-radius:10px;padding:12px 14px}
.final-score.badge-green{border-left-color:#16a34a}
.final-score.badge-blue{border-left-color:var(--accent)}
.final-score.badge-gold{border-left-color:#d97706}
.final-score.badge-red{border-left-color:#dc2626}
.final-score .l{font-size:.7rem;color:var(--muted);margin-bottom:4px}
.final-score .n{font-family:'Syne',sans-serif;font-weight:800;font-size:1.15rem;color:var(--ink)}
.global-box{background:var(--ink);color:#fff;border-radius:14px;padding:22px 26px;display:flex;align-items:center;justify-content:space-between;margin-top:18px}
.global-box .gn{font-family:'Syne',sans-serif;font-size:2.4rem;font-weight:800;color:var(--accent)}
.global-box .gl{font-size:.78rem;color:#cbd5e1;text-transform:uppercase;letter-spacing:.06em}
.foot{margin-top:48px;padding-top:20px;border-top:1px solid var(--border);font-size:.72rem;color:var(--muted);display:flex;justify-content:space-between}
.print-chrome{display:none}
.pdf-hint{position:fixed;top:64px;right:28px;background:#fff;color:var(--ink);font-size:.76rem;padding:10px 14px;border-radius:9px;box-shadow:0 8px 24px rgba(0,0,0,.18);z-index:99;max-width:230px;line-height:1.4;display:flex;align-items:center;gap:8px}
.pdf-hint button{background:none;border:none;color:var(--muted);font-size:.9rem;cursor:pointer;line-height:1;padding:0}
@media print{
    body{background:#fff}
    .toolbar,.pdf-hint{display:none}
    .sheet{margin:0 auto;box-shadow:none;padding:6mm 0 14mm;max-width:100%}
    .action-block,section,.client-box{break-inside:avoid}
    @page{ size:A4; margin:20mm 14mm 22mm 14mm; }
    .print-chrome{display:block}
    .print-watermark{position:fixed;top:45%;left:0;right:0;text-align:center;font-family:'Syne',sans-serif;font-size:5.5rem;font-weight:800;color:rgba(15,23,42,.035);transform:rotate(-28deg);letter-spacing:.05em;z-index:-1;pointer-events:none}
    .print-footer{position:fixed;bottom:6mm;left:14mm;right:14mm;display:flex;justify-content:space-between;align-items:center;font-size:8.5px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:5px;font-family:'DM Sans',sans-serif}
    .print-footer .pf-page::after{content:counter(page)}
}
</style>
</head>
<body>

<div class="toolbar">
    <div class="toolbar__logo">AKRA TECH STUDIO</div>
    <div class="toolbar__actions">
        <?php if ($view === 'admin'): ?>
        <a href="audits.php" class="toolbar__btn toolbar__btn--ghost">← Tornar</a>
        <?php else: ?>
        <a href="informe-client.php?logout=1&a=<?= htmlspecialchars($audit['access_token'] ?? '') ?>" class="toolbar__btn toolbar__btn--ghost"><?= htmlspecialchars($t('logout')) ?></a>
        <?php endif; ?>
        <button class="toolbar__btn" onclick="window.print()"><?= htmlspecialchars($t('download_pdf')) ?></button>
    </div>
</div>

<div class="pdf-hint" id="pdfHint" style="display:none">
    <span>💡</span>
    <span><?= htmlspecialchars($t('print_hint')) ?></span>
    <button onclick="document.getElementById('pdfHint').style.display='none'">✕</button>
</div>
<script>
(function(){
    try {
        if (!localStorage.getItem('akra_pdf_hint_seen')) {
            document.getElementById('pdfHint').style.display = 'flex';
            localStorage.setItem('akra_pdf_hint_seen', '1');
        }
    } catch(e) {}
})();
</script>

<div class="sheet">
    <div class="head">
        <div class="brand">AKRA<br>TECH STUDIO<small><?= htmlspecialchars($t('tagline')) ?></small></div>
        <div class="head-meta">
            Informe núm. <strong>#<?= htmlspecialchars(substr($audit['id'], 0, 10)) ?></strong><br>
            <?= $lang === 'es' ? 'Fecha' : 'Data' ?>: <strong><?= htmlspecialchars($audit['date']) ?></strong><br>
            akratechstudio.es
        </div>
    </div>

    <h1 class="doc-title"><?= htmlspecialchars($t('doc_title')) ?></h1>
    <p class="doc-sub"><?= htmlspecialchars($t('doc_sub')) ?></p>

    <div class="client-box">
        <div><span><?= htmlspecialchars($t('lbl_empresa')) ?>: </span><strong><?= htmlspecialchars($client['company'] ?: $client['name']) ?></strong></div>
        <div><span><?= htmlspecialchars($t('lbl_contacte')) ?>: </span><strong><?= htmlspecialchars($client['name']) ?></strong></div>
        <div><span><?= htmlspecialchars($t('lbl_web')) ?>: </span><strong><?= htmlspecialchars($audit['url'] ?: ($client['web_actual'] ?? '—')) ?></strong></div>
        <div><span><?= htmlspecialchars($t('lbl_cms')) ?>: </span><strong><?= htmlspecialchars($cms_opts[$audit['cms']] ?? '—') ?></strong></div>
        <div><span><?= htmlspecialchars($t('lbl_sector')) ?>: </span><strong><?= htmlspecialchars($client['sector'] ?? '—') ?></strong></div>
        <div><span><?= htmlspecialchars($t('lbl_consultor')) ?>: </span><strong>AKRA Tech Studio</strong></div>
    </div>

    <!-- 1. RESUM EXECUTIU -->
    <section>
        <h2 class="sec-title"><span class="sn">1</span><?= htmlspecialchars($t('sec_1')) ?></h2>
        <div class="score-mini"><?= (int)($audit['valoracio_general'] ?? 0) ?>/10</div>
        <p style="clear:both"></p>
        <?php if (!empty($audit['fortaleses'])): ?>
        <h3 class="sub-title"><?= htmlspecialchars($t('fortaleses')) ?></h3>
        <ul class="report-list"><?php foreach (parseLines($audit['fortaleses']) as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (!empty($audit['debilitats'])): ?>
        <h3 class="sub-title"><?= htmlspecialchars($t('debilitats')) ?></h3>
        <ul class="report-list"><?php foreach (parseLines($audit['debilitats']) as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (!empty($audit['prioritats'])): ?>
        <h3 class="sub-title"><?= htmlspecialchars($t('prioritats')) ?></h3>
        <ul class="report-list"><?php foreach (parseLines($audit['prioritats']) as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>

    <?php
    // Helper per pintar cada secció "puntuació + anàlisi"
    function reportArea($num, $title, $score, $notes, $sense_obs, $extra_title = null, $extra_text = '') {
        ob_start(); ?>
        <section>
            <h2 class="sec-title"><span class="sn"><?= $num ?></span><?= htmlspecialchars($title) ?></h2>
            <div class="score-mini"><?= (int)$score ?>/10</div>
            <p class="body-text" style="clear:both"><?= htmlspecialchars($notes ?: $sense_obs) ?></p>
            <?php if ($extra_title && !empty($extra_text)): ?>
            <h3 class="sub-title"><?= htmlspecialchars($extra_title) ?></h3>
            <ul class="report-list"><?php foreach (parseLines($extra_text) as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </section>
        <?php return ob_get_clean();
    }
    echo reportArea(2, $t('sec_2'), $audit['score_disseny'] ?? 0, $audit['notes_disseny'] ?? '', $t('sense_obs'));
    echo reportArea(3, $t('sec_3'), $audit['score_ux'] ?? 0, $audit['notes_ux'] ?? '', $t('sense_obs'), $t('abandonament'), $audit['punts_abandonament'] ?? '');
    echo reportArea(4, $t('sec_4'), $audit['score_mobile'] ?? 0, $audit['notes_mobile'] ?? '', $t('sense_obs'));
    echo reportArea(5, $t('sec_5'), $audit['score_velocitat'] ?? 0, $audit['notes_velocitat'] ?? '', $t('sense_obs'));
    echo reportArea(6, $t('sec_6'), $audit['score_seo'] ?? 0, $audit['notes_seo'] ?? '', $t('sense_obs'));
    echo reportArea(7, $t('sec_7'), $audit['score_contingut'] ?? 0, $audit['notes_contingut'] ?? '', $t('sense_obs'));
    echo reportArea(8, $t('sec_8'), $audit['score_accessibilitat'] ?? 0, $audit['notes_accessibilitat'] ?? '', $t('sense_obs'));
    echo reportArea(9, $t('sec_9'), $audit['score_seguretat'] ?? 0, $audit['notes_seguretat'] ?? '', $t('sense_obs'));
    echo reportArea(10, $t('sec_10'), $audit['score_conversio'] ?? 0, $audit['notes_conversio'] ?? '', $t('sense_obs'));
    ?>

    <?php if (!empty($audit['notes_competencia'])): ?>
    <section>
        <h2 class="sec-title"><span class="sn">11</span><?= htmlspecialchars($t('sec_11')) ?></h2>
        <p class="body-text"><?= htmlspecialchars($audit['notes_competencia']) ?></p>
    </section>
    <?php endif; ?>

    <!-- 12. PLA D'ACCIÓ -->
    <section>
        <h2 class="sec-title"><span class="sn">12</span><?= htmlspecialchars($t('sec_12')) ?></h2>
        <?php foreach ($action_buckets as $key => $b):
            $items = parseLines($audit[$key] ?? '');
            if (empty($items)) continue;
            $cls_map = ['accions_critiques'=>'crit','accions_importants'=>'imp','accions_recomanables'=>'rec','accions_creixement'=>'gro'];
        ?>
        <div class="action-block action-block--<?= $cls_map[$key] ?>">
            <h4><?= htmlspecialchars($b['label']) ?></h4>
            <div class="sub"><?= htmlspecialchars($b['sub']) ?></div>
            <ul class="report-list"><?php foreach ($items as $it): ?><li><?= htmlspecialchars($it) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($table_rows)): ?>
    <!-- 13. TAULA RESUM -->
    <section>
        <h2 class="sec-title"><span class="sn">13</span><?= htmlspecialchars($t('sec_13')) ?></h2>
        <table class="data-table">
            <thead><tr><th><?= htmlspecialchars($t('th_problema')) ?></th><th><?= htmlspecialchars($t('th_impacte')) ?></th><th><?= htmlspecialchars($t('th_dificultat')) ?></th><th><?= htmlspecialchars($t('th_prioritat')) ?></th><th><?= htmlspecialchars($t('th_solucio')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($table_rows as $r): $pcls = strtolower(str_replace(['í','ó'],['i','o'],$r['prioritat'])); ?>
            <tr>
                <td><?= htmlspecialchars($r['problema']) ?></td>
                <td><?= htmlspecialchars($r['impacte']) ?></td>
                <td><?= htmlspecialchars($r['dificultat']) ?></td>
                <td><span class="priority-pill priority-pill--<?= htmlspecialchars($pcls) ?>"><?= htmlspecialchars($r['prioritat']) ?></span></td>
                <td><?= htmlspecialchars($r['solucio']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- 14. VALORACIÓ FINAL -->
    <section>
        <h2 class="sec-title"><span class="sn">14</span><?= htmlspecialchars($t('sec_14')) ?></h2>
        <div class="final-scores">
            <?php foreach ($score_cats as $key => $label): $sv = (int)($audit['score_' . $key] ?? 0); $slvl = auditScoreLabel($sv); ?>
            <div class="final-score <?= $slvl['class'] ?>"><div class="l"><?= htmlspecialchars($label) ?></div><div class="n"><?= $sv ?>/10</div></div>
            <?php endforeach; ?>
        </div>
        <div class="global-box">
            <div><div class="gl"><?= htmlspecialchars($t('nota_global')) ?></div><div class="gn"><?= $global_avg ?>/10</div></div>
            <span class="badge-level"><?= htmlspecialchars($global_lvl['label']) ?></span>
        </div>
        <?php if (!empty($audit['conclusio'])): ?>
        <h3 class="sub-title" style="margin-top:24px"><?= htmlspecialchars($t('conclusio')) ?></h3>
        <p class="body-text"><?= htmlspecialchars($audit['conclusio']) ?></p>
        <?php endif; ?>
    </section>

    <!-- PROPOSTA ECONÒMICA -->
    <section>
        <h2 class="sec-title"><span class="sn">$</span><?= htmlspecialchars($t('sec_prop')) ?></h2>
        <?php if (empty($proposals)): ?>
        <p class="body-text" style="color:var(--muted)">
        <?php if ($view === 'admin'): ?>
            <?= htmlspecialchars($t('prop_no')) ?> <a href="proposals.php?new=1&client=<?= $client['id'] ?>&audit=<?= $audit['id'] ?>" style="color:var(--accent);font-weight:600"><?= htmlspecialchars($t('prop_crear')) ?></a>
        <?php else: ?>
            <?= htmlspecialchars($t('prop_no_client')) ?>
        <?php endif; ?>
        </p>
        <?php else: ?>
        <table class="data-table prop-table">
            <thead><tr><th><?= htmlspecialchars($t('th_tipus')) ?></th><th><?= htmlspecialchars($t('th_desc')) ?></th><th style="text-align:right"><?= htmlspecialchars($t('th_import')) ?></th><?php if ($view === 'client'): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php $total = 0; foreach ($proposals as $pr): $total += (float)$pr['price']; ?>
            <tr>
                <td><strong><?= htmlspecialchars($type_opts[$pr['type']] ?? $pr['type']) ?></strong></td>
                <td style="color:var(--muted)"><?= htmlspecialchars(mb_strimwidth($pr['description'] ?? '', 0, 90, '…')) ?></td>
                <td class="price"><?= number_format($pr['price'], 0, ',', '.') ?> €</td>
                <?php if ($view === 'client'): ?>
                <td style="text-align:right;white-space:nowrap">
                    <?php if ($pr['status'] === 'aceptada'): ?>
                        <span style="color:#16a34a;font-weight:700;font-size:.82rem">✅ <?= $lang === 'es' ? 'Aceptada' : 'Acceptada' ?><?php if (!empty($pr['accepted_at'])): ?> · <?= date('d/m/Y', strtotime($pr['accepted_at'])) ?><?php endif; ?></span>
                    <?php elseif ($pr['status'] === 'rechazada'): ?>
                        <span style="color:#9ca3af;font-size:.82rem"><?= $lang === 'es' ? 'Rechazada' : 'Rebutjada' ?></span>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('<?= $lang === 'es' ? '¿Confirmas que aceptas esta propuesta?' : 'Confirmes que acceptes esta proposta?' ?>')">
                            <input type="hidden" name="action" value="accept_proposal">
                            <input type="hidden" name="proposal_id" value="<?= $pr['id'] ?>">
                            <button type="submit" style="background:#16a34a;color:white;border:none;padding:7px 14px;border-radius:6px;font-size:.8rem;font-weight:700;cursor:pointer">✓ <?= $lang === 'es' ? 'Aceptar' : 'Acceptar' ?></button>
                        </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="2" style="text-align:right;font-weight:700"><?= htmlspecialchars($t('total_proposat')) ?></td><td class="price" style="font-size:1.1rem"><?= number_format($total, 0, ',', '.') ?> €</td><?php if ($view === 'client'): ?><td></td><?php endif; ?></tr>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <?php if ($view === 'client'):
        $c_invoices = getInvoices($client['id']);
        $c_jobs     = getJobs($client['id']);
    ?>
    <?php if (!empty($c_invoices)): ?>
    <section>
        <h2 class="sec-title"><span class="sn">€</span><?= $lang === 'es' ? 'Facturas' : 'Factures' ?></h2>
        <table class="data-table prop-table">
            <thead><tr><th><?= $lang === 'es' ? 'Número' : 'Número' ?></th><th><?= $lang === 'es' ? 'Fecha' : 'Data' ?></th><th><?= $lang === 'es' ? 'Estado' : 'Estat' ?></th><th style="text-align:right">Total</th><th style="text-align:right"><?= $lang === 'es' ? 'Pendiente' : 'Pendent' ?></th></tr></thead>
            <tbody>
            <?php foreach ($c_invoices as $inv): if ($inv['status'] === 'cancelled') continue; $isum = invoicePaymentSummary($inv); ?>
            <tr>
                <td><strong><?= htmlspecialchars($inv['number']) ?></strong></td>
                <td style="color:var(--muted)"><?= date('d/m/Y', strtotime($inv['date'])) ?></td>
                <td style="color:var(--muted)"><?= $isum['status'] === 'paid' ? ($lang === 'es' ? 'Pagada' : 'Pagada') : ($isum['status'] === 'partial' ? ($lang === 'es' ? 'Parcial' : 'Parcial') : ($lang === 'es' ? 'Pendiente' : 'Pendent')) ?></td>
                <td class="price"><?= number_format($isum['total'], 2, ',', '.') ?> €</td>
                <td class="price" style="color:<?= $isum['due'] > 0 ? '#dc2626' : '#16a34a' ?>"><?= number_format(max(0,$isum['due']), 2, ',', '.') ?> €</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($c_jobs)): ?>
    <section>
        <h2 class="sec-title"><span class="sn">✓</span><?= $lang === 'es' ? 'Trabajos' : 'Treballs' ?></h2>
        <table class="data-table prop-table">
            <thead><tr><th><?= $lang === 'es' ? 'Trabajo' : 'Treball' ?></th><th><?= $lang === 'es' ? 'Inicio' : 'Inici' ?></th><th><?= $lang === 'es' ? 'Estado' : 'Estat' ?></th></tr></thead>
            <tbody>
            <?php foreach ($c_jobs as $j): $js = getJobStatusOptions()[$j['status'] ?? 'pressupostat']; ?>
            <tr>
                <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                <td style="color:var(--muted)"><?= !empty($j['start_date']) ? date('d/m/Y', strtotime($j['start_date'])) : '—' ?></td>
                <td style="color:var(--muted)"><?= htmlspecialchars($js['label']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
    <?php endif; ?>

    <div class="foot">
        <span>AKRA Tech Studio — akratechstudio.es</span>
        <span><?= htmlspecialchars($t('foot_doc')) ?> <?= date('d/m/Y') ?></span>
    </div>
</div>

<div class="print-chrome print-watermark">AKRA</div>
<div class="print-chrome print-footer">
    <span>AKRA Tech Studio · <?= htmlspecialchars($t('confidential')) ?></span>
    <span class="pf-page"></span>
</div>

</body>
</html>
