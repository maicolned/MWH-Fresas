// =====================================================================
//  admin/admin.js — validaciones del panel en el navegador.
//  Le avisan al usuario ANTES de enviar. El servidor vuelve a revisar
//  todo, porque esto se puede saltar desactivando JavaScript.
// =====================================================================

function mostrarError(formulario, texto) {
    const caja = formulario.querySelector("#error-form");
    if (caja) caja.textContent = texto;
    return texto === "";
}

// Cada formulario tiene su función de validación. Devuelve el error o "".
const validaciones = {

    "form-login": f => {
        if (!/^\S+@\S+\.\S+$/.test(f.correo.value.trim())) return "Escriba un correo válido.";
        if (f.clave.value === "") return "Escriba la clave.";
        return "";
    },

    "form-producto": f => {
        const nombre = f.nombre.value.trim();
        const precio = Number(f.precio.value);
        const toppings = Number(f.toppings_incluidos.value);
        if (nombre.length < 3 || nombre.length > 60) return "El nombre debe tener entre 3 y 60 letras.";
        if (!Number.isInteger(precio) || precio < 1000 || precio > 200000) return "El precio debe estar entre 1000 y 200000, sin puntos.";
        if (!Number.isInteger(toppings) || toppings < 1 || toppings > 4) return "Los toppings incluidos deben ser entre 1 y 4.";
        const foto = f.imagen.files[0];
        if (foto && foto.size > 5 * 1024 * 1024) return "La foto pesa más de 5 MB. Redúzcala antes de subirla.";
        if (foto && !["image/jpeg", "image/png", "image/webp"].includes(foto.type)) return "La foto debe ser JPG, PNG o WEBP.";
        return "";
    },

    "form-ingrediente": f => {
        if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]{3,40}$/.test(f.nombre.value.trim())) return "El nombre debe tener entre 3 y 40 letras.";
        return "";
    },

    "form-clave": f => {
        if (f.actual.value === "") return "Escriba su clave actual.";
        if (f.nueva.value.length < 8) return "La clave nueva debe tener mínimo 8 caracteres.";
        if (f.nueva.value !== f.repite.value) return "La clave nueva y su repetición no coinciden.";
        return "";
    },
};

Object.keys(validaciones).forEach(id => {
    const formulario = document.getElementById(id);
    if (!formulario) return;
    formulario.addEventListener("submit", evento => {
        const error = validaciones[id](formulario);
        if (!mostrarError(formulario, error)) {
            evento.preventDefault();
        }
    });
});

// Formularios con data-confirmar="¿Seguro...?" piden confirmación antes de enviar.
document.querySelectorAll("form[data-confirmar]").forEach(formulario => {
    formulario.addEventListener("submit", evento => {
        if (!confirm(formulario.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});

// Vista previa de la foto del producto.
// Al escoger un archivo, se muestra de una vez, SIN subirlo todavía:
// URL.createObjectURL crea una dirección temporal para la foto que está
// en el computador del usuario. Se sube de verdad al oprimir "Guardar".
const campoFoto = document.getElementById("imagen");
const vistaPrevia = document.getElementById("vista-previa");
if (campoFoto && vistaPrevia) {
    const fotoActual = vistaPrevia.getAttribute("src");
    const nota = document.getElementById("nota-previa");

    campoFoto.addEventListener("change", () => {
        const foto = campoFoto.files[0];
        if (!foto) {
            // Quitó la selección: se vuelve a mostrar la foto actual.
            vistaPrevia.src = fotoActual;
            vistaPrevia.hidden = !fotoActual;
            nota.textContent = fotoActual ? "Foto actual" : "";
            return;
        }
        if (!foto.type.startsWith("image/")) {
            nota.textContent = "Ese archivo no es una imagen.";
            return;
        }
        vistaPrevia.src = URL.createObjectURL(foto);
        vistaPrevia.hidden = false;
        nota.textContent = "Foto nueva (todavía no se ha guardado): " + foto.name;
    });
}
