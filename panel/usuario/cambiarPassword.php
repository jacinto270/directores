<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/usuarioDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
require_once __DIR__ . "/../../includes/base_datos.php";

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    //las contraseñas no se limpian: cualquier caracter es valido y se comparan tal cual
    $actual = campoPost("actual");
    $nueva = campoPost("nueva");
    $confirmar = campoPost("confirmar");

    $dao = new UsuarioDAO();
    $usuario = $dao->findById($_SESSION["usuario_id"]);

    if (!$usuario || !password_verify($actual, $usuario->getPassword())) {
        $errores["actual"] = "La contraseña actual no es correcta.";
    }
    if ($error = errorPasswordNueva($nueva, $_SESSION["usuario_nombre"])) {
        $errores["nueva"] = $error;
    } elseif ($nueva === $actual) {
        $errores["nueva"] = "La nueva contraseña debe ser distinta de la actual.";
    }
    if ($nueva !== $confirmar) {
        $errores["confirmar"] = "Las contraseñas no coinciden.";
    }

    if (!$errores) {
        try {
            $dao->cambiarPassword($usuario->getId(), $nueva);
            //nuevo id de sesion tras cambiar la contraseña
            session_regenerate_id(true);
            //se vuelve a esta misma pagina con un mensaje claro dentro de ella
            //(antes volvia al panel con un aviso pequeño que desaparecia solo y pasaba desapercibido)
            $_SESSION["password_cambiada"] = true;
            header("Location: cambiarPassword.php");
            exit;
        } catch (PDOException $e) {
            //la contraseña sigue siendo la anterior; el aviso de error se muestra en esta pagina
            avisarErrorBD($e, "cambiar la contraseña");
        }
    }
}

//mensaje de exito dentro de la pagina tras cambiarla (solo se muestra una vez)
$cambiada = !empty($_SESSION["password_cambiada"]);
unset($_SESSION["password_cambiada"]);

$titulo = "Cambiar contraseña";
$seccion = "usuario";
$base = "../../";
require __DIR__ . "/../../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1>Cambiar contraseña</h1>
            <p>Usuario: <?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?></p>
        </div>
        <a class="boton boton--secundario" href="../index.php">← Volver</a>
    </div>

    <?php if ($errores): ?>
        <!-- se dice exactamente que ha fallado: los campos de contraseña vuelven vacios y sin esto parecia que no pasaba nada -->
        <div class="alerta alerta--error formulario-ancho" role="alert" data-ir-al-error>
            <strong>No se ha cambiado la contraseña.</strong>
            <ul class="alerta__lista">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            Vuelve a escribir las contraseñas.
        </div>
    <?php elseif ($cambiada): ?>
        <div class="alerta alerta--ok formulario-ancho" role="status">
            <strong>✓ Contraseña cambiada correctamente.</strong> La próxima vez que inicies sesión, usa la nueva.
            <a href="../index.php">Volver al panel</a>
        </div>
    <?php endif; ?>

    <form class="tarjeta formulario" method="post" data-validar>
        <?php echo campoToken(); ?>

        <div class="campo <?php echo claseError($errores, "actual"); ?>">
            <label for="actual">Contraseña actual <span class="obligatorio" aria-hidden="true">*</span></label>
            <input type="password" id="actual" name="actual" maxlength="<?php echo PASSWORD_MAX; ?>" autocomplete="current-password" required
                   data-mensaje="Escribe tu contraseña actual.">
            <?php echo errorCampoPanel($errores, "actual"); ?>
        </div>

        <div class="fila-campos">
            <div class="campo <?php echo claseError($errores, "nueva"); ?>">
                <label for="nueva">Nueva contraseña <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="password" id="nueva" name="nueva"
                       minlength="<?php echo PASSWORD_MIN; ?>" maxlength="<?php echo PASSWORD_MAX; ?>"
                       pattern="(?=.*\p{L})(?=.*[0-9]).+" autocomplete="new-password" required data-fuerza
                       data-no-contener="<?php echo htmlspecialchars($_SESSION["usuario_nombre"]); ?>"
                       data-mensaje="La contraseña debe tener al menos <?php echo PASSWORD_MIN; ?> caracteres, con letras y números.">
                <?php echo errorCampoPanel($errores, "nueva") ?: '<span class="campo__nota">Mínimo ' . PASSWORD_MIN . ' caracteres, con letras y números, distinta de la actual y sin tu nombre de usuario («' . htmlspecialchars($_SESSION["usuario_nombre"]) . '»).</span>'; ?>
            </div>

            <div class="campo <?php echo claseError($errores, "confirmar"); ?>">
                <label for="confirmar">Repite la nueva contraseña <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="password" id="confirmar" name="confirmar" maxlength="<?php echo PASSWORD_MAX; ?>"
                       autocomplete="new-password" required data-igual-a="nueva"
                       data-mensaje="Las contraseñas no coinciden.">
                <?php echo errorCampoPanel($errores, "confirmar"); ?>
            </div>
        </div>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php">Cancelar</a>
            <button type="submit" class="boton boton--primario">Cambiar contraseña</button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
