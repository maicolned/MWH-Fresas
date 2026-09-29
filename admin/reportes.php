<?php
// =====================================================================
//  admin/reportes.php — reportes del negocio (solo admin).
//  Todos ignoran los pedidos cancelados.
// =====================================================================
require "seguridad.php";
exigir_admin();

// Rango de días del reporte de ventas: 7 por defecto, se puede cambiar.
$dias = (int) ($_GET["dias"] ?? 7);
if (!in_array($dias, [7, 15, 30], true)) {
    $dias = 7;
}

// 1. Ventas por día.
$ventas = consultar(
    "SELECT DATE(p.fecha) AS dia,
            COUNT(DISTINCT p.id) AS pedidos,
            COUNT(d.id)          AS vasos,
            SUM(d.precio)        AS total
       FROM pedidos p
       JOIN detalle_pedidos d ON d.pedido_id = p.id
      WHERE p.estado <> 'cancelado'
        AND p.fecha >= CURDATE() - INTERVAL ? DAY
      GROUP BY DATE(p.fecha)
      ORDER BY dia DESC",
    "i", [$dias - 1]
);

// 2. Toppings más pedidos (JOIN de 4 tablas).
$top_toppings = consultar(
    "SELECT t.nombre, t.color, COUNT(*) AS veces
       FROM detalle_toppings dt
       JOIN toppings t        ON t.id = dt.topping_id
       JOIN detalle_pedidos d ON d.id = dt.detalle_id
       JOIN pedidos p         ON p.id = d.pedido_id
      WHERE p.estado <> 'cancelado'
      GROUP BY t.id
      ORDER BY veces DESC"
);

// 3. Salsas más pedidas.
$top_salsas = consultar(
    "SELECT s.nombre, s.color, COUNT(*) AS veces
       FROM detalle_pedidos d
       JOIN salsas s  ON s.id = d.salsa_id
       JOIN pedidos p ON p.id = d.pedido_id
      WHERE p.estado <> 'cancelado'
      GROUP BY s.id
      ORDER BY veces DESC"
);

// 4. Ventas por producto.
$por_producto = consultar(
    "SELECT pr.nombre, COUNT(*) AS vasos, SUM(d.precio) AS total
       FROM detalle_pedidos d
       JOIN productos pr ON pr.id = d.producto_id
       JOIN pedidos p    ON p.id = d.pedido_id
      WHERE p.estado <> 'cancelado'
      GROUP BY pr.id
      ORDER BY total DESC"
);

// Para dibujar las barras: la más grande ocupa el 100%.
function porcentaje($valor, $filas, $columna)
{
    $maximo = max(array_column($filas, $columna) ?: [1]);
    return $maximo > 0 ? round($valor * 100 / $maximo) : 0;
}

$titulo = "Reportes";
$activa = "reportes";
require "encabezado.php";
?>

<h1>Reportes</h1>

<h2>Ventas por día</h2>
<form class="filtros" method="get">
    <select name="dias" onchange="this.form.submit()">
        <?php foreach ([7, 15, 30] as $d): ?>
            <option value="<?= $d ?>" <?= $d === $dias ? "selected" : "" ?>>Últimos <?= $d ?> días</option>
        <?php endforeach; ?>
    </select>
</form>
<div class="caja tabla-scroll">
    <table>
        <tr><th>Día</th><th class="numero">Pedidos</th><th class="numero">Vasos</th><th class="numero">Vendido</th><th style="width:35%"></th></tr>
        <?php foreach ($ventas as $v): ?>
            <tr>
                <td><?= date("d/m/Y", strtotime($v["dia"])) ?></td>
                <td class="numero"><?= $v["pedidos"] ?></td>
                <td class="numero"><?= $v["vasos"] ?></td>
                <td class="numero"><?= pesos($v["total"]) ?></td>
                <td><div class="barra" style="width: <?= porcentaje($v["total"], $ventas, "total") ?>%"></div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$ventas): ?>
            <tr><td colspan="5">No hay ventas en esos días.</td></tr>
        <?php else: ?>
            <tr>
                <th>Total</th>
                <th class="numero"><?= array_sum(array_column($ventas, "pedidos")) ?></th>
                <th class="numero"><?= array_sum(array_column($ventas, "vasos")) ?></th>
                <th class="numero"><?= pesos(array_sum(array_column($ventas, "total"))) ?></th>
                <th></th>
            </tr>
        <?php endif; ?>
    </table>
</div>

<h2>Toppings más pedidos</h2>
<div class="caja tabla-scroll">
    <table>
        <tr><th>Topping</th><th class="numero">Veces</th><th style="width:50%"></th></tr>
        <?php foreach ($top_toppings as $t): ?>
            <tr>
                <td><span class="punto" style="background: <?= limpiar($t["color"]) ?>"></span><?= limpiar($t["nombre"]) ?></td>
                <td class="numero"><?= $t["veces"] ?></td>
                <td><div class="barra" style="width: <?= porcentaje($t["veces"], $top_toppings, "veces") ?>%"></div></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<h2>Salsas más pedidas</h2>
<div class="caja tabla-scroll">
    <table>
        <tr><th>Salsa</th><th class="numero">Veces</th><th style="width:50%"></th></tr>
        <?php foreach ($top_salsas as $s): ?>
            <tr>
                <td><span class="punto" style="background: <?= limpiar($s["color"]) ?>"></span><?= limpiar($s["nombre"]) ?></td>
                <td class="numero"><?= $s["veces"] ?></td>
                <td><div class="barra" style="width: <?= porcentaje($s["veces"], $top_salsas, "veces") ?>%"></div></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<h2>Ventas por producto</h2>
<div class="caja tabla-scroll">
    <table>
        <tr><th>Producto</th><th class="numero">Vasos</th><th class="numero">Vendido</th></tr>
        <?php foreach ($por_producto as $p): ?>
            <tr>
                <td><?= limpiar($p["nombre"]) ?></td>
                <td class="numero"><?= $p["vasos"] ?></td>
                <td class="numero"><?= pesos($p["total"]) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require "pie.php"; ?>
