<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../includes/imagenes.php";
$dao = new PeliculasDAO();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php?tab=peliculas");
    exit;
}

$id = $_POST["id"] ?? null;
$titulo = trim($_POST["titulo"] ?? "");
$director = trim($_POST["director"] ?? "");
$anio = (int) ($_POST["anio"] ?? 0);
$genero = trim($_POST["genero"] ?? "");
$visto = isset($_POST["visto"]);

//los actores llegan separados por comas y se guardan como array
$actores = array_values(array_filter(array_map("trim", explode(",", $_POST["actores"] ?? ""))));

//sube la portada a la carpeta img con un nombre unico; devuelve null si no se ha subido ninguna valida
function subirImagen(): ?string {
    if (!isset($_FILES["imagen"]) || $_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
        return null;
    }

    $permitidas = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp", "image/gif" => "gif"];
    $tipo = mime_content_type($_FILES["imagen"]["tmp_name"]);

    if (!isset($permitidas[$tipo]) || $_FILES["imagen"]["size"] > 2 * 1024 * 1024) {
        return null;
    }

    //se reduce a 600x900 como maximo (sin recortar) y se guarda como WEBP
    return guardarPortada($_FILES["imagen"]["tmp_name"], $tipo, __DIR__ . "/../../img/");
}

$imagen = subirImagen();

//si existe un id, se actualiza la pelicula, si no, se crea una nueva
if ($id) {
    $pelicula = $dao->getById((int) $id);
    if ($pelicula) {
        $pelicula->setTitulo($titulo);
        $pelicula->setDirector($director);
        $pelicula->setAnio($anio);
        $pelicula->setGenero($genero);
        $pelicula->setVisto($visto);
        if ($imagen !== null) {
            $pelicula->setImagen($imagen);
        }
        //la clase solo tiene setActor para añadir uno, asi que se crea de nuevo con la lista completa
        $pelicula = new Pelicula(
            $pelicula->getTitulo(),
            $pelicula->getDirector(),
            $pelicula->getAnio(),
            $pelicula->getGenero(),
            $pelicula->getImagen(),
            $pelicula->isVisto(),
            $pelicula->getId(),
            $actores
        );
        $dao->actualizar($pelicula);
    }
    $mensaje = "pelicula_actualizada";
} else {
    $nuevaPelicula = new Pelicula($titulo, $director, $anio, $genero, $imagen ?? "", $visto, null, $actores);
    $dao->setPelicula($nuevaPelicula);
    $mensaje = "pelicula_creada";
}

header("Location: ../index.php?tab=peliculas&msg=$mensaje");
exit;
?>
