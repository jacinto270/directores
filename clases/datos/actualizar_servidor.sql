-- ============================================================================
--  Catálogo de películas · Actualización de la base de datos
-- ============================================================================
--  Pone al día una base de datos de cualquier versión anterior del proyecto
--  con todos los cambios hechos durante el desarrollo:
--
--    1. director:    columna biografia (+ biografías de los directores de ejemplo)
--    2. usuario:     tabla de usuarios del panel, con contraseña cifrada y rol
--    3. miembro:     tabla de cuentas del sitio público, con avatar y verificación del email
--    4. token_email: enlaces de verificación del email (solo se guarda su hash)
--
--  CÓMO USARLO (en el hosting):
--    1. Haz una copia de seguridad: phpMyAdmin > tu base > Exportar.
--    2. En phpMyAdmin, selecciona TU BASE en la columna de la izquierda.
--    3. Pestaña "Importar" > elige este archivo > Continuar.
--
--  Es seguro ejecutarlo varias veces: cada paso comprueba antes si ya está hecho
--  y, si es así, no toca nada. No borra tablas ni datos.
--  No lleva "USE peliculas_bd" a propósito: en el hosting la base tiene otro nombre
--  y se usa la que tengas seleccionada.
--  Compatible con MySQL 5.7+ y MariaDB 10.2+ (no usa procedimientos almacenados).
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. director: biografía
-- ----------------------------------------------------------------------------

SET @falta = (SELECT COUNT(*) = 0 FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'director' AND column_name = 'biografia');
SET @sql = IF(@falta, 'ALTER TABLE director ADD COLUMN biografia TEXT NULL AFTER fecha_nacimiento', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- biografías de los directores de ejemplo (solo si todavía no tienen ninguna)
UPDATE director SET biografia = 'Director y guionista británico conocido por narrativas complejas y estructuras temporales no lineales. Ha dirigido Memento, la trilogía de El caballero oscuro, Interstellar y Oppenheimer, por la que ganó el Óscar a mejor director.' WHERE nombre = 'Christopher Nolan' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Cineasta tapatío que mezcla fantasía, terror y melodrama. Su universo de criaturas y monstruos destaca en El espinazo del diablo, El laberinto del fauno y La forma del agua, ganadora del Óscar a mejor película.' WHERE nombre = 'Guillermo del Toro' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Uno de los directores más influyentes de Hollywood y pionero del cine de gran espectáculo. Entre sus películas están Tiburón, E.T., Jurassic Park y La lista de Schindler.' WHERE nombre = 'Steven Spielberg' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Director y guionista autodidacta que se formó trabajando en un videoclub. Su estilo combina diálogos afilados, violencia estilizada y referencias al cine de género en Pulp Fiction, Kill Bill y Érase una vez en Hollywood.' WHERE nombre = 'Quentin Tarantino' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Director mexicano de gran versatilidad, de Y tu mamá también a Harry Potter y el prisionero de Azkaban. Ganó el Óscar a mejor director por Gravity y por Roma, un retrato íntimo de su infancia en la Ciudad de México.' WHERE nombre = 'Alfonso Cuarón' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Figura clave del Nuevo Hollywood, famoso por sus retratos del crimen y la culpa. Ha dirigido Taxi Driver, Toro salvaje, Uno de los nuestros, Infiltrados y El irlandés.' WHERE nombre = 'Martin Scorsese' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Cofundador de Studio Ghibli y maestro de la animación japonesa. Sus películas, como Mi vecino Totoro, La princesa Mononoke y El viaje de Chihiro, destacan por su imaginación y su mirada sobre la naturaleza.' WHERE nombre = 'Hayao Miyazaki' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Director manchego surgido de la Movida madrileña. Su cine, lleno de color, melodrama y personajes femeninos inolvidables, incluye Mujeres al borde de un ataque de nervios, Todo sobre mi madre y Hable con ella.' WHERE nombre = 'Pedro Almodóvar' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Director mexicano conocido por historias entrelazadas y de gran intensidad emocional como Amores perros y Babel. Ganó dos años seguidos el Óscar a mejor director por Birdman y El renacido.' WHERE nombre = 'Alejandro González Iñárritu' AND (biografia IS NULL OR biografia = '');
UPDATE director SET biografia = 'Director quebequense especializado en ciencia ficción y thrillers de atmósfera densa. Ha dirigido Incendies, Prisioneros, La llegada, Blade Runner 2049 y la adaptación de Dune.' WHERE nombre = 'Denis Villeneuve' AND (biografia IS NULL OR biografia = '');

-- ----------------------------------------------------------------------------
-- 2. usuario: usuarios del panel de administración
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    -- admin gestiona usuarios; usuario solo películas y directores
    rol ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- versiones antiguas: la columna se llamaba "passwrod" (errata) y era demasiado corta
SET @errata = (SELECT COUNT(*) > 0 FROM information_schema.columns
               WHERE table_schema = DATABASE() AND table_name = 'usuario' AND column_name = 'passwrod');
SET @sql = IF(@errata, 'ALTER TABLE usuario CHANGE passwrod password VARCHAR(255) NOT NULL', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

SET @sql = 'ALTER TABLE usuario MODIFY password VARCHAR(255) NOT NULL';
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- versiones antiguas: el id no era autoincremental
SET @sin_auto = (SELECT COUNT(*) > 0 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = 'usuario' AND column_name = 'id'
                 AND extra NOT LIKE '%auto_increment%');
SET @sql = IF(@sin_auto, 'ALTER TABLE usuario MODIFY id INT NOT NULL AUTO_INCREMENT', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- rol de cada usuario
SET @falta = (SELECT COUNT(*) = 0 FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'usuario' AND column_name = 'rol');
SET @sql = IF(@falta, 'ALTER TABLE usuario ADD COLUMN rol ENUM(''admin'', ''usuario'') NOT NULL DEFAULT ''usuario''', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- al añadir el rol, el usuario "admin" pasa a ser administrador
SET @sql = IF(@falta, 'UPDATE usuario SET rol = ''admin'' WHERE nombre = ''admin''', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- si no hay ningún usuario, se crea admin / admin123 como administrador (¡cámbiale la contraseña!)
INSERT INTO usuario (nombre, password, rol)
SELECT 'admin', '$2y$10$gNhDi4olCw2r61CFDA6jJuy/x4.g6lop7ZKlc3O5dj/Q.6kWmT5hm', 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM usuario);

-- si hay usuarios pero ninguno es administrador, nadie podría gestionar usuarios: el más antiguo pasa a admin
UPDATE usuario SET rol = 'admin'
WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM usuario) AS primero)
AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM usuario WHERE rol = 'admin') AS admins);

