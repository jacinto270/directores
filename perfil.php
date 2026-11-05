<?php
require_once __DIR__ . "/includes/sesion.php";
requiereCuentaMiembro("");
require_once __DIR__ . "/clases/miembroDAO.php";
require_once __DIR__ . "/includes/validacion_miembro.php";
require_once __DIR__ . "/includes/base_datos.php";
require_once __DIR__ . "/includes/imagenes.php";

$dao = new MiembroDAO();
$miembro = $dao->findById($_SESSION["miembro_id"]);
//datos guardados, para la tarjeta de la izquierda aunque el formulario falle
$emailOriginal = $miembro->getEmail();
$nombreOriginal = $miembro->getNombre();

$carpetaAvatares = __DIR__ . "/img/avatares/";
$errores = [];
$formulario = "";   //que formulario ha fallado, para mostrar sus errores

//borra del disco la foto anterior del miembro
//avatares de personajes que se pueden elegir (img/avatares/predefinidos/<clave>.webp)
const AVATARES_PREDEFINIDOS = [
    "robot" => "Robot",
    "vampiro" => "Vampiro",
    "pirata" => "Pirata",
    "astronauta" => "Astronauta",
    "mago" => "Mago",
    "dinosaurio" => "Dinosaurio",
    "fantasma" => "Fantasma",
    "superheroe" => "Superhéroe",
    "detective" => "Detective",
    "alien" => "Alienígena",
];

function esPredefinido(?string $archivo): bool {
    return $archivo !== null && str_starts_with($archivo, "predefinidos/");
}

//borra del disco la foto anterior del miembro (los avatares predefinidos son compartidos y no se borran)
function borrarAvatar(?string $archivo, string $carpeta): void {
    if ($archivo && !esPredefinido($archivo) && is_file($carpeta . basename($archivo))) {
        unlink($carpeta . basename($archivo));
    }
}

/*
 * Guarda la nueva foto en la base de datos y despues borra la anterior del disco
 * (en este orden: si la base de datos falla, la foto anterior sigue intacta).
 * Siempre deja un aviso con el resultado y vuelve al perfil.
 */
