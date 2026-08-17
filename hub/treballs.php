<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rate') {
    $job = getJob($_POST['job_id'] ?? '');
    if ($job && $job['client_id'] === $client['id'] && !getSatisfaction($job['id'])) {
        saveSatisfactionRating($job['id'], (int)($_POST['rating'] ?? 0), $_POST['comment'] ?? '');
    }
    header('Location: treballs.php?rated=1');
    exit;
}

$jobs       = getJobs($client['id']);
$job_status = getJobStatusOptions();
$job_types  = getJobTypeOptions();
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('jobs_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('jobs_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= count($jobs) ?> <?= htmlspecialchars(hubT('jobs_sub', $lang)) ?></p>

    <?php if (isset($_GET['rated'])): ?>
    <div class="hub-alert hub-alert--success"><?= htmlspecialchars(hubT('jobs_rated_ok', $lang)) ?></div>
    <?php endif; ?>

    <?php if (empty($jobs)): ?>
    <div class="hub-card"><div class="hub-empty"><?= htmlspecialchars(hubT('jobs_empty', $lang)) ?></div></div>
    <?php else: foreach ($jobs as $j):
        $st       = $job_status[$j['status'] ?? 'pressupostat'] ?? $job_status['pressupostat'];
        $hours    = getJobTotalHours($j['id']);
        $sat      = getSatisfaction($j['id']);
        $progress = match($j['status'] ?? '') {
            'pressupostat' => 5, 'en_curs' => 55, 'en_pausa' => 55, 'acabat' => 100, 'cancelat' => 0, default => 5,
        };
    ?>
    <div class="hub-card">
        <div class="hub-card-header">
            <div>
                <div class="hub-card-title"><?= htmlspecialchars($j['title'] ?? 'Treball') ?></div>
                <div style="font-size:.78rem;color:var(--h-muted);margin-top:2px"><?= htmlspecialchars(hubTStatus($job_types[$j['type'] ?? ''] ?? '', $lang)) ?></div>
            </div>
            <span class="hub-badge hub-badge--<?= str_replace('badge-', '', $st['class']) ?>"><?= htmlspecialchars(hubTStatus($st['label'], $lang)) ?></span>
        </div>
        <div class="hub-card-body">
            <?php if (!empty($j['description'])): ?>
            <p style="font-size:.88rem;color:#374151;margin-bottom:14px;white-space:pre-wrap"><?= htmlspecialchars($j['description']) ?></p>
            <?php endif; ?>

            <div class="hub-progress"><div class="hub-progress-fill" style="width:<?= $progress ?>%"></div></div>

            <div style="display:flex;gap:26px;flex-wrap:wrap;margin-top:16px;font-size:.82rem;color:var(--h-muted)">
                <div><strong style="color:var(--h-text)"><?= !empty($j['start_date']) ? date('d/m/Y', strtotime($j['start_date'])) : '—' ?></strong><br><?= htmlspecialchars(hubT('jobs_start_date', $lang)) ?></div>
                <?php if (!empty($j['end_date'])): ?>
                <div><strong style="color:var(--h-text)"><?= date('d/m/Y', strtotime($j['end_date'])) ?></strong><br><?= htmlspecialchars(hubT('jobs_end_date', $lang)) ?></div>
                <?php endif; ?>
                <div><strong style="color:var(--h-text)"><?= $hours ?> h</strong><br><?= htmlspecialchars(hubT('jobs_hours', $lang)) ?></div>
            </div>

            <?php if (($j['status'] ?? '') === 'acabat'): ?>
                <?php if ($sat): ?>
                <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--h-border)">
                    <div style="font-size:.82rem;color:var(--h-muted);margin-bottom:4px"><?= htmlspecialchars(hubT('jobs_your_rating', $lang)) ?></div>
                    <div style="font-size:1.1rem"><?= str_repeat('⭐', (int)$sat['rating']) . str_repeat('☆', 5 - (int)$sat['rating']) ?></div>
                    <?php if (!empty($sat['comment'])): ?><p style="font-size:.85rem;color:#374151;margin-top:6px"><?= htmlspecialchars($sat['comment']) ?></p><?php endif; ?>
                </div>
                <?php else: ?>
                <form method="POST" style="margin-top:18px;padding-top:16px;border-top:1px solid var(--h-border)" onsubmit="return document.getElementById('rating-<?= $j['id'] ?>').value > 0">
                    <input type="hidden" name="action" value="rate">
                    <input type="hidden" name="job_id" value="<?= htmlspecialchars($j['id']) ?>">
                    <input type="hidden" name="rating" id="rating-<?= $j['id'] ?>" value="0">
                    <div style="font-size:.86rem;font-weight:700;margin-bottom:8px"><?= htmlspecialchars(hubT('jobs_rate_prompt', $lang)) ?></div>
                    <div class="hub-star-picker" data-target="rating-<?= $j['id'] ?>" style="font-size:1.6rem;letter-spacing:4px;cursor:pointer;margin-bottom:10px">☆☆☆☆☆</div>
                    <textarea name="comment" placeholder="<?= htmlspecialchars(hubT('jobs_rate_comment', $lang)) ?>" style="margin-bottom:10px"></textarea>
                    <button type="submit" class="hub-btn hub-btn--gold hub-btn--sm"><?= htmlspecialchars(hubT('jobs_rate_submit', $lang)) ?></button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
<script>
document.querySelectorAll('.hub-star-picker').forEach(picker => {
    const target = document.getElementById(picker.dataset.target);
    picker.addEventListener('click', e => {
        const rect = picker.getBoundingClientRect();
        const pct = (e.clientX - rect.left) / rect.width;
        const stars = Math.max(1, Math.min(5, Math.ceil(pct * 5)));
        target.value = stars;
        picker.textContent = '⭐'.repeat(stars) + '☆'.repeat(5 - stars);
    });
});
</script>
</body></html>
