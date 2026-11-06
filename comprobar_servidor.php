<?php
/*
 * Comprobacion del servidor: subelo al hosting, abrelo una vez en el navegador y BORRALO despues
 * (tiene un boton para borrarse solo). No muestra contraseñas ni claves.
 * Comprueba lo que necesita el proyecto sin tener que tocar la configuracion de PHP.
 */

//boton "Borrar este archivo"
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["borrar"])) {
    $borrado = @unlink(__FILE__);
    header("Content-Type: text/html; charset=UTF-8");
    echo $borrado
        ? "<p style='font-family:sans-serif'>✅ Archivo borrado. Ya puedes cerrar esta página.</p>"
        : "<p style='font-family:sans-serif'>⚠️ No se ha podido borrar. Bórralo a mano por FTP: <code>comprobar_servidor.php</code></p>";
    exit;
}

$resultados = [];   //[estado, que se comprueba, detalle]  estado: ok | aviso | fallo
$anadir = function (string $estado, string $que, string $detalle) use (&$resultados) {
    $resultados[] = [$estado, $que, $detalle];
};

// ---------- PHP ----------
$anadir(
    version_compare(PHP_VERSION, "8.1.0", ">=") ? "ok" : "fallo",
    "Versión de PHP",
    PHP_VERSION . (version_compare(PHP_VERSION, "8.1.0", ">=") ? " (vale: hace falta 8.1 o superior)" : " — el proyecto necesita PHP 8.1 o superior")
);
$anadir(extension_loaded("pdo_mysql") ? "ok" : "fallo", "Extensión pdo_mysql", extension_loaded("pdo_mysql") ? "Activa" : "Falta: sin ella no se puede conectar con MySQL");
$anadir(extension_loaded("mbstring") ? "ok" : "fallo", "Extensión mbstring", extension_loaded("mbstring") ? "Activa" : "Falta: se usa para validar textos con tildes");
$anadir(function_exists("mime_content_type") ? "ok" : "aviso", "Extensión fileinfo", function_exists("mime_content_type") ? "Activa" : "No está, pero no pasa nada: el proyecto usa getimagesize() en su lugar");
$anadir(extension_loaded("gd") ? "ok" : "aviso", "Extensión GD", extension_loaded("gd") ? "Activa: las fotos y portadas se reducirán" : "No está: las imágenes se guardarán sin reducir (funciona igual)");

$puedeHttps = function_exists("curl_init") || ini_get("allow_url_fopen");
$anadir(
    $puedeHttps ? "ok" : "fallo",
    "Conexiones HTTPS (para Brevo)",
    function_exists("curl_init") ? "cURL activa" : (ini_get("allow_url_fopen") ? "Sin cURL, pero allow_url_fopen está activo: se usará como alternativa" : "No hay cURL ni allow_url_fopen: no se podrán enviar correos con Brevo")
);

$maxSubida = ini_get("upload_max_filesize");
$bytes = (int) $maxSubida * (stripos($maxSubida, "G") ? 1073741824 : (stripos($maxSubida, "M") ? 1048576 : (stripos($maxSubida, "K") ? 1024 : 1)));
$anadir($bytes >= 2 * 1048576 ? "ok" : "aviso", "Tamaño máximo de subida", "$maxSubida" . ($bytes >= 2 * 1048576 ? " (vale: el proyecto permite imágenes de hasta 2 MB)" : " — menor que los 2 MB que permite el proyecto"));

