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
