<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$amigo_id = (int)($_GET['usuario_id'] ?? $_GET['id'] ?? 0);
$redirect_page = $_GET['redirect'] ?? 'feed';
$actividad = $_GET['actividad'] ?? 'general';

// Funció per fer redirect
function redirect_back($redirect_page, $actividad, $type, $msg) {
    if ($redirect_page === 'sugerencias') {
        header("Location: sugerencias.php?actividad=" . urlencode($actividad) . "&{$type}=" . urlencode($msg));
    } else {
        header("Location: feed.php?tab=coincidencias&{$type}=" . urlencode($msg));
    }
    exit();
}

if ($amigo_id <= 0 || $amigo_id === $usuario_id) {
    redirect_back($redirect_page, $actividad, 'error', 'Usuario no válido');
}

// Verificar que el usuario existe
$sql = "SELECT id FROM usuarios WHERE id = :id AND activo = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $amigo_id]);

if (!$stmt->fetch()) {
    redirect_back($redirect_page, $actividad, 'error', 'Usuario no encontrado');
}

// Verificar si ya existe alguna relación
$sql = "SELECT estado FROM amistades 
        WHERE (usuario_id = :usuario_id AND amigo_id = :amigo_id) 
           OR (usuario_id = :amigo_id AND amigo_id = :usuario_id)";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id, 'amigo_id' => $amigo_id]);
$relacion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($relacion) {
    redirect_back($redirect_page, $actividad, 'error', 'Ya existe una solicitud con este usuario');
}

// Crear la solicitud
try {
    $sql = "INSERT INTO amistades (usuario_id, amigo_id, estado) VALUES (:usuario_id, :amigo_id, 'pendiente')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id, 'amigo_id' => $amigo_id]);
    
    // Crear notificación
    $sqlNotif = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                 VALUES (:amigo_id, 'amistad', 
                         CONCAT((SELECT nombre FROM usuarios WHERE id = :usuario_id), ' te ha enviado una solicitud de amistad'), 
                         'solicitudes.php')";
    $stmtNotif = $pdo->prepare($sqlNotif);
    $stmtNotif->execute(['amigo_id' => $amigo_id, 'usuario_id' => $usuario_id]);
    
    redirect_back($redirect_page, $actividad, 'success', '✅ Solicitud enviada correctamente');
    
} catch (PDOException $e) {
    redirect_back($redirect_page, $actividad, 'error', 'Error al enviar la solicitud');
}
