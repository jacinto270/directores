<?php
require_once __DIR__ . "/../../clases/peliculaDAO.php";

//solo se elimina si llega el id por POST desde el panel
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = (int) $_POST["id"];
    $dao = new PeliculasDAO();
    $pelicula = $dao->getById($id);

    if ($pelicula) {
        $dao->eliminar($id);

        //se borra tambien la portada si estaba subida en img
        $ruta = __DIR__ . "/../../img/" . basename($pelicula->getImagen());
        if ($pelicula->getImagen() !== "" && is_file($ruta)) {
            unlink($ruta);
        }
    }
}

header("Location: ../index.php?tab=peliculas&msg=pelicula_eliminada");
exit;
?>
