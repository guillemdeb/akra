<!-- Widget de Notificacions (afegir al header de totes les pàgines) -->
<div class="notificaciones-widget">
    <button class="notif-btn" id="notifBtn" onclick="toggleNotificaciones()">
        <i class="fas fa-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
    </button>
    
    <div class="notif-dropdown" id="notifDropdown" style="display: none;">
        <div class="notif-header">
            <h4>Notificaciones</h4>
            <button class="btn-marcar-leidas" onclick="marcarTodasLeidas()">
                <i class="fas fa-check-double"></i> Marcar todas
            </button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-loading">
                <i class="fas fa-spinner fa-spin"></i> Cargando...
            </div>
        </div>
        <div class="notif-footer">
            <a href="notificaciones.php">Ver todas las notificaciones</a>
        </div>
    </div>
</div>

<style>
.notificaciones-widget {
    position: relative;
}

.notif-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.3rem;
    cursor: pointer;
    padding: 8px 15px;
    border-radius: 5px;
    transition: background 0.3s;
    position: relative;
}

.notif-btn:hover {
    background: rgba(255,255,255,0.2);
}

.notif-badge {
    position: absolute;
    top: 5px;
    right: 8px;
    background: #E74C3C;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notif-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    width: 400px;
    max-height: 500px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    z-index: 1000;
    overflow: hidden;
}

.notif-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-header h4 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.btn-marcar-leidas {
    background: none;
    border: none;
    color: #4A90E2;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn-marcar-leidas:hover {
    background: #e9ecef;
}

.notif-list {
    max-height: 400px;
    overflow-y: auto;
}

.notif-loading {
    padding: 40px;
    text-align: center;
    color: #999;
}

.notif-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
}

.notif-item:hover {
    background: #f8f9fa;
}

.notif-item.no-leida {
    background: #e3f2fd;
}

.notif-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notif-icon.amistad { background: #E8F5E9; color: #4CAF50; }
.notif-icon.evento { background: #E3F2FD; color: #2196F3; }
.notif-icon.mensaje { background: #FFF3E0; color: #FF9800; }
.notif-icon.comentario { background: #F3E5F5; color: #9C27B0; }
.notif-icon.sistema { background: #ECEFF1; color: #607D8B; }

.notif-content {
    flex: 1;
}

.notif-titulo {
    font-weight: 600;
    color: #333;
    margin-bottom: 3px;
    font-size: 0.95rem;
}

.notif-mensaje {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.notif-tiempo {
    color: #999;
    font-size: 0.8rem;
}

.notif-empty {
    padding: 40px 20px;
    text-align: center;
    color: #999;
}

.notif-empty i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ddd;
}

.notif-footer {
    padding: 12px 20px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.notif-footer a {
    color: #4A90E2;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.notif-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .notif-dropdown {
        width: 100vw;
        right: -20px;
        max-height: 70vh;
    }
}
</style>

<script>
let notifDropdownAbierto = false;
let intervaloNotif = null;

// Cargar notificaciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarNotificaciones();
    // Actualizar cada 30 segundos
    intervaloNotif = setInterval(cargarNotificaciones, 30000);
});

function toggleNotificaciones() {
    notifDropdownAbierto = !notifDropdownAbierto;
    document.getElementById('notifDropdown').style.display = notifDropdownAbierto ? 'block' : 'none';
    
    if (notifDropdownAbierto) {
        cargarNotificaciones();
    }
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notificaciones-widget')) {
        document.getElementById('notifDropdown').style.display = 'none';
        notifDropdownAbierto = false;
    }
});

function cargarNotificaciones() {
    fetch('api_notificaciones.php?accion=obtener')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificaciones(data.notificaciones);
                actualizarBadge(data.no_leidas);
            }
        })
        .catch(error => console.error('Error:', error));
}

function mostrarNotificaciones(notificaciones) {
    const lista = document.getElementById('notifList');
    
    if (notificaciones.length === 0) {
        lista.innerHTML = `
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No tienes notificaciones</p>
            </div>
        `;
        return;
    }
    
    lista.innerHTML = notificaciones.map(n => {
        const iconos = {
            'amistad': 'fa-user-friends',
            'evento': 'fa-calendar',
            'mensaje': 'fa-comment',
            'comentario': 'fa-comments',
            'sistema': 'fa-info-circle'
        };
        
        return `
            <div class="notif-item ${n.leida == 0 ? 'no-leida' : ''}" 
                 onclick="irANotificacion(${n.id}, '${n.enlace || '#'}')">
                <div class="notif-icon ${n.tipo}">
                    <i class="fas ${iconos[n.tipo] || 'fa-bell'}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-titulo">${escapeHtml(n.titulo)}</div>
                    <div class="notif-mensaje">${escapeHtml(n.mensaje)}</div>
                    <div class="notif-tiempo">${tiempoTranscurrido(n.fecha_creacion)}</div>
                </div>
            </div>
        `;
    }).join('');
}

function actualizarBadge(cantidad) {
    const badge = document.getElementById('notifBadge');
    if (cantidad > 0) {
        badge.textContent = cantidad > 99 ? '99+' : cantidad;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function irANotificacion(id, enlace) {
    // Marcar como leída
    fetch('api_notificaciones.php?accion=marcar_leida', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    }).then(() => {
        if (enlace && enlace !== '#') {
            window.location.href = enlace;
        }
        cargarNotificaciones();
    });
}

function marcarTodasLeidas() {
    fetch('api_notificaciones.php?accion=marcar_todas_leidas', {
        method: 'POST'
    }).then(() => {
        cargarNotificaciones();
    });
}

function tiempoTranscurrido(fecha) {
    const ahora = new Date();
    const entonces = new Date(fecha);
    const diff = Math.floor((ahora - entonces) / 1000); // en segundos
    
    if (diff < 60) return 'Hace un momento';
    if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`;
    return entonces.toLocaleDateString('es-ES');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
