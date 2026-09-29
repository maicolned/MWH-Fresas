<?php
// =====================================================================
//  productos.php — el menú del negocio (antes era productos.html).
//  Todo sale de la base de datos: productos, toppings y salsas,
//  y muestra cuáles están agotados hoy.
// =====================================================================
require "includes/funciones.php";

$productos = consultar(
    "SELECT id, nombre, descripcion, toppings_incluidos, precio, imagen, disponible
       FROM productos WHERE activo = 1 ORDER BY precio"
);
$toppings = consultar("SELECT nombre, color, disponible FROM toppings WHERE activo = 1 ORDER BY nombre");
$salsas   = consultar("SELECT nombre, color, disponible FROM salsas WHERE activo = 1 ORDER BY nombre");

$titulo = "Productos";
$activa = "productos";
require "includes/encabezado.php";
?>

    <!-- PRODUCTOS -->
    <section class="productos" id="productos">
        <h2>Nuestros</h2>
        <h3>Productos</h3>

        <div class="productos-container">
            <?php foreach ($productos as $p): ?>
                <div class="producto">
                    <img src="<?= limpiar($p["imagen"]) ?>" alt="<?= limpiar($p["nombre"]) ?>">
                    <h4><?= limpiar($p["nombre"]) ?></h4>
                    <p class="descripcion"><?= limpiar($p["descripcion"]) ?></p>
                    <p><?= pesos($p["precio"]) ?></p>
                    <?php if ($p["disponible"]): ?>
                        <a class="boton" href="personaliza.php?producto=<?= $p["id"] ?>">Personalizar y pedir</a>
                    <?php else: ?>
                        <span class="etiqueta-agotado">Agotado por hoy</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$productos): ?>
                <p>Por ahora no hay productos en el menú.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- LO QUE PUEDES PONERLE -->
    <section class="ingredientes">
        <div>
            <h3>Toppings</h3>
            <ul>
                <?php foreach ($toppings as $t): ?>
                    <li class="<?= $t["disponible"] ? "" : "agotado" ?>">
                        <span class="punto" style="background: <?= limpiar($t["color"]) ?>"></span>
                        <?= limpiar($t["nombre"]) ?>
                        <?= $t["disponible"] ? "" : "<small>(agotado hoy)</small>" ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <h3>Salsas</h3>
            <ul>
                <?php foreach ($salsas as $s): ?>
                    <li class="<?= $s["disponible"] ? "" : "agotado" ?>">
                        <span class="punto" style="background: <?= limpiar($s["color"]) ?>"></span>
                        <?= limpiar($s["nombre"]) ?>
                        <?= $s["disponible"] ? "" : "<small>(agotada hoy)</small>" ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

<?php require "includes/pie.php"; ?>
