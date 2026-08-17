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
    switch ($accion) {
        case 'crear':
            $evento_id = (int)($_POST['evento_id'] ?? 0);
            $comentario = trim($_POST['comentario'] ?? '');
            
            if (empty($comentario)) {
                throw new Exception('El comentario no puede estar vacío');
            }
            
            // Verificar que el evento existe
            $sql = "SELECT id, creador_id, titulo FROM eventos WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $evento_id]);
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$evento) {
                throw new Exception('Evento no encontrado');
            }
            
            // Verificar que el usuario está apuntado o es el creador
            $sql = "SELECT COUNT(*) FROM evento_participantes 
                    WHERE evento_id = :evento_id 
                    AND usuario_id = :usuario_id 
                    AND estado = 'confirmado'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['evento_id' => $evento_id, 'usuario_id' => $usuario_id]);
            $esta_apuntado = $stmt->fetchColumn() > 0;
            
            $es_creador = $evento['creador_id'] == $usuario_id;
            
            if (!$esta_apuntado && !$es_creador) {
                throw new Exception('Debes estar apuntado al evento para comentar');
            }
            
            // Crear comentario
            $sql = "INSERT INTO evento_comentarios (evento_id, usuario_id, comentario) 
                    VALUES (:evento_id, :usuario_id, :comentario)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'evento_id' => $evento_id,
                'usuario_id' => $usuario_id,
                'comentario' => $comentario
            ]);
            
            // Obtener nombre del usuario que comentó
            $sql = "SELECT nombre FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $usuario_id]);
            $mi_nombre = $stmt->fetchColumn();
            
            // Notificar al creador si no es quien comentó
            if (!$es_creador) {
                notificarCreadorEvento(
                    $pdo,
                    $evento_id,
                    'Nuevo comentario',
                    "{$mi_nombre} ha comentado en tu evento '{$evento['titulo']}'"
                );
            }
            
            // Notificar a otros participantes (opcional, solo si quieres)
            // notificarParticipantesEvento($pdo, $evento_id, 'Nuevo comentario', "{$mi_nombre} ha comentado en '{$evento['titulo']}'", $usuario_id);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'eliminar':
            $comentario_id = (int)($_POST['comentario_id'] ?? 0);
            
            // Verificar que el comentario existe y es del usuario
            $sql = "SELECT id FROM evento_comentarios 
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $comentario_id, 'usuario_id' => $usuario_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Comentario no encontrado o no tienes permiso para eliminarlo');
            }
            
            // Eliminar comentario
            $sql = "DELETE FROM evento_comentarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $comentario_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'obtener':
            $evento_id = (int)($_GET['evento_id'] ?? 0);
            
            $sql = "SELECT c.*, u.nombre, u.foto
                    FROM evento_comentarios c
                    JOIN usuarios u ON c.usuario_id = u.id
                    WHERE c.evento_id = :evento_id
                    ORDER BY c.fecha_creacion DESC
                    LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['evento_id' => $evento_id]);
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'comentarios' => $comentarios]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
