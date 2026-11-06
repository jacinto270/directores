-- Base de datos peliculas_bd (MySQL / MariaDB - XAMPP)
-- Tablas basadas en las clases Director y Pelicula del proyecto

CREATE DATABASE IF NOT EXISTS peliculas_bd
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE peliculas_bd;

DROP TABLE IF EXISTS pelicula;
DROP TABLE IF EXISTS director;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS token_email;
DROP TABLE IF EXISTS miembro;

-- Tabla director: id, nombre, nacionalidad, fecha_nacimiento, biografia
CREATE TABLE director (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nacionalidad VARCHAR(60) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    biografia TEXT NULL
) ENGINE=InnoDB;

-- Tabla pelicula: id, titulo, director, anio, genero, imagen, visto, actores
-- director guarda el nombre del director (la clase Pelicula lo maneja como string)
-- visto se almacena como 0 o 1
-- actores se almacena como JSON, por ejemplo ["Actor Uno", "Actor Dos"]
CREATE TABLE pelicula (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    director VARCHAR(100) NOT NULL,
    anio INT NOT NULL,
    genero VARCHAR(50) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    visto TINYINT(1) NOT NULL DEFAULT 0 CHECK (visto IN (0, 1)),
    actores TEXT NOT NULL DEFAULT '[]'
) ENGINE=InnoDB;

-- Datos de muestra: directores
INSERT INTO director (nombre, nacionalidad, fecha_nacimiento) VALUES
('Christopher Nolan', 'Británica', '1970-07-30'),
('Guillermo del Toro', 'Mexicana', '1964-10-09'),
('Steven Spielberg', 'Estadounidense', '1946-12-18'),
('Quentin Tarantino', 'Estadounidense', '1963-03-27'),
('Alfonso Cuarón', 'Mexicana', '1961-11-28'),
('Martin Scorsese', 'Estadounidense', '1942-11-17'),
('Hayao Miyazaki', 'Japonesa', '1941-01-05'),
('Pedro Almodóvar', 'Española', '1949-09-25'),
('Alejandro González Iñárritu', 'Mexicana', '1963-08-15'),
('Denis Villeneuve', 'Canadiense', '1967-10-03');

-- Datos de muestra: películas
INSERT INTO pelicula (titulo, director, anio, genero, imagen, visto, actores) VALUES
('Inception', 'Christopher Nolan', 2010, 'Ciencia ficción', 'inception.jpg', 1, '["Leonardo DiCaprio", "Joseph Gordon-Levitt", "Elliot Page"]'),
('El laberinto del fauno', 'Guillermo del Toro', 2006, 'Fantasía', 'laberinto_del_fauno.jpg', 1, '["Ivana Baquero", "Sergi López", "Maribel Verdú"]'),
('Jurassic Park', 'Steven Spielberg', 1993, 'Aventura', 'jurassic_park.jpg', 1, '["Sam Neill", "Laura Dern", "Jeff Goldblum"]'),
('Pulp Fiction', 'Quentin Tarantino', 1994, 'Crimen', 'pulp_fiction.jpg', 0, '["John Travolta", "Uma Thurman", "Samuel L. Jackson"]'),
('Roma', 'Alfonso Cuarón', 2018, 'Drama', 'roma.jpg', 0, '["Yalitza Aparicio", "Marina de Tavira"]'),
('El irlandés', 'Martin Scorsese', 2019, 'Crimen', 'el_irlandes.jpg', 0, '["Robert De Niro", "Al Pacino", "Joe Pesci"]'),
('El viaje de Chihiro', 'Hayao Miyazaki', 2001, 'Animación', 'el_viaje_de_chihiro.jpg', 1, '["Rumi Hiiragi", "Miyu Irino"]'),
('Todo sobre mi madre', 'Pedro Almodóvar', 1999, 'Drama', 'todo_sobre_mi_madre.jpg', 0, '["Cecilia Roth", "Marisa Paredes", "Penélope Cruz"]'),
('El renacido', 'Alejandro González Iñárritu', 2015, 'Aventura', 'el_renacido.jpg', 1, '["Leonardo DiCaprio", "Tom Hardy"]'),
('Dune', 'Denis Villeneuve', 2021, 'Ciencia ficción', 'dune.jpg', 0, '["Timothée Chalamet", "Rebecca Ferguson", "Zendaya"]');

