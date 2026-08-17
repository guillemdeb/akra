<?php
// admin/cron_recurring.php
//
// Genera automàticament les factures recurrents que ja toquen.
//
// ÚS 1 — Programador de tasques de Windows (recomanat, WAMP):
//   Programa: C:\wamp64\bin\php\phpX.X.X\php.exe
//   Arguments: "C:\wamp64\www\akra\admin\cron_recurring.php"
//   Freqüència: cada dia, per exemple a les 07:00
//
// ÚS 2 — Via navegador / cron d'hosting amb un token secret a l'URL:
//   https://el-teu-domini.es/admin/cron_recurring.php?token=CANVIA_AQUEST_TOKEN
//   (canvia el token per un valor llarg i secret abans de fer-lo servir així)

require_once __DIR__ . '/includes/core.php';

$SECRET_TOKEN = 'CANVIA_AQUEST_TOKEN'; // ⚠️ canvia açò si l'executaràs via URL

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Accés per navegador/cron d'hosting: exigeix el token per seguretat
    if (($_GET['token'] ?? '') !== $SECRET_TOKEN || $SECRET_TOKEN === 'CANVIA_AQUEST_TOKEN') {
        http_response_code(403);
        die('Accés no autoritzat. Configura un token secret dins de cron_recurring.php.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

$created = generateDueRecurringInvoices();
$created_domains = generateDueDomainRenewalInvoices();

if (empty($created) && empty($created_domains)) {
    echo "Cap factura recurrent ni renovació de domini pendent de generar hui (" . date('d/m/Y H:i') . ").\n";
} else {
    if (!empty($created)) echo "Generades " . count($created) . " factura(es) recurrent(s): " . implode(', ', $created) . "\n";
    if (!empty($created_domains)) echo "Generades " . count($created_domains) . " factura(es) de renovació de domini/hosting: " . implode(', ', $created_domains) . "\n";
}
