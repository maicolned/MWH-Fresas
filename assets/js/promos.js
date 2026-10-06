// =====================================================================
//  promos.js — carrusel de publicidad del inicio.
//
//  El deslizamiento es CSS (scroll-snap en estilos.css): con el dedo o
//  el trackpad ya se mueve y se "pega" a cada pieza. Este archivo agrega:
//    1. Flechas que dan la vuelta: en la última, "siguiente" vuelve a
//       la primera; en la primera, "anterior" va a la última.
//    2. Avance automático cada 4 segundos, también dando la vuelta.
//    3. Pausa mientras el cliente lo está mirando o tocando (mouse
//       encima, dedo en la pantalla, foco del teclado) y cuando la
//       pestaña no está visible. Si la persona pidió en su equipo
//       "reducir movimiento", no avanza solo.
//    4. Puntos debajo que muestran en qué posición va y llevan a ella.
//       Hay un punto por cada POSICIÓN, no por cada pieza: en computador
//       se ven 3 piezas a la vez, así que con 5 piezas hay solo 3
//       posiciones (1-2-3, 2-3-4, 3-4-5). En el celular, 5.
// =====================================================================

const carril = document.getElementById("promos-carril");

if (carril) {
    const piezas = Array.from(carril.querySelectorAll(".promo"));
    const anterior = document.querySelector(".promos-anterior");
    const siguiente = document.querySelector(".promos-siguiente");
    const puntos = document.getElementById("promos-puntos");
    const ESPERA = 4000; // milisegundos entre cada avance automático

    // ¿En qué pieza va? La que está más cerca del borde izquierdo del carril.
    function actual() {
        let cerca = 0;
        let menor = Infinity;
        piezas.forEach((p, i) => {
            const distancia = Math.abs(p.offsetLeft - carril.offsetLeft - carril.scrollLeft - piezaInicio());
            if (distancia < menor) {
                menor = distancia;
                cerca = i;
            }
        });
        return cerca;
    }

    // El carril tiene un relleno a la izquierda; la primera pieza empieza ahí.
    function piezaInicio() {
        return piezas[0].offsetLeft - carril.offsetLeft;
    }

    // ¿Ya se ve la última pieza completa? (en computador se ven 3 a la vez,
    // así que el final llega antes de que la "actual" sea la quinta)
    function enElFinal() {
        return carril.scrollLeft >= carril.scrollWidth - carril.clientWidth - 4;
    }

    function irA(indice) {
        const total = piezas.length;
        const i = (indice + total) % total; // da la vuelta: 5 → 0 y -1 → 4
        carril.scrollTo({ left: piezas[i].offsetLeft - carril.offsetLeft - piezaInicio(), behavior: "smooth" });
    }

    function avanzar() {
        if (enElFinal()) {
            irA(0); // llegó al final: vuelve a empezar
        } else {
            irA(actual() + 1);
        }
    }

    function retroceder() {
        if (carril.scrollLeft <= 4) {
            carril.scrollTo({ left: carril.scrollWidth, behavior: "smooth" }); // al principio: salta al final
        } else {
            irA(actual() - 1);
        }
    }

    // --- Puntos -----------------------------------------------------------
    // ¿Cuántas piezas caben a la vista? Ancho del carril ÷ ancho de una pieza.
    function posiciones() {
        const espacio = parseFloat(getComputedStyle(carril).columnGap) || 0;
        const aLaVista = Math.max(1, Math.round((carril.clientWidth + espacio) / (piezas[0].offsetWidth + espacio)));
        return Math.max(1, piezas.length - aLaVista + 1);
    }

    // Se vuelven a crear si cambia el tamaño de la pantalla (3 ↔ 5 puntos).
    function crearPuntos() {
        const total = posiciones();
        if (puntos.children.length === total) return;
        puntos.innerHTML = "";
        for (let i = 0; i < total; i++) {
            const punto = document.createElement("button");
            punto.type = "button";
            punto.className = "promos-punto";
            punto.setAttribute("aria-label", "Ir a la posición " + (i + 1) + " de " + total);
            punto.addEventListener("click", () => { irA(i); reiniciar(); });
            puntos.appendChild(punto);
        }
    }

    function marcarPunto() {
        const total = puntos.children.length;
        // En el final se marca el último punto aunque la "actual" sea otra.
        const activo = enElFinal() ? total - 1 : Math.min(actual(), total - 1);
        Array.from(puntos.children).forEach((p, i) => p.classList.toggle("activo", i === activo));
    }

    // --- Avance automático ------------------------------------------------
    const sinMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let reloj = null;
    let pausado = false;

    function iniciar() {
        if (sinMovimiento) return;
        detener();
        reloj = setInterval(() => {
            if (!pausado && !document.hidden) avanzar();
        }, ESPERA);
    }

    function detener() {
        clearInterval(reloj);
    }

    // Después de que el cliente toca una flecha o un punto, se espera el
    // tiempo completo antes del siguiente avance (si no, salta enseguida).
    function reiniciar() {
        iniciar();
    }

    const marco = carril.closest(".promos-marco");
    marco.addEventListener("mouseenter", () => (pausado = true));
    marco.addEventListener("mouseleave", () => (pausado = false));
    marco.addEventListener("focusin", () => (pausado = true));
    marco.addEventListener("focusout", () => (pausado = false));
    carril.addEventListener("touchstart", () => (pausado = true), { passive: true });
    carril.addEventListener("touchend", () => { pausado = false; reiniciar(); }, { passive: true });

    // --- Flechas ----------------------------------------------------------
    anterior.addEventListener("click", () => { retroceder(); reiniciar(); });
    siguiente.addEventListener("click", () => { avanzar(); reiniciar(); });

    carril.addEventListener("scroll", marcarPunto, { passive: true });
    window.addEventListener("resize", () => { crearPuntos(); marcarPunto(); });

    crearPuntos();
    marcarPunto();
    iniciar();
}