// ---------- archivos y permisos ----------
foreach (["img" => "portadas", "img/avatares" => "fotos de perfil"] as $carpeta => $para) {
    $ruta = __DIR__ . "/$carpeta";
    $prueba = "$ruta/.prueba_escritura_" . bin2hex(random_bytes(4));
    $puede = is_dir($ruta) && @file_put_contents($prueba, "x") !== false;
    if ($puede) {
        @unlink($prueba);
    }
    $anadir($puede ? "ok" : "fallo", "Escritura en $carpeta/", $puede ? "PHP puede guardar $para" : (is_dir($ruta) ? "PHP no puede escribir: pon la carpeta a 755 (o 775) desde el FTP" : "La carpeta no existe: súbela"));
}
foreach ([".htaccess", "clases/.htaccess", "includes/.htaccess", "img/avatares/.htaccess"] as $archivo) {
    $anadir(is_file(__DIR__ . "/$archivo") ? "ok" : "fallo", "Archivo $archivo", is_file(__DIR__ . "/$archivo") ? "Subido" : "Falta: en FileZilla activa \"Forzar mostrar archivos ocultos\" y súbelo");
}
$anadir(is_file(__DIR__ . "/clases/lib/PHPMailer/PHPMailer.php") ? "ok" : "aviso", "PHPMailer", is_file(__DIR__ . "/clases/lib/PHPMailer/PHPMailer.php") ? "Subido" : "Falta clases/lib/PHPMailer/ (solo hace falta en modo smtp)");

// ---------- configuracion y base de datos ----------
$config = __DIR__ . "/clases/datos/config.php";
if (!is_file($config)) {
    $anadir("fallo", "config.php", "Falta clases/datos/config.php: súbelo con los datos de MySQL del hosting");
} else {
    require $config;
    try {
        $bd = new PDO("mysql:host=$servidor;dbname=$nombreBase;charset=utf8mb4", $usuarioBD, $passwordBD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        $anadir("ok", "Conexión con MySQL", "Conectado a la base «{$nombreBase}» en {$servidor}");

        $tablas = $bd->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $faltan = array_diff(["pelicula", "director", "usuario", "miembro", "token_email"], $tablas);
        $anadir($faltan ? "fallo" : "ok", "Tablas de la base de datos", $faltan ? "Faltan: " . implode(", ", $faltan) . ". Importa la base exportada desde tu XAMPP" : "Están las 5 tablas");

        if (!$faltan) {
            $columnas = $bd->query("SHOW COLUMNS FROM miembro")->fetchAll(PDO::FETCH_COLUMN);
            $falta = array_diff(["avatar", "verificado"], $columnas);
            $anadir($falta ? "fallo" : "ok", "Columnas de miembro", $falta ? "Faltan: " . implode(", ", $falta) . ". La base del servidor es antigua: vuelve a importar la de tu XAMPP" : "Al día (avatar y verificación del email)");
            $cuentas = $bd->query("SELECT (SELECT COUNT(*) FROM pelicula), (SELECT COUNT(*) FROM director), (SELECT COUNT(*) FROM usuario)")->fetch(PDO::FETCH_NUM);
            $anadir("ok", "Datos", "{$cuentas[0]} películas, {$cuentas[1]} directores y {$cuentas[2]} usuarios del panel");
        }
    } catch (PDOException $e) {
        $anadir("fallo", "Conexión con MySQL", "No conecta. Revisa \$servidor, \$nombreBase, \$usuarioBD y \$passwordBD en config.php (en el hosting el servidor NO es localhost)");
    }

    //la direccion del enlace de los correos tiene que ser la de esta web
    $actual = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "") . rtrim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/\\");
    $url = rtrim($urlSitio ?? "", "/");
    $anadir(
        $url === "" ? "fallo" : (str_contains($url, "localhost") && !str_contains($actual, "localhost") ? "fallo" : (strcasecmp($url, $actual) === 0 ? "ok" : "aviso")),
        "\$urlSitio (enlace de los correos)",
        $url === "" ? "No está definido en config.php" : (str_contains($url, "localhost") && !str_contains($actual, "localhost") ? "Sigue apuntando a localhost: cámbialo a $actual" : (strcasecmp($url, $actual) === 0 ? "$url (coincide con esta web)" : "$url — esta web está en $actual. Revisa que sea la misma (http/https, con o sin www)"))
    );
    $modo = $correo["modo"] ?? "archivo";
    $anadir($modo === "archivo" ? "aviso" : "ok", "Modo de envío de correos", $modo === "archivo" ? "\"archivo\": en el servidor nadie recibiría los correos. Pon \"brevo\" en config.php" : "\"$modo\"");
    if ($modo === "brevo") {
        $anadir(($correo["brevo"]["apiKey"] ?? "") !== "" || getenv("BREVO_API_KEY") ? "ok" : "fallo", "Clave de Brevo", ($correo["brevo"]["apiKey"] ?? "") !== "" || getenv("BREVO_API_KEY") ? "Definida (no se muestra)" : "Falta la clave en config.php");
    }
}

