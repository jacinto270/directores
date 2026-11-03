<?php

require_once("clases/peliculaDAO.php");
$dao = new PeliculasDAO();

$peliculas = $dao->getAll();

//generos para los filtros
$generos = array_unique(array_map(fn($p) => $p->getGenero(), $peliculas));
sort($generos);

$titulo = "Películas";
$base = "";
$seccion = "peliculas";
require __DIR__ . "/includes/cabecera.php";
?>

    <section class="portada">
        <span class="portada__antetitulo">Catálogo</span>
        <h1>Descubre nuestra colección de películas</h1>
        <p><?php echo count($peliculas); ?> películas de <?php echo count($generos); ?> géneros, de los clásicos a los estrenos más recientes.</p>
        <div class="buscador buscador--grande">
            <input type="search" placeholder="Busca por título, director o actor..." aria-label="Buscar película" data-buscador>
        </div>
    </section>

    <div class="filtros">
        <button type="button" class="chip activo" data-filtro="todos">Todas</button>
        <?php foreach ($generos as $genero): ?>
            <button type="button" class="chip" data-filtro="<?php echo htmlspecialchars($genero); ?>"><?php echo htmlspecialchars($genero); ?></button>
        <?php endforeach; ?>
    </div>

    <div class="rejilla">
        <?php foreach ($peliculas as $p): ?>
            <?php
                $actores = implode(", ", $p->getActores());
                $buscar = $p->getTitulo() . " " . $p->getDirector() . " " . $actores;
            ?>
            <article class="pelicula" data-buscar="<?php echo htmlspecialchars($buscar); ?>" data-categorias="<?php echo htmlspecialchars($p->getGenero()); ?>">
                <div class="pelicula__portada">
                    <span class="pelicula__inicial"><?php echo htmlspecialchars($p->getTitulo()); ?></span>
                    <!-- si la imagen no existe se quita y queda el titulo sobre el degradado -->
                    <img src="img/<?php echo htmlspecialchars($p->getImagen()); ?>" alt="<?php echo htmlspecialchars($p->getTitulo()); ?>" loading="lazy" onerror="this.remove()">
                    <span class="pelicula__estado"><?php echo $p->getAnio(); ?></span>
                </div>
                <div class="pelicula__cuerpo">
                    <span class="etiqueta etiqueta--genero"><?php echo htmlspecialchars($p->getGenero()); ?></span>
                    <h2 class="pelicula__titulo"><?php echo htmlspecialchars($p->getTitulo()); ?></h2>
                    <div class="pelicula__meta">Dirigida por <?php echo htmlspecialchars($p->getDirector()); ?></div>
                    <?php if ($actores !== ""): ?>
                        <div class="pelicula__actores">Con <?php echo htmlspecialchars($actores); ?></div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="tarjeta vacio" data-sin-resultados <?php echo count($peliculas) > 0 ? "hidden" : ""; ?>>
        No hay películas que coincidan con tu búsqueda.
    </div>

<?php require __DIR__ . "/includes/pie.php"; ?>
