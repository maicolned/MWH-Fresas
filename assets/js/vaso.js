// =====================================================================
//  vaso.js — dibuja el vaso de fresas con crema en SVG.
//
//  Este archivo SOLO dibuja. No sabe de pedidos ni de precios: recibe
//  qué toppings y qué salsa escogió el cliente y devuelve el dibujo.
//
//  Por qué SVG y no una foto: el SVG está hecho de figuras (círculos,
//  rectángulos, caminos) escritas como texto, así que JavaScript puede
//  cambiarles el color y la posición al instante. Con fotos habría que
//  tener una foto por cada combinación posible.
//
//  Orden del dibujo (de abajo hacia arriba), igual que un vaso real:
//    1. Fresas picadas en el fondo
//    2. Crema, con la salsa chorreando por las paredes del vaso
//    3. El copete de crema que sobresale del vaso
//    4. La salsa por encima del copete
//    5. Los toppings, encima de todo
//    6. La tapa de domo transparente
//
//  Cada topping tiene una FORMA (columna "forma" de la tabla toppings):
//    barquillo · galleta · gomita · masmelo · queso · chispas
//  y un COLOR (columna "color"). Si el administrador crea un topping
//  nuevo, escoge su forma en el panel y el dibujo ya sabe hacerlo.
// =====================================================================

// ---------------------------------------------------------------------
// Números "al azar" que siempre salen iguales para la misma semilla.
// Si usáramos Math.random(), los toppings saltarían de lugar cada vez
// que el cliente marca algo. Con esto, el queso siempre cae igual.
// ---------------------------------------------------------------------
function azarConSemilla(semilla) {
    let x = semilla * 9301 + 49297;
    return function () {
        x = (x * 9301 + 49297) % 233280;
        return x / 233280; // número entre 0 y 1
    };
}

// Oscurece un color #RRGGBB (factor 0.2 = 20 % más oscuro).
function oscurecer(hex, factor) {
    const n = parseInt(hex.slice(1), 16);
    const r = Math.round(((n >> 16) & 255) * (1 - factor));
    const g = Math.round(((n >> 8) & 255) * (1 - factor));
    const b = Math.round((n & 255) * (1 - factor));
    return "#" + [r, g, b].map(v => v.toString(16).padStart(2, "0")).join("");
}

// Aclara un color #RRGGBB (factor 0.5 = mitad de camino hacia el blanco).
function aclarar(hex, factor) {
    const n = parseInt(hex.slice(1), 16);
    const c = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map(v => Math.round(v + (255 - v) * factor));
    return "#" + c.map(v => v.toString(16).padStart(2, "0")).join("");
}

// Los colores solo pueden venir como #RRGGBB (así los guarda el panel).
// Si llegara otra cosa, se usa rosado: nunca se mete texto raro al SVG.
function colorSeguro(color) {
    return /^#[0-9A-Fa-f]{6}$/.test(color) ? color : "#E54895";
}

// ---------------------------------------------------------------------
// Lugares donde caen los toppings, sobre el copete de crema.
// Si hay 2 toppings, se reparten intercalados: 1, 2, 1, 2...
// ---------------------------------------------------------------------
const LUGARES = [
    [130, 78], [96, 90], [164, 88], [72, 104], [188, 104], [118, 100],
    [146, 102], [108, 76], [152, 74], [84, 112], [176, 114], [130, 112],
    [60, 116], [200, 118],
];

