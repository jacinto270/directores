<?php
require_once __DIR__ . "/../../clases/directorDAO.php";

//solo se elimina si llega el id por POST desde el panel
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = (int) $_POST["id"];
    $directorDAO = new DirectorDAO();
    $directorDAO->delete($id);
}

header("Location: ../index.php?tab=directores&msg=eliminado");
exit;
?>
