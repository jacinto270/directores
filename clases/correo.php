<?php
//envio de correos (adaptado del proyecto login-app)
//segun $correo["modo"] en config.php:
//  "archivo": el correo no se envia, se guarda como .txt en clases/datos/correos/ (para probar sin servidor de correo)
//  "smtp":    se envia de verdad con PHPMailer
//  "brevo":   se envia de verdad con la API de Brevo (recomendado: funciona aunque el hosting bloquee SMTP)

require_once __DIR__ . "/lib/PHPMailer/Exception.php";
require_once __DIR__ . "/lib/PHPMailer/PHPMailer.php";
require_once __DIR__ . "/lib/PHPMailer/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExcepcionPHPMailer;

class Correo {

    //carpeta del buzon de pruebas (dentro de clases/, que esta bloqueada desde el navegador)
    const CARPETA_BUZON = __DIR__ . "/datos/correos";

    //configuracion de config.php ($correo y $urlSitio)
    public static function configuracion(): array {
        require __DIR__ . "/datos/config.php";
        return [
            "correo" => $correo ?? ["modo" => "archivo", "remitente" => "no-reply@peliculas.local", "nombreRemitente" => "Catálogo de películas", "horasEnlace" => 24],
            "urlSitio" => rtrim($urlSitio ?? "http://localhost/peliculas", "/"),
        ];
    }

    public static function modo(): string {
        return self::configuracion()["correo"]["modo"] ?? "archivo";
    }

    //lanza una excepcion si no se ha podido enviar (o guardar), para que quien llama pueda deshacer el alta
    public static function enviar(string $para, string $nombre, string $asunto, string $html, string $texto): void {
        $config = self::configuracion()["correo"];

        match ($config["modo"] ?? "archivo") {
            "brevo" => self::enviarBrevo($config, $para, $nombre, $asunto, $html, $texto),
            "smtp" => self::enviarSmtp($config, $para, $nombre, $asunto, $html, $texto),
            default => self::guardarEnArchivo($config, $para, $nombre, $asunto, $texto),
        };
    }

    //clave de la API de Brevo: la variable de entorno BREVO_API_KEY tiene preferencia sobre config.php
    private static function claveBrevo(array $config): string {
        $deEntorno = getenv("BREVO_API_KEY");
        return trim($deEntorno !== false && $deEntorno !== "" ? $deEntorno : (string) ($config["brevo"]["apiKey"] ?? ""));
    }

    /*
     * Envio con la API transaccional de Brevo (https://developers.brevo.com/reference/sendtransacemail).
     * Usa cURL, que ya viene en XAMPP. El remitente debe estar verificado en Brevo
     * (Remitentes, dominios e IPs dedicadas); si no, Brevo rechaza el envio.
     */
    private static function enviarBrevo(array $config, string $para, string $nombre, string $asunto, string $html, string $texto): void {
        $clave = self::claveBrevo($config);
        if ($clave === "") {
            throw new RuntimeException("Falta la clave de la API de Brevo (\$correo[\"brevo\"][\"apiKey\"] en config.php o la variable de entorno BREVO_API_KEY).");
        }
        $destinatario = ["email" => $para];
        if ($nombre !== "") {
            $destinatario["name"] = $nombre;
        }
        $datos = [
            "sender" => ["email" => $config["remitente"], "name" => $config["nombreRemitente"] ?? ""],
            "to" => [$destinatario],
            "subject" => $asunto,
            "htmlContent" => $html,
            "textContent" => $texto,
        ];
        if (!empty($config["brevo"]["etiquetas"])) {
            $datos["tags"] = array_values((array) $config["brevo"]["etiquetas"]);
        }

        [$estado, $respuesta, $errorConexion] = self::peticionPost(
            "https://api.brevo.com/v3/smtp/email",
            ["accept: application/json", "content-type: application/json", "api-key: " . $clave],
            json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            (int) ($config["brevo"]["timeout"] ?? 15)
        );

        if ($respuesta === false) {
            throw new RuntimeException("No se pudo conectar con Brevo: $errorConexion");
        }
        //Brevo responde 201 Created con {"messageId": "..."} si todo ha ido bien
        if ($estado < 200 || $estado >= 300) {
            $json = json_decode((string) $respuesta, true);
            $mensaje = is_array($json) ? ($json["message"] ?? $respuesta) : $respuesta;
            $codigo = is_array($json) ? ($json["code"] ?? "") : "";
            throw new RuntimeException(sprintf("Brevo rechazó el envío (HTTP %d%s): %s", $estado, $codigo !== "" ? ", $codigo" : "", $mensaje));
        }
    }