// =====================================================================
//  CÓMO SE DIBUJA CADA FORMA DE TOPPING
//  Cada función recibe los lugares que le tocan, el color y el azar,
//  y devuelve el SVG (texto) de sus piezas.
// =====================================================================
const FORMAS = {

    // Barquillos: palitos de galleta enrollada, clavados en la crema.
    barquillo(lugares, color, azar) {
        const borde = oscurecer(color, 0.3);
        return lugares.slice(0, 3).map(([x, y]) => {
            const giro = -30 + azar() * 60;
            let rayas = "";
            for (let i = -42; i < 2; i += 7) {
                rayas += `<line x1="-5" y1="${i}" x2="5" y2="${i + 6}" stroke="${borde}" stroke-width="1.8"/>`;
            }
            return `<g transform="translate(${x} ${y + 4}) rotate(${giro.toFixed(1)})">
                <rect x="-6" y="-46" width="12" height="50" rx="6" fill="${color}" stroke="${borde}" stroke-width="1.2"/>
                ${rayas}
                <ellipse cx="0" cy="-46" rx="6" ry="3" fill="${aclarar(color, 0.35)}" stroke="${borde}" stroke-width="1"/>
                <ellipse cx="0" cy="-46" rx="2.5" ry="1.2" fill="${borde}"/>
            </g>`;
        }).join("");
    },

    // Galletas: trozos irregulares y moronas.
    galleta(lugares, color, azar) {
        const oscuro = oscurecer(color, 0.35);
        return lugares.map(([x, y]) => {
            // Un trozo con 6 esquinas a distancias distintas del centro.
            let puntos = [];
            for (let i = 0; i < 6; i++) {
                const ang = (Math.PI * 2 * i) / 6 + azar() * 0.5;
                const r = 6 + azar() * 5;
                puntos.push((x + Math.cos(ang) * r).toFixed(1) + "," + (y + Math.sin(ang) * r).toFixed(1));
            }
            const moronas = [0, 1, 2].map(() =>
                `<circle cx="${(x - 10 + azar() * 20).toFixed(1)}" cy="${(y + 4 + azar() * 8).toFixed(1)}" r="${(1.2 + azar() * 1.5).toFixed(1)}" fill="${oscuro}"/>`
            ).join("");
            return `<polygon points="${puntos.join(" ")}" fill="${color}" stroke="${oscuro}" stroke-width="1.2"/>
                    <circle cx="${x - 2}" cy="${y - 1}" r="1.5" fill="${oscuro}"/>
                    <circle cx="${x + 3}" cy="${y + 2}" r="1.3" fill="${oscuro}"/>
                    ${moronas}`;
        }).join("");
    },

    // Gomitas: gusanitos de dos colores con azúcar.
    gomita(lugares, color, azar) {
        const otro = "#FFE45C"; // la segunda franja del gusanito
        return lugares.map(([x, y]) => {
            const giro = azar() * 360;
            const camino = "M -17 5 C -10 -10, 0 14, 7 0 S 16 -8, 19 3";
            let azucar = "";
            for (let i = 0; i < 5; i++) {
                azucar += `<circle cx="${(-12 + azar() * 26).toFixed(1)}" cy="${(-4 + azar() * 8).toFixed(1)}" r="0.9" fill="#fff"/>`;
            }
            return `<g transform="translate(${x} ${y}) rotate(${giro.toFixed(0)})">
                <path d="${camino}" fill="none" stroke="${oscurecer(color, 0.25)}" stroke-width="11" stroke-linecap="round"/>
                <path d="${camino}" fill="none" stroke="${color}" stroke-width="9" stroke-linecap="round"/>
                <path d="${camino}" fill="none" stroke="${otro}" stroke-width="9" stroke-linecap="butt" stroke-dasharray="8 8"/>
                ${azucar}
            </g>`;
        }).join("");
    },

    // Masmelos: cubitos blandos, unos de su color y otros más claros.
    masmelo(lugares, color, azar) {
        return lugares.map(([x, y], i) => {
            const giro = -25 + azar() * 50;
            const relleno = i % 2 === 0 ? color : aclarar(color, 0.6);
            return `<g transform="translate(${x} ${y}) rotate(${giro.toFixed(1)})">
                <rect x="-8" y="-8" width="16" height="16" rx="5" fill="${relleno}" stroke="${oscurecer(color, 0.15)}" stroke-width="1"/>
                <rect x="-5" y="-6" width="6" height="3" rx="1.5" fill="#fff" opacity="0.6"/>
            </g>`;
        }).join("");
    },

    // Queso: rallado en tiritas delgadas regadas por encima.
    queso(lugares, color, azar) {
        const borde = oscurecer(color, 0.2);
        return lugares.map(([x, y]) => {
            let tiras = "";
            for (let i = 0; i < 5; i++) {
                const cx = x - 11 + azar() * 22;
                const cy = y - 6 + azar() * 12;
                const ang = azar() * Math.PI;
                const largo = 4 + azar() * 4;
                tiras += `<line x1="${(cx - Math.cos(ang) * largo).toFixed(1)}" y1="${(cy - Math.sin(ang) * largo).toFixed(1)}"
                                x2="${(cx + Math.cos(ang) * largo).toFixed(1)}" y2="${(cy + Math.sin(ang) * largo).toFixed(1)}"
                                stroke="${color}" stroke-width="3" stroke-linecap="round"/>
                          <line x1="${(cx - Math.cos(ang) * largo).toFixed(1)}" y1="${(cy - Math.sin(ang) * largo + 1).toFixed(1)}"
                                x2="${(cx + Math.cos(ang) * largo).toFixed(1)}" y2="${(cy + Math.sin(ang) * largo + 1).toFixed(1)}"
                                stroke="${borde}" stroke-width="0.8" stroke-linecap="round" opacity="0.6"/>`;
            }
            return tiras;
        }).join("");
    },

    // Chispas: para cualquier topping que no tenga forma propia.
    chispas(lugares, color, azar) {
        return lugares.map(([x, y]) => {
            let bolitas = "";
            for (let i = 0; i < 6; i++) {
                bolitas += `<circle cx="${(x - 10 + azar() * 20).toFixed(1)}" cy="${(y - 6 + azar() * 12).toFixed(1)}" r="${(2 + azar() * 1.5).toFixed(1)}" fill="${color}" stroke="${oscurecer(color, 0.25)}" stroke-width="0.6"/>`;
            }
            return bolitas;
        }).join("");
    },
};

