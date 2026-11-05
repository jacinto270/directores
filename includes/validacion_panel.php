<?php
//reglas de los formularios del panel (directores y peliculas)
//el HTML tambien las aplica para avisar antes de enviar, pero las que cuentan son estas,
//porque el navegador se puede saltar

require_once __DIR__ . "/seguridad.php";

//letras de cualquier idioma, espacios, punto, apostrofe y guion
const PATRON_NOMBRE_PERSONA = '/^[\p{L}][\p{L} .\'-]*$/u';

const NOMBRE_DIRECTOR_MIN = 2;
const NOMBRE_DIRECTOR_MAX = 30;
const EDAD_MINIMA_DIRECTOR = 18;
const FECHA_NACIMIENTO_MINIMA = "1850-01-01";

//texto de una linea: sin etiquetas, sin caracteres invisibles y sin espacios de sobra
function limpiarTexto(mixed $texto): string {
    return sanitizarTexto($texto);
}

//fecha mas reciente permitida: hoy hace 18 años
function fechaNacimientoMaxima(): string {
    return (new DateTimeImmutable("today"))->modify("-" . EDAD_MINIMA_DIRECTOR . " years")->format("Y-m-d");
}

/*
 * Valida los datos de un director enviados por POST.
 * Devuelve [datos limpios, errores por campo]; si no hay errores, el array de errores esta vacio.
 */
function validarDirector(array $post): array {
    $datos = [
        "nombre" => limpiarTexto($post["nombre"] ?? ""),
        "nacionalidad" => limpiarTexto($post["nacionalidad"] ?? ""),
        "fechaNacimiento" => limpiarTexto($post["fechaNacimiento"] ?? ""),
        //la biografia es texto libre: conserva los saltos de linea pero pierde las etiquetas HTML
        "biografia" => mb_substr(sanitizarTexto($post["biografia"] ?? "", true), 0, 1000),
    ];
    $errores = [];

    //nombre: obligatorio, de 2 a 30 caracteres, solo letras
    $largo = mb_strlen($datos["nombre"]);
    if ($datos["nombre"] === "") {
        $errores["nombre"] = "El nombre es obligatorio.";
    } elseif ($largo < NOMBRE_DIRECTOR_MIN || $largo > NOMBRE_DIRECTOR_MAX) {
        $errores["nombre"] = "El nombre debe tener entre " . NOMBRE_DIRECTOR_MIN . " y " . NOMBRE_DIRECTOR_MAX . " caracteres (tiene $largo).";
    } elseif (!preg_match(PATRON_NOMBRE_PERSONA, $datos["nombre"])) {
        $errores["nombre"] = "El nombre solo puede tener letras, espacios, puntos, apóstrofes y guiones.";
    }

    //nacionalidad: obligatoria, solo letras
    if ($datos["nacionalidad"] === "") {
        $errores["nacionalidad"] = "La nacionalidad es obligatoria.";
    } elseif (mb_strlen($datos["nacionalidad"]) < 3 || mb_strlen($datos["nacionalidad"]) > 60) {
        $errores["nacionalidad"] = "La nacionalidad debe tener entre 3 y 60 caracteres.";
    } elseif (!preg_match(PATRON_NOMBRE_PERSONA, $datos["nacionalidad"])) {
        $errores["nacionalidad"] = "La nacionalidad solo puede tener letras y espacios.";
    }

    //fecha de nacimiento: obligatoria, fecha real y como minimo 18 años antes de hoy
    $fecha = DateTimeImmutable::createFromFormat("!Y-m-d", $datos["fechaNacimiento"]);
    if ($datos["fechaNacimiento"] === "") {
        $errores["fechaNacimiento"] = "La fecha de nacimiento es obligatoria.";
    } elseif (!$fecha || $fecha->format("Y-m-d") !== $datos["fechaNacimiento"]) {
        //el format comprueba que no sea una fecha imposible como 2000-02-31
        $errores["fechaNacimiento"] = "Escribe una fecha válida.";
    } elseif ($datos["fechaNacimiento"] > fechaNacimientoMaxima()) {
        $errores["fechaNacimiento"] = "El director debe tener al menos " . EDAD_MINIMA_DIRECTOR . " años: la fecha no puede ser posterior al " . date("d/m/Y", strtotime(fechaNacimientoMaxima())) . ".";
    } elseif ($datos["fechaNacimiento"] < FECHA_NACIMIENTO_MINIMA) {
        $errores["fechaNacimiento"] = "La fecha no puede ser anterior a 1850.";
    }

    return [$datos, $errores];
}

/*
 * Valida los datos de una pelicula enviados por POST.
 * $directores: nombres de director permitidos (los de la tabla director y el que ya tuviera la pelicula).
 * Devuelve [datos limpios, errores por campo].
 */
