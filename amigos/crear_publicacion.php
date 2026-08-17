<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $contenido = trim($_POST['contenido'] ?? '');
    $visibilidad = $_POST['visibilidad'] ?? 'amigos';
    
    if (empty($contenido) || strlen($contenido) < 3) {
        $_SESSION['error'] = "La publicación debe tener al menos 3 caracteres";
        header("Location: timeline.php");
        exit();
    }
    
    try {
        $sql = "INSERT INTO publicaciones (usuario_id, contenido, tipo, visibilidad) 
                VALUES (:usuario_id, :contenido, 'texto', :visibilidad)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'contenido' => $contenido,
            'visibilidad' => $visibilidad
        ]);
        
        $_SESSION['success'] = "Publicación creada correctamente";
        header("Location: timeline.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al crear la publicación";
        header("Location: timeline.php");
        exit();
    }
}

header("Location: timeline.php");
exit();
?>
