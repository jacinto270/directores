<?php
//envio de correos (adaptado del proyecto login-app)
//segun $correo["modo"] en config.php:
//  "archivo": el correo no se envia, se guarda como .txt en clases/datos/correos/ (para probar sin servidor de correo)
//  "smtp":    se envia de verdad con PHPMailer

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

        if (($config["modo"] ?? "archivo") === "smtp") {
            self::enviarSmtp($config, $para, $nombre, $asunto, $html, $texto);
        } else {
            self::guardarEnArchivo($config, $para, $nombre, $asunto, $texto);
        }
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
