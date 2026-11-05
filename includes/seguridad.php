<?php
//cabeceras de seguridad para todas las paginas (se carga desde sesion.php)

if (!headers_sent()) {
    //no anunciar la version de PHP
    header_remove("X-Powered-By");

    //Content-Security-Policy: el navegador solo ejecuta scripts de este sitio. Aunque alguien
    //consiguiera colar un <script> o un onclick="..." en la pagina, no se ejecutaria
    header("Content-Security-Policy: " . implode("; ", [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: blob:",       //blob: para la vista previa de imagenes antes de subirlas
        "connect-src 'self'",
        "form-action 'self'",               //los formularios solo se envian a este sitio
        "frame-ancestors 'none'",           //nadie puede meter la web dentro de un iframe
        "base-uri 'self'",
        "object-src 'none'",
    ]));

    //el navegador no adivina el tipo de archivo (una "imagen" con codigo no se ejecuta como script)
    header("X-Content-Type-Options: nosniff");
    //protege contra clickjacking en navegadores antiguos que no entienden frame-ancestors
    header("X-Frame-Options: DENY");
    //no enviar la direccion completa de la pagina a otros sitios
    header("Referrer-Policy: same-origin");
    //la web no usa camara, microfono ni ubicacion
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

/*
 * Ultima red de seguridad: cualquier error que no se haya capturado en la pagina
 * (por ejemplo, MySQL apagado al abrir el catalogo) muestra una pagina clara en lugar
 * del error tecnico de PHP, que ensenaria rutas y datos internos.
 * El detalle se guarda en el registro de errores de PHP con un codigo de referencia.
 */
set_exception_handler(function (Throwable $error): void {
    $referencia = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    error_log(sprintf("[peliculas] ref %s sin capturar: %s en %s:%d", $referencia, $error->getMessage(), $error->getFile(), $error->getLine()));

    $esBaseDatos = $error instanceof PDOException;
    if (!headers_sent()) {
        http_response_code($esBaseDatos ? 503 : 500);
        header("Content-Type: text/html; charset=UTF-8");
    }

    //ruta web de la raiz del proyecto, para enlazar los estilos y volver al inicio
    $raiz = rtrim(str_replace("\\", "/", substr(dirname(__DIR__), strlen(rtrim($_SERVER["DOCUMENT_ROOT"] ?? "", "/\\")))), "/") . "/";
    $titulo = $esBaseDatos ? "No se puede conectar con la base de datos" : "Algo ha salido mal";
    $texto = $esBaseDatos
        ? "Ahora mismo no podemos acceder a los datos. Si eres quien administra la web, comprueba que MySQL está encendido en XAMPP."
        : "Se ha producido un error inesperado y no se ha podido completar la operación.";

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . htmlspecialchars($titulo) . '</title>'
        . '<link rel="stylesheet" href="' . htmlspecialchars($raiz) . 'assets/css/estilos.css"></head>'
        . '<body class="acceso"><main class="acceso__caja"><div class="tarjeta formulario">'
        . '<div class="alerta alerta--error" role="alert"><strong>' . htmlspecialchars($titulo) . '</strong></div>'
        . '<p>' . htmlspecialchars($texto) . '</p>'
        . '<p class="campo__nota">Código de referencia: <strong>' . $referencia . '</strong></p>'
        . '<a class="boton boton--primario boton--bloque" href="' . htmlspecialchars($raiz) . 'index.php">Volver al inicio</a>'
        . '</div></main></body></html>';
});

//id numerico de un campo POST: solo cifras ("12"), o null si no lo es
//(con (int) a secas, "1 OR 1=1" se convertiria en 1 y se actuaria sobre el registro 1)
function idPost(string $clave = "id"): ?int {
    $valor = $_POST[$clave] ?? "";
    return is_string($valor) && ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
}

//valor de un campo POST que debe ser texto (contraseñas: no se limpian, se usan tal cual)
//si alguien envia una lista en lugar de un texto devuelve "" en vez de provocar un error
function campoPost(string $clave): string {
    $valor = $_POST[$clave] ?? "";
    return is_string($valor) ? $valor : "";
}

/*
 * Limpia un texto que llega de un formulario:
 * - quita bytes que no son UTF-8 valido
 * - quita caracteres de control invisibles (los saltos de linea solo se conservan si $multilinea)
 * - quita etiquetas HTML y PHP
 * - une espacios repetidos y recorta los extremos
 * No sustituye al htmlspecialchars al mostrar los datos ni a las consultas preparadas: es una capa mas.
 */
function sanitizarTexto(mixed $texto, bool $multilinea = false): string {
    //si llega una lista (nombre[]=...) u otra cosa que no es texto, se trata como vacio
    if (!is_string($texto)) {
        return "";
    }
    $texto = mb_convert_encoding($texto, "UTF-8", "UTF-8");
    $texto = $multilinea
        ? preg_replace('/[^\P{C}\n\t]/u', "", str_replace("\r\n", "\n", $texto))
        : preg_replace('/\p{C}/u', "", $texto);
    $texto = strip_tags($texto);
    $texto = $multilinea
        ? preg_replace(["/[ \t]+/u", "/\n{3,}/"], [" ", "\n\n"], $texto)
        : preg_replace('/\s+/u', " ", $texto);
    return trim($texto);
}
?>
