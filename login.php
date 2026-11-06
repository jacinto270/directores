<?php
require_once __DIR__ . "/includes/sesion.php";
require_once __DIR__ . "/clases/miembroDAO.php";

if (hayMiembro()) {
    header("Location: index.php");
    exit;
}

$error = "";
$sinVerificar = false;
//tras verificar el email se llega aqui con el email ya escrito
$email = $_SESSION["email_verificado"] ?? "";
unset($_SESSION["email_verificado"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    $email = mb_strtolower(sanitizarTexto($_POST["email"] ?? ""));
    $password = campoPost("password");

    $miembro = (new MiembroDAO())->login($email, $password);

    if ($miembro && !$miembro->estaVerificado()) {
        //contraseña correcta, pero todavia no ha confirmado el email: no puede entrar
        $sinVerificar = true;
    } elseif ($miembro) {
        iniciarSesionMiembro($miembro);
        header("Location: index.php");
        exit;
    } else {
        //pequeña espera para frenar a quien pruebe contraseñas una tras otra
        sleep(1);
        $error = "Email o contraseña incorrectos.";
    }
}

$salida = ($_GET["msg"] ?? "") === "salida";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · Catálogo de películas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="acceso">
    <main class="acceso__caja">
        <div class="acceso__marca">
            <span class="marca__icono">🎬</span>
            <h1>Catálogo de películas</h1>
            <p>Inicia sesión para ver el catálogo.</p>
        </div>

        <?php if ($sinVerificar): ?>
            <div class="alerta alerta--error" role="alert">
                <strong>Aún no has confirmado tu email.</strong>
                Abre el enlace que te enviamos al registrarte para activar la cuenta.
                <form method="post" action="reenviarVerificacion.php" class="alerta__formulario">
                    <?php echo campoToken(); ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <button type="submit" class="boton boton--pequeno boton--secundario">Reenviar el enlace</button>
                </form>
            </div>
        <?php elseif ($error): ?>
            <div class="alerta alerta--error" role="alert"><?php echo $error; ?></div>
        <?php elseif ($salida): ?>
            <div class="alerta alerta--ok" role="status">Has cerrado la sesión. ¡Hasta pronto!</div>
        <?php endif; ?>

        <form class="tarjeta formulario" method="post">
            <?php echo campoToken(); ?>

            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">Entrar</button>
        </form>

        <p class="acceso__alternativa">¿Aún no tienes cuenta? <a href="registro.php">Regístrate gratis</a></p>
    </main>
<?php require __DIR__ . "/includes/aviso.php"; ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
