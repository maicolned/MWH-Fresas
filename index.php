<?php
// =====================================================================
//  index.php — página de inicio (antes era index.html).
// =====================================================================
require "includes/funciones.php";

// Los productos ya no están escritos a mano: salen de la base de datos.
// Si el administrador agrega uno en el panel, aparece aquí solo.
$productos = consultar(
    "SELECT id, nombre, descripcion, precio, imagen, disponible
       FROM productos
      WHERE activo = 1
      ORDER BY precio"
);

$titulo = "Inicio";
$activa = "inicio";
$scripts = ["assets/js/promos.js"];
require "includes/encabezado.php";
?>

    <!-- PORTADA
         El banner (hero.jpg) ya trae dibujados el título y el botón
         "Pídelo ahora", así que no se le pone texto encima ni se oscurece.
         - srcset: el celular descarga la versión de 1024 px (124 KB) y el
           computador la de 2048 px (365 KB). El PNG original pesaba 2,5 MB.
         - El botón dibujado no se puede tocar (es parte de la foto), por eso
           encima va un enlace transparente del mismo tamaño. -->
    <section class="hero-banner" id="inicio">
        <h1 class="solo-lectores">MWH Fresas — Fresas con crema en <?= limpiar(config("ciudad")) ?></h1>

        <img src="assets/img/hero.jpg"
             srcset="assets/img/hero-1024.jpg 1024w, assets/img/hero.jpg 2048w"
             sizes="100vw"
             width="2048" height="768"
             alt="Vasos de fresas con crema de MWH Fresas: postres que hacen tu día más dulce">

        <a class="hero-boton-dibujado" href="personaliza.php" aria-label="Pídelo ahora: arma tu vaso"></a>
    </section>

    <!-- En el celular el banner queda pequeño y su texto no se alcanza a leer:
         debajo se repite el mensaje en texto normal (en computador se oculta). -->
    <section class="hero-movil">
        <p>"<?= limpiar(config("lema")) ?>"</p>
        <a class="boton" href="personaliza.php">Arma tu vaso</a>
    </section>

    <!-- PRODUCTOS -->
    <section class="productos">
        <h2>Nuestros</h2>
        <h3>Productos</h3>

        <div class="productos-container">
            <?php foreach ($productos as $p): ?>
                <div class="producto">
                    <img src="<?= limpiar($p["imagen"]) ?>" alt="<?= limpiar($p["nombre"]) ?>">
                    <h4><?= limpiar($p["nombre"]) ?></h4>
                    <p><?= pesos($p["precio"]) ?></p>
                    <?php if ($p["disponible"]): ?>
                        <a class="boton" href="personaliza.php?producto=<?= $p["id"] ?>">Pedir</a>
                    <?php else: ?>
                        <span class="etiqueta-agotado">Agotado por hoy</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- PUBLICIDAD: las piezas promocionales del negocio en un carrusel.
         Avanza solo y, al llegar a la última, vuelve a la primera. Se
         desliza con el dedo en el celular y con las flechas en el
         computador (assets/js/promos.js). Cada pieza lleva a armar el vaso.
         loading="lazy": la foto se descarga solo cuando va a aparecer. -->
    <section class="promos">
        <h2>Antójate 🍓</h2>
        <p class="promos-sub">Lo más pedido de la semana</p>

        <div class="promos-marco">
            <button type="button" class="promos-flecha promos-anterior" aria-label="Ver la anterior">&#8249;</button>

            <div class="promos-carril" id="promos-carril" tabindex="0" aria-label="Promociones">
                <?php
                // Cada pieza: archivo y descripción (lo que dice la imagen,
                // para quien no la puede ver y para Google).
                $promos = [
                    ["promo1.jpg", "Capas que te enamoran: fresas, crema, caramelo y chocolate"],
                    ["promo2.jpg", "Hoy mereces algo delicioso: fresas, crema y un toque de diversión"],
                    ["promo3.jpg", "Capas de pura felicidad: un postre que enamora"],
                    ["promo4.jpg", "Fresas con crema: un postre irresistible con toppings únicos"],
                    ["promo5.jpg", "Un antojo que merece todo: fresas con crema y chocolate"],
                ];
                foreach ($promos as [$archivo, $texto]): ?>
                    <a class="promo" href="personaliza.php">
                        <img src="assets/img/<?= $archivo ?>" alt="<?= limpiar($texto) ?>"
                             width="800" height="800" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="promos-flecha promos-siguiente" aria-label="Ver la siguiente">&#8250;</button>
        </div>

        <!-- Los puntos los crea promos.js: uno por cada pieza -->
        <div class="promos-puntos" id="promos-puntos"></div>
    </section>

    <!-- UBICACIÓN: mapa de Google con la dirección del negocio.
         La dirección sale de la tabla configuracion (panel → Configuración):
         si el negocio se muda, se cambia ahí y el mapa se actualiza solo.
         Se usa el mapa "embebido" de Google, que no necesita clave (API key).
         loading="lazy": el mapa se carga solo cuando el cliente baja hasta él. -->
    <?php
    $direccion = config("direccion");
    $busqueda  = rawurlencode($direccion);
    ?>
    <section class="ubicacion" id="ubicacion">
        <h2>¿Dónde estamos? 📍</h2>

        <div class="ubicacion-contenido">
            <div class="ubicacion-datos">
                <h3>Visítanos</h3>
                <p><strong>Dirección:</strong><br><?= limpiar($direccion) ?></p>
                <p><strong>Horario:</strong><br><?= limpiar(config("horario")) ?></p>
                <p><strong>WhatsApp:</strong><br><?= limpiar(config("whatsapp")) ?></p>
                <a class="boton" href="https://www.google.com/maps/search/?api=1&amp;query=<?= $busqueda ?>"
                   target="_blank" rel="noopener">Cómo llegar</a>
            </div>

            <iframe class="ubicacion-mapa"
                    src="https://maps.google.com/maps?q=<?= $busqueda ?>&amp;z=16&amp;output=embed"
                    title="Mapa con la ubicación de <?= limpiar(config("nombre")) ?>"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
    </section>

<?php require "includes/pie.php"; ?>
