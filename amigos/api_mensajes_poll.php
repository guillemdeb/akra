<?php
/**
 * API: Obté nous missatges (polling AJAX)
 * Crida: GET api_mensajes_poll.php?con=USER_ID&desde=TIMESTAMP
 * Retorna: JSON { missatges: [...], ultima_id: N }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticat']);
    exit();
}

require_once "config.php";

$jo       = (int)$_SESSION['usuario_id'];
$amb      = (int)($_GET['con'] ?? 0);
$ultima_id = (int)($_GET['ultima_id'] ?? 0);

if ($amb <= 0) {
    echo json_encode(['missatges' => [], 'ultima_id' => $ultima_id]);
    exit();
}

// Verificar amistat
$s = $pdo->prepare("SELECT COUNT(*) FROM amistades 
    WHERE ((usuario_id=:jo AND amigo_id=:ell) OR (amigo_id=:jo AND usuario_id=:ell))
    AND estado='aceptada'");
$s->execute(['jo' => $jo, 'ell' => $amb]);
if (!$s->fetchColumn()) {
    echo json_encode(['error' => 'No sou amics']);
    exit();
}

// Obtenir missatges nous (id > ultima_id)
$sql = "SELECT m.id, m.remitente_id, m.mensaje, 
               DATE_FORMAT(m.fecha_envio, '%H:%i') as hora,
               u.nombre as nom_remitent
        FROM mensajes m
        JOIN usuarios u ON u.id = m.remitente_id
        WHERE ((m.remitente_id = :jo AND m.destinatario_id = :ell) 
            OR (m.remitente_id = :ell AND m.destinatario_id = :jo))
          AND m.id > :ultima_id
        ORDER BY m.fecha_envio ASC
        LIMIT 50";

$s = $pdo->prepare($sql);
$s->execute(['jo' => $jo, 'ell' => $amb, 'ultima_id' => $ultima_id]);
$msgs = $s->fetchAll(PDO::FETCH_ASSOC);

// Marcar com llegits els missatges rebuts
if (!empty($msgs)) {
    $pdo->prepare("UPDATE mensajes SET leido = 1 
                   WHERE remitente_id = :ell AND destinatario_id = :jo AND leido = 0")
        ->execute(['ell' => $amb, 'jo' => $jo]);
}

// Nova ultima_id
$nova_ultima = $ultima_id;
if (!empty($msgs)) {
    $nova_ultima = (int)end($msgs)['id'];
}

echo json_encode([
    'missatges' => $msgs,
    'ultima_id' => $nova_ultima,
    'jo'        => $jo
]);