    /*
     * Peticion POST por HTTPS. Devuelve [codigo HTTP, respuesta o false, error de conexion].
     * Usa cURL si el servidor la tiene; si no (algunos hostings gratuitos), las funciones
     * normales de PHP para abrir direcciones web (necesitan allow_url_fopen, activado por defecto).
     */
    private static function peticionPost(string $url, array $cabeceras, string $cuerpo, int $segundos): array {
        if (function_exists("curl_init")) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $segundos,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $cabeceras,
                CURLOPT_POSTFIELDS => $cuerpo,
            ]);
            $respuesta = curl_exec($ch);
            $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            return [$estado, $respuesta, $error];
        }

        if (!ini_get("allow_url_fopen")) {
            return [0, false, "el servidor no tiene cURL ni allow_url_fopen activados"];
        }
        $contexto = stream_context_create(["http" => [
            "method" => "POST",
            "header" => implode("\r\n", $cabeceras),
            "content" => $cuerpo,
            "timeout" => $segundos,
            "ignore_errors" => true,    //para leer la respuesta aunque sea un error (400, 401...)
        ]]);
        $respuesta = @file_get_contents($url, false, $contexto);
        //la primera linea de las cabeceras de respuesta es "HTTP/1.1 201 Created"
        $estado = isset($http_response_header[0]) && preg_match('~HTTP/\S+\s+(\d{3})~', $http_response_header[0], $m) ? (int) $m[1] : 0;
        $error = $respuesta === false ? (error_get_last()["message"] ?? "no se pudo conectar") : "";
        return [$estado, $respuesta, $error];
    }

    private static function enviarSmtp(array $config, string $para, string $nombre, string $asunto, string $html, string $texto): void {
        $smtp = $config["smtp"] ?? [];
        $mail = new PHPMailer(true);    //true = lanza excepciones si algo falla
        try {
            $mail->isSMTP();
            $mail->Host = (string) ($smtp["host"] ?? "");
            $mail->Port = (int) ($smtp["puerto"] ?? 587);
            $mail->SMTPAuth = ($smtp["usuario"] ?? "") !== "";
            $mail->Username = (string) ($smtp["usuario"] ?? "");
            $mail->Password = (string) ($smtp["password"] ?? "");
            $mail->SMTPSecure = match ($smtp["cifrado"] ?? "tls") {
                "ssl" => PHPMailer::ENCRYPTION_SMTPS,       //puerto 465
                "tls" => PHPMailer::ENCRYPTION_STARTTLS,    //puerto 587
                default => "",
            };
            if ($mail->SMTPSecure === "") {
                $mail->SMTPAutoTLS = false;
            }
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->setLanguage("es", __DIR__ . "/lib/PHPMailer/language/");
            $mail->setFrom($config["remitente"], $config["nombreRemitente"] ?? "");
            $mail->addAddress($para, $nombre);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $html;
            $mail->AltBody = $texto;
            $mail->send();
        } catch (ExcepcionPHPMailer $e) {
            throw new RuntimeException("Error al enviar el correo: " . $mail->ErrorInfo, 0, $e);
        }
    }

    private static function guardarEnArchivo(array $config, string $para, string $nombre, string $asunto, string $texto): void {
        $carpeta = self::CARPETA_BUZON;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new RuntimeException("No se pudo crear la carpeta del buzón: $carpeta");
        }

        //nombre del archivo: fecha + destinatario sin caracteres raros
        $destinoSeguro = preg_replace('/[^a-z0-9._-]+/i', "-", str_replace("@", "-at-", $para));
        $archivo = date("Ymd-His") . "_" . substr((string) hrtime(true), -6) . "_" . $destinoSeguro . ".txt";

        $contenido = implode("\n", [
            "Fecha:   " . date("d/m/Y H:i:s"),
            "De:      " . ($config["nombreRemitente"] ?? "") . " <" . ($config["remitente"] ?? "") . ">",
            "Para:    " . ($nombre !== "" ? "$nombre <$para>" : $para),
            "Asunto:  " . $asunto,
            str_repeat("-", 72),
            "",
            $texto,
            "",
        ]);

        if (file_put_contents("$carpeta/$archivo", $contenido, LOCK_EX) === false) {
            throw new RuntimeException("No se pudo guardar el correo en $carpeta");
        }
    }

    //---------- plantillas: cada una devuelve [asunto, html, texto] ----------

    public static function plantillaVerificacion(string $nombre, string $enlace, int $horas): array {
        $asunto = "Confirma tu cuenta en el Catálogo de películas";

        $texto = "Hola $nombre:\n\n"
            . "Gracias por registrarte en el Catálogo de películas. Para activar tu cuenta abre este enlace:\n\n"
            . "$enlace\n\n"
            . "El enlace caduca en $horas horas y solo se puede usar una vez.\n"
            . "Si no has creado tú esta cuenta, ignora este mensaje.";

        $e = fn(string $t) => htmlspecialchars($t, ENT_QUOTES, "UTF-8");
        $html = '<!DOCTYPE html><html lang="es"><body style="margin:0;background:#f8f5f2;font-family:Arial,sans-serif;color:#3d3a4b">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px"><tr><td align="center">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:14px;padding:32px">'
            . '<tr><td style="font-size:28px;text-align:center">🎬</td></tr>'
            . '<tr><td style="font-family:Georgia,serif;font-size:22px;font-weight:bold;text-align:center;padding:8px 0 20px">Confirma tu email</td></tr>'
            . '<tr><td style="font-size:15px;line-height:1.6">Hola ' . $e($nombre) . ':<br><br>'
            . 'Gracias por registrarte en el <strong>Catálogo de películas</strong>. Pulsa el botón para activar tu cuenta.</td></tr>'
            . '<tr><td align="center" style="padding:28px 0"><a href="' . $e($enlace) . '" style="background:#6c5bb5;color:#ffffff;text-decoration:none;padding:13px 26px;border-radius:9px;font-weight:bold;display:inline-block">Verificar mi cuenta</a></td></tr>'
            . '<tr><td style="font-size:13px;color:#7a768a;line-height:1.5">El enlace caduca en ' . $horas . ' horas y solo se puede usar una vez. '
            . 'Si el botón no funciona, copia esta dirección en el navegador:<br><span style="word-break:break-all;color:#6c5bb5">' . $e($enlace) . '</span><br><br>'
            . 'Si no has creado tú esta cuenta, ignora este mensaje.</td></tr>'
            . '</table></td></tr></table></body></html>';

        return [$asunto, $html, $texto];
    }
}
?>
