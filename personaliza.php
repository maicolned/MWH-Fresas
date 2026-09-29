<?php
// =====================================================================
//  personaliza.php — "Personaliza tu antojo" (antes personaliza.html).
//
//  Lo que cambió respecto a su versión:
//   - Los toppings, salsas y productos salen de la base de datos.
//   - Se ve el vaso llenándose con los colores de lo que se escoge.
//   - Se pueden pedir varios vasos en el mismo pedido.
//   - El navegador NO manda el precio: el servidor lo calcula
//     (ver guardar_pedido.php). Por eso nadie puede cambiarlo.
// =====================================================================
require "includes/funciones.php";

$productos = consultar(
    "SELECT id, nombre, toppings_incluidos, precio
       FROM productos WHERE activo = 1 AND disponible = 1 ORDER BY precio"
);
$toppings = consultar("SELECT id, nombre, color, forma, disponible FROM toppings WHERE activo = 1 ORDER BY nombre");
$salsas   = consultar("SELECT id, nombre, color, disponible FROM salsas WHERE activo = 1 ORDER BY nombre");

// Si viene de la página de productos (personaliza.php?producto=2), se deja escogido.
$escogido = (int) ($_GET["producto"] ?? 0);
if (!in_array($escogido, array_column($productos, "id"))) {
    $escogido = $productos[0]["id"] ?? 0;
}

// Estos datos se le pasan al JavaScript SOLO para dibujar el vaso y mostrar
// el precio en pantalla. El precio que se cobra lo decide el servidor.
$datos = [
    "productos" => $productos,
    "toppings"  => $toppings,
    "salsas"    => $salsas,
];

$titulo  = "Personaliza";
$activa  = "personaliza";
$scripts = ["assets/js/vaso.js", "assets/js/script.js"];
require "includes/encabezado.php";
?>

    <!-- PERSONALIZAR -->
    <section class="personaliza" id="personaliza">
        <h2>Personaliza tu antojo</h2>

        <?php if (!$productos): ?>
            <p class="aviso aviso-error">Hoy no tenemos vasos disponibles. ¡Vuelve pronto!</p>
        <?php else: ?>

        <div class="armador">

            <!-- Columna izquierda: lo que escoge el cliente -->
            <div class="opciones">

                <div class="paso">
                    <h3>1. Escoge tu vaso</h3>
                    <?php foreach ($productos as $p): ?>
                        <label>
                            <input type="radio" name="producto" value="<?= $p["id"] ?>" <?= $p["id"] == $escogido ? "checked" : "" ?>>
                            <?= limpiar($p["nombre"]) ?> — <strong><?= pesos($p["precio"]) ?></strong>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="paso">
                    <h3>2. Escoge tus toppings <small id="cuantos-toppings"></small></h3>
                    <?php foreach ($toppings as $t): ?>
                        <label class="<?= $t["disponible"] ? "" : "agotado" ?>">
                            <input type="checkbox" name="topping" value="<?= $t["id"] ?>" <?= $t["disponible"] ? "" : "disabled" ?>>
                            <span class="punto" style="background: <?= limpiar($t["color"]) ?>"></span>
                            <?= limpiar($t["nombre"]) ?>
                            <?= $t["disponible"] ? "" : "<small>(agotado hoy)</small>" ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="paso">
                    <h3>3. Escoge tu salsa</h3>
                    <?php foreach ($salsas as $s): ?>
                        <label class="<?= $s["disponible"] ? "" : "agotado" ?>">
                            <input type="radio" name="salsa" value="<?= $s["id"] ?>" <?= $s["disponible"] ? "" : "disabled" ?>>
                            <span class="punto" style="background: <?= limpiar($s["color"]) ?>"></span>
                            <?= limpiar($s["nombre"]) ?>
                            <?= $s["disponible"] ? "" : "<small>(agotada hoy)</small>" ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Columna derecha: el vaso que se va armando -->
            <div class="vista-vaso">
                <!-- Aquí vaso.js dibuja el vaso en SVG -->
                <div class="vaso-svg" id="vaso"></div>
                <p class="precio-vaso" id="precio-vaso"></p>
                <p class="resumen-vaso" id="resumen-vaso"></p>
                <button type="button" id="agregar">Agregar este vaso al pedido</button>
                <p class="mensaje-error" id="error-vaso" role="alert"></p>
            </div>
        </div>

        <!-- EL PEDIDO -->
        <div class="pedido" id="pedido">
            <h3>Tu pedido</h3>
            <ul id="lista-vasos"><li class="vacio">Todavía no has agregado vasos.</li></ul>
            <p class="total">Total: <strong id="total">$0</strong></p>

            <form id="form-pedido" novalidate>
                <div class="campo">
                    <label for="cliente">Tu nombre</label>
                    <input type="text" id="cliente" name="cliente" maxlength="60" autocomplete="name">
                </div>
                <div class="campo">
                    <label for="telefono">Tu celular</label>
                    <input type="tel" id="telefono" name="telefono" inputmode="numeric" placeholder="3001234567" autocomplete="tel">
                </div>
                <div class="campo">
                    <span class="etiqueta">¿Cómo lo recibes?</span>
                    <label class="en-linea"><input type="radio" name="entrega" value="recoger" checked> Paso a recogerlo</label>
                    <label class="en-linea"><input type="radio" name="entrega" value="domicilio"> A domicilio</label>
                </div>
                <div class="campo" id="campo-direccion" hidden>
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" maxlength="150" autocomplete="street-address">
                </div>
                <div class="campo">
                    <label for="notas">Notas (opcional)</label>
                    <input type="text" id="notas" name="notas" maxlength="200" placeholder="Ej: sin mucha crema">
                </div>

                <p class="mensaje-error" id="error-pedido" role="alert"></p>
                <button type="submit" class="confirmar" id="confirmar">Confirmar pedido</button>
            </form>
        </div>

        <!-- Aparece cuando el pedido quedó guardado -->
        <div class="pedido pedido-listo" id="pedido-listo" hidden>
            <h3>¡Pedido recibido! 💕</h3>
            <p>Tu número de pedido es <strong id="codigo"></strong> por <strong id="total-final"></strong>.</p>
            <p>Para terminar, envíanos el pedido por WhatsApp:</p>
            <a class="boton boton-whatsapp" id="boton-whatsapp" href="#" target="_blank" rel="noopener">Enviar por WhatsApp</a>
            <p><a href="personaliza.php">Hacer otro pedido</a></p>
        </div>

        <?php endif; ?>
    </section>

    <!-- Datos para el JavaScript (salen de la base de datos) -->
    <script>
        const DATOS = <?= json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    </script>

<?php require "includes/pie.php"; ?>