// ---------- conexion con Brevo (sin enviar nada: se usa una clave falsa a proposito) ----------
if ($puedeHttps) {
    $codigo = 0;
    if (function_exists("curl_init")) {
        $ch = curl_init("https://api.brevo.com/v3/smtp/email");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ["accept: application/json", "content-type: application/json", "api-key: comprobacion"], CURLOPT_POSTFIELDS => "{}"]);
        curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    } else {
        $contexto = stream_context_create(["http" => ["method" => "POST", "header" => "accept: application/json\r\ncontent-type: application/json\r\napi-key: comprobacion", "content" => "{}", "timeout" => 10, "ignore_errors" => true]]);
        @file_get_contents("https://api.brevo.com/v3/smtp/email", false, $contexto);
        $codigo = isset($http_response_header[0]) && preg_match('~HTTP/\S+\s+(\d{3})~', $http_response_header[0], $m) ? (int) $m[1] : 0;
        $error = error_get_last()["message"] ?? "";
    }
    //401 = Brevo ha respondido (rechaza la clave falsa, como se espera): hay conexion
    $anadir(
        $codigo > 0 ? "ok" : "fallo",
        "Conexión con Brevo",
        $codigo > 0 ? "El servidor llega a api.brevo.com (respuesta HTTP $codigo). No se ha enviado ningún correo" : "El servidor no puede conectar con api.brevo.com" . ($error ? " ($error)" : "") . ". Algunos hostings gratuitos bloquean las conexiones hacia fuera"
    );
}

$fallos = count(array_filter($resultados, fn($r) => $r[0] === "fallo"));
$avisos = count(array_filter($resultados, fn($r) => $r[0] === "aviso"));
$iconos = ["ok" => "✅", "aviso" => "⚠️", "fallo" => "❌"];
header("Content-Type: text/html; charset=UTF-8");
header("X-Robots-Tag: noindex");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Comprobación del servidor</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <main class="contenedor" style="max-width: 900px">
        <div class="encabezado-pagina">
            <div>
                <h1>Comprobación del servidor</h1>
                <p>Lo que necesita el Catálogo de películas para funcionar en este hosting.</p>
            </div>
        </div>

        <div class="alerta <?php echo $fallos ? "alerta--error" : "alerta--ok"; ?>">
            <?php if ($fallos): ?>
                <strong>Hay <?php echo $fallos; ?> <?php echo $fallos === 1 ? "problema" : "problemas"; ?> que corregir</strong> (marcados con ❌).
            <?php else: ?>
                <strong>Todo lo necesario está bien.</strong><?php echo $avisos ? " Revisa los avisos (⚠️), aunque no impiden que funcione." : ""; ?>
            <?php endif; ?>
        </div>

        <div class="tarjeta">
            <table class="tabla">
                <thead><tr><th></th><th>Qué se comprueba</th><th>Resultado</th></tr></thead>
                <tbody>
                <?php foreach ($resultados as [$estado, $que, $detalle]): ?>
                    <tr>
                        <td class="col-id"><?php echo $iconos[$estado]; ?></td>
                        <td><strong><?php echo htmlspecialchars($que); ?></strong></td>
                        <td><?php echo htmlspecialchars($detalle); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" style="margin-top: 20px">
            <div class="alerta alerta--error">
                <strong>Cuando termines, borra este archivo</strong>: muestra información del servidor que no debe quedar a la vista.
            </div>
            <button type="submit" name="borrar" value="1" class="boton boton--peligro">Borrar este archivo del servidor</button>
        </form>
    </main>
</body>
</html>