function cambiarFoto(MiembroDAO $dao, Miembro $miembro, ?string $nueva, string $carpeta, string $textoOk): never {
    try {
        $dao->cambiarAvatar($miembro->getId(), $nueva);
        borrarAvatar($miembro->getAvatar(), $carpeta);
        avisar("ok", $textoOk);
    } catch (PDOException $e) {
        //la foto nueva no ha llegado a usarse: se borra para no dejar archivos sueltos
        borrarAvatar($nueva, $carpeta);
        avisarErrorBD($e, "cambiar tu foto de perfil");
    }
    header("Location: perfil.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarToken();
    $formulario = campoPost("accion");

    //---------- subir foto de perfil ----------
    if ($formulario === "avatar") {
        $archivo = $_FILES["avatar"] ?? null;
        $permitidas = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp", "image/gif" => "gif"];

        if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
            $errores["avatar"] = "Elige una imagen.";
        } elseif ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["error"] === UPLOAD_ERR_FORM_SIZE || $archivo["size"] > 2 * 1024 * 1024) {
            $errores["avatar"] = "La imagen no puede pesar más de 2 MB.";
        } elseif ($archivo["error"] !== UPLOAD_ERR_OK) {
            $errores["avatar"] = "No se pudo subir la imagen. Inténtalo de nuevo.";
        } else {
            //el tipo se comprueba por el contenido del archivo, no por su nombre
            $tipo = mime_content_type($archivo["tmp_name"]);
            $medidas = @getimagesize($archivo["tmp_name"]);

            if (!isset($permitidas[$tipo]) || !$medidas) {
                $errores["avatar"] = "El archivo debe ser una imagen JPG, PNG, WEBP o GIF.";
            } elseif ($medidas[0] > 5000 || $medidas[1] > 5000) {
                $errores["avatar"] = "La imagen es demasiado grande (máximo 5000 × 5000 píxeles).";
            } else {
                //se recorta, se reduce y se guarda con un nombre aleatorio que no se puede adivinar
                $nombreArchivo = guardarAvatar($archivo["tmp_name"], $tipo, $carpetaAvatares);
                if ($nombreArchivo) {
                    cambiarFoto($dao, $miembro, $nombreArchivo, $carpetaAvatares, "Foto de perfil actualizada correctamente.");
                }
                $errores["avatar"] = "No se pudo guardar la imagen. Inténtalo de nuevo.";
            }
        }
    }

    //---------- elegir un avatar de personaje ----------
    if ($formulario === "avatar_predefinido") {
        $clave = campoPost("personaje");
        //solo se aceptan los de la lista, nunca una ruta enviada por el usuario
        if (isset(AVATARES_PREDEFINIDOS[$clave]) && is_file($carpetaAvatares . "predefinidos/$clave.webp")) {
            cambiarFoto($dao, $miembro, "predefinidos/$clave.webp", $carpetaAvatares, "Ahora tu avatar es el " . mb_strtolower(AVATARES_PREDEFINIDOS[$clave]) . ".");
        }
        $errores["personaje"] = "Elige uno de los personajes.";
    }

    //---------- quitar foto de perfil ----------
    if ($formulario === "quitar_avatar") {
        cambiarFoto($dao, $miembro, null, $carpetaAvatares, "Foto de perfil quitada.");
    }

    //---------- datos personales ----------
    if ($formulario === "datos") {
        $nombre = limpiarNombre($_POST["nombre"] ?? "");
        $email = limpiarEmail($_POST["email"] ?? "");
        $telefono = limpiarTelefono($_POST["telefono"] ?? "");
        $cambiaEmail = $email !== $miembro->getEmail();

        $errores = array_filter([
            "nombre" => errorNombre($nombre),
            "email" => errorEmail($email, $dao, $miembro->getId()),
            "telefono" => errorTelefono($telefono),
            //cambiar el email permite entrar con otro, asi que se pide la contraseña
            "password_datos" => $cambiaEmail && !password_verify(campoPost("password_actual"), $miembro->getPassword())
                ? "Para cambiar el email escribe tu contraseña actual." : "",
        ]);

        if (!$errores) {
            $miembro->setNombre($nombre);
            $miembro->setEmail($email);
            $miembro->setTelefono($telefono);
            try {
                $dao->update($miembro);
                avisar("ok", "Datos guardados correctamente." . ($cambiaEmail ? " A partir de ahora entra con $email." : ""));
                header("Location: perfil.php");
                exit;
            } catch (PDOException $e) {
                //se queda en la pagina con lo escrito; el aviso de error se muestra abajo
                avisarErrorBD($e, "guardar tus datos");
                $formulario = "";
            }
        }

        //se vuelve a mostrar lo que escribio para que pueda corregirlo
        $miembro->setNombre($nombre);
        $miembro->setEmail($email);
        $miembro->setTelefono(sanitizarTexto($_POST["telefono"] ?? ""));
    }

    //---------- contraseña ----------
    if ($formulario === "password") {
        $actual = campoPost("actual");
        $nueva = campoPost("nueva");
        $confirmar = campoPost("confirmar");

        $errores = array_filter([
            "actual" => !password_verify($actual, $miembro->getPassword()) ? "La contraseña actual no es correcta." : "",
            "nueva" => errorPassword($nueva, $miembro->getEmail()) ?: ($nueva === $actual ? "La nueva contraseña debe ser distinta de la actual." : ""),
            "confirmar" => $nueva !== $confirmar ? "Las contraseñas no coinciden." : "",
        ]);

        if (!$errores) {
            try {
                $dao->cambiarPassword($miembro->getId(), $nueva);
                //nuevo id de sesion tras cambiar la contraseña
                session_regenerate_id(true);
                avisar("ok", "Contraseña cambiada correctamente.");
                header("Location: perfil.php");
                exit;
            } catch (PDOException $e) {
                //la contraseña sigue siendo la anterior; el aviso de error se muestra abajo
                avisarErrorBD($e, "cambiar tu contraseña");
            }
        }
    }
}

