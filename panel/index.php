<?php
require_once __DIR__ . "/../includes/sesion.php";
requiereLogin("../");
require_once __DIR__ . "/../clases/directorDAO.php";
require_once __DIR__ . "/../clases/peliculaDAO.php";

$directorDAO = new DirectorDAO();
$peliculaDAO = new PeliculasDAO();

$listaDirectores = $directorDAO->findAll();
$listaPeliculas = $peliculaDAO->getAll();

//pestaña que se muestra: peliculas o directores
$tab = ($_GET["tab"] ?? "peliculas") === "directores" ? "directores" : "peliculas";

//datos para el resumen
$vistas = count(array_filter($listaPeliculas, fn($p) => $p->isVisto()));
$sinBiografia = count(array_filter($listaDirectores, fn($d) => trim($d->getBiografia()) === ""));

$titulo = $tab === "directores" ? "Directores" : "Películas";
$seccion = $tab;
require __DIR__ . "/../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1>Panel de control</h1>
            <p>Crea, modifica y elimina el contenido que ve el público.</p>
        </div>
    </div>

    <section class="resumen">
        <div class="dato">
            <div class="dato__valor"><?php echo count($listaPeliculas); ?></div>
            <div class="dato__etiqueta">Películas</div>
        </div>
        <div class="dato dato--menta">
            <div class="dato__valor"><?php echo $vistas; ?></div>
            <div class="dato__etiqueta">Vistas</div>
        </div>
        <div class="dato dato--cielo">
            <div class="dato__valor"><?php echo count($listaDirectores); ?></div>
            <div class="dato__etiqueta">Directores</div>
        </div>
        <div class="dato dato--melocoton">
            <div class="dato__valor"><?php echo $sinBiografia; ?></div>
            <div class="dato__etiqueta">Directores sin biografía</div>
        </div>
    </section>

    <div class="pestanas" role="tablist">
        <a href="?tab=peliculas" class="pestana <?php echo $tab === "peliculas" ? "activo" : ""; ?>" role="tab" aria-selected="<?php echo $tab === "peliculas" ? "true" : "false"; ?>">
            Películas <span class="pestana__total"><?php echo count($listaPeliculas); ?></span>
        </a>
        <a href="?tab=directores" class="pestana <?php echo $tab === "directores" ? "activo" : ""; ?>" role="tab" aria-selected="<?php echo $tab === "directores" ? "true" : "false"; ?>">
            Directores <span class="pestana__total"><?php echo count($listaDirectores); ?></span>
        </a>
    </div>

    <div class="tarjeta">
        <div class="tarjeta__barra">
            <div class="buscador">
                <input type="search" placeholder="Buscar <?php echo $tab === "directores" ? "director" : "película"; ?>..." aria-label="Buscar" data-buscador>
            </div>
            <?php if ($tab === "directores"): ?>
                <a class="boton boton--primario" href="director/formDirector.php">+ Nuevo director</a>
            <?php else: ?>
                <a class="boton boton--primario" href="pelicula/formPelicula.php">+ Nueva película</a>
            <?php endif; ?>
        </div>

        <div class="tabla-scroll">
            <?php if ($tab === "peliculas"): ?>
                <table class="tabla tabla--adaptable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Película</th>
                            <th>Director</th>
                            <th>Año</th>
                            <th>Género</th>
                            <th>Estado</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaPeliculas as $p): ?>
                            <tr data-buscar="<?php echo htmlspecialchars($p->getTitulo() . " " . $p->getDirector() . " " . $p->getGenero() . " " . implode(" ", $p->getActores())); ?>">
                                <td class="col-id"><?php echo $p->getId(); ?></td>
                                <td class="col-principal">
                                    <div class="persona">
                                        <span class="miniatura">
                                            <img src="../img/<?php echo htmlspecialchars($p->getImagen()); ?>" alt="" loading="lazy" onerror="this.remove()">
                                        </span>
                                        <span>
                                            <?php echo htmlspecialchars($p->getTitulo()); ?>
                                            <small class="persona__detalle"><?php echo htmlspecialchars(implode(", ", $p->getActores())); ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Director"><?php echo htmlspecialchars($p->getDirector()); ?></td>
                                <td class="fecha" data-label="Año"><?php echo $p->getAnio(); ?></td>
                                <td data-label="Género"><span class="etiqueta etiqueta--genero"><?php echo htmlspecialchars($p->getGenero()); ?></span></td>
                                <td data-label="Estado">
                                    <?php if ($p->isVisto()): ?>
                                        <span class="etiqueta">✓ Vista</span>
                                    <?php else: ?>
                                        <span class="etiqueta etiqueta--pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-acciones">
                                    <a class="boton boton--pequeno boton--editar" href="pelicula/formPelicula.php?id=<?php echo $p->getId(); ?>">Editar</a>
                                    <form action="pelicula/eliminarPelicula.php" method="post" data-confirmar="Se eliminará «<?php echo htmlspecialchars($p->getTitulo()); ?>». Esta acción no se puede deshacer.">
                                        <?php echo campoToken(); ?>
                                        <input type="hidden" name="id" value="<?php echo $p->getId(); ?>">
                                        <button type="submit" class="boton boton--pequeno boton--eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="tabla tabla--adaptable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Nacionalidad</th>
                            <th>Nacimiento</th>
                            <th>Biografía</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listaDirectores as $director): ?>
                            <tr data-buscar="<?php echo htmlspecialchars($director->getNombre() . " " . $director->getNacionalidad()); ?>">
                                <td class="col-id"><?php echo $director->getId(); ?></td>
                                <td class="col-principal">
                                    <div class="persona">
                                        <span class="avatar" data-nombre="<?php echo htmlspecialchars($director->getNombre()); ?>"></span>
                                        <?php echo htmlspecialchars($director->getNombre()); ?>
                                    </div>
                                </td>
                                <td data-label="Nacionalidad"><span class="etiqueta"><?php echo htmlspecialchars($director->getNacionalidad()); ?></span></td>
                                <td class="fecha" data-label="Nacimiento"><?php echo date("d/m/Y", strtotime($director->getFechaNacimiento())); ?></td>
                                <td class="col-bio" data-label="Biografía">
                                    <?php if (trim($director->getBiografia()) !== ""): ?>
                                        <p class="biografia"><?php echo htmlspecialchars($director->getBiografia()); ?></p>
                                    <?php else: ?>
                                        <span class="fecha">Sin biografía</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-acciones">
                                    <a class="boton boton--pequeno boton--editar" href="director/formDirector.php?id=<?php echo $director->getId(); ?>">Editar</a>
                                    <form action="director/eliminarDirector.php" method="post" data-confirmar="Se eliminará a <?php echo htmlspecialchars($director->getNombre()); ?>. Esta acción no se puede deshacer.">
                                        <?php echo campoToken(); ?>
                                        <input type="hidden" name="id" value="<?php echo $director->getId(); ?>">
                                        <button type="submit" class="boton boton--pequeno boton--eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php $total = $tab === "directores" ? count($listaDirectores) : count($listaPeliculas); ?>
        <div class="vacio" data-sin-resultados <?php echo $total > 0 ? "hidden" : ""; ?>>
            No hay resultados que mostrar.
        </div>
    </div>

<?php require __DIR__ . "/../includes/pie_panel.php"; ?>
