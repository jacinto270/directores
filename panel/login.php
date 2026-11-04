<?php
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../clases/usuarioDAO.php";

//si ya hay sesion no tiene sentido ver el login
if (haySesion()) {
    header("Location: index.php");
    exit;
}

$error = "";
$nombre = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    $nombre = trim($_POST["nombre"] ?? "");
    $password = $_POST["password"] ?? "";

    $usuario = (new UsuarioDAO())->login($nombre, $password);

    if ($usuario) {
        iniciarSesion($usuario);
        header("Location: index.php");
        exit;
    }

    //pequeña espera para frenar a quien pruebe contraseñas una tras otra
    sleep(1);
    $error = "Usuario o contraseña incorrectos.";
}

$salida = ($_GET["msg"] ?? "") === "salida";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Iniciar sesión · Panel de administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="acceso">
    <main class="acceso__caja">
        <div class="acceso__marca">
            <span class="marca__icono">🎬</span>
            <h1>Panel de administración</h1>
            <p>Inicia sesión para gestionar el catálogo.</p>
        </div>

        <?php if ($error): ?>
            <div class="alerta alerta--error" role="alert"><?php echo $error; ?></div>
        <?php elseif ($salida): ?>
            <div class="alerta alerta--ok" role="status">Has cerrado la sesión.</div>
        <?php endif; ?>

        <form class="tarjeta formulario" method="post">
            <?php echo campoToken(); ?>

            <div class="campo">
                <label for="nombre">Usuario</label>
                <input type="text" id="nombre" name="nombre" autocomplete="username" value="<?php echo htmlspecialchars($nombre); ?>" required autofocus>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">Entrar</button>
        </form>

        <a class="acceso__volver" href="../index.php">← Volver al sitio público</a>
    </main>
</body>
</html>
