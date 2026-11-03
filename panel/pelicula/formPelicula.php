<?php
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../clases/directorDAO.php";

$dao = new PeliculasDAO();
$directorDAO = new DirectorDAO();

$pelicula = null;

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $pelicula = $dao->getById($id);
}

//directores para el desplegable y generos existentes para sugerir
$directores = $directorDAO->findAll();
$generos = array_unique(array_map(fn($p) => $p->getGenero(), $dao->getAll()));
sort($generos);

//si la pelicula tiene un director que no esta en la tabla director, se añade igualmente a la lista
$nombresDirectores = array_map(fn($d) => $d->getNombre(), $directores);
if ($pelicula && !in_array($pelicula->getDirector(), $nombresDirectores)) {
    $nombresDirectores[] = $pelicula->getDirector();
}
sort($nombresDirectores);

$titulo = $pelicula ? "Editar película" : "Nueva película";
$seccion = "peliculas";
$base = "../../";
require __DIR__ . "/../../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1><?php echo $titulo; ?></h1>
            <p><?php echo $pelicula ? "Modifica los datos de " . htmlspecialchars($pelicula->getTitulo()) . "." : "Añade una película a tu colección."; ?></p>
        </div>
        <a class="boton boton--secundario" href="../index.php?tab=peliculas">← Volver</a>
    </div>

    <form class="tarjeta formulario" action="guardarPelicula.php" method="post" enctype="multipart/form-data">
        <?php if ($pelicula): ?>
            <input type="hidden" name="id" value="<?php echo $pelicula->getId(); ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" maxlength="150" value="<?php echo $pelicula ? htmlspecialchars($pelicula->getTitulo()) : ''; ?>" required>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="director">Director</label>
                <select id="director" name="director" required>
                    <option value="">Selecciona un director</option>
                    <?php foreach ($nombresDirectores as $nombre): ?>
                        <option value="<?php echo htmlspecialchars($nombre); ?>" <?php echo $pelicula && $pelicula->getDirector() === $nombre ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="campo__nota">¿No está en la lista? <a href="../director/formDirector.php">Añade el director</a>.</span>
            </div>

            <div class="campo">
                <label for="anio">Año</label>
                <input type="number" id="anio" name="anio" min="1888" max="<?php echo date("Y") + 5; ?>" value="<?php echo $pelicula ? $pelicula->getAnio() : ''; ?>" required>
            </div>
        </div>

        <div class="campo">
            <label for="genero">Género</label>
            <input type="text" id="genero" name="genero" maxlength="50" list="lista-generos" value="<?php echo $pelicula ? htmlspecialchars($pelicula->getGenero()) : ''; ?>" required>
            <datalist id="lista-generos">
                <?php foreach ($generos as $genero): ?>
                    <option value="<?php echo htmlspecialchars($genero); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="campo">
            <label for="actores">Actores</label>
            <input type="text" id="actores" name="actores" placeholder="Separados por comas: Actor Uno, Actor Dos" value="<?php echo $pelicula ? htmlspecialchars(implode(", ", $pelicula->getActores())) : ''; ?>">
        </div>

        <div class="campo">
            <label for="imagen">Portada</label>
            <div class="subida">
                <div class="subida__vista" data-vista-previa>
                    <?php if ($pelicula && $pelicula->getImagen() !== "" && is_file(__DIR__ . "/../../img/" . $pelicula->getImagen())): ?>
                        <img src="../../img/<?php echo htmlspecialchars($pelicula->getImagen()); ?>" alt="Portada actual">
                    <?php else: ?>
                        <span>Sin portada</span>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif" data-subida>
                    <span class="campo__nota">JPG, PNG, WEBP o GIF, máximo 2 MB.<?php echo $pelicula ? " Si no eliges ninguna se mantiene la actual." : ""; ?></span>
                </div>
            </div>
        </div>

        <label class="interruptor">
            <input type="checkbox" name="visto" value="1" <?php echo $pelicula && $pelicula->isVisto() ? "checked" : ""; ?>>
            <span class="interruptor__pista"></span>
            Ya la he visto
        </label>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php?tab=peliculas">Cancelar</a>
            <button type="submit" class="boton boton--primario"><?php echo $pelicula ? "Actualizar" : "Guardar"; ?></button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
