<?php
require_once 'includes/hub-core.php';
hubRequireLogin();
$client = hubCurrentClient();
$lang   = getClientHubLang($client);

$month_param = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month_param)) $month_param = date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['approve', 'request_changes'])) {
    $decision = $_POST['action'] === 'approve' ? 'acceptat' : 'canvis_sollicitats';
    decideCalendarApproval($client['id'], $month_param, $decision, trim($_POST['comment'] ?? ''));
    header('Location: calendari.php?month=' . $month_param . '&decided=1');
    exit;
}

$approval = getCalendarApproval($client['id'], $month_param);

$first_of_month = $month_param . '-01';
$ts_first       = strtotime($first_of_month);
$days_in_month  = (int)date('t', $ts_first);
$first_weekday  = (int)date('N', $ts_first); // 1=Dilluns ... 7=Diumenge
$today_str      = date('Y-m-d');
$today_month    = date('Y-m');

$prev_month = date('Y-m', strtotime($first_of_month . ' -1 month'));
$next_month = date('Y-m', strtotime($first_of_month . ' +1 month'));

$platforms  = getSocialPlatformOptions();
$formats    = getSocialFormatOptions();
$statuses   = getSocialStatusOptions();

$posts_by_day = [];
foreach (getSocialPosts($client['id']) as $p) {
    if (substr($p['date'] ?? '', 0, 7) === $month_param) {
        $day = (int)substr($p['date'], 8, 2);
        $posts_by_day[$day][] = $p;
    }
}
$total_this_month = array_sum(array_map('count', $posts_by_day));

$platform_dot = [
    'instagram' => '#e1306c', 'facebook' => '#1877f2', 'stories' => '#8b5cf6',
    'web' => '#0ea5e9', 'newsletter' => '#f59e0b', 'altres' => '#6b7280',
];

$months_names  = hubTArr('cal_months', $lang);
$weekday_names = hubTArr('cal_weekdays', $lang);
$month_label   = ($months_names[(int)date('n', $ts_first) - 1] ?? '') . ' ' . date('Y', $ts_first);
?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars(hubT('cal_title', $lang)) ?> · AKRA Tech Studio</title><meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/hub.css">
</head><body>
<?php include 'includes/hub-nav.php'; ?>

