<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../includes/base_datos.php";

$id = idPost();

if ($id === null) {
    avisar("error", "No se ha podido eliminar la película: la petición no indica cuál.");
} else {
    try {
        $dao = new PeliculasDAO();
        $pelicula = $dao->getById($id);

        if ($pelicula && $dao->eliminar($id)) {
            //la portada se borra solo despues de borrar la pelicula de la base de datos
            $ruta = __DIR__ . "/../../img/" . basename($pelicula->getImagen());
            if ($pelicula->getImagen() !== "" && is_file($ruta)) {
                unlink($ruta);
            }
            avisar("ok", "Película «" . $pelicula->getTitulo() . "» eliminada correctamente.");
        } else {
            avisar("error", "No se ha eliminado nada: esa película no existe (puede que ya estuviera eliminada).");
        }
    } catch (PDOException $e) {
        avisarErrorBD($e, "eliminar la película");
    }
}

header("Location: ../index.php?tab=peliculas");
exit;
?>
