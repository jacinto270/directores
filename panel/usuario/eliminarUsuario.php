<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereAdmin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/usuarioDAO.php";
require_once __DIR__ . "/../../includes/base_datos.php";

$id = idPost();

try {
    $dao = new UsuarioDAO();
    $usuario = $id !== null ? $dao->findById($id) : null;

    if (!$usuario) {
        avisar("error", "No se ha eliminado nada: ese usuario no existe (puede que ya estuviera eliminado).");
    } elseif ($id === $_SESSION["usuario_id"]) {
        //no se puede eliminar el usuario con el que se ha entrado
        avisar("error", "No puedes eliminar tu propio usuario.");
    } elseif ($usuario->getRol() === "admin" && $dao->contarAdmins() <= 1) {
        //ni el ultimo administrador, o nadie podria volver a gestionar usuarios
        avisar("error", "No se puede eliminar al único administrador.");
    } elseif ($dao->delete($id)) {
        avisar("ok", "Usuario «" . $usuario->getNombre() . "» eliminado correctamente.");
    } else {
        avisar("error", "No se ha eliminado nada: ese usuario ya no existe.");
    }
} catch (PDOException $e) {
    avisarErrorBD($e, "eliminar el usuario");
}

header("Location: usuarios.php");
exit;
?>
