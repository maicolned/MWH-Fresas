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
require "includes/encabezado.php";
?>

    <!-- PORTADA -->
    <section class="hero" id="inicio">
        <img src="assets/img/img1.png" alt="Vaso de fresas con crema de MWH Fresas">

        <div class="hero-text">
            <h1>Fresas con<br>crema</h1>
            <p>"<?= limpiar(config("lema")) ?>"</p>
            <a class="boton" href="personaliza.php">Arma tu vaso</a>
        </div>
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

    <!-- GALERÍA: fotos reales de los vasos -->
    <section class="galeria">
        <h2>Así quedan 🍓</h2>
        <div class="galeria-fotos">
            <img src="assets/img/img3.png" alt="Vaso con salsa de arequipe">
            <img src="assets/img/img4.png" alt="Vaso con masmellos y salsa de mora">
            <img src="assets/img/img2.png" alt="Vaso con gomitas y salsa de mora">
        </div>
    </section>

<?php require "includes/pie.php"; ?>
