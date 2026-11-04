<?php
require_once __DIR__ . "/../includes/sesion.php";

//se cierra con POST y token para que un enlace de otra web no pueda cerrar la sesion
verificarToken();
cerrarSesion();

header("Location: login.php?msg=salida");
exit;
?>
