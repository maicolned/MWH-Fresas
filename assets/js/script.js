// =====================================================================
//  script.js — el armador de vasos de la página "Personaliza".
//
//  Este archivo antes tenía las marcas de un conflicto de Git
//  (<<<<<<< HEAD, =======, >>>>>>>). JavaScript no entiende esas marcas
//  y dejaba de leer TODO el archivo: por eso el botón no hacía nada.
//  Regla del grupo: antes de "git push" siempre "git pull", y si sale
//  CONFLICT se resuelve antes de subir.
//
//  Qué hace:
//   1. Dibuja el vaso (el dibujo está en vaso.js) con lo que escoge el cliente.
//   2. No deja escoger más toppings de los que trae el vaso.
//   3. Guarda los vasos del pedido en una lista (el arreglo "vasos").
//   4. Revisa los datos del cliente antes de enviar (validación en JS).
//   5. Envía el pedido a guardar_pedido.php SIN precios: el servidor
//      los calcula con la base de datos.
// =====================================================================

// Si esta página no es la de personalizar, no hay nada que hacer.
if (typeof DATOS !== "undefined" && document.getElementById("vaso")) {
    iniciarArmador();
}

function iniciarArmador() {

    // Los vasos que el cliente va agregando. Cada uno es:
    // { producto_id: 2, toppings: [1, 5], salsa_id: 3 }
    const vasos = [];
    const MAXIMO_VASOS = 10;

    // --- Buscar cosas en DATOS por su id ---------------------------------
    function buscar(lista, id) {
        return lista.find(elemento => elemento.id === Number(id));
    }

    function pesos(valor) {
        return "$" + Number(valor).toLocaleString("es-CO");
    }

    // --- Leer lo que está escogido en pantalla ---------------------------
    function productoEscogido() {
        const radio = document.querySelector('input[name="producto"]:checked');
        return radio ? buscar(DATOS.productos, radio.value) : null;
    }

    function toppingsEscogidos() {
        const marcados = document.querySelectorAll('input[name="topping"]:checked');
        return Array.from(marcados).map(caja => Number(caja.value));
    }

    function salsaEscogida() {
        const radio = document.querySelector('input[name="salsa"]:checked');
        return radio ? Number(radio.value) : null;
    }

    // --- 2. No dejar marcar más toppings de los que trae el vaso ---------
    function actualizarLimite() {
        const producto = productoEscogido();
        const maximo = producto ? producto.toppings_incluidos : 0;
        const cajas = document.querySelectorAll('input[name="topping"]');

        // Si cambió a un vaso con menos toppings, se desmarcan los que sobran.
        let marcados = toppingsEscogidos();
        cajas.forEach(caja => {
            if (caja.checked && marcados.length > maximo) {
                caja.checked = false;
                marcados = toppingsEscogidos();
            }
        });

        // Cuando ya se llegó al máximo, las demás cajas se bloquean.
        const lleno = toppingsEscogidos().length >= maximo;
        cajas.forEach(caja => {
            const agotado = caja.closest("label").classList.contains("agotado");
            caja.disabled = agotado || (lleno && !caja.checked);
        });

        document.getElementById("cuantos-toppings").textContent =
            "(escoge " + maximo + ")";
    }

    // --- 1. Dibujar el vaso ------------------------------------------------
    // El dibujo lo hace dibujarVaso() en vaso.js. Aquí solo se le dice qué
    // escogió el cliente, y cuáles cosas son NUEVAS para que caigan animadas
    // (las que ya estaban no se vuelven a animar).
    let toppingsAntes = [];
    let salsaAntes = null;

    function pintarVaso() {
        const ids = toppingsEscogidos();
        const toppings = ids.map(id => buscar(DATOS.toppings, id));
        const salsa = buscar(DATOS.salsas, salsaEscogida()) || null;

        const nuevos = ids.filter(id => !toppingsAntes.includes(id));
        const salsaNueva = salsa !== null && (!salsaAntes || salsaAntes.id !== salsa.id);

        document.getElementById("vaso").innerHTML = dibujarVaso(toppings, salsa, nuevos, salsaNueva);

        toppingsAntes = ids;
        salsaAntes = salsa;

        // Precio y resumen del vaso (solo para mostrar).
        const producto = productoEscogido();
        document.getElementById("precio-vaso").textContent = producto ? pesos(producto.precio) : "";
        document.getElementById("resumen-vaso").textContent = describirVaso({
            producto_id: producto ? producto.id : null,
            toppings: ids,
            salsa_id: salsaEscogida(),
        });
    }

    // "Fresas con 2 toppings: Gomitas, Queso · salsa Mora"
    function describirVaso(v) {
        const producto = buscar(DATOS.productos, v.producto_id);
        if (!producto) return "";
        const nombres = v.toppings.map(id => buscar(DATOS.toppings, id).nombre);
        const salsa = buscar(DATOS.salsas, v.salsa_id);
        let texto = producto.nombre;
        if (nombres.length) texto += ": " + nombres.join(", ");
        if (salsa) texto += " · salsa " + salsa.nombre;
        return texto;
    }

    // --- 3. Agregar el vaso al pedido --------------------------------------
    function agregarVaso() {
        const error = document.getElementById("error-vaso");
        const producto = productoEscogido();
        const toppings = toppingsEscogidos();
        const salsa = salsaEscogida();

        error.textContent = "";

        if (!producto) {
            error.textContent = "Escoge un vaso 🍓";
            return;
        }
        if (toppings.length !== producto.toppings_incluidos) {
            error.textContent = "Este vaso lleva " + producto.toppings_incluidos +
                " topping" + (producto.toppings_incluidos > 1 ? "s" : "") + ". Llevas " + toppings.length + ".";
            return;
        }
        if (!salsa) {
            error.textContent = "Escoge una salsa 🥄";
            return;
        }
        if (vasos.length >= MAXIMO_VASOS) {
            error.textContent = "Máximo " + MAXIMO_VASOS + " vasos por pedido. Para más, escríbenos por WhatsApp.";
            return;
        }

        vasos.push({ producto_id: producto.id, toppings: toppings, salsa_id: salsa });

        // Se limpia la selección para armar el siguiente vaso.
        document.querySelectorAll('input[name="topping"]').forEach(c => (c.checked = false));
        document.querySelectorAll('input[name="salsa"]').forEach(r => (r.checked = false));
        actualizarLimite();
        pintarVaso();
        mostrarPedido();
        document.getElementById("pedido").scrollIntoView({ behavior: "smooth" });
    }

    function mostrarPedido() {
        const lista = document.getElementById("lista-vasos");
        lista.innerHTML = "";
        let total = 0;

        if (vasos.length === 0) {
            lista.innerHTML = '<li class="vacio">Todavía no has agregado vasos.</li>';
        }

        vasos.forEach((v, posicion) => {
            const producto = buscar(DATOS.productos, v.producto_id);
            total += producto.precio;

            const li = document.createElement("li");
            const texto = document.createElement("span");
            // textContent (y no innerHTML) para que ningún nombre se ejecute como código.
            texto.textContent = (posicion + 1) + ". " + describirVaso(v) + " — " + pesos(producto.precio);

            const quitar = document.createElement("button");
            quitar.type = "button";
            quitar.className = "quitar";
            quitar.textContent = "Quitar";
            quitar.addEventListener("click", () => {
                vasos.splice(posicion, 1);
                mostrarPedido();
            });

            li.append(texto, quitar);
            lista.appendChild(li);
        });

        // Este total es solo para mostrar. El que se cobra lo calcula el servidor.
        document.getElementById("total").textContent = pesos(total);
    }

    // --- 4. Validar los datos del cliente ----------------------------------
    // El servidor vuelve a revisar TODO esto, porque el JavaScript se puede
    // desactivar o modificar desde el navegador (F12).
    function validarPedido(datos) {
        if (datos.vasos.length === 0) return "Agrega al menos un vaso al pedido.";
        if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]{3,60}$/.test(datos.cliente)) return "Escribe tu nombre (solo letras, mínimo 3).";
        if (!/^3\d{9}$/.test(datos.telefono)) return "El celular debe tener 10 números y empezar por 3.";
        if (datos.entrega === "domicilio" && datos.direccion.length < 5) return "Escribe la dirección del domicilio.";
        return "";
    }

    // --- 5. Enviar el pedido -----------------------------------------------
    async function confirmarPedido(evento) {
        evento.preventDefault();
        const error = document.getElementById("error-pedido");
        const boton = document.getElementById("confirmar");

        const datos = {
            cliente: document.getElementById("cliente").value.trim(),
            telefono: document.getElementById("telefono").value.trim(),
            entrega: document.querySelector('input[name="entrega"]:checked').value,
            direccion: document.getElementById("direccion").value.trim(),
            notas: document.getElementById("notas").value.trim(),
            vasos: vasos, // OJO: no se envía ningún precio
            empresa: document.getElementById("empresa").value, // campo trampa: debe ir vacío
        };

        const problema = validarPedido(datos);
        if (problema) {
            error.textContent = problema;
            return;
        }

        error.textContent = "";
        boton.disabled = true;
        boton.textContent = "Enviando...";

        try {
            const respuesta = await fetch("guardar_pedido.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datos),
            });
            const resultado = await respuesta.json();

            if (!resultado.ok) {
                // El servidor encontró algo mal (por ejemplo, un topping se agotó).
                error.textContent = resultado.errores.join(" ");
                return;
            }

            // Pedido guardado: se muestra el número y el botón de WhatsApp.
            // Es un enlace que el cliente toca (y no un window.open automático)
            // porque los celulares bloquean las ventanas que se abren solas.
            document.getElementById("codigo").textContent = resultado.codigo;
            document.getElementById("total-final").textContent = pesos(resultado.total);
            document.getElementById("boton-whatsapp").href = resultado.whatsapp;
            document.getElementById("pedido").hidden = true;
            document.querySelector(".armador").hidden = true;
            document.getElementById("pedido-listo").hidden = false;
            document.getElementById("pedido-listo").scrollIntoView({ behavior: "smooth" });
        } catch (e) {
            console.error(e);
            error.textContent = "No se pudo enviar el pedido. Revisa tu conexión e inténtalo de nuevo 😢";
        } finally {
            boton.disabled = false;
            boton.textContent = "Confirmar pedido";
        }
    }

    // --- Conectar los eventos ----------------------------------------------
    document.querySelectorAll('input[name="producto"]').forEach(r =>
        r.addEventListener("change", () => { actualizarLimite(); pintarVaso(); }));
    document.querySelectorAll('input[name="topping"]').forEach(c =>
        c.addEventListener("change", () => { actualizarLimite(); pintarVaso(); }));
    document.querySelectorAll('input[name="salsa"]').forEach(r =>
        r.addEventListener("change", pintarVaso));

    document.querySelectorAll('input[name="entrega"]').forEach(r =>
        r.addEventListener("change", () => {
            document.getElementById("campo-direccion").hidden =
                document.querySelector('input[name="entrega"]:checked').value !== "domicilio";
        }));

    // El celular solo acepta números.
    document.getElementById("telefono").addEventListener("input", e => {
        e.target.value = e.target.value.replace(/\D/g, "").slice(0, 10);
    });

    document.getElementById("agregar").addEventListener("click", agregarVaso);
    document.getElementById("form-pedido").addEventListener("submit", confirmarPedido);

    actualizarLimite();
    pintarVaso();
}
