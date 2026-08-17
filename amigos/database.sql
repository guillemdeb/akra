-- ============================================
-- BASE DE DADES: RedAmigos
-- App social per a gent adulta i gran activa
-- ============================================

CREATE DATABASE IF NOT EXISTS app_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
    ubicacion VARCHAR(200),
    foto VARCHAR(255) DEFAULT 'default.png',
    descripcion TEXT,
    mostrar_telefono BOOLEAN DEFAULT FALSE,
    mostrar_email BOOLEAN DEFAULT FALSE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_ubicacion (ubicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: intereses
-- ============================================
CREATE TABLE IF NOT EXISTS intereses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    icono VARCHAR(100) NOT NULL COMMENT 'Classe FontAwesome',
    categoria VARCHAR(50) DEFAULT 'general',
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: usuario_interes (molts a molts)
-- ============================================
CREATE TABLE IF NOT EXISTS usuario_interes (
    usuario_id INT NOT NULL,
    interes_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, interes_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (interes_id) REFERENCES intereses(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_interes (interes_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: amistades
-- ============================================
CREATE TABLE IF NOT EXISTS amistades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'Qui envia la sol·licitud',
    amigo_id INT NOT NULL COMMENT 'Qui rep la sol·licitud',
    estado ENUM('pendiente', 'aceptada', 'rechazada', 'bloqueada') DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    UNIQUE KEY unico_amistad (usuario_id, amigo_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (amigo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_amigo (amigo_id),
    INDEX idx_estado (estado)
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
    INDEX idx_remitente (remitente_id),
    INDEX idx_destinatario (destinatario_id),
    INDEX idx_leido (leido),
    INDEX idx_fecha (fecha_envio)
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
-- TAULA: password_resets (recuperació contrasenya)
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(150) PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    expiracion DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_expiracion (expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAULA: notificaciones
-- ============================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('amistad', 'mensaje', 'sistema') NOT NULL,
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
-- INSERTAR INTERESES PREDEFINITS
-- ============================================
INSERT INTO intereses (nombre, icono, categoria) VALUES
-- Activitats físiques
('Caminar', 'fas fa-walking', 'deporte'),
('Yoga', 'fas fa-spa', 'deporte'),
('Natación', 'fas fa-swimmer', 'deporte'),
('Bailar', 'fas fa-music', 'deporte'),
('Ciclismo', 'fas fa-bicycle', 'deporte'),

-- Hobbies creatius
('Lectura', 'fas fa-book', 'cultura'),
('Cocina', 'fas fa-utensils', 'hobbies'),
('Jardinería', 'fas fa-leaf', 'hobbies'),
('Fotografía', 'fas fa-camera', 'hobbies'),
('Pintura', 'fas fa-palette', 'hobbies'),
('Manualidades', 'fas fa-scissors', 'hobbies'),

-- Cultura i aprenentatge
('Cine', 'fas fa-film', 'cultura'),
('Teatro', 'fas fa-theater-masks', 'cultura'),
('Música', 'fas fa-headphones', 'cultura'),
('Historia', 'fas fa-landmark', 'cultura'),
('Idiomas', 'fas fa-language', 'cultura'),

-- Tecnologia
('Informática', 'fas fa-laptop', 'tecnologia'),
('Redes Sociales', 'fas fa-share-alt', 'tecnologia'),

-- Jocs i entreteniment
('Juegos de mesa', 'fas fa-chess', 'ocio'),
('Cartas', 'fas fa-cards', 'ocio'),
('Voluntariado', 'fas fa-hands-helping', 'social'),

-- Altres
('Viajes', 'fas fa-plane', 'ocio'),
('Naturaleza', 'fas fa-tree', 'ocio'),
('Mascotas', 'fas fa-dog', 'otros'),
('Café y conversación', 'fas fa-coffee', 'social')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- ============================================
-- USUARIS D'EXEMPLE (per proves)
-- ============================================
-- Contrasenya per defecte: "password123" (hashejada amb password_hash)
INSERT INTO usuarios (nombre, email, password, telefono, edad, genero, ubicacion, descripcion, mostrar_telefono, mostrar_email) VALUES
('María García', 'maria@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666 111 222', 65, 'Mujer', 'Alicante', 'Me encanta pasear por la playa y hacer nuevas amistades. Soy muy activa y me gusta bailar.', TRUE, FALSE),
('Juan Pérez', 'juan@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666 333 444', 70, 'Hombre', 'Valencia', 'Jubilado, me gusta la fotografía y el senderismo. Busco gente con quien compartir aficiones.', FALSE, TRUE),
('Carmen López', 'carmen@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666 555 666', 62, 'Mujer', 'Alicante', 'Apasionada de la cocina y la lectura. Me encanta conocer gente nueva y hacer tertulia.', TRUE, TRUE),
('Antonio Martínez', 'antonio@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666 777 888', 68, 'Hombre', 'Alicante', 'Me gusta el cine, los paseos y las conversaciones tranquilas. Viudo buscando amistad.', FALSE, FALSE),
('Rosa Sánchez', 'rosa@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '666 999 000', 58, 'Mujer', 'Valencia', 'Activa y alegre. Me encanta la naturaleza, el yoga y los animales. Tengo dos gatos.', TRUE, TRUE)
ON DUPLICATE KEY UPDATE nombre=nombre;

-- ============================================
-- INTERESSOS D'EXEMPLE PER ALS USUARIS
-- ============================================
-- María: Caminar, Bailar, Café y conversación
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
(1, 1), (1, 4), (1, 24);

-- Juan: Fotografía, Caminar, Viajes, Naturaleza
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
(2, 9), (2, 1), (2, 22), (2, 23);

-- Carmen: Cocina, Lectura, Cine
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
(3, 7), (3, 6), (3, 12);

-- Antonio: Cine, Caminar, Historia
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
(4, 12), (4, 1), (4, 15);

-- Rosa: Yoga, Naturaleza, Mascotas, Jardinería
INSERT INTO usuario_interes (usuario_id, interes_id) VALUES
(5, 2), (5, 23), (5, 24), (5, 8);

-- ============================================
-- AMISTADES D'EXEMPLE
-- ============================================
-- María i Juan són amics
INSERT INTO amistades (usuario_id, amigo_id, estado, fecha_respuesta) VALUES
(1, 2, 'aceptada', NOW());

-- María ha enviat sol·licitud a Carmen
INSERT INTO amistades (usuario_id, amigo_id, estado) VALUES
(1, 3, 'pendiente');

-- Antonio i Rosa són amics
INSERT INTO amistades (usuario_id, amigo_id, estado, fecha_respuesta) VALUES
(4, 5, 'aceptada', NOW());

-- ============================================
-- FI DE L'SCRIPT
-- ============================================
