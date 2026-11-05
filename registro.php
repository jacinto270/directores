<?php
require_once __DIR__ . "/includes/sesion.php";
require_once __DIR__ . "/clases/miembroDAO.php";
require_once __DIR__ . "/includes/validacion_miembro.php";
require_once __DIR__ . "/includes/base_datos.php";

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

    $errores = array_filter([
        "nombre" => errorNombre($datos["nombre"]),
        "email" => errorEmail($datos["email"], $dao),
        "telefono" => errorTelefono($telefono),
        "password" => errorPassword($password, $datos["email"]),
        "confirmar" => $password !== $confirmar ? "Las contraseñas no coinciden." : "",
    ]);

    if (!$errores) {
        try {
            $id = $dao->insert($datos["nombre"], $datos["email"], $telefono, $password);
            iniciarSesionMiembro($dao->findById($id));
            avisar("ok", "Cuenta creada. ¡Te damos la bienvenida, " . $datos["nombre"] . "!");
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            //se queda en el formulario con lo escrito; el aviso de error se muestra abajo
            avisarErrorBD($e, "crear tu cuenta");
        }
    }
}
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
    </main>

<?php require __DIR__ . "/includes/aviso.php"; ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
