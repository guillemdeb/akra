<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.php"); 
    exit(); 
}
require_once "config.php";

$usuario_id = $_SESSION['usuario_id'];
$success = $_SESSION['success_intereses'] ?? '';
unset($_SESSION['success_intereses']);

// Llistar interessos
$sql = "SELECT i.id, i.nombre, i.icono, i.categoria,
               CASE WHEN ui.usuario_id IS NULL THEN 0 ELSE 1 END AS activo
        FROM intereses i
        LEFT JOIN usuario_interes ui
        ON i.id = ui.interes_id AND ui.usuario_id = :usuario_id
        ORDER BY i.categoria, i.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$intereses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar per categoria
$por_categoria = [];
foreach ($intereses as $interes) {
    $por_categoria[$interes['categoria']][] = $interes;
}
?>
<?php $page_title = 'Editar perfil'; require_once "includes/pwa_head.php"; ?>
<html lang="ca">
<body>
<?php ra_splash_body(); ?>
<?php $active_page = 'perfil'; require_once "includes/navbar.php"; ?>

<div class="container">
    <h2><i class="fas fa-heart"></i> Mis Intereses</h2>
    <p class="subtitle">Selecciona las actividades que te interesan</p>
    
    <?php if ($success): ?>
        <div class="success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="contador">
        <i class="fas fa-info-circle"></i> Has seleccionado <strong id="contador">0</strong> intereses
    </div>
    
    <form action="update_intereses.php" method="POST">
        <?php foreach ($por_categoria as $categoria => $items): ?>
            <div class="categoria-section">
                <div class="categoria-titulo">
                    <i class="fas fa-star"></i>
                    <?php echo htmlspecialchars($categoria); ?>
                </div>
                
                <div class="interests">
                    <?php foreach ($items as $interes): ?>
                        <label class="interes-label <?php echo $interes['activo'] ? 'activo' : 'inactivo'; ?>">
                            <input type="checkbox" 
                                   name="intereses[]" 
                                   value="<?php echo $interes['id']; ?>" 
                                   <?php if($interes['activo']) echo 'checked'; ?>
                                   onchange="toggleInteres(this)">
                            <i class="fas <?php echo htmlspecialchars($interes['icono']); ?>"></i>
                            <span><?php echo htmlspecialchars($interes['nombre']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="botones">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function toggleInteres(checkbox) {
    const label = checkbox.closest('.interes-label');
    if (checkbox.checked) {
        label.classList.remove('inactivo');
        label.classList.add('activo');
    } else {
        label.classList.remove('activo');
        label.classList.add('inactivo');
    }
    actualitzarContador();
}

function actualitzarContador() {
    const total = document.querySelectorAll('input[type="checkbox"]:checked').length;
    document.getElementById('contador').textContent = total;
}

document.addEventListener('DOMContentLoaded', actualitzarContador);
</script>
</body>
</html>
