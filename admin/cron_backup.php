<?php
// admin/cron_backup.php
//
// Genera una còpia de seguretat automàtica (segons la configuració de
// Configuració → Còpies de seguretat automàtiques) i l'esborra si ha
// caducat la retenció configurada. No fa res si l'opció està desactivada.
//
// ÚS 1 — Programador de tasques de Windows (recomanat):
//   Programa: C:\wamp64\bin\php\phpX.X.X\php.exe
//   Arguments: "C:\wamp64\www\akra\admin\cron_backup.php"
//   Freqüència: cada dia, per exemple a les 03:30
//
// ÚS 2 — Via navegador / cron d'hosting amb un token secret a l'URL:
//   https://el-teu-domini.es/admin/cron_backup.php?token=CANVIA_AQUEST_TOKEN

require_once __DIR__ . '/includes/core.php';

$SECRET_TOKEN = 'CANVIA_AQUEST_TOKEN'; // ⚠️ canvia açò si l'executaràs via URL

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (($_GET['token'] ?? '') !== $SECRET_TOKEN || $SECRET_TOKEN === 'CANVIA_AQUEST_TOKEN') {
        http_response_code(403);
        die('Accés no autoritzat. Configura un token secret dins de cron_backup.php.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

$result = runScheduledBackup();

if (!$result['ok']) {
    echo "Res a fer: " . $result['error'] . "\n";
} else {
    echo "Còpia de seguretat creada: " . basename($result['path']) . " (" . date('d/m/Y H:i') . ")\n";
    if ($result['purged'] > 0) echo $result['purged'] . " còpia(es) antiga(es) esborrada(es) per retenció.\n";
}
