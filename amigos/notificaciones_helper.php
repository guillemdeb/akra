<?php
/**
 * Funcions helper per a notificacions
 */

/**
 * Crea una notificació per a un usuari
 */
function crearNotificacion($pdo, $usuario_id, $tipo, $titulo, $mensaje, $enlace = null) {
    try {
        $sql = "INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace) 
                VALUES (:usuario_id, :tipo, :titulo, :mensaje, :enlace)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'enlace' => $enlace
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error creando notificación: " . $e->getMessage());
        return false;
    }
}

/**
 * Notifica als participants d'un esdeveniment
 */
function notificarParticipantesEvento($pdo, $evento_id, $titulo, $mensaje, $excluir_usuario_id = null) {
    try {
        $sql = "SELECT DISTINCT usuario_id FROM evento_participantes 
                WHERE evento_id = :evento_id AND estado = 'confirmado'";
        if ($excluir_usuario_id) {
            $sql .= " AND usuario_id != :excluir_usuario_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $params = ['evento_id' => $evento_id];
        if ($excluir_usuario_id) {
            $params['excluir_usuario_id'] = $excluir_usuario_id;
        }
        $stmt->execute($params);
        $participantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($participantes as $usuario_id) {
            crearNotificacion(
                $pdo, 
                $usuario_id, 
                'evento', 
                $titulo, 
                $mensaje, 
                "ver_evento.php?id={$evento_id}"
            );
        }
        return true;
    } catch (Exception $e) {
        error_log("Error notificando participantes: " . $e->getMessage());
        return false;
    }
}

/**
 * Notifica al creador d'un esdeveniment
 */
function notificarCreadorEvento($pdo, $evento_id, $titulo, $mensaje) {
    try {
        $sql = "SELECT creador_id FROM eventos WHERE id = :evento_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['evento_id' => $evento_id]);
        $creador_id = $stmt->fetchColumn();
        
        if ($creador_id) {
            return crearNotificacion(
                $pdo, 
                $creador_id, 
                'evento', 
                $titulo, 
                $mensaje, 
                "ver_evento.php?id={$evento_id}"
            );
        }
        return false;
    } catch (Exception $e) {
        error_log("Error notificando creador: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina notificacions antigues (més de 30 dies)
 */
function limpiarNotificacionesAntiguas($pdo) {
    try {
        $sql = "DELETE FROM notificaciones 
                WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND leida = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("Error limpiando notificaciones: " . $e->getMessage());
        return false;
    }
}