$titulo = "Mi perfil";
$base = "";
$seccion = "perfil";
require __DIR__ . "/includes/cabecera.php";

//los errores solo se muestran en el formulario que se envio
$erroresDe = fn(string $nombre) => $formulario === $nombre ? $errores : [];
?>

    <div class="encabezado-pagina">
        <div>
            <h1>Mi perfil</h1>
            <p>Consulta y cambia los datos de tu cuenta.</p>
        </div>
    </div>

    <div class="perfil">
        <!-- foto de perfil -->
        <section class="tarjeta perfil__avatar">
            <div class="perfil__foto">
                <?php if ($miembro->getAvatar()): ?>
                    <img src="img/avatares/<?php echo htmlspecialchars($miembro->getAvatar()); ?>" alt="Tu foto de perfil" data-vista-previa-avatar>
                <?php else: ?>
                    <span class="avatar avatar--perfil" data-nombre="<?php echo htmlspecialchars($nombreOriginal); ?>" data-vista-previa-avatar></span>
                <?php endif; ?>
            </div>
            <h2><?php echo htmlspecialchars($nombreOriginal); ?></h2>
            <p class="perfil__email"><?php echo htmlspecialchars($emailOriginal); ?></p>
            <?php if ($miembro->getFechaAlta()): ?>
                <p class="perfil__alta">Miembro desde el <?php echo date("d/m/Y", strtotime($miembro->getFechaAlta())); ?></p>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="perfil__subida">
                <?php echo campoToken(); ?>
                <input type="hidden" name="accion" value="avatar">
                <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
                <label class="boton boton--secundario boton--bloque perfil__elegir">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" required data-avatar>
                    <?php echo $miembro->getAvatar() ? "Cambiar foto" : "Subir foto"; ?>
                </label>
                <button type="submit" class="boton boton--primario boton--bloque" data-guardar-avatar hidden>Guardar foto</button>
                <?php echo errorCampo($erroresDe("avatar"), "avatar"); ?>
                <span class="campo__nota">JPG, PNG, WEBP o GIF. Máximo 2 MB.</span>
            </form>

            <?php if ($miembro->getAvatar()): ?>
                <form method="post" data-confirmar="Se quitará tu foto de perfil.">
                    <?php echo campoToken(); ?>
                    <input type="hidden" name="accion" value="quitar_avatar">
                    <button type="submit" class="perfil__quitar">Quitar foto</button>
                </form>
            <?php endif; ?>

            <!-- avatares de personajes: cada boton guarda el suyo -->
            <form method="post" class="personajes">
                <?php echo campoToken(); ?>
                <input type="hidden" name="accion" value="avatar_predefinido">
                <span class="personajes__titulo">O elige un personaje</span>
                <div class="personajes__rejilla">
                    <?php foreach (AVATARES_PREDEFINIDOS as $clave => $nombre): ?>
                        <?php $elegido = $miembro->getAvatar() === "predefinidos/$clave.webp"; ?>
                        <button type="submit" name="personaje" value="<?php echo $clave; ?>" class="personaje <?php echo $elegido ? "activo" : ""; ?>" title="<?php echo $nombre; ?>" aria-label="Usar el avatar <?php echo $nombre; ?>" <?php echo $elegido ? 'aria-pressed="true"' : ""; ?>>
                            <img src="img/avatares/predefinidos/<?php echo $clave; ?>.webp" alt="" loading="lazy" width="56" height="56">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php echo errorCampo($erroresDe("avatar_predefinido"), "personaje"); ?>
            </form>
        </section>

        <div class="perfil__formularios">
            <!-- datos personales -->
            <form class="tarjeta formulario" method="post" data-validar>
                <h2 class="tarjeta__titulo">Datos personales</h2>
                <?php $e = $erroresDe("datos"); ?>
                <?php echo campoToken(); ?>
                <input type="hidden" name="accion" value="datos">

                <div class="campo <?php echo isset($e["nombre"]) ? "campo--error" : ""; ?>">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="80" autocomplete="name" value="<?php echo htmlspecialchars($miembro->getNombre()); ?>" required>
                    <?php echo errorCampo($e, "nombre"); ?>
                </div>

                <div class="fila-campos">
                    <div class="campo <?php echo isset($e["email"]) ? "campo--error" : ""; ?>">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="120" autocomplete="email" value="<?php echo htmlspecialchars($miembro->getEmail()); ?>" data-email-original="<?php echo htmlspecialchars($emailOriginal); ?>" required>
                        <?php echo errorCampo($e, "email"); ?>
                    </div>

                    <div class="campo <?php echo isset($e["telefono"]) ? "campo--error" : ""; ?>">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" maxlength="20" autocomplete="tel" value="<?php echo htmlspecialchars($miembro->getTelefono()); ?>" required>
                        <?php echo errorCampo($e, "telefono"); ?>
                    </div>
                </div>

                <!-- solo hace falta si cambia el email; el javascript lo muestra al cambiarlo -->
                <div class="campo <?php echo isset($e["password_datos"]) ? "campo--error" : ""; ?>" data-pide-password <?php echo isset($e["password_datos"]) || isset($e["email"]) ? "" : "hidden"; ?>>
                    <label for="password_actual">Contraseña actual</label>
                    <input type="password" id="password_actual" name="password_actual" autocomplete="current-password">
                    <?php echo errorCampo($e, "password_datos") ?: '<span class="campo__nota">Para cambiar el email necesitamos confirmar que eres tú.</span>'; ?>
                </div>

                <div class="acciones-formulario">
                    <button type="submit" class="boton boton--primario">Guardar datos</button>
                </div>
            </form>

            <!-- contraseña -->
            <form class="tarjeta formulario" method="post" data-validar>
                <h2 class="tarjeta__titulo">Cambiar contraseña</h2>
                <?php $e = $erroresDe("password"); ?>
                <?php echo campoToken(); ?>
                <input type="hidden" name="accion" value="password">

                <div class="campo <?php echo isset($e["actual"]) ? "campo--error" : ""; ?>">
                    <label for="actual">Contraseña actual</label>
                    <input type="password" id="actual" name="actual" autocomplete="current-password" required>
                    <?php echo errorCampo($e, "actual"); ?>
                </div>

                <div class="fila-campos">
                    <div class="campo <?php echo isset($e["nueva"]) ? "campo--error" : ""; ?>">
                        <label for="nueva">Nueva contraseña</label>
                        <input type="password" id="nueva" name="nueva" minlength="8" autocomplete="new-password" required data-fuerza>
                        <?php echo errorCampo($e, "nueva") ?: '<span class="campo__nota">Mínimo 8 caracteres, con letras y números.</span>'; ?>
                    </div>

                    <div class="campo <?php echo isset($e["confirmar"]) ? "campo--error" : ""; ?>">
                        <label for="confirmar">Repite la nueva contraseña</label>
                        <input type="password" id="confirmar" name="confirmar" minlength="8" autocomplete="new-password" required data-igual-a="nueva" data-mensaje="Las contraseñas no coinciden.">
                        <?php echo errorCampo($e, "confirmar"); ?>
                    </div>
                </div>

                <div class="acciones-formulario">
                    <button type="submit" class="boton boton--primario">Cambiar contraseña</button>
                </div>
            </form>
        </div>
    </div>

<?php require __DIR__ . "/includes/modal.php"; ?>
<?php require __DIR__ . "/includes/pie.php"; ?>
