<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
require_once __DIR__ . "/../../includes/base_datos.php";
$directorDAO = new DirectorDAO();

$id = idPost();

//al editar, el director tiene que existir
$director = null;
if (($_POST["id"] ?? "") !== "") {
    $director = $id !== null ? $directorDAO->findById($id) : null;
    if (!$director) {
        avisar("error", "No se ha guardado nada: ese director no existe (puede que lo haya eliminado otra persona).");
        header("Location: ../index.php?tab=directores");
        exit;
    }
}

[$datos, $errores] = validarDirector($_POST);

//si algo esta mal se vuelve al formulario con los errores y con lo que se habia escrito
if ($errores) {
    guardarFormulario("director", $datos, $errores);
    header("Location: formDirector.php" . ($id !== null ? "?id=$id" : ""));
    exit;
}

//si existe un id, se actualiza el director, si no, se crea uno nuevo
try {
    if ($director) {
        $director->setNombre($datos["nombre"]);
        $director->setNacionalidad($datos["nacionalidad"]);
        $director->setFechaNacimiento($datos["fechaNacimiento"]);
        $director->setBiografia($datos["biografia"]);
        $directorDAO->update($director);
        avisar("ok", "Director «" . $datos["nombre"] . "» actualizado correctamente.");
    } else {
        $nuevoDirector = new Director(null, $datos["nombre"], $datos["nacionalidad"], $datos["fechaNacimiento"], $datos["biografia"]);
        $directorDAO->insert($nuevoDirector);
        avisar("ok", "Director «" . $datos["nombre"] . "» creado correctamente.");
    }
} catch (PDOException $e) {
    //se vuelve al formulario con lo escrito, para no perderlo
    avisarErrorBD($e, $director ? "actualizar el director" : "crear el director");
    guardarFormulario("director", $datos, []);
    header("Location: formDirector.php" . ($id !== null ? "?id=$id" : ""));
    exit;
}

header("Location: ../index.php?tab=directores");
exit;
?>
