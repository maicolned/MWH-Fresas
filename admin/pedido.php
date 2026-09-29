<?php
// =====================================================================
//  admin/pedido.php — detalle de un pedido y cambio de estado.
//  Muestra la "orden de preparación": qué lleva cada vaso.
// =====================================================================
require "seguridad.php";
exigir_sesion();

$id = (int) ($_GET["id"] ?? 0);
$estados = ["pendiente", "preparando", "entregado", "cancelado"];

// Cambiar el estado (lo pueden hacer el admin y el vendedor).
if (es_post()) {
    $nuevo = $_POST["estado"] ?? "";
    if (in_array($nuevo, $estados, true)) {
        ejecutar("UPDATE pedidos SET estado = ? WHERE id = ?", "si", [$nuevo, $id]);
        avisar("El pedido " . codigo_pedido($id) . " quedó en estado: $nuevo.");
    } else {
        avisar("Ese estado no existe.", "error");
    }
    header("Location: pedido.php?id=$id");
    exit;
}

$pedido = consultar_uno("SELECT * FROM pedidos WHERE id = ?", "i", [$id]);
if (!$pedido) {
    http_response_code(404);
    avisar("Ese pedido no existe.", "error");
    header("Location: pedidos.php");
    exit;
}

// Consulta con varios JOIN: cada vaso con su producto, su salsa y sus toppings.
// GROUP_CONCAT junta los nombres de los toppings en un solo texto.
$vasos = consultar(
    "SELECT d.id, pr.nombre AS producto, s.nombre AS salsa, d.precio,
            GROUP_CONCAT(t.nombre ORDER BY t.nombre SEPARATOR ', ') AS toppings
       FROM detalle_pedidos d
       JOIN productos pr        ON pr.id = d.producto_id
       JOIN salsas s            ON s.id  = d.salsa_id
       JOIN detalle_toppings dt ON dt.detalle_id = d.id
       JOIN toppings t          ON t.id  = dt.topping_id
      WHERE d.pedido_id = ?
      GROUP BY d.id
      ORDER BY d.id",
    "i", [$id]
);

$titulo = "Pedido " . codigo_pedido($id);
$activa = "pedidos";
require "encabezado.php";
?>

<h1>Pedido <?= codigo_pedido($id) ?></h1>

<div class="caja">
    <p><strong>Cliente:</strong> <?= limpiar($pedido["cliente"]) ?></p>
    <p><strong>Celular:</strong>
        <a href="https://wa.me/57<?= limpiar($pedido["telefono"]) ?>" target="_blank" rel="noopener"><?= limpiar($pedido["telefono"]) ?></a>
    </p>
    <p><strong>Entrega:</strong>
        <?= $pedido["entrega"] === "domicilio" ? "Domicilio — " . limpiar($pedido["direccion"]) : "Pasa a recogerlo" ?>
    </p>
    <?php if ($pedido["notas"]): ?>
        <p><strong>Notas:</strong> <?= limpiar($pedido["notas"]) ?></p>
    <?php endif; ?>
    <p><strong>Fecha:</strong> <?= date("d/m/Y h:i a", strtotime($pedido["fecha"])) ?></p>
    <p><strong>Estado:</strong> <span class="estado estado-<?= $pedido["estado"] ?>"><?= $pedido["estado"] ?></span></p>
</div>

<h2>Orden de preparación</h2>
<div class="caja tabla-scroll">
    <table>
        <tr><th>#</th><th>Vaso</th><th>Toppings</th><th>Salsa</th><th class="numero">Precio</th></tr>
        <?php foreach ($vasos as $n => $v): ?>
            <tr>
                <td><?= $n + 1 ?></td>
                <td><?= limpiar($v["producto"]) ?></td>
                <td><?= limpiar($v["toppings"]) ?></td>
                <td><?= limpiar($v["salsa"]) ?></td>
                <td class="numero"><?= pesos($v["precio"]) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><th colspan="4">Total</th><th class="numero"><?= pesos($pedido["total"]) ?></th></tr>
    </table>
</div>

<h2>Cambiar estado</h2>
<form class="caja filtros" method="post">
    <select name="estado">
        <?php foreach ($estados as $e): ?>
            <option value="<?= $e ?>" <?= $e === $pedido["estado"] ? "selected" : "" ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Guardar estado</button>
    <a class="boton boton-gris" href="pedidos.php">Volver a pedidos</a>
</form>

<?php require "pie.php"; ?>
