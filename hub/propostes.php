<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

if (isset($_GET['download_pdf'])) {
    $prop = getProposal($_GET['download_pdf']);
    if (!$prop || $prop['client_id'] !== $client['id']) { http_response_code(403); die('No autoritzat.'); }
    $pdf_lang = in_array($_GET['lang'] ?? '', ['ca', 'es']) ? $_GET['lang'] : ($lang === 'es' ? 'es' : 'ca');
    $result = generateProposalPdf($prop['id'], $pdf_lang);
    if (!$result['ok']) die('No s\'ha pogut generar el PDF.');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Content-Length: ' . strlen($result['pdf']));
    echo $result['pdf'];
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['accept', 'reject'])) {
    $prop = getProposal($_POST['id'] ?? '');
    if ($prop && $prop['client_id'] === $client['id'] && $prop['status'] === 'enviada') {
        $prop['status'] = $_POST['action'] === 'accept' ? 'aceptada' : 'rechazada';
        saveProposal($prop);
        advanceClientStage($client['id'], $prop['status'] === 'aceptada' ? 'guanyat' : 'perdut');
        saveContact([
            'id' => generateId(), 'client_id' => $client['id'], 'date' => date('Y-m-d'),
            'channel' => 'hub', 'direction' => 'client_jo',
            'message' => $prop['status'] === 'aceptada' ? 'Proposta acceptada des del portal.' : 'Proposta rebutjada des del portal.',
            'response' => '', 'status' => 'pendent', 'follow_up' => '',
        ]);
    }
    header('Location: propostes.php?done=1');
    exit;
}

$proposals = getProposals($client['id']);
$type_opts = getProposalTypeOptions();
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('prop_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('prop_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= htmlspecialchars(hubT('prop_sub', $lang)) ?></p>

    <?php if (isset($_GET['done'])): ?>
    <div class="hub-alert hub-alert--success"><?= htmlspecialchars(hubT('prop_done', $lang)) ?></div>
    <?php endif; ?>

    <?php if (empty($proposals)): ?>
    <div class="hub-card"><div class="hub-empty"><?= htmlspecialchars(hubT('prop_empty', $lang)) ?></div></div>
    <?php else: foreach ($proposals as $p): $sl = proposalStatusLabel($p['status'] ?? 'borrador'); ?>
    <div class="hub-card">
        <div class="hub-card-header">
            <div>
                <div class="hub-card-title"><?= htmlspecialchars(hubTStatus($type_opts[$p['type'] ?? ''] ?? 'Proposta', $lang)) ?></div>
                <div style="font-size:.78rem;color:var(--h-muted);margin-top:2px"><?= !empty($p['date']) ? date('d/m/Y', strtotime($p['date'])) : '' ?></div>
            </div>
            <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $sl['class']) ?>"><?= htmlspecialchars(hubTStatus($sl['label'], $lang)) ?></span>
        </div>
        <div class="hub-card-body">
            <?php if (!empty($p['description'])): ?>
            <p style="font-size:.88rem;color:#374151;margin-bottom:14px;white-space:pre-wrap"><?= htmlspecialchars($p['description']) ?></p>
            <?php endif; ?>
            <div class="hub-row-amount" style="margin-bottom:16px"><?= number_format($p['price'] ?? 0, 2, ',', '.') ?> €</div>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="propostes.php?download_pdf=<?= htmlspecialchars($p['id']) ?>" class="hub-btn hub-btn--sm"><?= htmlspecialchars(hubT('prop_pdf', $lang)) ?></a>
                <?php if (($p['status'] ?? '') === 'enviada'): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                    <button type="submit" class="hub-btn hub-btn--gold hub-btn--sm" onclick="return confirm('<?= htmlspecialchars(hubT('prop_confirm_accept', $lang)) ?>')"><?= htmlspecialchars(hubT('prop_accept', $lang)) ?></button>
                </form>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                    <button type="submit" class="hub-btn hub-btn--danger hub-btn--sm" onclick="return confirm('<?= htmlspecialchars(hubT('prop_confirm_reject', $lang)) ?>')"><?= htmlspecialchars(hubT('prop_reject', $lang)) ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
</body></html>
