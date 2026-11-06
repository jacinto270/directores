<?php
//envia un enlace de verificacion nuevo (desde el login, la pagina de "revisa tu correo" o un enlace caducado)
require_once __DIR__ . "/includes/sesion.php";
verificarToken();
require_once __DIR__ . "/includes/verificacion_email.php";
require_once __DIR__ . "/includes/validacion_miembro.php";
require_once __DIR__ . "/includes/base_datos.php";

$email = limpiarEmail($_POST["email"] ?? "");

try {
    $dao = new MiembroDAO();
    $miembro = filter_var($email, FILTER_VALIDATE_EMAIL) ? $dao->findByEmail($email) : null;

    //solo se envia si la cuenta existe, esta sin verificar y no se ha enviado otro enlace hace menos de un minuto
    if ($miembro && !$miembro->estaVerificado() && !$dao->tokenEnviadoHaceMenosDe($miembro->getId(), ESPERA_REENVIO)) {
        $dao->transaccion(function (MiembroDAO $dao) use ($miembro) {
            enviarCorreoVerificacion($dao, $miembro->getId(), $miembro->getNombre(), $miembro->getEmail());
        });
    }

    //el mensaje es siempre el mismo, exista o no la cuenta: asi no se puede averiguar que emails estan registrados
    avisar("ok", "Si hay una cuenta pendiente de verificar con ese email, te hemos enviado un enlace nuevo. Si no te llega, espera un minuto antes de pedir otro.");
} catch (PDOException $e) {
    avisarErrorBD($e, "reenviar el enlace");
} catch (Throwable $e) {
    error_log("[peliculas] reenvío de verificación a $email: " . $e->getMessage());
    avisar("error", "No hemos podido enviar el correo. Inténtalo de nuevo en unos minutos.");
}

$_SESSION["registro_pendiente"] = $email;
header("Location: registro.php?enviado=1");
exit;
?>
