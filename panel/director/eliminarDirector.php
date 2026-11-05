<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/base_datos.php";

$id = idPost();

if ($id === null) {
    avisar("error", "No se ha podido eliminar el director: la petición no indica cuál.");
} else {
    try {
        $directorDAO = new DirectorDAO();
        $director = $directorDAO->findById($id);

        if ($director && $directorDAO->delete($id)) {
            avisar("ok", "Director «" . $director->getNombre() . "» eliminado correctamente.");
        } else {
            avisar("error", "No se ha eliminado nada: ese director no existe (puede que ya estuviera eliminado).");
        }
    } catch (PDOException $e) {
        avisarErrorBD($e, "eliminar el director");
    }
}

header("Location: ../index.php?tab=directores");
exit;
?>
