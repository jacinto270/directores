    <!-- ventana de confirmacion que usan los formularios con data-confirmar -->
    <div class="modal" id="modal-confirmar" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
        <div class="modal__caja">
            <h2 id="modal-titulo">¿Estás seguro?</h2>
            <p data-modal-mensaje></p>
            <div class="modal__acciones">
                <button type="button" class="boton boton--secundario" data-modal-cancelar>Cancelar</button>
                <button type="button" class="boton boton--peligro" data-modal-aceptar>Eliminar</button>
            </div>
        </div>
    </div>
