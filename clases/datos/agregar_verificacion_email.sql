-- Verificacion del email en el registro de miembros, para una base peliculas_bd ya creada
-- (si creas la base desde cero con peliculas_bd.sql no hace falta ejecutar este archivo)

USE peliculas_bd;

-- 1 cuando el miembro ha pulsado el enlace del correo; sin verificar no puede iniciar sesion
ALTER TABLE miembro
    ADD COLUMN verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar,
    ADD COLUMN email_verificado_en DATETIME NULL AFTER verificado;

-- los miembros que ya existian se registraron antes de esta funcion: se dan por verificados
UPDATE miembro SET verificado = 1, email_verificado_en = NOW();

-- tokens enviados por correo. Solo se guarda el hash SHA-256 del token, nunca el token en claro:
-- si alguien leyera la base de datos, no podria usar los enlaces
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
