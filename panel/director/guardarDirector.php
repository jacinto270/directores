<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
$directorDAO = new DirectorDAO();

$id = isset($_POST["id"]) && $_POST["id"] !== "" ? (int) $_POST["id"] : null;

//al editar, el director tiene que existir
$director = null;
if ($id !== null) {
    $director = $directorDAO->findById($id);
    if (!$director) {
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
if ($director) {
    $director->setNombre($datos["nombre"]);
    $director->setNacionalidad($datos["nacionalidad"]);
    $director->setFechaNacimiento($datos["fechaNacimiento"]);
    $director->setBiografia($datos["biografia"]);
    $directorDAO->update($director);
    $mensaje = "actualizado";
} else {
    $nuevoDirector = new Director(null, $datos["nombre"], $datos["nacionalidad"], $datos["fechaNacimiento"], $datos["biografia"]);
    $directorDAO->insert($nuevoDirector);
    $mensaje = "creado";
}

header("Location: ../index.php?tab=directores&msg=$mensaje");
exit;
?>
