<?php
/**
 * API: Verificació AJAX del codi personal PWA
 * GET api_verificar_pwa.php?c=XX-0000-XX
 * Retorna JSON { valid: bool, nombre?: string, error?: string }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

// Rate limiting bàsic (evitar brute force)
session_start();
$_SESSION['pwa_intents'] = ($_SESSION['pwa_intents'] ?? 0) + 1;
if ($_SESSION['pwa_intents'] > 30) {
    http_response_code(429);
    echo json_encode(['valid' => false, 'error' => 'Massa intents. Torna-ho a intentar més tard.']);
    exit();
}

require_once "config.php";
require_once "includes/pwa_codigos.php";

$codi    = strtoupper(trim($_GET['c'] ?? ''));
$resultat = pwa_validar_codi($codi);

echo json_encode([
    'valid'  => $resultat['valid'],
    'nombre' => $resultat['nombre'] ?? null,
    'error'  => $resultat['error']  ?? null,
]);
