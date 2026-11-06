<?php
//pagina que se abre desde el enlace del correo de verificacion
require_once __DIR__ . "/includes/sesion.php";
require_once __DIR__ . "/clases/miembroDAO.php";
require_once __DIR__ . "/includes/base_datos.php";

$token = is_string($_GET["token"] ?? null) ? $_GET["token"] : "";

try {
    [$estado, $miembro] = (new MiembroDAO())->verificarEmail($token);
} catch (PDOException $e) {
    registrarErrorBD($e, "verificar el email");
    $estado = "error";
    $miembro = null;
}

if ($estado === "ok") {
    //el email aparecera ya escrito en el login
    $_SESSION["email_verificado"] = $miembro->getEmail();
}

//si el enlace ya se habia usado y la cuenta esta activa, no es un problema
if ($estado === "usado" && $miembro && $miembro->estaVerificado()) {
    $estado = "ya_verificado";
}

$mensajes = [
    "ok" => ["✅", "¡Email confirmado!", "Tu cuenta ya está activa. Ya puedes iniciar sesión y ver el catálogo."],
    "ya_verificado" => ["✅", "Tu cuenta ya estaba activa", "Este enlace ya se había usado. Puedes iniciar sesión con normalidad."],
    "caducado" => ["⏰", "El enlace ha caducado", "Por seguridad, los enlaces de verificación caducan. Pide uno nuevo y ábrelo en cuanto te llegue."],
    "usado" => ["⚠️", "Este enlace ya se ha usado", "Si no puedes entrar, pide un enlace nuevo."],
    "invalido" => ["⚠️", "El enlace no es válido", "Comprueba que has copiado la dirección completa del correo, o pide un enlace nuevo desde la página de inicio de sesión."],
    "error" => ["⚠️", "No hemos podido comprobar el enlace", "Ha habido un problema con la base de datos. Vuelve a abrir el enlace dentro de unos minutos."],
];
[$icono, $titulo, $texto] = $mensajes[$estado];
$puedeReenviar = in_array($estado, ["caducado", "usado"], true) && $miembro && !$miembro->estaVerificado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?php echo htmlspecialchars($titulo); ?> · Catálogo de películas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="acceso">
    <main class="acceso__caja">
        <div class="acceso__marca">
            <span class="marca__icono"><?php echo $icono; ?></span>
            <h1><?php echo htmlspecialchars($titulo); ?></h1>
        </div>

        <div class="tarjeta formulario">
            <p><?php echo htmlspecialchars($texto); ?></p>

            <?php if ($puedeReenviar): ?>
                <form method="post" action="reenviarVerificacion.php">
                    <?php echo campoToken(); ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($miembro->getEmail()); ?>">
                    <button type="submit" class="boton boton--primario boton--bloque">Enviarme un enlace nuevo</button>
                </form>
            <?php else: ?>
                <a class="boton boton--primario boton--bloque" href="login.php">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </main>
<?php require __DIR__ . "/includes/aviso.php"; ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