// =====================================================================
//  EL VASO COMPLETO
//  toppings: lista de objetos {id, nombre, color, forma} de la base de datos
//  salsa:    objeto {id, nombre, color} o null si no ha escogido
//  nuevos:   ids de los toppings recién marcados (esos caen con animación)
//  salsaNueva: true si la salsa se acaba de escoger
// =====================================================================
function dibujarVaso(toppings, salsa, nuevos = [], salsaNueva = false) {

    // Forma del vaso: ancho arriba, angosto abajo.
    const formaVaso = "M35 124 L225 124 L197 344 L63 344 Z";

    // 1. Fresas picadas en el fondo (siempre en el mismo lugar: semilla fija).
    const azarFresas = azarConSemilla(7);
    let fresas = "";
    for (let fila = 0; fila < 4; fila++) {
        for (let col = 0; col < 8; col++) {
            const x = 56 + col * 20 + azarFresas() * 10 + (fila % 2) * 8;
            const y = 284 + fila * 17 + azarFresas() * 8;
            const rx = 9 + azarFresas() * 5;
            const giro = (azarFresas() * 90 - 45).toFixed(0);
            const rojo = (fila + col) % 3 === 0 ? "#C81D3E" : "#E8344E";
            fresas += `<g transform="translate(${x.toFixed(1)} ${y.toFixed(1)}) rotate(${giro})">
                <ellipse rx="${rx.toFixed(1)}" ry="${(rx * 0.72).toFixed(1)}" fill="${rojo}"/>
                <ellipse rx="${(rx * 0.45).toFixed(1)}" ry="${(rx * 0.25).toFixed(1)}" fill="#F7909E" opacity="0.8"/>
                <circle cx="-4" cy="-3" r="0.9" fill="#FFE08A"/>
                <circle cx="4" cy="2" r="0.9" fill="#FFE08A"/>
            </g>`;
        }
    }

    // 2. La salsa chorreando por dentro de las paredes del vaso.
    let salsaAdentro = "";
    let salsaEncima = "";
    if (salsa) {
        const c = colorSeguro(salsa.color);
        const clase = salsaNueva ? ' class="salsa-nueva"' : "";
        salsaAdentro = `<g${clase} fill="none" stroke="${c}" stroke-linecap="round" stroke-linejoin="round">
            <g opacity="0.85">
                <path d="M44 128 C74 160, 38 196, 72 232 S 56 296, 86 340" stroke-width="16"/>
                <path d="M216 128 C186 166, 224 204, 188 244 S 206 300, 176 340" stroke-width="16"/>
                <path d="M96 126 C80 160, 122 186, 94 222 S 110 268, 100 300" stroke-width="10"/>
                <path d="M164 126 C182 158, 142 192, 168 228 S 150 270, 162 300" stroke-width="10"/>
            </g>
            <path d="M44 128 C74 160, 38 196, 72 232 S 56 296, 86 340" stroke="${aclarar(c, 0.35)}" stroke-width="4" opacity="0.6"/>
            <path d="M216 128 C186 166, 224 204, 188 244 S 206 300, 176 340" stroke="${aclarar(c, 0.35)}" stroke-width="4" opacity="0.6"/>
        </g>`;
        // 4. La salsa por encima del copete: en zigzag y goteando por el borde.
        salsaEncima = `<g${clase} fill="none" stroke="${c}" stroke-linecap="round" stroke-linejoin="round">
            <path d="M48 118 C60 98, 78 92, 92 106 S 114 80, 130 92 S 156 72, 170 96 S 198 88, 212 112" stroke-width="8"/>
            <path d="M66 110 C88 124, 108 100, 128 112 S 168 96, 194 116" stroke-width="6"/>
            <path d="M104 88 C118 78, 140 76, 158 86" stroke-width="5"/>
            <path d="M54 118 L54 136" stroke-width="7"/>
            <path d="M206 114 L206 142" stroke-width="7"/>
            <path d="M150 114 L150 130" stroke-width="5"/>
        </g>
        <g${clase} fill="${c}">
            <circle cx="54" cy="138" r="4.5"/>
            <circle cx="206" cy="144" r="4.5"/>
            <circle cx="150" cy="132" r="3.5"/>
        </g>`;
    }

    // 5. Los toppings: cada uno toma sus lugares intercalados.
    // Lo menudo (queso, chispas) va debajo; lo grande (gomitas, barquillos)
    // encima, para que nada quede tapado.
    const orden = ["queso", "chispas", "galleta", "masmelo", "gomita", "barquillo"];
    const capaDe = t => orden.indexOf(t.forma) === -1 ? 1 : orden.indexOf(t.forma);
    let piezas = "";
    const conLugar = toppings.map((t, i) => ({ t, i })).sort((a, b) => capaDe(a.t) - capaDe(b.t));
    conLugar.forEach(({ t, i }) => {
        const dibujar = FORMAS[t.forma] || FORMAS.chispas;
        const lugares = LUGARES.filter((_, k) => k % toppings.length === i);
        const azar = azarConSemilla(t.id * 31 + 3);
        const clase = nuevos.includes(t.id) ? ' class="pieza-nueva"' : "";
        piezas += `<g${clase}>${dibujar(lugares, colorSeguro(t.color), azar)}</g>`;
    });

    return `<svg viewBox="0 0 260 360" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Vista del vaso armado">
        <defs>
            <clipPath id="dentro-del-vaso"><path d="${formaVaso}"/></clipPath>
        </defs>

        <!-- Todo lo de adentro queda recortado con la forma del vaso -->
        <g clip-path="url(#dentro-del-vaso)">
            <rect x="0" y="120" width="260" height="240" fill="#FFF6EC"/>
            <path d="M40 180 C90 170, 170 190, 222 176 M44 230 C100 222, 160 238, 216 226"
                  stroke="#F2E2D0" stroke-width="3" fill="none"/>
            ${salsaAdentro}
            ${fresas}
        </g>

        <!-- Etiqueta del negocio, como en los vasos de verdad -->
        <circle cx="130" cy="210" r="30" fill="#E54895" stroke="#fff" stroke-width="3"/>
        <circle cx="130" cy="210" r="23" fill="none" stroke="#fff" stroke-width="1" stroke-dasharray="3 3"/>
        <text x="130" y="217" text-anchor="middle" font-family="'Dancing Script', cursive" font-size="21" font-weight="700" fill="#fff">MWH</text>

        <!-- Brillo y borde del vaso plástico -->
        <path d="M52 132 L64 132 L80 330 L72 330 Z" fill="#fff" opacity="0.35"/>
        <path d="${formaVaso}" fill="none" stroke="#D9C7D1" stroke-width="3" stroke-linejoin="round"/>
        <rect x="30" y="119" width="200" height="8" rx="4" fill="#fff" stroke="#D9C7D1" stroke-width="2"/>

        <!-- 3. Copete de crema -->
        <path d="M36 126 C30 102 52 90 66 96 C68 72 98 62 114 74 C122 52 162 52 170 72
                 C190 64 214 82 206 98 C222 98 232 114 224 126 Z"
              fill="#FFF3E6" stroke="#E6CFB5" stroke-width="2"/>
        <path d="M70 118 C84 104, 104 106, 116 114 M140 106 C156 96, 178 100, 192 114 M100 90 C116 80, 140 80, 156 88"
              fill="none" stroke="#EEDCC6" stroke-width="3" stroke-linecap="round"/>

        ${salsaEncima}
        ${piezas}

        <!-- 6. Tapa de domo transparente -->
        <path d="M26 122 C26 6, 234 6, 234 122" fill="#fff" fill-opacity="0.12" stroke="#D9C7D1" stroke-width="2.5"/>
        <path d="M52 64 C70 40, 104 30, 130 30" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity="0.7"/>
    </svg>`;
}
