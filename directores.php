<?php

require_once("clases/directorDAO.php");
require_once("clases/peliculaDAO.php");

$directores = (new DirectorDAO())->findAll();
$peliculas = (new PeliculasDAO())->getAll();

//peliculas de cada director (la pelicula guarda el nombre del director)
$peliculasPorDirector = [];
foreach ($peliculas as $p) {
    $peliculasPorDirector[$p->getDirector()][] = $p;
}

$titulo = "Directores";
$base = "";
$seccion = "directores";
require __DIR__ . "/includes/cabecera.php";
?>

    <section class="portada">
        <span class="portada__antetitulo">Directores</span>
        <h1>Las personas detrás de cada película</h1>
        <p>Conoce la trayectoria de los <?php echo count($directores); ?> directores de nuestro catálogo.</p>
        <div class="buscador buscador--grande">
            <input type="search" placeholder="Busca un director o una nacionalidad..." aria-label="Buscar director" data-buscador>
        </div>
    </section>

    <div class="rejilla-directores">
        <?php foreach ($directores as $director): ?>
            <?php $suyas = $peliculasPorDirector[$director->getNombre()] ?? []; ?>
            <article class="ficha" data-buscar="<?php echo htmlspecialchars($director->getNombre() . " " . $director->getNacionalidad()); ?>">
                <div class="ficha__cabecera">
                    <span class="avatar avatar--grande" data-nombre="<?php echo htmlspecialchars($director->getNombre()); ?>"></span>
                    <div>
                        <h2><?php echo htmlspecialchars($director->getNombre()); ?></h2>
                        <div class="pelicula__meta">
                            <?php echo htmlspecialchars($director->getNacionalidad()); ?>
                            · Nacido el <?php echo date("d/m/Y", strtotime($director->getFechaNacimiento())); ?>
                        </div>
                    </div>
                </div>

                <?php if (trim($director->getBiografia()) !== ""): ?>
                    <p class="biografia"><?php echo htmlspecialchars($director->getBiografia()); ?></p>
                <?php endif; ?>

                <?php if (count($suyas) > 0): ?>
                    <div class="ficha__peliculas">
                        <span class="ficha__titulo">En el catálogo</span>
                        <?php foreach ($suyas as $p): ?>
                            <span class="etiqueta etiqueta--genero"><?php echo htmlspecialchars($p->getTitulo()); ?> (<?php echo $p->getAnio(); ?>)</span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="tarjeta vacio" data-sin-resultados <?php echo count($directores) > 0 ? "hidden" : ""; ?>>
        No hay directores que coincidan con tu búsqueda.
    </div>

<?php require __DIR__ . "/includes/pie.php"; ?>