-- ----------------------------------------------------------------------------
-- 3. miembro: cuentas del sitio público
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS miembro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    verificado TINYINT(1) NOT NULL DEFAULT 0,
    email_verificado_en DATETIME NULL,
    fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- foto de perfil
SET @falta = (SELECT COUNT(*) = 0 FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'miembro' AND column_name = 'avatar');
SET @sql = IF(@falta, 'ALTER TABLE miembro ADD COLUMN avatar VARCHAR(255) NULL AFTER password', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- verificación del email
SET @falta_verificado = (SELECT COUNT(*) = 0 FROM information_schema.columns
                         WHERE table_schema = DATABASE() AND table_name = 'miembro' AND column_name = 'verificado');
SET @sql = IF(@falta_verificado, 'ALTER TABLE miembro ADD COLUMN verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

SET @falta = (SELECT COUNT(*) = 0 FROM information_schema.columns
              WHERE table_schema = DATABASE() AND table_name = 'miembro' AND column_name = 'email_verificado_en');
SET @sql = IF(@falta, 'ALTER TABLE miembro ADD COLUMN email_verificado_en DATETIME NULL AFTER verificado', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- los miembros que ya existían se registraron antes de la verificación: se dan por verificados
-- (solo al añadir la columna; si se vuelve a ejecutar el script, no se verifica a nadie que no lo esté)
SET @sql = IF(@falta_verificado, 'UPDATE miembro SET verificado = 1, email_verificado_en = NOW()', 'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;

-- ----------------------------------------------------------------------------
-- 4. token_email: enlaces de verificación (solo se guarda el hash SHA-256 del token)
-- ----------------------------------------------------------------------------

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Resumen: estado final de la base de datos
-- ----------------------------------------------------------------------------

SELECT 'películas' AS tabla, COUNT(*) AS filas FROM pelicula
UNION ALL SELECT 'directores', COUNT(*) FROM director
UNION ALL SELECT 'directores con biografía', COUNT(*) FROM director WHERE biografia IS NOT NULL AND biografia <> ''
UNION ALL SELECT 'usuarios del panel', COUNT(*) FROM usuario
UNION ALL SELECT 'administradores', COUNT(*) FROM usuario WHERE rol = 'admin'
UNION ALL SELECT 'miembros', COUNT(*) FROM miembro
UNION ALL SELECT 'miembros verificados', COUNT(*) FROM miembro WHERE verificado = 1;
