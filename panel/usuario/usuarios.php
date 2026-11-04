<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereAdmin("../../");
require_once __DIR__ . "/../../clases/usuarioDAO.php";

$dao = new UsuarioDAO();
$errores = [];
$nombre = "";
$rol = "usuario";

//alta de un usuario nuevo (el formulario se envia a esta misma pagina)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    $nombre = trim($_POST["nombre"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmar = $_POST["confirmar"] ?? "";
    $rol = $_POST["rol"] ?? "usuario";

    if (!in_array($rol, UsuarioDAO::ROLES, true)) {
        $errores[] = "Elige un rol válido.";
        $rol = "usuario";
    }

    if (!preg_match('/^[\p{L}0-9_.-]{3,50}$/u', $nombre)) {
        $errores[] = "El nombre debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo, sin espacios.";
    } elseif ($dao->findByNombre($nombre)) {
        $errores[] = "Ya existe un usuario llamado «" . htmlspecialchars($nombre) . "».";
    }
    if (mb_strlen($password) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres.";
    }
    if ($password !== $confirmar) {
        $errores[] = "La contraseña y la confirmación no coinciden.";
    }

    if (!$errores) {
        $dao->insert($nombre, $password, $rol);
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

        <form class="tarjeta formulario" method="post" id="nuevo">
            <h2 class="tarjeta__titulo">Nuevo usuario</h2>

            <?php if ($errores): ?>
                <div class="alerta alerta--error" role="alert">
                    <?php foreach ($errores as $error): ?>
                        <div><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php echo campoToken(); ?>

            <div class="campo">
                <label for="nombre">Nombre de usuario</label>
                <input type="text" id="nombre" name="nombre" minlength="3" maxlength="50" pattern="[\p{L}0-9_.\-]{3,50}" autocomplete="off" value="<?php echo htmlspecialchars($nombre); ?>" required>
                <span class="campo__nota">Sin espacios. Con él iniciará sesión.</span>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                <span class="campo__nota">Mínimo 8 caracteres. Podrá cambiarla después.</span>
            </div>

            <div class="campo">
                <label for="confirmar">Repite la contraseña</label>
                <input type="password" id="confirmar" name="confirmar" minlength="8" autocomplete="new-password" required>
            </div>

            <div class="campo">
                <label for="rol">Rol</label>
                <select id="rol" name="rol">
                    <option value="usuario" <?php echo $rol === "usuario" ? "selected" : ""; ?>>Usuario: gestiona películas y directores</option>
                    <option value="admin" <?php echo $rol === "admin" ? "selected" : ""; ?>>Administrador: además gestiona usuarios</option>
                </select>
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="boton boton--primario">Crear usuario</button>
            </div>
        </form>
    </div>

<?php require __DIR__ . "/../../includes/pie_panel.php"; ?>
