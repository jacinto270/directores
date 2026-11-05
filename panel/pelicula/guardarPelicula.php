<?php
require_once __DIR__ . "/../../includes/sesion.php";
requiereLogin("../../");
verificarToken();
require_once __DIR__ . "/../../clases/peliculaDAO.php";
require_once __DIR__ . "/../../clases/directorDAO.php";
require_once __DIR__ . "/../../includes/imagenes.php";
require_once __DIR__ . "/../../includes/validacion_panel.php";
require_once __DIR__ . "/../../includes/base_datos.php";
$dao = new PeliculasDAO();

$id = idPost();

//al editar, la pelicula tiene que existir
$pelicula = null;
if (($_POST["id"] ?? "") !== "") {
    $pelicula = $id !== null ? $dao->getById($id) : null;
    if (!$pelicula) {
        avisar("error", "No se ha guardado nada: esa película no existe (puede que la haya eliminado otra persona).");
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
    $medidas = @getimagesize($archivo["tmp_name"]);
    if (!isset(EXTENSIONES_IMAGEN[$tipo]) || !$medidas) {
        $errores["imagen"] = "La portada debe ser una imagen JPG, PNG, WEBP o GIF.";
        return null;
    }
    //un archivo que solo empieza como un GIF puede engañar a getimagesize con medidas absurdas
    if ($medidas[0] > 5000 || $medidas[1] > 5000) {
        $errores["imagen"] = "La portada es demasiado grande (máximo 5000 × 5000 píxeles).";
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

    //si no se ha podido abrir como imagen (archivo dañado o disfrazado) no se guarda nada
    if ($imagen === null) {
        $datos["actores"] = implode(", ", $datos["actores"]);
        guardarFormulario("pelicula", $datos, ["imagen" => "La portada no es una imagen válida o está dañada."]);
        header("Location: formPelicula.php" . ($id !== null ? "?id=$id" : ""));
        exit;
    }
}

//si existe un id, se actualiza la pelicula, si no, se crea una nueva
try {
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

        //la portada vieja se borra (solo cuando ya se ha guardado la nueva) si se subio desde el panel
        if ($imagen !== null && str_starts_with($portadaAnterior, "portada_") && is_file(__DIR__ . "/../../img/" . basename($portadaAnterior))) {
            unlink(__DIR__ . "/../../img/" . basename($portadaAnterior));
        }
        avisar("ok", "Película «" . $datos["titulo"] . "» actualizada correctamente.");
    } else {
        $nuevaPelicula = new Pelicula($datos["titulo"], $datos["director"], (int) $datos["anio"], $datos["genero"], $imagen ?? "", $datos["visto"], null, $datos["actores"]);
        $dao->setPelicula($nuevaPelicula);
        avisar("ok", "Película «" . $datos["titulo"] . "» añadida correctamente.");
    }
} catch (PDOException $e) {
    //la portada nueva no ha llegado a usarse: se borra para no dejar archivos sueltos
    if ($imagen !== null && is_file(__DIR__ . "/../../img/" . $imagen)) {
        unlink(__DIR__ . "/../../img/" . $imagen);
    }
    avisarErrorBD($e, $pelicula ? "actualizar la película" : "añadir la película");
    $datos["actores"] = implode(", ", $datos["actores"]);
    guardarFormulario("pelicula", $datos, []);
    header("Location: formPelicula.php" . ($id !== null ? "?id=$id" : ""));
    exit;
}

header("Location: ../index.php?tab=peliculas");
exit;
?>
