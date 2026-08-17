<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once "config.php";
require_once "notificaciones_helper.php";

$usuario_id = $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? '';

try {
    $pdo->beginTransaction();
    
    switch ($accion) {
        case 'aceptar':
            $solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
            
            // Verificar que la solicitud existe y es para este usuario
            $sql = "SELECT * FROM amistades 
                    WHERE id = :id AND amigo_id = :usuario_id AND estado = 'pendiente'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $solicitud_id, 'usuario_id' => $usuario_id]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solicitud) {
                throw new Exception('Solicitud no encontrada');
            }
            
            // Actualizar estado a aceptada
            $sql = "UPDATE amistades 
                    SET estado = 'aceptada', fecha_respuesta = NOW() 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $solicitud_id]);
            
            // Crear la relación inversa (bidireccional)
            $sql = "INSERT INTO amistades (usuario_id, amigo_id, estado, fecha_solicitud, fecha_respuesta) 
                    VALUES (:usuario_id, :amigo_id, 'aceptada', NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'amigo_id' => $solicitud['usuario_id']
            ]);
            
            // Obtener nombre del usuario que aceptó
            $sql = "SELECT nombre FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $usuario_id]);
            $mi_nombre = $stmt->fetchColumn();
            
            // Notificar al usuario que envió la solicitud
            crearNotificacion(
                $pdo,
                $solicitud['usuario_id'],
                'amistad',
                '¡Nueva amistad!',
                "{$mi_nombre} ha aceptado tu solicitud de amistad",
                "amigos.php"
            );
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
            
        case 'rechazar':
            $solicitud_id = (int)($_POST['solicitud_id'] ?? 0);
            
            // Verificar que la solicitud existe y es para este usuario
            $sql = "SELECT * FROM amistades 
                    WHERE id = :id AND amigo_id = :usuario_id AND estado = 'pendiente'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $solicitud_id, 'usuario_id' => $usuario_id]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solicitud) {
                throw new Exception('Solicitud no encontrada');
            }
            
            // Actualizar estado a rechazada
            $sql = "UPDATE amistades 
                    SET estado = 'rechazada', fecha_respuesta = NOW() 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $solicitud_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
            
        case 'enviar':
            $amigo_id = (int)($_POST['usuario_id'] ?? 0);
            
            if ($amigo_id === $usuario_id) {
                throw new Exception('No puedes enviarte solicitud a ti mismo');
            }
            
            // Verificar que el usuario existe
            $sql = "SELECT id, nombre FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $amigo_id]);
            $amigo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$amigo) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Verificar que no existe ya una solicitud
            $sql = "SELECT id FROM amistades 
                    WHERE ((usuario_id = :usuario_id AND amigo_id = :amigo_id) 
                        OR (usuario_id = :amigo_id2 AND amigo_id = :usuario_id2))
                    AND estado IN ('pendiente', 'aceptada')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'amigo_id' => $amigo_id,
                'usuario_id2' => $usuario_id,
                'amigo_id2' => $amigo_id
            ]);
            
            if ($stmt->fetch()) {
                throw new Exception('Ya existe una solicitud con este usuario');
            }
            
            // Crear solicitud
            $sql = "INSERT INTO amistades (usuario_id, amigo_id, estado) 
                    VALUES (:usuario_id, :amigo_id, 'pendiente')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'amigo_id' => $amigo_id
            ]);
            
            // Obtener mi nombre
            $sql = "SELECT nombre FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $usuario_id]);
            $mi_nombre = $stmt->fetchColumn();
            
            // Notificar al usuario
            crearNotificacion(
                $pdo,
                $amigo_id,
                'amistad',
                'Nueva solicitud de amistad',
                "{$mi_nombre} te ha enviado una solicitud de amistad",
                "amigos.php"
            );
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
            
        case 'eliminar':
            $amigo_id = (int)($_POST['usuario_id'] ?? 0);
            
            // Eliminar ambas relaciones (bidireccional)
            $sql = "DELETE FROM amistades 
                    WHERE (usuario_id = :usuario_id AND amigo_id = :amigo_id)
                       OR (usuario_id = :amigo_id2 AND amigo_id = :usuario_id2)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'amigo_id' => $amigo_id,
                'usuario_id2' => $usuario_id,
                'amigo_id2' => $amigo_id
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
