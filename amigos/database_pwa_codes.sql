-- ============================================================
-- MIGRACIÓ: Codis personals d'instal·lació PWA
-- Executar UNA SOLA VEGADA sobre la BD existent
-- ============================================================

-- Columna del codi personal a cada usuari
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS codigo_pwa VARCHAR(10) NULL UNIQUE
        COMMENT 'Codi personal per instal·lar la PWA'
    AFTER aprobado;

-- Taula de sessions d'instal·lació (log de qui ha instal·lat i quan)
CREATE TABLE IF NOT EXISTS pwa_instalaciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    codigo_usat     VARCHAR(10) NOT NULL,
    ip              VARCHAR(45),
    user_agent      VARCHAR(500),
    fecha           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha   (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