<div class="hub-main">
    <h1 class="hub-page-title"><?= htmlspecialchars(hubT('cal_title', $lang)) ?></h1>
    <p class="hub-page-subtitle"><?= htmlspecialchars(hubT('cal_sub', $lang)) ?></p>

    <?php if (isset($_GET['decided'])): ?>
    <div class="hub-alert hub-alert--success">
        <?= $approval['status'] === 'acceptat' ? htmlspecialchars(hubT('cal_approval_accepted', $lang)) : htmlspecialchars(hubT('cal_approval_changes', $lang)) ?>
    </div>
    <?php endif; ?>

    <?php if ($approval && $approval['status'] === 'pendent'): ?>
    <div class="hub-card" style="border-color:rgba(201,168,76,.4)">
        <div class="hub-card-body">
            <div style="font-weight:700;margin-bottom:6px"><?= htmlspecialchars(hubT('cal_approval_pending_title', $lang)) ?></div>
            <p style="font-size:.85rem;color:var(--h-muted);margin-bottom:14px">
                <?= htmlspecialchars(hubT('cal_approval_deadline', $lang)) ?> <strong style="color:var(--h-text)"><?= date('d/m/Y', calendarApprovalDeadline($approval)) ?></strong><?= htmlspecialchars(hubT('cal_approval_deadline_end', $lang)) ?>
            </p>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="hub-btn hub-btn--gold"><?= htmlspecialchars(hubT('cal_approval_accept', $lang)) ?></button>
                </form>
                <button type="button" class="hub-btn" onclick="document.getElementById('request-changes-box').style.display='block'"><?= htmlspecialchars(hubT('cal_approval_request', $lang)) ?></button>
            </div>
            <form method="POST" id="request-changes-box" style="display:none;margin-top:14px">
                <input type="hidden" name="action" value="request_changes">
                <div class="hub-form-group" style="margin-bottom:10px">
                    <textarea name="comment" placeholder="<?= htmlspecialchars(hubT('cal_approval_comment_ph', $lang)) ?>"></textarea>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="submit" class="hub-btn hub-btn--primary hub-btn--sm"><?= htmlspecialchars(hubT('cal_approval_send', $lang)) ?></button>
                    <button type="button" class="hub-btn hub-btn--sm" onclick="document.getElementById('request-changes-box').style.display='none'"><?= htmlspecialchars(hubT('cal_approval_cancel', $lang)) ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($approval && in_array($approval['status'], ['acceptat', 'acceptat_auto', 'canvis_sollicitats'])): ?>
    <div class="hub-alert <?= $approval['status'] === 'canvis_sollicitats' ? 'hub-alert--error' : 'hub-alert--success' ?>">
        <?php if ($approval['status'] === 'acceptat'): ?><?= htmlspecialchars(hubT('cal_approval_accepted', $lang)) ?>
        <?php elseif ($approval['status'] === 'acceptat_auto'): ?><?= htmlspecialchars(hubT('cal_approval_accepted_auto', $lang)) ?>
        <?php else: ?><?= htmlspecialchars(hubT('cal_approval_changes', $lang)) ?>
            <?php if (!empty($approval['client_comment'])): ?><br><em><?= htmlspecialchars(hubT('cal_approval_your_comment', $lang)) ?> «<?= htmlspecialchars($approval['client_comment']) ?>»</em><?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="hub-card">
        <div class="hub-cal-toolbar">
            <a href="calendari.php?month=<?= $prev_month ?>" class="hub-btn hub-btn--sm" aria-label="Mes anterior">‹</a>
            <div class="hub-cal-month-label"><?= htmlspecialchars($month_label) ?> <?php if ($total_this_month): ?><span class="hub-badge hub-badge--gray"><?= $total_this_month ?></span><?php endif; ?></div>
            <a href="calendari.php?month=<?= $next_month ?>" class="hub-btn hub-btn--sm" aria-label="Mes següent">›</a>
            <?php if ($month_param !== $today_month): ?>
            <a href="calendari.php" class="hub-btn hub-btn--sm hub-btn--gold" style="margin-left:auto"><?= htmlspecialchars(hubT('cal_today', $lang)) ?></a>
            <?php endif; ?>
        </div>

        <div class="hub-cal-grid hub-cal-weekdays">
            <?php foreach ($weekday_names as $wd): ?><div class="hub-cal-weekday"><?= htmlspecialchars($wd) ?></div><?php endforeach; ?>
        </div>

        <div class="hub-cal-grid">
            <?php for ($i = 1; $i < $first_weekday; $i++): ?><div class="hub-cal-cell hub-cal-cell--empty"></div><?php endfor; ?>

            <?php for ($day = 1; $day <= $days_in_month; $day++):
                $date_str = $month_param . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $is_today = $date_str === $today_str;
                $day_posts = $posts_by_day[$day] ?? [];
            ?>
            <div class="hub-cal-cell <?= $is_today ? 'hub-cal-cell--today' : '' ?>">
                <div class="hub-cal-daynum"><?= $day ?></div>
                <?php foreach (array_slice($day_posts, 0, 3) as $p):
                    $dot = $platform_dot[$p['platform'] ?? 'altres'] ?? '#6b7280';
                    $label = $p['theme'] ?: ($p['series'] ?: ($platforms[$p['platform'] ?? ''] ?? ''));
                    $title = trim(($platforms[$p['platform'] ?? ''] ?? '') . ' / ' . ($formats[$p['format'] ?? ''] ?? '') . ' — ' . ($p['theme'] ?? ''));
                ?>
                <div class="hub-cal-pill" style="border-left-color:<?= $dot ?>" title="<?= htmlspecialchars($title) ?>">
                    <span class="hub-cal-pill-dot" style="background:<?= $dot ?>"></span><span class="hub-cal-pill-label"><?= htmlspecialchars(mb_strimwidth($label, 0, 16, '…')) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (count($day_posts) > 3): ?><div class="hub-cal-more">+<?= count($day_posts) - 3 ?></div><?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>

        <?php if ($total_this_month === 0): ?>
        <div class="hub-empty"><?= htmlspecialchars(hubT('cal_empty_month', $lang)) ?></div>
        <?php endif; ?>
    </div>
</div>
</body></html>
