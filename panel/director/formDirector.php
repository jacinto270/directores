<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/directorDAO.php";
$directorDAO = new DirectorDAO();

$director = null;

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    $director = $directorDAO->findById($id);
}

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

    <form class="tarjeta formulario" action="guardarDirector.php" method="post">
        <?php echo campoToken(); ?>
        <?php if ($director): ?>
            <input type="hidden" name="id" value="<?php echo $director->getId(); ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" maxlength="100" value="<?php echo $director ? htmlspecialchars($director->getNombre()) : ''; ?>" required>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="nacionalidad">Nacionalidad</label>
                <input type="text" id="nacionalidad" name="nacionalidad" maxlength="60" value="<?php echo $director ? htmlspecialchars($director->getNacionalidad()) : ''; ?>" required>
            </div>

            <div class="campo">
                <label for="fechaNacimiento">Fecha de nacimiento</label>
                <input type="date" id="fechaNacimiento" name="fechaNacimiento" value="<?php echo $director ? $director->getFechaNacimiento() : ''; ?>" required>
            </div>
        </div>

        <div class="campo">
            <label for="biografia">Biografía</label>
            <textarea id="biografia" name="biografia" maxlength="1000" placeholder="Trayectoria, estilo y películas destacadas..."><?php echo $director ? htmlspecialchars($director->getBiografia()) : ''; ?></textarea>
        </div>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php?tab=directores">Cancelar</a>
            <button type="submit" class="boton boton--primario"><?php echo $director ? "Actualizar" : "Guardar"; ?></button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
