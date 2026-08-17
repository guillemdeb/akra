<?php
/**
 * API: Verificació de codi d'invitació en temps real (AJAX)
 * GET: api_verificar_codi.php?codi=XXXX-XXXX-XXXX
 * Retorna JSON: { valid: bool, missatge: string }
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once "config.php";
require_once "includes/codigos_helper.php";

$codi     = strtoupper(trim($_GET['codi'] ?? ''));
$validacio = ra_validar_codigo($codi);

echo json_encode([
    'valid'    => $validacio['valid'],
    'missatge' => $validacio['error'] ?? 'Codi vàlid',
]);
