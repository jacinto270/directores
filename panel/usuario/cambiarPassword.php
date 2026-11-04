<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
require_once __DIR__ . "/../../clases/usuarioDAO.php";

$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    $actual = $_POST["actual"] ?? "";
    $nueva = $_POST["nueva"] ?? "";
    $confirmar = $_POST["confirmar"] ?? "";

    $dao = new UsuarioDAO();
    $usuario = $dao->findById($_SESSION["usuario_id"]);

    if (!$usuario || !password_verify($actual, $usuario->getPassword())) {
        $errores[] = "La contraseña actual no es correcta.";
    }
    if (mb_strlen($nueva) < 8) {
        $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }
    if ($nueva !== $confirmar) {
        $errores[] = "La nueva contraseña y la confirmación no coinciden.";
    }
    if ($nueva !== "" && $nueva === $actual) {
        $errores[] = "La nueva contraseña debe ser distinta de la actual.";
    }

    if (!$errores) {
        $dao->cambiarPassword($usuario->getId(), $nueva);
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
            <?php foreach ($errores as $error): ?>
                <div><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="tarjeta formulario" method="post">
        <?php echo campoToken(); ?>

        <div class="campo">
            <label for="actual">Contraseña actual</label>
            <input type="password" id="actual" name="actual" autocomplete="current-password" required>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="nueva">Nueva contraseña</label>
                <input type="password" id="nueva" name="nueva" minlength="8" autocomplete="new-password" required>
                <span class="campo__nota">Mínimo 8 caracteres.</span>
            </div>

            <div class="campo">
                <label for="confirmar">Repite la nueva contraseña</label>
                <input type="password" id="confirmar" name="confirmar" minlength="8" autocomplete="new-password" required>
            </div>
        </div>

        <div class="acciones-formulario">
            <a class="boton boton--secundario" href="../index.php">Cancelar</a>
            <button type="submit" class="boton boton--primario">Cambiar contraseña</button>
        </div>
    </form>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
