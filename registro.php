<?php
require_once __DIR__ . "/includes/sesion.php";
require_once __DIR__ . "/clases/miembroDAO.php";
require_once __DIR__ . "/includes/validacion_miembro.php";
require_once __DIR__ . "/includes/base_datos.php";
require_once __DIR__ . "/includes/verificacion_email.php";

if (hayMiembro()) {
    header("Location: index.php");
    exit;
}

$errores = [];
$datos = ["nombre" => "", "email" => "", "telefono" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();

    $datos["nombre"] = limpiarNombre($_POST["nombre"] ?? "");
    $datos["email"] = limpiarEmail($_POST["email"] ?? "");
    $datos["telefono"] = sanitizarTexto($_POST["telefono"] ?? "");
    $telefono = limpiarTelefono($datos["telefono"]);
    $password = campoPost("password");
    $confirmar = campoPost("confirmar");

    $dao = new MiembroDAO();

    //si el email ya se registro pero nunca se verifico (por ejemplo, el enlace caduco),
    //no se trata como repetido: se actualizan sus datos y se envia un enlace nuevo
    $existente = filter_var($datos["email"], FILTER_VALIDATE_EMAIL) ? $dao->findByEmail($datos["email"]) : null;
    $sinVerificar = $existente && !$existente->estaVerificado();

    $errores = array_filter([
        "nombre" => errorNombre($datos["nombre"]),
        "email" => errorEmail($datos["email"], $dao, $sinVerificar ? $existente->getId() : null),
        "telefono" => errorTelefono($telefono),
        "password" => errorPassword($password, $datos["email"]),
        "confirmar" => $password !== $confirmar ? "Las contraseñas no coinciden." : "",
    ]);

    if (!$errores) {
        try {
            //alta y envio del correo juntos: si el correo falla, no se guarda la cuenta
            $dao->transaccion(function (MiembroDAO $dao) use ($datos, $telefono, $password, $existente, $sinVerificar) {
                if ($sinVerificar) {
                    $id = $existente->getId();
                    $dao->actualizarSinVerificar($id, $datos["nombre"], $telefono, $password);
                } else {
                    $id = $dao->insert($datos["nombre"], $datos["email"], $telefono, $password);
                }
                enviarCorreoVerificacion($dao, $id, $datos["nombre"], $datos["email"]);
            });

            //no se inicia sesion: primero tiene que confirmar el email
            $_SESSION["registro_pendiente"] = $datos["email"];
            header("Location: registro.php?enviado=1");
            exit;
        } catch (PDOException $e) {
            avisarErrorBD($e, "crear tu cuenta");
        } catch (Throwable $e) {
            //fallo al enviar el correo: el detalle va al registro de errores, no a la pantalla
            error_log("[peliculas] registro: no se pudo enviar el correo de verificación a {$datos["email"]}: " . $e->getMessage());
            avisar("error", "No hemos podido enviarte el correo de verificación, así que la cuenta no se ha creado. Inténtalo de nuevo en unos minutos.");
        }
    }
}

//pantalla de "revisa tu correo" tras registrarse
$pendiente = isset($_GET["enviado"]) ? ($_SESSION["registro_pendiente"] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta · Catálogo de películas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="acceso">
    <main class="acceso__caja acceso__caja--ancha">
    <?php if ($pendiente): ?>
        <div class="acceso__marca">
            <span class="marca__icono">📬</span>
            <h1>Revisa tu correo</h1>
            <p>Ya casi está: solo falta confirmar tu email.</p>
        </div>

        <div class="tarjeta formulario">
            <p>Te hemos enviado un enlace a <strong><?php echo htmlspecialchars($pendiente); ?></strong>.
               Ábrelo para activar tu cuenta y después inicia sesión.</p>
            <p class="campo__nota">El enlace caduca en <?php echo (int) (Correo::configuracion()["correo"]["horasEnlace"] ?? 24); ?> horas.
               Si no lo ves, mira en la carpeta de correo no deseado.</p>

            <form method="post" action="reenviarVerificacion.php">
                <?php echo campoToken(); ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($pendiente); ?>">
                <button type="submit" class="boton boton--secundario boton--bloque">No me ha llegado: reenviar el enlace</button>
            </form>
        </div>

        <p class="acceso__alternativa"><a href="login.php">Ir a iniciar sesión</a></p>
    <?php else: ?>
        <div class="acceso__marca">
            <span class="marca__icono">🎬</span>
            <h1>Crea tu cuenta</h1>
            <p>Regístrate gratis para ver el catálogo de películas.</p>
        </div>

        <form class="tarjeta formulario" method="post" data-validar>
            <?php echo campoToken(); ?>

            <div class="campo <?php echo isset($errores["nombre"]) ? "campo--error" : ""; ?>">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80" autocomplete="name" value="<?php echo htmlspecialchars($datos["nombre"]); ?>" required autofocus>
                <?php echo errorCampo($errores, "nombre"); ?>
            </div>

            <div class="fila-campos">
                <div class="campo <?php echo isset($errores["email"]) ? "campo--error" : ""; ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="120" autocomplete="email" value="<?php echo htmlspecialchars($datos["email"]); ?>" required>
                    <?php echo errorCampo($errores, "email"); ?>
                </div>

                <div class="campo <?php echo isset($errores["telefono"]) ? "campo--error" : ""; ?>">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" maxlength="20" autocomplete="tel" placeholder="+34 600 111 222" value="<?php echo htmlspecialchars($datos["telefono"]); ?>" required>
                    <?php echo errorCampo($errores, "telefono"); ?>
                </div>
            </div>

            <div class="fila-campos">
                <div class="campo <?php echo isset($errores["password"]) ? "campo--error" : ""; ?>">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required data-fuerza>
                    <?php echo errorCampo($errores, "password") ?: '<span class="campo__nota">Mínimo 8 caracteres, con letras y números.</span>'; ?>
                </div>

                <div class="campo <?php echo isset($errores["confirmar"]) ? "campo--error" : ""; ?>">
                    <label for="confirmar">Repite la contraseña</label>
                    <input type="password" id="confirmar" name="confirmar" minlength="8" autocomplete="new-password" required data-igual-a="password" data-mensaje="Las contraseñas no coinciden.">
                    <?php echo errorCampo($errores, "confirmar"); ?>
                </div>
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">Crear cuenta</button>
        </form>

        <p class="acceso__alternativa">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    <?php endif; ?>
    </main>

<?php require __DIR__ . "/includes/aviso.php"; ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
