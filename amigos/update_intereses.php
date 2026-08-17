<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$seleccionados = $_POST['intereses'] ?? [];

try {
    // Borrar anteriors
    $stmt = $pdo->prepare("DELETE FROM usuario_interes WHERE usuario_id = :uid");
    $stmt->execute(['uid' => $usuario_id]);
    
    // Inserir nous
    if (!empty($seleccionados)) {
        $stmt = $pdo->prepare("INSERT INTO usuario_interes (usuario_id, interes_id) VALUES (:uid, :iid)");
        foreach ($seleccionados as $iid) {
            $stmt->execute(['uid' => $usuario_id, 'iid' => (int)$iid]);
        }
    }
    
    $_SESSION['success_intereses'] = '¡Intereses actualizados correctamente!';
} catch (PDOException $e) {
    $_SESSION['error_intereses'] = 'Error al actualizar intereses: ' . $e->getMessage();
}

header("Location: editar_perfil.php");
exit();
