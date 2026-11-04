<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereAdmin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/usuarioDAO.php";

$id = (int) ($_POST["id"] ?? 0);
$dao = new UsuarioDAO();

$usuario = $dao->findById($id);

//no se puede eliminar el usuario con el que se ha entrado ni dejar el panel sin usuarios
if (!$usuario || $id === $_SESSION["usuario_id"] || $dao->contar() <= 1) {
    header("Location: usuarios.php?msg=usuario_no_eliminado");
    exit;
}

//tampoco el ultimo administrador, o nadie podria volver a gestionar usuarios
if ($usuario->getRol() === "admin" && $dao->contarAdmins() <= 1) {
    header("Location: usuarios.php?msg=ultimo_admin");
    exit;
}

$dao->delete($id);

header("Location: usuarios.php?msg=usuario_eliminado");
exit;
?>
