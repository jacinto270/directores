<?php
//errores de la base de datos: se traducen a un mensaje claro para el usuario
//y el detalle tecnico se guarda en el registro de errores (en XAMPP: C:\xampp\apache\logs\error.log),
//nunca en pantalla, porque mostraria rutas y datos internos del servidor

require_once __DIR__ . "/sesion.php";

/*
 * Mensaje para el usuario segun el error de MySQL.
 * $accion describe lo que se intentaba hacer: "guardar el director", "eliminar la película"...
 */
function mensajeErrorBD(Throwable $error, string $accion): string {
    //codigo de error de MySQL (1062 = valor repetido, 2002 = sin conexion...)
    $codigo = $error instanceof PDOException ? (int) ($error->errorInfo[1] ?? 0) : 0;

    $motivo = match (true) {
        $codigo === 1062 => "ya existe un registro con ese mismo valor (por ejemplo, el mismo nombre o email)",
        $codigo === 1406 => "algún dato es demasiado largo",
        in_array($codigo, [1451, 1452], true) => "está relacionado con otros datos que lo impiden",
        in_array($codigo, [1146, 1054], true) => "la base de datos no tiene la estructura esperada (falta una tabla o columna)",
        in_array($codigo, [2002, 2006, 2013], true) || str_contains($error->getMessage(), "2002") => "no hay conexión con la base de datos (¿está MySQL encendido?)",
        in_array($codigo, [1205, 1213], true) => "la base de datos estaba ocupada; inténtalo de nuevo",
        default => "se ha producido un error en la base de datos",
    };

    return "No se ha podido $accion: $motivo. Código de referencia: " . referenciaError($error) . ".";
}

//codigo corto para buscar el error en el registro sin mostrar el detalle al usuario
//(WeakMap asocia el codigo a ese error concreto: el mismo error da siempre el mismo codigo
//en pantalla y en el registro, y dos errores distintos nunca comparten codigo)
function referenciaError(Throwable $error): string {
    static $referencias = null;
    $referencias ??= new WeakMap();
    return $referencias[$error] ??= strtoupper(bin2hex(random_bytes(3)));
}

//guarda el detalle tecnico en el registro de errores de PHP
function registrarErrorBD(Throwable $error, string $accion): void {
    error_log(sprintf(
        "[peliculas] ref %s al %s: %s en %s:%d",
        referenciaError($error), $accion, $error->getMessage(), $error->getFile(), $error->getLine()
    ));
}

//registra el error y prepara el aviso para la siguiente pagina
function avisarErrorBD(Throwable $error, string $accion): void {
    registrarErrorBD($error, $accion);
    avisar("error", mensajeErrorBD($error, $accion));
}
?>
