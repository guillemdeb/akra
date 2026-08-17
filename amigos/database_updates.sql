-- Taula de notificacions
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('amistad', 'evento', 'mensaje', 'comentario', 'sistema') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    enlace VARCHAR(500),
    leida TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de comentaris d'esdeveniments
CREATE TABLE IF NOT EXISTS evento_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Millorem la taula amistades si cal (afegir camps que poden faltar)
ALTER TABLE amistades 
ADD COLUMN IF NOT EXISTS fecha_respuesta TIMESTAMP NULL AFTER fecha_solicitud;

-- Taula de valoracions d'esdeveniments
CREATE TABLE IF NOT EXISTS evento_valoraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    puntuacion TINYINT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_valoracion (evento_id, usuario_id),
    INDEX idx_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de configuració de privacitat d'usuari
CREATE TABLE IF NOT EXISTS configuracion_privacidad (
    usuario_id INT PRIMARY KEY,
    perfil_visible ENUM('publico', 'amigos', 'privado') DEFAULT 'publico',
    eventos_visibles ENUM('publico', 'amigos', 'privado') DEFAULT 'publico',
    amigos_visibles ENUM('publico', 'amigos', 'privado') DEFAULT 'amigos',
    mensajes_de ENUM('todos', 'amigos', 'nadie') DEFAULT 'todos',
    notif_nuevos_eventos TINYINT(1) DEFAULT 1,
    notif_mensajes TINYINT(1) DEFAULT 1,
    notif_amistades TINYINT(1) DEFAULT 1,
    notif_comentarios TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Afegir camps a la taula usuarios si no existeixen
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS biografia TEXT AFTER ubicacion,
ADD COLUMN IF NOT EXISTS intereses VARCHAR(500) AFTER biografia,
ADD COLUMN IF NOT EXISTS verificado TINYINT(1) DEFAULT 0 AFTER intereses;

-- Afegir camps a la taula eventos si no existeixen
ALTER TABLE eventos
ADD COLUMN IF NOT EXISTS foto VARCHAR(255) AFTER descripcion,
ADD COLUMN IF NOT EXISTS precio DECIMAL(10,2) DEFAULT 0 AFTER plazas_max,
ADD COLUMN IF NOT EXISTS puntuacion_media DECIMAL(3,2) DEFAULT 0 AFTER precio;
