<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";

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

//si guardarPelicula.php ha devuelto errores, se muestran con lo que se habia escrito
[$enviado, $errores] = recuperarFormulario("pelicula");
$valor = fn(string $campo, string $actual) => htmlspecialchars((string) ($enviado[$campo] ?? $actual));
$directorElegido = $enviado["director"] ?? ($pelicula ? $pelicula->getDirector() : "");
$vista = array_key_exists("visto", $enviado) ? $enviado["visto"] : ($pelicula && $pelicula->isVisto());

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

    <?php if ($errores): ?>
        <div class="alerta alerta--error formulario-ancho" role="alert">
            Revisa los campos marcados: <?php echo count($errores) === 1 ? "hay 1 error" : "hay " . count($errores) . " errores"; ?>.<?php echo isset($errores["imagen"]) ? "" : " Si habías elegido una portada, vuelve a elegirla."; ?>
        </div>
    <?php endif; ?>

    <form class="tarjeta formulario" action="guardarPelicula.php" method="post" enctype="multipart/form-data" data-validar>
        <?php echo campoToken(); ?>
        <?php if ($pelicula): ?>
            <input type="hidden" name="id" value="<?php echo $pelicula->getId(); ?>">
        <?php endif; ?>

        <div class="campo <?php echo claseError($errores, "titulo"); ?>">
            <label for="titulo">Título <span class="obligatorio" aria-hidden="true">*</span></label>
            <input type="text" id="titulo" name="titulo" maxlength="150" required data-mensaje="El título es obligatorio." value="<?php echo $valor("titulo", $pelicula ? $pelicula->getTitulo() : ""); ?>">
            <?php echo errorCampoPanel($errores, "titulo"); ?>
        </div>

        <div class="fila-campos">
            <div class="campo <?php echo claseError($errores, "director"); ?>">
                <label for="director">Director <span class="obligatorio" aria-hidden="true">*</span></label>
                <select id="director" name="director" required data-mensaje="Elige un director de la lista.">
                    <option value="">Selecciona un director</option>
                    <?php foreach ($nombresDirectores as $nombre): ?>
                        <option value="<?php echo htmlspecialchars($nombre); ?>" <?php echo $directorElegido === $nombre ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo errorCampoPanel($errores, "director"); ?>
                <span class="campo__nota">¿No está en la lista? <a href="../director/formDirector.php">Añade el director</a>.</span>
            </div>

            <div class="campo <?php echo claseError($errores, "anio"); ?>">
                <label for="anio">Año <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="number" id="anio" name="anio" min="1888" max="<?php echo date("Y") + 5; ?>" step="1" required data-mensaje="El año debe estar entre 1888 y <?php echo date("Y") + 5; ?>." value="<?php echo $valor("anio", $pelicula ? (string) $pelicula->getAnio() : ""); ?>">
                <?php echo errorCampoPanel($errores, "anio"); ?>
            </div>
        </div>

        <div class="campo <?php echo claseError($errores, "genero"); ?>">
            <label for="genero">Género <span class="obligatorio" aria-hidden="true">*</span></label>
            <input type="text" id="genero" name="genero" minlength="3" maxlength="50" list="lista-generos" pattern="[\p{L}][\p{L} .'\-]*" required data-mensaje="El género es obligatorio y solo puede tener letras." value="<?php echo $valor("genero", $pelicula ? $pelicula->getGenero() : ""); ?>">
            <?php echo errorCampoPanel($errores, "genero"); ?>
            <datalist id="lista-generos">
                <?php foreach ($generos as $genero): ?>
                    <option value="<?php echo htmlspecialchars($genero); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="campo <?php echo claseError($errores, "actores"); ?>">
            <label for="actores">Actores</label>
            <input type="text" id="actores" name="actores" maxlength="2500" placeholder="Separados por comas: Actor Uno, Actor Dos" value="<?php echo $valor("actores", $pelicula ? implode(", ", $pelicula->getActores()) : ""); ?>">
            <?php echo errorCampoPanel($errores, "actores"); ?>
        </div>

        <div class="campo <?php echo claseError($errores, "imagen"); ?>">
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
                    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif" data-subida data-max-bytes="2097152">
                    <?php echo errorCampoPanel($errores, "imagen"); ?>
                    <span class="campo__nota">JPG, PNG, WEBP o GIF, máximo 2 MB.<?php echo $pelicula ? " Si no eliges ninguna se mantiene la actual." : ""; ?></span>
                </div>
            </div>
        </div>

        <label class="interruptor">
            <input type="checkbox" name="visto" value="1" <?php echo $vista ? "checked" : ""; ?>>
            <span class="interruptor__pista"></span>
            Ya la he visto
        </label>

        <p class="campo__nota"><span class="obligatorio">*</span> Campos obligatorios</p>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php?tab=peliculas">Cancelar</a>
            <button type="submit" class="boton boton--primario"><?php echo $pelicula ? "Actualizar" : "Guardar"; ?></button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
