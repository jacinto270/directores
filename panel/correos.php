<?php
//buzon de pruebas: muestra los correos guardados en modo "archivo" (adaptado del dev-mailbox de login-app)
//solo para administradores y solo en modo "archivo": con "smtp" los correos se envian de verdad y aqui no hay nada
require_once __DIR__ . "/../includes/sesion.php";
requiereAdmin("../");
require_once __DIR__ . "/../clases/correo.php";

$modoArchivo = Correo::modo() !== "smtp";
$archivos = $modoArchivo ? (glob(Correo::CARPETA_BUZON . "/*.txt") ?: []) : [];
rsort($archivos);                        //los nombres empiezan por la fecha: los mas nuevos primero
$archivos = array_slice($archivos, 0, 30);

//convierte las direcciones del texto en enlaces (el resto del texto va escapado)
function enlazar(string $texto): string {
    return preg_replace(
        '~(https?://[^\s<]+)~',
        '<a href="$1" target="_blank" rel="noopener">$1</a>',
        htmlspecialchars($texto)
    );
}

$titulo = "Correos";
$seccion = "correos";
$base = "../";
require __DIR__ . "/../includes/cabecera_panel.php";
?>

    <div class="encabezado-pagina">
        <div>
            <h1>Buzón de pruebas</h1>
            <p>Correos que la web habría enviado (modo <strong>archivo</strong>). Sirve para probar el registro sin un servidor de correo.</p>
        </div>
    </div>

    <?php if (!$modoArchivo): ?>
        <div class="alerta alerta--ok formulario-ancho">
            Los correos se están enviando de verdad por SMTP (modo <strong>smtp</strong> en <code>config.php</code>), así que aquí no se guarda ninguno.
        </div>
    <?php elseif (!$archivos): ?>
        <div class="tarjeta vacio">Todavía no hay correos. Regístrate en el sitio público y el correo de verificación aparecerá aquí.</div>
    <?php else: ?>
        <div class="lista-correos">
            <?php foreach ($archivos as $archivo): ?>
                <?php
                    $contenido = (string) file_get_contents($archivo);
                    [$cabecera, $cuerpo] = array_pad(explode(str_repeat("-", 72), $contenido, 2), 2, "");
                    preg_match('/^Asunto:\s*(.*)$/m', $cabecera, $asunto);
                    preg_match('/^Para:\s*(.*)$/m', $cabecera, $para);
                    preg_match('/^Fecha:\s*(.*)$/m', $cabecera, $fecha);
                ?>
                <article class="tarjeta correo">
                    <div class="correo__cabecera">
                        <strong><?php echo htmlspecialchars($asunto[1] ?? "(sin asunto)"); ?></strong>
                        <span class="campo__nota">Para: <?php echo htmlspecialchars($para[1] ?? ""); ?> · <?php echo htmlspecialchars($fecha[1] ?? ""); ?></span>
                    </div>
                    <pre class="correo__cuerpo"><?php echo enlazar(trim($cuerpo)); ?></pre>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require __DIR__ . "/../includes/pie_panel.php"; ?>
