<?php
// admin/cron_alerts.php
//
// Envia un email de resum diari (factures vençudes, dominis/hostings a
// renovar, seguiments de clients pendents) a l'email configurat a
// Configuració → Email. Si no hi ha res a avisar, no s'envia cap email.
//
// ÚS 1 — Programador de tasques de Windows (recomanat):
//   Programa: C:\wamp64\bin\php\phpX.X.X\php.exe
//   Arguments: "C:\wamp64\www\akra\admin\cron_alerts.php"
//   Freqüència: cada dia, per exemple a les 08:00
//
// ÚS 2 — Via navegador / cron d'hosting amb un token secret a l'URL:
//   https://el-teu-domini.es/admin/cron_alerts.php?token=CANVIA_AQUEST_TOKEN

require_once __DIR__ . '/includes/core.php';

$SECRET_TOKEN = 'CANVIA_AQUEST_TOKEN'; // ⚠️ canvia açò si l'executaràs via URL

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (($_GET['token'] ?? '') !== $SECRET_TOKEN || $SECRET_TOKEN === 'CANVIA_AQUEST_TOKEN') {
        http_response_code(403);
        die('Accés no autoritzat. Configura un token secret dins de cron_alerts.php.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

$result = sendDailyAlertsEmail();
$purged = purgeOldTrash(30);

if (!$result['ok']) {
    echo "Error: " . $result['error'] . "\n";
} elseif (empty($result['sent'])) {
    echo "Cap avís pendent hui (" . date('d/m/Y H:i') . "). No s'ha enviat cap email.\n";
} else {
    echo "Email de resum enviat correctament (" . date('d/m/Y H:i') . ").\n";
}
if ($purged > 0) echo "Paperera: {$purged} element(s) purgat(s) definitivament (+30 dies).\n";
