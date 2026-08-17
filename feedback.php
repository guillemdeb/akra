<?php
require_once __DIR__ . '/admin/includes/core.php';

$job_id = $_GET['job'] ?? '';
$job = $job_id ? getJob($job_id) : null;
$client = $job ? getClient($job['client_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job) {
    saveSatisfactionRating($job_id, $_POST['rating'] ?? 0, $_POST['comment'] ?? '');
    header('Location: feedback.php?job=' . urlencode($job_id) . '&done=1'); exit;
}

$rating = (int)($_GET['rating'] ?? 0);
if ($job && $rating >= 1 && $rating <= 5 && !isset($_GET['done'])) {
    saveSatisfactionRating($job_id, $rating);
}
$existing = $job ? getSatisfaction($job_id) : null;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gràcies per la teua opinió</title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;background:#0a0a0a;padding:20px}
.box{background:#fff;border-radius:20px;padding:44px 40px;max-width:420px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.35)}
h1{font-family:'Syne',sans-serif;font-size:1.4rem;margin-bottom:14px}
p{color:#6b7280;font-size:.92rem;line-height:1.6;margin-bottom:18px}
.stars{font-size:2.2rem;margin-bottom:18px}
textarea{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:10px 12px;font-family:inherit;font-size:.88rem;margin-bottom:14px;resize:vertical}
button{background:#0a0a0a;color:white;border:none;padding:12px 24px;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer}
</style>
</head>
<body>
<div class="box">
<?php if (!$job): ?>
    <h1>Enllaç no vàlid</h1>
    <p>No hem trobat el treball associat a este enllaç.</p>
<?php elseif (isset($_GET['done'])): ?>
    <h1>🙏 Gràcies!</h1>
    <p>Hem rebut la teua valoració sobre <strong><?= htmlspecialchars($job['title']) ?></strong>. Ens ajuda molt a millorar.</p>
<?php elseif ($rating >= 1 && $rating <= 5): ?>
    <h1>Gràcies per valorar-nos!</h1>
    <div class="stars"><?= str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating) ?></div>
    <p>Vols afegir algun comentari? (Opcional — ajuda'ns a saber què podem millorar.)</p>
    <form method="POST">
        <input type="hidden" name="rating" value="<?= $rating ?>">
        <textarea name="comment" rows="3" placeholder="El teu comentari (opcional)..."></textarea>
        <button type="submit">Enviar</button>
    </form>
<?php else: ?>
    <h1>Com valores <?= htmlspecialchars($job['title']) ?>?</h1>
    <p>Fes clic en el nombre d'estreles que vulgues donar.</p>
    <div>
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <a href="feedback.php?job=<?= urlencode($job_id) ?>&rating=<?= $i ?>" style="font-size:2rem;text-decoration:none;margin:0 4px">⭐</a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>
