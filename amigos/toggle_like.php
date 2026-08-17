<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$publicacion_id = (int)($_GET['id'] ?? 0);

if ($publicacion_id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit();
}

try {
    // Comprovar si ja existeix el like
    $sql = "SELECT id FROM publicacion_likes WHERE publicacion_id = :publicacion_id AND usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['publicacion_id' => $publicacion_id, 'usuario_id' => $usuario_id]);
    $like_existente = $stmt->fetch();
    
    if ($like_existente) {
        // Eliminar like
        $sql = "DELETE FROM publicacion_likes WHERE publicacion_id = :publicacion_id AND usuario_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['publicacion_id' => $publicacion_id, 'usuario_id' => $usuario_id]);
        $me_gusta = false;
    } else {
        // Afegir like
        $sql = "INSERT INTO publicacion_likes (publicacion_id, usuario_id) VALUES (:publicacion_id, :usuario_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['publicacion_id' => $publicacion_id, 'usuario_id' => $usuario_id]);
        $me_gusta = true;
        
        // Notificar a l'autor de la publicació
        $sql = "SELECT usuario_id FROM publicaciones WHERE id = :publicacion_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['publicacion_id' => $publicacion_id]);
        $autor_id = $stmt->fetchColumn();
        
        if ($autor_id && $autor_id != $usuario_id) {
            $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                    VALUES (:usuario_id, 'publicacion', :contenido, :enlace)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $autor_id,
                'contenido' => $_SESSION['usuario_nombre'] . ' le ha gustado tu publicación',
                'enlace' => 'timeline.php#pub-' . $publicacion_id
            ]);
        }
    }
    
    // Obtenir total de likes
    $sql = "SELECT COUNT(*) FROM publicacion_likes WHERE publicacion_id = :publicacion_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['publicacion_id' => $publicacion_id]);
    $total_likes = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'me_gusta' => $me_gusta,
        'likes' => $total_likes
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos']);
}
?>
