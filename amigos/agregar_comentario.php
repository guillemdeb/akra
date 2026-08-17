<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $publicacion_id = (int)($_POST['publicacion_id'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($publicacion_id <= 0 || empty($comentario)) {
        header("Location: timeline.php");
        exit();
    }
    
    try {
        $sql = "INSERT INTO publicacion_comentarios (publicacion_id, usuario_id, comentario) 
                VALUES (:publicacion_id, :usuario_id, :comentario)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'publicacion_id' => $publicacion_id,
            'usuario_id' => $usuario_id,
            'comentario' => $comentario
        ]);
        
        // Notificar a l'autor
        $sql = "SELECT usuario_id FROM publicaciones WHERE id = :publicacion_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['publicacion_id' => $publicacion_id]);
        $autor_id = $stmt->fetchColumn();
        
        if ($autor_id && $autor_id != $usuario_id) {
            $sql = "INSERT INTO notificaciones (usuario_id, tipo, contenido, enlace) 
                    VALUES (:usuario_id, 'comentario', :contenido, :enlace)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'usuario_id' => $autor_id,
                'contenido' => $_SESSION['usuario_nombre'] . ' comentó tu publicación',
                'enlace' => 'timeline.php#pub-' . $publicacion_id
            ]);
        }
        
        header("Location: timeline.php#pub-" . $publicacion_id);
        exit();
        
    } catch (PDOException $e) {
        header("Location: timeline.php");
        exit();
    }
}

header("Location: timeline.php");
exit();
?>
