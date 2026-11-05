<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
$directorDAO = new DirectorDAO();

$director = null;

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $director = $directorDAO->findById($id);
}

//si guardarDirector.php ha devuelto errores, se muestran con lo que se habia escrito
[$enviado, $errores] = recuperarFormulario("director");
$valor = fn(string $campo, string $actual) => htmlspecialchars($enviado[$campo] ?? $actual);

$titulo = $director ? "Editar director" : "Nuevo director";
$seccion = "directores";
$base = "../../";
require __DIR__ . "/../../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1><?php echo $titulo; ?></h1>
            <p><?php echo $director ? "Modifica los datos de " . htmlspecialchars($director->getNombre()) . "." : "Añade un director a tu colección."; ?></p>
        </div>
        <a class="boton boton--secundario" href="../index.php?tab=directores">← Volver</a>
    </div>

    <?php if ($errores): ?>
        <div class="alerta alerta--error formulario-ancho" role="alert">
            Revisa los campos marcados: <?php echo count($errores) === 1 ? "hay 1 error" : "hay " . count($errores) . " errores"; ?>.
        </div>
    <?php endif; ?>

    <form class="tarjeta formulario" action="guardarDirector.php" method="post" data-validar>
        <?php echo campoToken(); ?>
        <?php if ($director): ?>
            <input type="hidden" name="id" value="<?php echo $director->getId(); ?>">
        <?php endif; ?>

        <div class="campo <?php echo claseError($errores, "nombre"); ?>">
            <label for="nombre">Nombre <span class="obligatorio" aria-hidden="true">*</span></label>
            <input type="text" id="nombre" name="nombre"
                   minlength="<?php echo NOMBRE_DIRECTOR_MIN; ?>" maxlength="<?php echo NOMBRE_DIRECTOR_MAX; ?>"
                   pattern="[\p{L}][\p{L} .'\-]*" autocomplete="off" required
                   data-mensaje="El nombre es obligatorio, de <?php echo NOMBRE_DIRECTOR_MIN; ?> a <?php echo NOMBRE_DIRECTOR_MAX; ?> caracteres y solo con letras."
                   value="<?php echo $valor("nombre", $director ? $director->getNombre() : ""); ?>">
            <?php echo errorCampoPanel($errores, "nombre") ?: '<span class="campo__nota">Entre ' . NOMBRE_DIRECTOR_MIN . ' y ' . NOMBRE_DIRECTOR_MAX . ' caracteres.</span>'; ?>
        </div>

        <div class="fila-campos">
            <div class="campo <?php echo claseError($errores, "nacionalidad"); ?>">
                <label for="nacionalidad">Nacionalidad <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="text" id="nacionalidad" name="nacionalidad" minlength="3" maxlength="60"
                       pattern="[\p{L}][\p{L} .'\-]*" required
                       data-mensaje="La nacionalidad es obligatoria y solo puede tener letras."
                       value="<?php echo $valor("nacionalidad", $director ? $director->getNacionalidad() : ""); ?>">
                <?php echo errorCampoPanel($errores, "nacionalidad"); ?>
            </div>

            <div class="campo <?php echo claseError($errores, "fechaNacimiento"); ?>">
                <label for="fechaNacimiento">Fecha de nacimiento <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="date" id="fechaNacimiento" name="fechaNacimiento"
                       min="<?php echo FECHA_NACIMIENTO_MINIMA; ?>" max="<?php echo fechaNacimientoMaxima(); ?>" required
                       data-mensaje="El director debe tener al menos <?php echo EDAD_MINIMA_DIRECTOR; ?> años: elige una fecha hasta el <?php echo date("d/m/Y", strtotime(fechaNacimientoMaxima())); ?>."
                       value="<?php echo $valor("fechaNacimiento", $director ? $director->getFechaNacimiento() : ""); ?>">
                <?php echo errorCampoPanel($errores, "fechaNacimiento") ?: '<span class="campo__nota">Debe tener al menos ' . EDAD_MINIMA_DIRECTOR . ' años.</span>'; ?>
            </div>
        </div>

        <div class="campo">
            <label for="biografia">Biografía</label>
            <textarea id="biografia" name="biografia" maxlength="1000" placeholder="Trayectoria, estilo y películas destacadas..."><?php echo $valor("biografia", $director ? $director->getBiografia() : ""); ?></textarea>
        </div>

        <p class="campo__nota"><span class="obligatorio">*</span> Campos obligatorios</p>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php?tab=directores">Cancelar</a>
            <button type="submit" class="boton boton--primario"><?php echo $director ? "Actualizar" : "Guardar"; ?></button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
