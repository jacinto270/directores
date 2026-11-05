<?php
//aviso del resultado de la ultima operacion (guardado con avisar() antes de redirigir)
//app.js lo anima y lo oculta; los errores se quedan hasta que se cierran
$aviso = tomarAviso();
if ($aviso): ?>
    <div class="aviso visible <?php echo $aviso["tipo"] === "error" ? "aviso--error" : ""; ?>"
         role="<?php echo $aviso["tipo"] === "error" ? "alert" : "status"; ?>"
         data-aviso="<?php echo $aviso["tipo"]; ?>">
        <span class="aviso__icono" aria-hidden="true"><?php echo $aviso["tipo"] === "error" ? "✕" : "✓"; ?></span>
        <span class="aviso__texto"><?php echo htmlspecialchars($aviso["texto"]); ?></span>
        <button type="button" class="aviso__cerrar" aria-label="Cerrar aviso" data-cerrar-aviso>×</button>
    </div>
<?php endif; ?>
