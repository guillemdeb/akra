-- ============================================================
-- MIGRACIÓ: Sistema de codis d'invitació
-- Executar una sola vegada sobre la BD existent
-- ============================================================

CREATE TABLE IF NOT EXISTS codigos_invitacion (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(12) NOT NULL UNIQUE,
    creado_por      INT NOT NULL COMMENT 'Admin que el va generar',
    usado_por       INT NULL COMMENT 'Usuari que el va usar (NULL = disponible)',
    fecha_creacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_uso       TIMESTAMP NULL,
    nota            VARCHAR(255) NULL COMMENT 'Per a qui és (opcional)',
    activo          BOOLEAN DEFAULT TRUE COMMENT 'Fals = anul·lat per admin',
    
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usado_por)  REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_codigo  (codigo),
    INDEX idx_activo  (activo),
    INDEX idx_usado   (usado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna al registre per saber quin codi va usar
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS codigo_invitacion VARCHAR(12) NULL
        COMMENT 'Codi d\'invitació usat en el registre'
    AFTER aprobado;
