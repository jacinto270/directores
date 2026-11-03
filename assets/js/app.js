// ==========================================================
// Interacciones comunes del proyecto Películas
// ==========================================================

document.addEventListener("DOMContentLoaded", () => {
    iniciarAvatares();
    iniciarBiografias();
    iniciarBuscador();
    iniciarFiltros();
    iniciarConfirmacion();
    iniciarContador();
    iniciarVistaPrevia();
    mostrarAviso();
});

// muestra la portada elegida antes de subirla
function iniciarVistaPrevia() {
    const input = document.querySelector("[data-subida]");
    const vista = document.querySelector("[data-vista-previa]");
    if (!input || !vista) return;

    input.addEventListener("change", () => {
        const archivo = input.files[0];
        if (!archivo || !archivo.type.startsWith("image/")) return;

        const img = document.createElement("img");
        img.alt = "Vista previa de la portada";
        img.src = URL.createObjectURL(archivo);
        vista.replaceChildren(img);
    });
}

// colores pastel para los avatares con las iniciales del director
const COLORES_PASTEL = ["#c9bdf2", "#c4ead9", "#ffd9c7", "#f8cfe0", "#cbe4f6", "#f7efc0"];

function iniciarAvatares() {
    document.querySelectorAll(".avatar[data-nombre]").forEach((avatar) => {
        const nombre = avatar.dataset.nombre.trim();
        const iniciales = nombre
            .split(/\s+/)
            .slice(0, 2)
            .map((parte) => parte[0])
            .join("")
            .toUpperCase();
        // el mismo nombre siempre tiene el mismo color
        let suma = 0;
        for (const letra of nombre) suma += letra.charCodeAt(0);
        avatar.textContent = iniciales;
        avatar.style.background = COLORES_PASTEL[suma % COLORES_PASTEL.length];
    });
}

// botón "ver más" en biografías largas
function iniciarBiografias() {
    document.querySelectorAll(".biografia").forEach((bio) => {
        if (bio.scrollHeight <= bio.clientHeight + 2) return;

        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "ver-mas";
        boton.textContent = "Ver más";
        boton.addEventListener("click", () => {
            const abierta = bio.classList.toggle("abierta");
            boton.textContent = abierta ? "Ver menos" : "Ver más";
        });
        bio.after(boton);
    });
}

// filtra filas o tarjetas que tengan data-buscar mientras se escribe
function iniciarBuscador() {
    const input = document.querySelector("[data-buscador]");
    if (!input) return;

    input.addEventListener("input", aplicarFiltros);
}

// filtros por chips (género, visto...)
function iniciarFiltros() {
    document.querySelectorAll(".chip[data-filtro]").forEach((chip) => {
        chip.addEventListener("click", () => {
            document.querySelectorAll(".chip[data-filtro]").forEach((c) => c.classList.remove("activo"));
            chip.classList.add("activo");
            aplicarFiltros();
        });
    });
}

function normalizar(texto) {
    return texto.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
}

function aplicarFiltros() {
    const input = document.querySelector("[data-buscador]");
    const texto = input ? normalizar(input.value.trim()) : "";
    const chip = document.querySelector(".chip.activo[data-filtro]");
    const filtro = chip ? chip.dataset.filtro : "todos";

    let visibles = 0;
    document.querySelectorAll("[data-buscar]").forEach((elemento) => {
        const coincideTexto = normalizar(elemento.dataset.buscar).includes(texto);
        const categorias = (elemento.dataset.categorias || "").split("|");
        const coincideFiltro = filtro === "todos" || categorias.includes(filtro);
        const mostrar = coincideTexto && coincideFiltro;
        elemento.hidden = !mostrar;
        if (mostrar) visibles++;
    });

    const vacio = document.querySelector("[data-sin-resultados]");
    if (vacio) vacio.hidden = visibles > 0;
}

// ventana de confirmación para los formularios con data-confirmar
function iniciarConfirmacion() {
    const modal = document.getElementById("modal-confirmar");
    if (!modal) return;

    const mensaje = modal.querySelector("[data-modal-mensaje]");
    const aceptar = modal.querySelector("[data-modal-aceptar]");
    const cancelar = modal.querySelector("[data-modal-cancelar]");
    let formularioPendiente = null;

    const cerrar = () => {
        modal.classList.remove("abierto");
        formularioPendiente = null;
    };

    document.querySelectorAll("form[data-confirmar]").forEach((formulario) => {
        formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();
            formularioPendiente = formulario;
            mensaje.textContent = formulario.dataset.confirmar;
            modal.classList.add("abierto");
            cancelar.focus();
        });
    });

    aceptar.addEventListener("click", () => {
        if (formularioPendiente) formularioPendiente.submit();
    });
    cancelar.addEventListener("click", cerrar);
    modal.addEventListener("click", (evento) => {
        if (evento.target === modal) cerrar();
    });
    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") cerrar();
    });
}

// contador de caracteres para textareas con maxlength
function iniciarContador() {
    document.querySelectorAll("textarea[maxlength]").forEach((textarea) => {
        const ayuda = document.createElement("span");
        ayuda.className = "campo__ayuda";
        const actualizar = () => {
            ayuda.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
        };
        textarea.addEventListener("input", actualizar);
        textarea.after(ayuda);
        actualizar();
    });
}

// aviso temporal después de crear, actualizar o eliminar (?msg=...)
function mostrarAviso() {
    const parametros = new URLSearchParams(window.location.search);
    const msg = parametros.get("msg");
    const textos = {
        creado: "Director creado correctamente",
        actualizado: "Director actualizado correctamente",
        eliminado: "Director eliminado",
        pelicula_creada: "Película añadida correctamente",
        pelicula_actualizada: "Película actualizada correctamente",
        pelicula_eliminada: "Película eliminada",
    };
    if (!textos[msg]) return;

    const aviso = document.createElement("div");
    aviso.className = "aviso" + (msg.includes("elimina") ? " aviso--eliminado" : "");
    aviso.setAttribute("role", "status");
    aviso.textContent = "✓ " + textos[msg];
    document.body.appendChild(aviso);

    requestAnimationFrame(() => aviso.classList.add("visible"));
    setTimeout(() => {
        aviso.classList.remove("visible");
        setTimeout(() => aviso.remove(), 300);
    }, 3000);

    // quita ?msg de la URL para que no se repita al recargar
    parametros.delete("msg");
    const query = parametros.toString();
    history.replaceState(null, "", window.location.pathname + (query ? "?" + query : ""));
}
