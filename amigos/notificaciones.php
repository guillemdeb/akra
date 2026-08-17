<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit(); }
require_once "config.php";
$uid = $_SESSION['usuario_id'];

// Marcar com llegides
$pdo->prepare("UPDATE notificaciones SET leida=1 WHERE usuario_id=:u")->execute(['u'=>$uid]);

// Obtenir notificacions
try {
    $s = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id=:u ORDER BY fecha_creacion DESC LIMIT 50");
    $s->execute(['u'=>$uid]);
    $notifs = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $notifs = []; }
?>
<?php $page_title = 'Notificacions'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<!DOCTYPE html><html lang="ca">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notificacions - RedAmigos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/styles.css">
</head><?php $active_page='notificaciones'; require_once "includes/navbar.php"; ?>
<div class="page-container">
<div class="card">
<div class="card-header"><i class="fas fa-bell"></i> Notificacions</div>
<?php if (empty($notifs)): ?>
<div class="empty-state"><div class="empty-icon"><i class="fas fa-bell-slash"></i></div><h3>Cap notificació</h3></div>
<?php else: ?>
<?php foreach($notifs as $n):
  $data = isset($n['fecha_creacion']) ? (new DateTime($n['fecha_creacion']))->format('d/m H:i') : '';
  $icona = match($n['tipo'] ?? '') { 'mensaje'=>'fa-comment','sistema'=>'fa-info-circle', default=>'fa-bell' };
?>
<div class="notif-item">
<div class="notif-icon"><i class="fas <?=$icona?>"></i></div>
<div class="notif-text"><?=htmlspecialchars($n['contenido']??'')?></div>
<div class="notif-time"><?=$data?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div></div>
</body></html>
