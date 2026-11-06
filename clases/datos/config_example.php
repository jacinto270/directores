<?php
//copia este archivo como config.php y rellena tus datos (config.php no se sube a git)

$servidor = "";
$nombreBase = "";
$usuarioBD = "";
$passwordBD = "";

// direccion de la web, sin barra final: se usa para el enlace de los correos de verificacion
$urlSitio = "http://localhost/peliculas";

// envio de correos (verificacion del email al registrarse)
$correo = [
    //"archivo": no se envian, se guardan en clases/datos/correos/ y se leen en el panel (Correos)
    //"smtp":    se envian de verdad con PHPMailer
    "modo" => "archivo",
    "remitente" => "no-reply@peliculas.local",
    "nombreRemitente" => "Catálogo de películas",
    //validez del enlace de verificacion, en horas
    "horasEnlace" => 24,
    //datos SMTP (solo modo "smtp"). Gmail: smtp.gmail.com, 587, "tls" con una contraseña de aplicacion
    "smtp" => [
        "host" => "",
        "puerto" => 587,
        "cifrado" => "tls",      //"tls" (puerto 587), "ssl" (puerto 465) o "" (sin cifrar)
        "usuario" => "",
        "password" => "",
    ],
];

?>
