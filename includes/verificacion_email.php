<?php
//envio del correo de verificacion, compartido por el registro y el reenvio del enlace

require_once __DIR__ . "/../clases/miembroDAO.php";
require_once __DIR__ . "/../clases/correo.php";

//segundos que hay que esperar para pedir otro enlace (evita llenar el buzon de alguien)
const ESPERA_REENVIO = 60;

/*
 * Crea un token nuevo y envia el enlace de verificacion.
 * Lanza una excepcion si el correo no se ha podido enviar: llamalo dentro de
 * $dao->transaccion() para que, si falla, no quede guardado nada.
 */
function enviarCorreoVerificacion(MiembroDAO $dao, int $miembroId, string $nombre, string $email): void {
    $config = Correo::configuracion();
    $horas = (int) ($config["correo"]["horasEnlace"] ?? 24);

    $token = $dao->crearTokenVerificacion($miembroId, $horas);
    $enlace = $config["urlSitio"] . "/verificar.php?token=" . $token;

    [$asunto, $html, $texto] = Correo::plantillaVerificacion($nombre, $enlace, $horas);
    Correo::enviar($email, $nombre, $asunto, $html, $texto);
}
?>
