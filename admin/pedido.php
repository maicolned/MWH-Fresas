<?php
// =====================================================================
//  admin/pedido.php — detalle de un pedido y cambio de estado.
//  Muestra la "orden de preparación": qué lleva cada vaso, y quién es
//  el cliente: si ya nos había comprado o si es la primera vez.
// =====================================================================
require "clientes_funciones.php";
exigir_sesion();

$id = (int) ($_GET["id"] ?? 0);
$estados = ["pendiente", "preparando", "entregado", "cancelado"];

// Dos formularios llegan aquí (los pueden usar el admin y el vendedor):
//   accion = estado  -> cambiar el estado del pedido
//   accion = cliente -> verificar / bloquear al cliente y dejarle una nota
if (es_post()) {
    if (($_POST["accion"] ?? "") === "cliente") {
        $error = guardar_cliente($_POST["telefono"] ?? "", $_POST["estado_cliente"] ?? "", $_POST["notas_cliente"] ?? "");
        avisar($error ?: "Cliente actualizado.", $error ? "error" : "ok");
    } else {
        $nuevo = $_POST["estado"] ?? "";
        if (in_array($nuevo, $estados, true)) {
            ejecutar("UPDATE pedidos SET estado = ? WHERE id = ?", "si", [$nuevo, $id]);
            // Entregado = el cliente recibió y pagó: su número queda verificado.
            // (Si estaba bloqueado no se toca: eso lo decide el negocio.)
            if ($nuevo === "entregado") {
                ejecutar(
                    "UPDATE clientes c JOIN pedidos p ON p.telefono = c.telefono
                        SET c.estado = 'verificado', c.actualizado = NOW()
                      WHERE p.id = ? AND c.estado = 'nuevo'",
                    "i", [$id]
                );
            }
            avisar("El pedido " . codigo_pedido($id) . " quedó en estado: $nuevo.");
        } else {
            avisar("Ese estado no existe.", "error");
        }
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

// El historial del cliente y sus últimos pedidos.
$cliente = historial_cliente($pedido["telefono"]);
$otros = consultar(
    "SELECT id, total, estado, fecha FROM pedidos
      WHERE telefono = ? AND id <> ?
      ORDER BY fecha DESC LIMIT 5",
    "si", [$pedido["telefono"], $id]
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

<?php if ($cliente): [$clase, $texto, $explicacion] = etiqueta_cliente($cliente); ?>
    <h2>¿Quién pide?</h2>
    <div class="caja tarjeta-cliente tc-<?= $clase ?>">
        <p class="tc-titulo"><?= mostrar_etiqueta($cliente) ?> <?= limpiar($explicacion) ?></p>
        <p>
            <strong><?= $cliente["total"] ?></strong> pedido(s) en total ·
            <strong><?= $cliente["entregados"] ?></strong> entregado(s) ·
            <strong><?= $cliente["cancelados"] ?></strong> cancelado(s) ·
            primer pedido el <?= date("d/m/Y", strtotime($cliente["primer_pedido"])) ?>
        </p>
        <?php if (mb_strtolower(trim($pedido["cliente"])) !== mb_strtolower(trim($cliente["nombre"]))): ?>
            <!-- El mismo celular pidió con otro nombre: puede ser un familiar,
                 o alguien usando el número de otra persona. Vale la pena preguntar. -->
            <p class="aviso aviso-error">⚠ En este pedido escribió el nombre «<?= limpiar($pedido["cliente"]) ?>»,
               pero este celular está registrado como «<?= limpiar($cliente["nombre"]) ?>».</p>
        <?php endif; ?>
        <?php if ($cliente["notas"]): ?>
            <p><strong>Nota:</strong> <?= limpiar($cliente["notas"]) ?></p>
        <?php endif; ?>

        <?php if ($otros): ?>
            <p><strong>Sus otros pedidos:</strong>
                <?php foreach ($otros as $o): ?>
                    <a href="pedido.php?id=<?= $o["id"] ?>"><?= codigo_pedido($o["id"]) ?></a>
                    <span class="estado estado-<?= $o["estado"] ?>"><?= $o["estado"] ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>

        <form method="post" class="filtros form-cliente">
            <input type="hidden" name="accion" value="cliente">
            <input type="hidden" name="telefono" value="<?= limpiar($cliente["telefono"]) ?>">
            <select name="estado_cliente" aria-label="Estado del cliente">
                <option value="nuevo" <?= $cliente["estado"] === "nuevo" ? "selected" : "" ?>>Nuevo</option>
                <option value="verificado" <?= $cliente["estado"] === "verificado" ? "selected" : "" ?>>Verificado</option>
                <option value="bloqueado" <?= $cliente["estado"] === "bloqueado" ? "selected" : "" ?>>Bloqueado</option>
            </select>
            <input type="text" name="notas_cliente" value="<?= limpiar($cliente["notas"]) ?>" maxlength="200" placeholder="Nota sobre este cliente (opcional)">
            <button type="submit">Guardar cliente</button>
        </form>
    </div>
<?php endif; ?>

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
    <input type="hidden" name="accion" value="estado">
    <select name="estado">
        <?php foreach ($estados as $e): ?>
            <option value="<?= $e ?>" <?= $e === $pedido["estado"] ? "selected" : "" ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Guardar estado</button>
    <a class="boton boton-gris" href="pedidos.php">Volver a pedidos</a>
</form>

<?php require "pie.php"; ?>
