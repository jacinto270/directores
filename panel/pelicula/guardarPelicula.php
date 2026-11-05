<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/imagenes.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
$dao = new PeliculasDAO();

$id = isset($_POST["id"]) && $_POST["id"] !== "" ? (int) $_POST["id"] : null;

//al editar, la pelicula tiene que existir
$pelicula = null;
if ($id !== null) {
    $pelicula = $dao->getById($id);
    if (!$pelicula) {
        header("Location: ../index.php?tab=peliculas");
        exit;
    }
}

//directores permitidos: los de la tabla y, al editar, el que ya tuviera la pelicula
$directores = array_map(fn($d) => $d->getNombre(), (new DirectorDAO())->findAll());
if ($pelicula) {
    $directores[] = $pelicula->getDirector();
}

[$datos, $errores] = validarPelicula($_POST, $directores);

//comprueba la portada sin guardarla todavia; devuelve el tipo de imagen o null si no se ha subido ninguna
function comprobarImagen(array &$errores): ?string {
    $archivo = $_FILES["imagen"] ?? null;
    if (!$archivo || $archivo["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["error"] === UPLOAD_ERR_FORM_SIZE || $archivo["size"] > 2 * 1024 * 1024) {
        $errores["imagen"] = "La portada no puede pesar más de 2 MB.";
        return null;
    }
    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        $errores["imagen"] = "No se pudo subir la portada. Inténtalo de nuevo.";
        return null;
    }
    //el tipo se comprueba por el contenido del archivo, no por su nombre
    $tipo = mime_content_type($archivo["tmp_name"]);
    if (!isset(EXTENSIONES_IMAGEN[$tipo]) || !@getimagesize($archivo["tmp_name"])) {
        $errores["imagen"] = "La portada debe ser una imagen JPG, PNG, WEBP o GIF.";
        return null;
    }
    return $tipo;
}

$tipoImagen = comprobarImagen($errores);

//si algo esta mal se vuelve al formulario con los errores y con lo que se habia escrito
if ($errores) {
    $datos["actores"] = implode(", ", $datos["actores"]);
    guardarFormulario("pelicula", $datos, $errores);
    header("Location: formPelicula.php" . ($id !== null ? "?id=$id" : ""));
    exit;
}

//la portada solo se guarda cuando todo lo demas es correcto
$imagen = null;
if ($tipoImagen !== null) {
    //se reduce a 600x900 como maximo (sin recortar) y se guarda como WEBP
    $imagen = guardarPortada($_FILES["imagen"]["tmp_name"], $tipoImagen, __DIR__ . "/../../img/");
}

//si existe un id, se actualiza la pelicula, si no, se crea una nueva
if ($pelicula) {
    $portadaAnterior = $pelicula->getImagen();
    $pelicula = new Pelicula(
        $datos["titulo"],
        $datos["director"],
        (int) $datos["anio"],
        $datos["genero"],
        $imagen ?? $portadaAnterior,
        $datos["visto"],
        $pelicula->getId(),
        $datos["actores"]
    );
    $dao->actualizar($pelicula);

    //la portada vieja se borra si se ha cambiado por una subida desde el panel
    if ($imagen !== null && str_starts_with($portadaAnterior, "portada_") && is_file(__DIR__ . "/../../img/" . basename($portadaAnterior))) {
        unlink(__DIR__ . "/../../img/" . basename($portadaAnterior));
    }
    $mensaje = "pelicula_actualizada";
} else {
    $nuevaPelicula = new Pelicula($datos["titulo"], $datos["director"], (int) $datos["anio"], $datos["genero"], $imagen ?? "", $datos["visto"], null, $datos["actores"]);
    $dao->setPelicula($nuevaPelicula);
    $mensaje = "pelicula_creada";
}

header("Location: ../index.php?tab=peliculas&msg=$mensaje");
exit;
?>
