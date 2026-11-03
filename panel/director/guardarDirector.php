<?php
require_once __DIR__ . "/../../clases/directorDAO.php";
$directorDAO = new DirectorDAO();

$id = $_POST["id"] ?? null;
$nombre = trim($_POST["nombre"] ?? "");
$nacionalidad = trim($_POST["nacionalidad"] ?? "");
$fechaNacimiento = $_POST["fechaNacimiento"] ?? null;
$biografia = trim($_POST["biografia"] ?? "");

//si existe un id, se actualiza el director, si no, se crea uno nuevo
if ($id) {
    $director = $directorDAO->findById((int)$id);
    if ($director) {
        $director->setNombre($nombre);
        $director->setNacionalidad($nacionalidad);
        $director->setFechaNacimiento($fechaNacimiento);
        $director->setBiografia($biografia);
        $directorDAO->update($director);
    }
    $mensaje = "actualizado";
} else {
    $nuevoDirector = new Director(null, $nombre, $nacionalidad, $fechaNacimiento, $biografia);
    $directorDAO->insert($nuevoDirector);
    $mensaje = "creado";
}

header("Location: ../index.php?tab=directores&msg=$mensaje");
exit;
?>
