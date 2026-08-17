-- ============================================
-- REDAMIGOS v3.0 - BASE DE DADES COMPLETA
-- Sistema complet d'interacció entre usuaris
-- ============================================

-- Esborrar BD si existeix i crear de nou
DROP DATABASE IF EXISTS app_social;
CREATE DATABASE app_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE app_social;

-- ============================================
-- TAULA: usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    edad INT,
    genero ENUM('Hombre', 'Mujer', 'Otro', 'Prefiero no decirlo') DEFAULT 'Prefiero no decirlo',
    ubicacion VARCHAR(150),
    foto VARCHAR(255) DEFAULT 'default.png',
    descripcion TEXT,
    mostrar_telefono BOOLEAN DEFAULT FALSE,
    mostrar_email BOOLEAN DEFAULT FALSE,
    mostrar_edad BOOLEAN DEFAULT TRUE,
    mostrar_ubicacion BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion TIMESTAMP NULL,
    activo BOOLEAN DEFAULT TRUE,
    aprobado BOOLEAN DEFAULT FALSE,
    fecha_aprobacion TIMESTAMP NULL,
    aprobado_por INT NULL,
    motivo_rechazo TEXT NULL,
    notas_admin TEXT NULL,
    INDEX idx_email (email),
    INDEX idx_activo (activo),
    INDEX idx_aprobado (aprobado),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: intereses
