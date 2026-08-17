<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$accion = $_GET['accion'] ?? '';

try {
    switch ($accion) {
        case 'obtener':
            // Obtener notificaciones no leídas
            $limite = isset($_GET['todas']) ? 50 : 10;
            $sql = "SELECT * FROM notificaciones 
                    WHERE usuario_id = :usuario_id 
                    ORDER BY fecha_creacion DESC 
                    LIMIT :limite";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar no leídas
            $sql = "SELECT COUNT(*) as total FROM notificaciones 
                    WHERE usuario_id = :usuario_id AND leida = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['usuario_id' => $usuario_id]);
            $no_leidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'notificaciones' => $notificaciones,
                'no_leidas' => $no_leidas
            ]);
            break;
            
        case 'marcar_leida':
            $notif_id = (int)($_POST['id'] ?? 0);
            $sql = "UPDATE notificaciones SET leida = 1 
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $notif_id, 'usuario_id' => $usuario_id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'marcar_todas_leidas':
            $sql = "UPDATE notificaciones SET leida = 1 
                    WHERE usuario_id = :usuario_id AND leida = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['usuario_id' => $usuario_id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'eliminar':
            $notif_id = (int)($_POST['id'] ?? 0);
            $sql = "DELETE FROM notificaciones 
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $notif_id, 'usuario_id' => $usuario_id]);
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
