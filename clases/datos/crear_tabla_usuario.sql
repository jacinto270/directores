-- Crea la tabla usuario en una base peliculas_bd ya creada
-- (si creas la base desde cero con peliculas_bd.sql no hace falta ejecutar este archivo)

USE peliculas_bd;

-- password guarda la contraseña cifrada con password_hash (60 caracteres o más)
CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    -- admin gestiona usuarios; usuario solo peliculas y directores
    rol ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB;

-- Usuario de ejemplo: admin / admin123, con rol de administrador (cámbiale la contraseña)
INSERT IGNORE INTO usuario (nombre, password, rol) VALUES
('admin', '$2y$10$KmViNnMHjL4D8fC8rAAAqurYU0fHI4Buwi4Og6PPi7ueTJpi8xbV6', 'admin');
