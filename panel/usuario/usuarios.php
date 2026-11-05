<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereAdmin("../../");
require_once __DIR__ . "/../../clases/usuarioDAO.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";

$dao = new UsuarioDAO();
$errores = [];
$datos = ["nombre" => "", "rol" => "usuario"];

//alta de un usuario nuevo (el formulario se envia a esta misma pagina)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    [$datos, $errores, $password] = validarUsuario($_POST, $dao);

    if (!$errores) {
        $dao->insert($datos["nombre"], $password, $datos["rol"]);
        header("Location: usuarios.php?msg=usuario_creado");
        exit;
    }
}

$usuarios = $dao->findAll();

$titulo = "Usuarios";
$seccion = "usuarios";
$base = "../../";
require __DIR__ . "/../../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1>Usuarios</h1>
            <p>Personas que pueden entrar al panel de administración.</p>
        </div>
    </div>

    <div class="dos-columnas">
        <div class="tarjeta">
            <div class="tarjeta__barra">
                <h2 class="tarjeta__titulo">Usuarios con acceso <span class="pestana__total"><?php echo count($usuarios); ?></span></h2>
            </div>
            <ul class="lista-usuarios">
                <?php foreach ($usuarios as $u): ?>
                    <?php $soyYo = $u->getId() === $_SESSION["usuario_id"]; ?>
                    <li>
                        <div class="persona">
                            <span class="avatar" data-nombre="<?php echo htmlspecialchars($u->getNombre()); ?>"></span>
                            <span>
                                <?php echo htmlspecialchars($u->getNombre()); ?>
                                <?php if ($u->getRol() === "admin"): ?>
                                    <span class="etiqueta etiqueta--rol">Administrador</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta--genero">Usuario</span>
                                <?php endif; ?>
                                <?php if ($soyYo): ?>
                                    <small class="persona__detalle">Tu sesión actual</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($soyYo): ?>
                            <a class="boton boton--pequeno boton--editar" href="cambiarPassword.php">Cambiar contraseña</a>
                        <?php else: ?>
                            <form action="eliminarUsuario.php" method="post" data-confirmar="Se eliminará el usuario «<?php echo htmlspecialchars($u->getNombre()); ?>» y ya no podrá entrar al panel.">
                                <?php echo campoToken(); ?>
                                <input type="hidden" name="id" value="<?php echo $u->getId(); ?>">
                                <button type="submit" class="boton boton--pequeno boton--eliminar">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form class="tarjeta formulario" method="post" id="nuevo" data-validar>
            <h2 class="tarjeta__titulo">Nuevo usuario</h2>

            <?php if ($errores): ?>
                <div class="alerta alerta--error" role="alert">
                    Revisa los campos marcados: <?php echo count($errores) === 1 ? "hay 1 error" : "hay " . count($errores) . " errores"; ?>.
                </div>
            <?php endif; ?>

            <?php echo campoToken(); ?>

            <div class="campo <?php echo claseError($errores, "nombre"); ?>">
                <label for="nombre">Nombre de usuario <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="text" id="nombre" name="nombre"
                       minlength="<?php echo USUARIO_NOMBRE_MIN; ?>" maxlength="<?php echo USUARIO_NOMBRE_MAX; ?>"
                       pattern="[\p{L}0-9_.\-]+" autocomplete="off" autocapitalize="none" spellcheck="false" required
                       data-mensaje="El nombre de usuario es obligatorio, de <?php echo USUARIO_NOMBRE_MIN; ?> a <?php echo USUARIO_NOMBRE_MAX; ?> caracteres, sin espacios: letras, números, punto, guion o guion bajo."
                       value="<?php echo htmlspecialchars($datos["nombre"]); ?>">
                <?php echo errorCampoPanel($errores, "nombre") ?: '<span class="campo__nota">Entre ' . USUARIO_NOMBRE_MIN . ' y ' . USUARIO_NOMBRE_MAX . ' caracteres, sin espacios. Con él iniciará sesión.</span>'; ?>
            </div>

            <div class="campo <?php echo claseError($errores, "password"); ?>">
                <label for="password">Contraseña <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="password" id="password" name="password"
                       minlength="<?php echo PASSWORD_MIN; ?>" maxlength="<?php echo PASSWORD_MAX; ?>"
                       pattern="(?=.*\p{L})(?=.*[0-9]).+" autocomplete="new-password" required data-fuerza
                       data-mensaje="La contraseña debe tener al menos <?php echo PASSWORD_MIN; ?> caracteres, con letras y números.">
                <?php echo errorCampoPanel($errores, "password") ?: '<span class="campo__nota">Mínimo ' . PASSWORD_MIN . ' caracteres, con letras y números. Podrá cambiarla después.</span>'; ?>
            </div>

            <div class="campo <?php echo claseError($errores, "confirmar"); ?>">
                <label for="confirmar">Repite la contraseña <span class="obligatorio" aria-hidden="true">*</span></label>
                <input type="password" id="confirmar" name="confirmar" maxlength="<?php echo PASSWORD_MAX; ?>"
                       autocomplete="new-password" required data-igual-a="password"
                       data-mensaje="Las contraseñas no coinciden.">
                <?php echo errorCampoPanel($errores, "confirmar"); ?>
            </div>

            <div class="campo <?php echo claseError($errores, "rol"); ?>">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" required>
                    <option value="usuario" <?php echo $datos["rol"] === "usuario" ? "selected" : ""; ?>>Usuario: gestiona películas y directores</option>
                    <option value="admin" <?php echo $datos["rol"] === "admin" ? "selected" : ""; ?>>Administrador: además gestiona usuarios</option>
                </select>
                <?php echo errorCampoPanel($errores, "rol"); ?>
            </div>

            <p class="campo__nota"><span class="obligatorio">*</span> Campos obligatorios</p>

            <div class="acciones-formulario">
                <button type="submit" class="boton boton--primario">Crear usuario</button>
            </div>
        </form>
    </div>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
