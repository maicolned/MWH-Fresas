<?php
// =====================================================================
//  admin/index.php — resumen del día con contadores.
// =====================================================================
require "clientes_funciones.php";
exigir_sesion();

// Contadores. Los pedidos cancelados no cuentan como venta.
$hoy = consultar_uno(
    "SELECT COUNT(*)                   AS pedidos,
            COALESCE(SUM(total), 0)    AS ventas
       FROM pedidos
      WHERE DATE(fecha) = CURDATE() AND estado <> 'cancelado'"
);
$vasos_hoy = consultar_uno(
    "SELECT COUNT(*) AS vasos
       FROM detalle_pedidos d
       JOIN pedidos p ON p.id = d.pedido_id
      WHERE DATE(p.fecha) = CURDATE() AND p.estado <> 'cancelado'"
)["vasos"];
$por_atender = consultar_uno(
    "SELECT COUNT(*) AS n FROM pedidos WHERE estado IN ('pendiente', 'preparando')"
)["n"];
// Clientes que pidieron por primera vez hoy, y los que hay que vigilar.
$clientes_nuevos = consultar_uno(
    "SELECT COUNT(*) AS n FROM clientes WHERE DATE(primer_pedido) = CURDATE()"
)["n"];
$agotados = consultar_uno(
    "SELECT (SELECT COUNT(*) FROM toppings WHERE activo = 1 AND disponible = 0)
          + (SELECT COUNT(*) FROM salsas   WHERE activo = 1 AND disponible = 0) AS n"
)["n"];

// Los pedidos que hay que atender, del más viejo al más nuevo.
// JOIN + COUNT: cuántos vasos tiene cada pedido.
$cola = consultar(
    "SELECT p.id, p.cliente, p.entrega, p.total, p.estado, p.fecha,
            COUNT(d.id) AS vasos,
            MAX(c.estado) AS estado_cliente, MAX(h.entregados) AS entregados,
            MAX(h.cancelados) AS cancelados, MAX(h.total_pedidos) AS total_pedidos
       FROM pedidos p
       JOIN detalle_pedidos d ON d.pedido_id = p.id
       " . SQL_HISTORIAL . "
      WHERE p.estado IN ('pendiente', 'preparando')
      GROUP BY p.id
      ORDER BY p.fecha"
);

$titulo = "Resumen";
$activa = "resumen";
require "encabezado.php";
?>

<h1>Resumen de hoy</h1>

<div class="contadores">
    <div class="contador"><strong><?= $hoy["pedidos"] ?></strong><span>pedidos hoy</span></div>
    <div class="contador"><strong><?= $vasos_hoy ?></strong><span>vasos vendidos hoy</span></div>
    <div class="contador"><strong><?= pesos($hoy["ventas"]) ?></strong><span>vendido hoy</span></div>
    <div class="contador"><strong><?= $por_atender ?></strong><span>pedidos por atender</span></div>
    <div class="contador"><strong><?= $clientes_nuevos ?></strong><span>clientes nuevos hoy</span></div>
    <div class="contador"><strong><?= $agotados ?></strong><span>toppings y salsas agotados</span></div>
</div>

<h2>Por atender</h2>
<div class="caja tabla-scroll">
    <?php if (!$cola): ?>
        <p>No hay pedidos pendientes. 🍓</p>
    <?php else: ?>
        <table>
            <tr><th>Pedido</th><th>Cliente</th><th>Tipo</th><th>Hora</th><th>Entrega</th><th class="numero">Vasos</th><th class="numero">Total</th><th>Estado</th><th></th></tr>
            <?php foreach ($cola as $p): ?>
                <tr>
                    <td><?= codigo_pedido($p["id"]) ?></td>
                    <td><?= limpiar($p["cliente"]) ?></td>
                    <td><?= mostrar_etiqueta($p) ?></td>
                    <td><?= date("d/m h:i a", strtotime($p["fecha"])) ?></td>
                    <td><?= $p["entrega"] === "domicilio" ? "Domicilio" : "Recoge" ?></td>
                    <td class="numero"><?= $p["vasos"] ?></td>
                    <td class="numero"><?= pesos($p["total"]) ?></td>
                    <td><span class="estado estado-<?= $p["estado"] ?>"><?= $p["estado"] ?></span></td>
                    <td><a class="boton boton-chico" href="pedido.php?id=<?= $p["id"] ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require "pie.php"; ?>