-- ============================================
CREATE TABLE IF NOT EXISTS intereses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    icono VARCHAR(50) DEFAULT 'fa-heart',
    categoria ENUM('Deportes', 'Arte', 'Cultura', 'Tecnología', 'Ocio', 'Social') DEFAULT 'Ocio',
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: usuario_interes (relació molts-a-molts)
-- ============================================
CREATE TABLE IF NOT EXISTS usuario_interes (
    usuario_id INT NOT NULL,
    interes_id INT NOT NULL,
    PRIMARY KEY (usuario_id, interes_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (interes_id) REFERENCES intereses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: amistades
-- ============================================
CREATE TABLE IF NOT EXISTS amistades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    amigo_id INT NOT NULL,
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'bloqueada') DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (amigo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_amistad (usuario_id, amigo_id),
    INDEX idx_estado (estado),
    INDEX idx_usuario (usuario_id),
    INDEX idx_amigo (amigo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: mensajes
-- ============================================
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    leido BOOLEAN DEFAULT FALSE,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_conversacion (remitente_id, destinatario_id),
    INDEX idx_leido (leido),
    INDEX idx_fecha (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: eventos (NOVA - Quedades i esdeveniments)
-- ============================================
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creador_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo ENUM('quedada', 'actividad', 'excursion', 'cafe', 'paseo', 'otro') DEFAULT 'quedada',
    ubicacion VARCHAR(200),
    fecha_evento DATETIME NOT NULL,
    plazas_max INT DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    estado ENUM('activo', 'cancelado', 'finalizado') DEFAULT 'activo',
    visibilidad ENUM('publico', 'amigos', 'privado') DEFAULT 'amigos',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_fecha_evento (fecha_evento),
    INDEX idx_estado (estado),
    INDEX idx_creador (creador_id),
    INDEX idx_visibilidad (visibilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: evento_participantes (Qui s'ha apuntat)
-- ============================================
CREATE TABLE IF NOT EXISTS evento_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    estado ENUM('confirmado', 'pendiente', 'cancelado') DEFAULT 'confirmado',
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participacion (evento_id, usuario_id),
    INDEX idx_evento (evento_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: publicaciones (Mur/Timeline compartit)
-- ============================================
CREATE TABLE IF NOT EXISTS publicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    tipo ENUM('texto', 'foto', 'evento', 'logro') DEFAULT 'texto',
    imagen VARCHAR(255) DEFAULT NULL,
    evento_id INT DEFAULT NULL,
    visibilidad ENUM('publico', 'amigos', 'privado') DEFAULT 'amigos',
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_publicacion),
    INDEX idx_visibilidad (visibilidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: publicacion_likes (M'agrada a publicacions)
-- ============================================
CREATE TABLE IF NOT EXISTS publicacion_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publicacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_like TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (publicacion_id, usuario_id),
    INDEX idx_publicacion (publicacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: publicacion_comentarios
-- ============================================
CREATE TABLE IF NOT EXISTS publicacion_comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publicacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_publicacion (publicacion_id),
    INDEX idx_fecha (fecha_comentario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: notificaciones
-- ============================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('amistad', 'mensaje', 'evento', 'publicacion', 'comentario', 'sistema') DEFAULT 'sistema',
    contenido TEXT NOT NULL,
    leida BOOLEAN DEFAULT FALSE,
    enlace VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_leida (leida),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: intentos_login (rate limiting)
-- ============================================
CREATE TABLE IF NOT EXISTS intentos_login (
    ip VARCHAR(45) PRIMARY KEY,
    intentos_fallidos INT DEFAULT 0,
    ultimo_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ultimo_intento (ultimo_intento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: password_resets
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(150) PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expiracion DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_expiracion (expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DADES INICIALS: Intereses
-- ============================================
INSERT INTO intereses (nombre, icono, categoria) VALUES
-- Deportes
('Caminar', 'fa-walking', 'Deportes'),
('Yoga', 'fa-spa', 'Deportes'),
('Natación', 'fa-swimmer', 'Deportes'),
('Bailar', 'fa-music', 'Deportes'),
('Ciclismo', 'fa-biking', 'Deportes'),
('Golf', 'fa-golf-ball', 'Deportes'),
('Tenis', 'fa-table-tennis', 'Deportes'),

-- Arte
('Lectura', 'fa-book', 'Arte'),
('Pintura', 'fa-palette', 'Arte'),
('Manualidades', 'fa-cut', 'Arte'),
('Fotografía', 'fa-camera', 'Arte'),
('Música', 'fa-music', 'Arte'),

-- Cultura
('Cocina', 'fa-utensils', 'Cultura'),
('Cine', 'fa-film', 'Cultura'),
('Teatro', 'fa-theater-masks', 'Cultura'),
('Historia', 'fa-landmark', 'Cultura'),
('Idiomas', 'fa-language', 'Cultura'),

-- Tecnología
('Informática', 'fa-laptop', 'Tecnología'),
('Redes Sociales', 'fa-share-alt', 'Tecnología'),

-- Ocio
('Jardinería', 'fa-seedling', 'Ocio'),
('Juegos de mesa', 'fa-chess', 'Ocio'),
('Cartas', 'fa-cards', 'Ocio'),
('Viajes', 'fa-plane', 'Ocio'),
('Naturaleza', 'fa-tree', 'Ocio'),
('Mascotas', 'fa-paw', 'Ocio'),

-- Social
('Café y conversación', 'fa-coffee', 'Social'),
('Voluntariado', 'fa-hands-helping', 'Social');

-- ============================================
-- DADES INICIALS: Usuarios de prova
-- ============================================
-- Contrasenya: "password123" (bcrypt hash)
INSERT INTO usuarios (nombre, email, password, telefono, edad, genero, ubicacion, descripcion, mostrar_telefono, mostrar_email, aprobado, fecha_aprobacion) VALUES
('María García', 'maria@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666123456', 65, 'Mujer', 'Alicante', 'Me encanta caminar por la playa y conocer gente nueva. Busco compañía para tomar café y compartir experiencias.', TRUE, FALSE, TRUE, NOW()),
('Juan Pérez', 'juan@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '677234567', 68, 'Hombre', 'Valencia', 'Jubilado activo. Aficionado a la fotografía y el senderismo. Me gusta organizar quedadas para compartir hobbies.', TRUE, TRUE, TRUE, NOW()),
('Carmen López', 'carmen@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '688345678', 62, 'Mujer', 'Alicante', 'Apasionada de la lectura y el teatro. Siempre dispuesta a descubrir nuevos lugares y hacer nuevas amistades.', FALSE, TRUE, TRUE, NOW()),
('Antonio Martínez', 'antonio@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '699456789', 70, 'Hombre', 'Castellón', 'Amante de la naturaleza y los animales. Me gusta el voluntariado y ayudar a los demás.', TRUE, FALSE, TRUE, NOW()),
('Rosa Sánchez', 'rosa@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '600567890', 58, 'Mujer', 'Valencia', 'Entusiasta del yoga y la vida saludable. Busco amigas para compartir actividades y risas.', TRUE, TRUE, TRUE, NOW()),
-- Usuari administrador
('Admin Sistema', 'admin@redamigos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 35, 'Prefiero no decirlo', 'Sistema', 'Cuenta de administración', FALSE, FALSE, TRUE, NOW());

-- ============================================
-- DADES INICIALS: Intereses de usuarios
-- ============================================
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
-- María: Caminar, Lectura, Café
(1, 1), (1, 8), (1, 25),
-- Juan: Fotografía, Caminar, Viajes
(2, 11), (2, 1), (2, 23),
-- Carmen: Lectura, Teatro, Cine
(3, 8), (3, 15), (3, 14),
-- Antonio: Jardinería, Voluntariado, Mascotas
(4, 20), (4, 26), (4, 24),
-- Rosa: Yoga, Natación, Cocina
(5, 2), (5, 3), (5, 13);

-- ============================================
-- DADES INICIALS: Amistades
-- ============================================
INSERT INTO amistades (usuario_id, amigo_id, estado, fecha_respuesta) VALUES
(1, 2, 'aceptada', NOW()),
(2, 1, 'aceptada', NOW()),
(4, 5, 'aceptada', NOW()),
(5, 4, 'aceptada', NOW()),
(1, 3, 'pendiente', NULL);

-- ============================================
-- DADES INICIALS: Eventos d'exemple
-- ============================================
INSERT INTO eventos (creador_id, titulo, descripcion, tipo, ubicacion, fecha_evento, plazas_max, visibilidad) VALUES
(2, 'Paseo fotográfico por el centro histórico', 'Quedada para hacer fotos por el casco antiguo de Valencia. Nivel principiante. Traer cámara o móvil.', 'excursion', 'Centro histórico Valencia', DATE_ADD(NOW(), INTERVAL 5 DAY), 8, 'publico'),
(1, 'Café y tertulia - Martes por la tarde', 'Nos reunimos cada martes para tomar café y charlar. Ambiente relajado y amigable.', 'cafe', 'Cafetería Central, Alicante', DATE_ADD(NOW(), INTERVAL 2 DAY), 6, 'amigos'),
(5, 'Clase de yoga en el parque', 'Sesión de yoga suave al aire libre. Apto para todos los niveles. Traer esterilla.', 'actividad', 'Parque del Oeste, Valencia', DATE_ADD(NOW(), INTERVAL 7 DAY), 10, 'publico');

-- ============================================
-- DADES INICIALS: Participantes en eventos
-- ============================================
INSERT INTO evento_participantes (evento_id, usuario_id, estado) VALUES
(1, 2, 'confirmado'),  -- Juan crea l'esdeveniment
(1, 1, 'confirmado'),  -- María s'apunta
(2, 1, 'confirmado'),  -- María crea l'esdeveniment
(2, 3, 'confirmado'),  -- Carmen s'apunta
(3, 5, 'confirmado'),  -- Rosa crea l'esdeveniment
(3, 4, 'confirmado');  -- Antonio s'apunta

-- ============================================
-- DADES INICIALS: Publicaciones d'exemple
-- ============================================
INSERT INTO publicaciones (usuario_id, contenido, tipo, visibilidad) VALUES
(1, '¡Hola a todos! Acabo de unirme a RedAmigos y estoy encantada de conocer gente nueva. ¿Alguien de Alicante para tomar un café? ☕', 'texto', 'amigos'),
(2, 'Compartiendo algunas fotos de mi última excursión por la Sierra de Espadán. ¡Qué vistas! 📸🏞️', 'foto', 'publico'),
(5, 'Terminé mi primera clase de yoga del mes. Me siento renovada y con mucha energía. ¡Os animo a probar! 🧘‍♀️', 'texto', 'amigos');

-- ============================================
-- FI DE SCRIPT
-- ============================================