function validarPelicula(array $post, array $directores): array {
    $actores = array_values(array_unique(array_filter(array_map("limpiarTexto", explode(",", (string) ($post["actores"] ?? ""))))));

    $datos = [
        "titulo" => limpiarTexto($post["titulo"] ?? ""),
        "director" => limpiarTexto($post["director"] ?? ""),
        "anio" => limpiarTexto($post["anio"] ?? ""),
        "genero" => limpiarTexto($post["genero"] ?? ""),
        "actores" => $actores,
        "visto" => isset($post["visto"]),
    ];
    $errores = [];
    $anioMaximo = (int) date("Y") + 5;

    if ($datos["titulo"] === "") {
        $errores["titulo"] = "El título es obligatorio.";
    } elseif (mb_strlen($datos["titulo"]) > 150) {
        $errores["titulo"] = "El título no puede tener más de 150 caracteres.";
    }

    //solo se acepta un director de la lista, no un texto inventado
    if ($datos["director"] === "") {
        $errores["director"] = "Elige un director.";
    } elseif (!in_array($datos["director"], $directores, true)) {
        $errores["director"] = "Elige un director de la lista.";
    }

    if (!preg_match('/^\d{4}$/', $datos["anio"]) || (int) $datos["anio"] < 1888 || (int) $datos["anio"] > $anioMaximo) {
        $errores["anio"] = "El año debe estar entre 1888 y $anioMaximo.";
    }

    if ($datos["genero"] === "") {
        $errores["genero"] = "El género es obligatorio.";
    } elseif (mb_strlen($datos["genero"]) < 3 || mb_strlen($datos["genero"]) > 50 || !preg_match(PATRON_NOMBRE_PERSONA, $datos["genero"])) {
        $errores["genero"] = "El género debe tener entre 3 y 50 letras.";
    }

    if (count($actores) > 30) {
        $errores["actores"] = "Como máximo 30 actores.";
    } else {
        foreach ($actores as $actor) {
            if (mb_strlen($actor) > 80 || !preg_match(PATRON_NOMBRE_PERSONA, $actor)) {
                $errores["actores"] = "Revisa «" . mb_substr($actor, 0, 40) . "»: cada actor debe ser un nombre de hasta 80 letras.";
                break;
            }
        }
    }

    return [$datos, $errores];
}

// ---------- usuarios del panel ----------

const USUARIO_NOMBRE_MIN = 2;
const USUARIO_NOMBRE_MAX = 30;
//letras, numeros, punto, guion y guion bajo, sin espacios (es con lo que se inicia sesion)
const PATRON_NOMBRE_USUARIO = '/^[\p{L}0-9_.-]+$/u';
const PASSWORD_MIN = 8;
//password_hash ignora lo que pase de 72 bytes: se limita para que no haya sorpresas
const PASSWORD_MAX = 72;

//devuelve el error de una contraseña nueva, o "" si es valida
function errorPasswordNueva(string $password, string $nombreUsuario): string {
    if ($password === "") {
        return "La contraseña es obligatoria.";
    }
    if (mb_strlen($password) < PASSWORD_MIN || !preg_match('/\p{L}/u', $password) || !preg_match('/[0-9]/', $password)) {
        return "La contraseña debe tener al menos " . PASSWORD_MIN . " caracteres, con letras y números.";
    }
    if (strlen($password) > PASSWORD_MAX) {
        return "La contraseña no puede tener más de " . PASSWORD_MAX . " caracteres.";
    }
    //con nombres muy cortos ("a") casi cualquier contraseña lo contendria
    if (mb_strlen($nombreUsuario) >= 3 && mb_stripos($password, $nombreUsuario) !== false) {
        return "La contraseña no puede contener el nombre de usuario.";
    }
    return "";
}

/*
 * Valida el alta de un usuario del panel.
 * Devuelve [datos limpios, errores por campo, contraseña]. La contraseña va aparte, fuera de los datos,
 * para que nunca se vuelva a escribir en la pagina.
 */
function validarUsuario(array $post, UsuarioDAO $dao): array {
    $datos = [
        "nombre" => limpiarTexto($post["nombre"] ?? ""),
        "rol" => is_string($post["rol"] ?? null) ? $post["rol"] : "",
    ];
    $password = is_string($post["password"] ?? null) ? $post["password"] : "";
    $confirmar = is_string($post["confirmar"] ?? null) ? $post["confirmar"] : "";
    $errores = [];

    $largo = mb_strlen($datos["nombre"]);
    if ($datos["nombre"] === "") {
        $errores["nombre"] = "El nombre de usuario es obligatorio.";
    } elseif ($largo < USUARIO_NOMBRE_MIN || $largo > USUARIO_NOMBRE_MAX) {
        $errores["nombre"] = "El nombre de usuario debe tener entre " . USUARIO_NOMBRE_MIN . " y " . USUARIO_NOMBRE_MAX . " caracteres (tiene $largo).";
    } elseif (!preg_match(PATRON_NOMBRE_USUARIO, $datos["nombre"])) {
        $errores["nombre"] = "Solo letras, números, punto, guion y guion bajo, sin espacios.";
    } elseif ($dao->findByNombre($datos["nombre"])) {
        $errores["nombre"] = "Ya existe un usuario con ese nombre.";
    }

    if ($error = errorPasswordNueva($password, $datos["nombre"])) {
        $errores["password"] = $error;
    }
    if ($confirmar === "") {
        $errores["confirmar"] = "Repite la contraseña.";
    } elseif ($password !== $confirmar) {
        $errores["confirmar"] = "Las contraseñas no coinciden.";
    }

    //solo los roles de la lista, nunca un valor inventado
    if (!in_array($datos["rol"], UsuarioDAO::ROLES, true)) {
        $errores["rol"] = "Elige un rol válido.";
        $datos["rol"] = "usuario";
    }

    return [$datos, $errores, $password];
}

//muestra el error de un campo si lo tiene
function errorCampoPanel(array $errores, string $campo): string {
    return isset($errores[$campo]) ? '<span class="campo__error">' . htmlspecialchars($errores[$campo]) . '</span>' : "";
}

//clase que marca en rojo el campo con error
function claseError(array $errores, string $campo): string {
    return isset($errores[$campo]) ? "campo--error" : "";
}
?>
