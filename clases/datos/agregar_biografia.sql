-- Añade la columna biografia a la tabla director de una base peliculas_bd ya creada
-- (si creas la base desde cero con peliculas_bd.sql no hace falta ejecutar este archivo)

USE peliculas_bd;

ALTER TABLE director ADD COLUMN biografia TEXT NULL AFTER fecha_nacimiento;

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
