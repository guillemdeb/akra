<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$evento_id = (int)($_GET['id'] ?? 0);

if ($evento_id <= 0) {
    header("Location: eventos.php");
    exit();
}

// Comprovar que l'esdeveniment existeix i està actiu
$sql = "SELECT e.*, u.nombre as creador_nombre,
        (SELECT COUNT(*) FROM evento_participantes WHERE evento_id = e.id AND estado = 'confirmado') as num_participantes
        FROM eventos e
        JOIN usuarios u ON e.creador_id = u.id
        WHERE e.id = :evento_id AND e.estado = 'activo'";
$stmt = $pdo->prepare($sql);
$stmt->execute(['evento_id' => $evento_id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    $_SESSION['error'] = "El evento no existe o ya no está disponible";
    header("Location: eventos.php");
    exit();
}

// Comprovar que no està ja apuntat
$sql = "SELECT id FROM evento_participantes WHERE evento_id = :evento_id AND usuario_id = :usuario_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);
$ya_apuntado = $stmt->fetch();

if ($ya_apuntado) {
    $_SESSION['error'] = "Ya estás apuntado a este evento";
    header("Location: ver_evento.php?id=" . $evento_id);
    exit();
}

// Comprovar plazas disponibles
if ($evento['plazas_max'] && $evento['num_participantes'] >= $evento['plazas_max']) {
    $_SESSION['error'] = "Este evento ya no tiene plazas disponibles";
    header("Location: ver_evento.php?id=" . $evento_id);
    exit();
}

// Apuntar-se
try {
    $sql = "INSERT INTO evento_participantes (evento_id, usuario_id, estado) VALUES (:evento_id, :usuario_id, 'confirmado')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);
    
    // Crear notificació per al creador
    $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
            VALUES (:usuario_id, 'evento', :contenido, :enlace)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'usuario_id' => $evento['creador_id'],
        'contenido' => $_SESSION['usuario_nombre'] . ' se ha apuntado a tu evento: ' . $evento['titulo'],
        'enlace' => 'ver_evento.php?id=' . $evento_id
    ]);
    
    $_SESSION['success'] = "¡Te has apuntado correctamente! Nos vemos en el evento";
    header("Location: ver_evento.php?id=" . $evento_id);
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al apuntarte al evento";
    header("Location: ver_evento.php?id=" . $evento_id);
    exit();
}
?>