-- Biografías de los directores
UPDATE director SET biografia = 'Director y guionista británico conocido por narrativas complejas y estructuras temporales no lineales. Ha dirigido Memento, la trilogía de El caballero oscuro, Interstellar y Oppenheimer, por la que ganó el Óscar a mejor director.' WHERE nombre = 'Christopher Nolan';
UPDATE director SET biografia = 'Cineasta tapatío que mezcla fantasía, terror y melodrama. Su universo de criaturas y monstruos destaca en El espinazo del diablo, El laberinto del fauno y La forma del agua, ganadora del Óscar a mejor película.' WHERE nombre = 'Guillermo del Toro';
UPDATE director SET biografia = 'Uno de los directores más influyentes de Hollywood y pionero del cine de gran espectáculo. Entre sus películas están Tiburón, E.T., Jurassic Park y La lista de Schindler.' WHERE nombre = 'Steven Spielberg';
UPDATE director SET biografia = 'Director y guionista autodidacta que se formó trabajando en un videoclub. Su estilo combina diálogos afilados, violencia estilizada y referencias al cine de género en Pulp Fiction, Kill Bill y Érase una vez en Hollywood.' WHERE nombre = 'Quentin Tarantino';
UPDATE director SET biografia = 'Director mexicano de gran versatilidad, de Y tu mamá también a Harry Potter y el prisionero de Azkaban. Ganó el Óscar a mejor director por Gravity y por Roma, un retrato íntimo de su infancia en la Ciudad de México.' WHERE nombre = 'Alfonso Cuarón';
UPDATE director SET biografia = 'Figura clave del Nuevo Hollywood, famoso por sus retratos del crimen y la culpa. Ha dirigido Taxi Driver, Toro salvaje, Uno de los nuestros, Infiltrados y El irlandés.' WHERE nombre = 'Martin Scorsese';
UPDATE director SET biografia = 'Cofundador de Studio Ghibli y maestro de la animación japonesa. Sus películas, como Mi vecino Totoro, La princesa Mononoke y El viaje de Chihiro, destacan por su imaginación y su mirada sobre la naturaleza.' WHERE nombre = 'Hayao Miyazaki';
UPDATE director SET biografia = 'Director manchego surgido de la Movida madrileña. Su cine, lleno de color, melodrama y personajes femeninos inolvidables, incluye Mujeres al borde de un ataque de nervios, Todo sobre mi madre y Hable con ella.' WHERE nombre = 'Pedro Almodóvar';
UPDATE director SET biografia = 'Director mexicano conocido por historias entrelazadas y de gran intensidad emocional como Amores perros y Babel. Ganó dos años seguidos el Óscar a mejor director por Birdman y El renacido.' WHERE nombre = 'Alejandro González Iñárritu';
UPDATE director SET biografia = 'Director quebequense especializado en ciencia ficción y thrillers de atmósfera densa. Ha dirigido Incendies, Prisioneros, La llegada, Blade Runner 2049 y la adaptación de Dune.' WHERE nombre = 'Denis Villeneuve';

-- password guarda la contraseña cifrada con password_hash (60 caracteres o más)
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    -- admin gestiona usuarios; usuario solo peliculas y directores
    rol ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB;

-- Usuario de ejemplo: admin / admin123, con rol de administrador (cámbiale la contraseña)
INSERT INTO usuario (nombre, password, rol) VALUES
('admin', '$2y$10$KmViNnMHjL4D8fC8rAAAqurYU0fHI4Buwi4Og6PPi7ueTJpi8xbV6', 'admin');

-- Cuentas del sitio publico (no tienen acceso al panel)
CREATE TABLE miembro (
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
CREATE TABLE token_email (
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
