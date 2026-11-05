<?php
//reglas de los datos de un miembro, compartidas por el registro y el perfil
//cada funcion devuelve el mensaje de error o "" si el dato es valido

require_once __DIR__ . "/seguridad.php";

function limpiarNombre(mixed $nombre): string {
    return sanitizarTexto($nombre);
}

function limpiarEmail(mixed $email): string {
    return mb_strtolower(sanitizarTexto($email));
}

//el telefono se guarda sin espacios, guiones ni parentesis: +34600111222
function limpiarTelefono(mixed $telefono): string {
    return preg_replace('/[\s\-().]/', "", sanitizarTexto($telefono));
}

function errorNombre(string $nombre): string {
    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80 || !preg_match('/^[\p{L} .\'-]+$/u', $nombre)) {
        return "Escribe tu nombre (solo letras, de 2 a 80 caracteres).";
    }
    return "";
}

//$idPropio: al editar el perfil, el email propio no cuenta como repetido
function errorEmail(string $email, MiembroDAO $dao, ?int $idPropio = null): string {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        return "Escribe un email válido, por ejemplo nombre@correo.com.";
    }
    $existente = $dao->findByEmail($email);
    if ($existente && $existente->getId() !== $idPropio) {
        return $idPropio === null
            ? "Ya hay una cuenta con ese email. ¿Quieres iniciar sesión?"
            : "Ese email ya lo usa otra cuenta.";
    }
    return "";
}

function errorTelefono(string $telefonoLimpio): string {
    if (!preg_match('/^\+?[0-9]{9,15}$/', $telefonoLimpio)) {
        return "Escribe un teléfono válido de 9 a 15 cifras (puede empezar por +).";
    }
    return "";
}

function errorPassword(string $password, string $email): string {
    if (mb_strlen($password) < 8 || !preg_match('/\p{L}/u', $password) || !preg_match('/[0-9]/', $password)) {
        return "La contraseña debe tener al menos 8 caracteres, con letras y números.";
    }
    if (mb_strtolower($password) === $email) {
        return "La contraseña no puede ser tu email.";
    }
    return "";
}

//muestra el error de un campo si lo tiene
function errorCampo(array $errores, string $campo): string {
    return isset($errores[$campo]) ? '<span class="campo__error">' . htmlspecialchars($errores[$campo]) . '</span>' : "";
}
?>
