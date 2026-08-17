<!-- Secció de Comentaris (afegir a ver_evento.php abans del tancament de </main>) -->

<div class="comentarios-section">
    <h3><i class="fas fa-comments"></i> Comentarios (<?php echo count($comentarios ?? []); ?>)</h3>
    
    <?php if ($es_creador || $estoy_apuntado): ?>
    <div class="comentario-form">
        <form onsubmit="enviarComentario(event)">
            <textarea id="nuevoComentario" 
                      placeholder="Escribe un comentario..." 
                      rows="3" 
                      required></textarea>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-paper-plane"></i> Comentar
            </button>
        </form>
    </div>
    <?php else: ?>
    <p class="info-comentario">
        <i class="fas fa-info-circle"></i> Debes estar apuntado al evento para comentar
    </p>
    <?php endif; ?>
    
    <div class="comentarios-lista" id="comentariosLista">
        <?php if (!empty($comentarios)): ?>
            <?php foreach ($comentarios as $com): ?>
            <div class="comentario-item" data-id="<?php echo $com['id']; ?>">
                <img src="uploads/<?php echo htmlspecialchars($com['foto'] ?: 'default.png'); ?>" 
                     alt="<?php echo htmlspecialchars($com['nombre']); ?>" 
                     class="comentario-avatar">
                <div class="comentario-contenido">
                    <div class="comentario-header">
                        <strong><?php echo htmlspecialchars($com['nombre']); ?></strong>
                        <?php if ($com['usuario_id'] == $evento['creador_id']): ?>
                            <span class="badge-organizador">Organizador</span>
                        <?php endif; ?>
                        <span class="comentario-fecha">
                            <?php 
                            $diff = time() - strtotime($com['fecha_creacion']);
                            if ($diff < 60) echo 'Hace un momento';
                            elseif ($diff < 3600) echo 'Hace ' . floor($diff/60) . ' min';
                            elseif ($diff < 86400) echo 'Hace ' . floor($diff/3600) . ' h';
                            else echo 'Hace ' . floor($diff/86400) . ' días';
                            ?>
                        </span>
                    </div>
                    <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($com['comentario'])); ?></p>
                    
                    <?php if ($com['usuario_id'] == $usuario_id): ?>
                    <button class="btn-eliminar-comentario" 
                            onclick="eliminarComentario(<?php echo $com['id']; ?>)">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-comentarios">
                <i class="fas fa-comment-slash"></i>
                <p>No hay comentarios todavía</p>
                <p>¡Sé el primero en comentar!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.comentarios-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-top: 25px;
}

.comentarios-section h3 {
    color: #333;
    margin-bottom: 20px;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.comentarios-section h3 i {
    color: var(--color-principal);
}

.comentario-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.comentario-form textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    font-family: Arial, sans-serif;
    font-size: 1rem;
    resize: vertical;
    margin-bottom: 10px;
    transition: border-color 0.3s;
}

.comentario-form textarea:focus {
    outline: none;
    border-color: var(--color-principal);
}

.comentario-form button {
    float: right;
}

.info-comentario {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #ffc107;
}

.comentarios-lista {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.comentario-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: background 0.3s;
}

.comentario-item:hover {
    background: #e9ecef;
}

.comentario-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.comentario-contenido {
    flex: 1;
}

.comentario-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.comentario-header strong {
    color: #333;
}

.badge-organizador {
    background: var(--color-principal);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: normal;
}

.comentario-fecha {
    color: #999;
    font-size: 0.85rem;
    margin-left: auto;
}

.comentario-texto {
    color: #555;
    line-height: 1.6;
    margin-bottom: 10px;
}

.btn-eliminar-comentario {
    background: none;
    border: none;
    color: var(--color-danger);
    cursor: pointer;
    font-size: 0.85rem;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn-eliminar-comentario:hover {
    background: #ffe6e6;
}

.empty-comentarios {
    text-align: center;
    padding: 40px;
    color: #999;
}

.empty-comentarios i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-comentarios p:first-of-type {
    font-weight: 600;
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .comentario-item {
        flex-direction: column;
    }
    
    .comentario-avatar {
        width: 40px;
        height: 40px;
    }
}
</style>

<script>
const eventoId = <?php echo $evento_id; ?>;

function enviarComentario(e) {
    e.preventDefault();
    
    const textarea = document.getElementById('nuevoComentario');
    const comentario = textarea.value.trim();
    
    if (!comentario) return;
    
    fetch('api_comentarios.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `accion=crear&evento_id=${eventoId}&comentario=${encodeURIComponent(comentario)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            textarea.value = '';
            location.reload(); // Recargar para mostrar el nuevo comentario
        } else {
            alert(data.error || 'Error al enviar el comentario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

function eliminarComentario(comentarioId) {
    if (!confirm('¿Seguro que quieres eliminar este comentario?')) {
        return;
    }
    
    fetch('api_comentarios.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `accion=eliminar&comentario_id=${comentarioId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`.comentario-item[data-id="${comentarioId}"]`).remove();
            
            // Actualizar contador
            const contador = document.querySelector('.comentarios-section h3');
            const match = contador.textContent.match(/\d+/);
            if (match) {
                const nuevo = parseInt(match[0]) - 1;
                contador.innerHTML = contador.innerHTML.replace(/\d+/, nuevo);
            }
            
            // Si no quedan comentarios, mostrar mensaje vacío
            if (document.querySelectorAll('.comentario-item').length === 0) {
                document.getElementById('comentariosLista').innerHTML = `
                    <div class="empty-comentarios">
                        <i class="fas fa-comment-slash"></i>
                        <p>No hay comentarios todavía</p>
                        <p>¡Sé el primero en comentar!</p>
                    </div>
                `;
            }
        } else {
            alert(data.error || 'Error al eliminar el comentario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}
</script>
