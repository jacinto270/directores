-- Crea la tabla miembro en una base peliculas_bd ya creada
-- (si creas la base desde cero con peliculas_bd.sql no hace falta ejecutar este archivo)
-- Los miembros son las cuentas del sitio publico: pueden ver el catalogo pero NO entrar al panel
-- (los usuarios del panel estan en la tabla usuario)

USE peliculas_bd;

CREATE TABLE IF NOT EXISTS miembro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    -- nombre del archivo de la foto de perfil dentro de img/avatares (NULL si no tiene)
    avatar VARCHAR(255) NULL,
    -- 1 cuando ha pulsado el enlace del correo de verificacion; sin verificar no puede entrar
    verificado TINYINT(1) NOT NULL DEFAULT 0,
    email_verificado_en DATETIME NULL,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tokens enviados por correo para verificar el email (solo se guarda su hash SHA-256)
CREATE TABLE IF NOT EXISTS token_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    miembro_id INT NOT NULL,
    tipo ENUM('verificar') NOT NULL DEFAULT 'verificar',
    token_hash CHAR(64) NOT NULL UNIQUE,
    caduca_en DATETIME NOT NULL,
    usado_en DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_token_miembro (miembro_id, tipo),
    CONSTRAINT fk_token_miembro FOREIGN KEY (miembro_id) REFERENCES miembro (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Si la tabla ya existia sin foto de perfil, añade la columna:
-- ALTER TABLE miembro ADD COLUMN avatar VARCHAR(255) NULL AFTER password;
