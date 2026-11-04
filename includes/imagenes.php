<?php
//tratamiento de imagenes subidas (fotos de perfil y portadas de peliculas)
//al volver a crear cada imagen desde cero se pierden los datos ocultos (ubicacion GPS,
//modelo de camara...) y cualquier codigo escondido en el archivo

//lado en pixeles de las fotos de perfil (son cuadradas)
const LADO_AVATAR = 400;

//tamaño maximo de las portadas; se reducen sin recortar, manteniendo la proporcion
const ANCHO_PORTADA = 600;
const ALTO_PORTADA = 900;

const EXTENSIONES_IMAGEN = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp", "image/gif" => "gif"];

/*
 * Guarda una foto de perfil recortada en cuadrado y reducida a LADO_AVATAR, como WEBP.
 * Devuelve el nombre del archivo guardado dentro de $carpeta, o null si falla.
 */
function guardarAvatar(string $origen, string $tipo, string $carpeta): ?string {
    $nombre = bin2hex(random_bytes(16));

    if (!extension_loaded("gd")) {
        return moverSinProcesar($origen, $tipo, $carpeta, $nombre);
    }

    $imagen = abrirImagen($origen, $tipo);
    if (!$imagen) {
        return null;
    }

    //recorte cuadrado centrado
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);
    $lado = min($ancho, $alto);
    $final = min(LADO_AVATAR, $lado);

    $avatar = lienzoTransparente($final, $final);
    imagecopyresampled($avatar, $imagen, 0, 0, intdiv($ancho - $lado, 2), intdiv($alto - $lado, 2), $final, $final, $lado, $lado);
    imagedestroy($imagen);

    return guardarWebp($avatar, $carpeta, $nombre);
}

/*
 * Guarda una portada reducida para que quepa en ANCHO_PORTADA x ALTO_PORTADA, sin recortar
 * (si ya es mas pequeña no se agranda), como WEBP.
 * Devuelve el nombre del archivo guardado dentro de $carpeta, o null si falla.
 */
function guardarPortada(string $origen, string $tipo, string $carpeta): ?string {
    $nombre = uniqid("portada_");

    if (!extension_loaded("gd")) {
        return moverSinProcesar($origen, $tipo, $carpeta, $nombre);
    }

    $imagen = abrirImagen($origen, $tipo);
    if (!$imagen) {
        return null;
    }

    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);
    $escala = min(1, ANCHO_PORTADA / $ancho, ALTO_PORTADA / $alto);
    $nuevoAncho = max(1, (int) round($ancho * $escala));
    $nuevoAlto = max(1, (int) round($alto * $escala));

    $portada = lienzoTransparente($nuevoAncho, $nuevoAlto);
    imagecopyresampled($portada, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
    imagedestroy($imagen);

    return guardarWebp($portada, $carpeta, $nombre);
}

// ---------- funciones de apoyo ----------

//abre la imagen y la endereza si es una foto de movil girada
function abrirImagen(string $origen, string $tipo) {
    $imagen = match ($tipo) {
        "image/jpeg" => @imagecreatefromjpeg($origen),
        "image/png" => @imagecreatefrompng($origen),
        "image/webp" => @imagecreatefromwebp($origen),
        "image/gif" => @imagecreatefromgif($origen),
        default => false,
    };
    if (!$imagen) {
        return false;
    }

    //las fotos de movil guardan el giro en los datos EXIF en lugar de girar los pixeles
    if ($tipo === "image/jpeg" && function_exists("exif_read_data")) {
        $exif = @exif_read_data($origen);
        $giro = match ($exif["Orientation"] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($giro !== 0) {
            $imagen = imagerotate($imagen, $giro, 0);
        }
    }

    return $imagen;
}

//lienzo vacio que conserva la transparencia de PNG, WEBP y GIF
function lienzoTransparente(int $ancho, int $alto) {
    $lienzo = imagecreatetruecolor($ancho, $alto);
    imagealphablending($lienzo, false);
    imagesavealpha($lienzo, true);
    imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
    return $lienzo;
}

function guardarWebp($imagen, string $carpeta, string $nombre): ?string {
    $archivo = $nombre . ".webp";
    $guardado = imagewebp($imagen, $carpeta . $archivo, 85);
    imagedestroy($imagen);
    return $guardado ? $archivo : null;
}

//sin la extension GD la imagen se guarda tal cual
function moverSinProcesar(string $origen, string $tipo, string $carpeta, string $nombre): ?string {
    $archivo = $nombre . "." . EXTENSIONES_IMAGEN[$tipo];
    return move_uploaded_file($origen, $carpeta . $archivo) ? $archivo : null;
}
?>
