<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/usuarioDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";

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
        $dao->cambiarPassword($usuario->getId(), $nueva);
        //nuevo id de sesion tras cambiar la contraseña
        session_regenerate_id(true);
        header("Location: ../index.php?msg=password_cambiada");
        exit;
    }
}

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
        <div class="alerta alerta--error formulario-ancho" role="alert">
            Revisa los campos marcados: <?php echo count($errores) === 1 ? "hay 1 error" : "hay " . count($errores) . " errores"; ?>.
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
                       data-mensaje="La contraseña debe tener al menos <?php echo PASSWORD_MIN; ?> caracteres, con letras y números.">
                <?php echo errorCampoPanel($errores, "nueva") ?: '<span class="campo__nota">Mínimo ' . PASSWORD_MIN . ' caracteres, con letras y números.</span>'; ?>
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
