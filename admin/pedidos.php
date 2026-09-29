<?php
// =====================================================================
//  admin/pedidos.php — listado de pedidos con buscador y filtro.
// =====================================================================
require "seguridad.php";
exigir_sesion();

$buscar = trim($_GET["buscar"] ?? "");
$estado = $_GET["estado"] ?? "";
$estados = ["pendiente", "preparando", "entregado", "cancelado"];
if (!in_array($estado, $estados, true)) {
    $estado = "";
}

// La consulta se arma por partes, pero los VALORES siempre van con "?".
$sql = "SELECT p.id, p.cliente, p.telefono, p.entrega, p.total, p.estado, p.fecha,
               COUNT(d.id) AS vasos
          FROM pedidos p
          JOIN detalle_pedidos d ON d.pedido_id = p.id
         WHERE 1 = 1";
$tipos = "";
$valores = [];

if ($buscar !== "") {
    // Se busca por nombre, celular o número de pedido.
    // Solo se busca por número si lo escrito ES un número de pedido
    // ("MWH-00012" o "12"); si no, el número queda en 0 y no encuentra nada.
    $numero = preg_match('/^(MWH-?)?0*(\d{1,9})$/i', $buscar, $m) ? (int) $m[2] : 0;
    $sql .= " AND (p.cliente LIKE ? OR p.telefono LIKE ? OR p.id = ?)";
    $tipos .= "ssi";
    // Los comodines % y _ que escriba el usuario se escapan para que
    // se busquen como texto normal.
    $patron = "%" . addcslashes($buscar, "%_\\") . "%";
    array_push($valores, $patron, $patron, $numero);
}
if ($estado !== "") {
    $sql .= " AND p.estado = ?";
    $tipos .= "s";
    $valores[] = $estado;
}
$sql .= " GROUP BY p.id ORDER BY p.fecha DESC LIMIT 200";

$pedidos = consultar($sql, $tipos, $valores);

$titulo = "Pedidos";
$activa = "pedidos";
require "encabezado.php";
?>

<h1>Pedidos</h1>

<form class="filtros" method="get">
    <input type="search" name="buscar" value="<?= limpiar($buscar) ?>" placeholder="Nombre, celular o número de pedido">
    <select name="estado">
        <option value="">Todos los estados</option>
        <?php foreach ($estados as $e): ?>
            <option value="<?= $e ?>" <?= $e === $estado ? "selected" : "" ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Buscar</button>
    <?php if ($buscar !== "" || $estado !== ""): ?>
        <a class="boton boton-gris" href="pedidos.php">Quitar filtro</a>
    <?php endif; ?>
</form>

<div class="caja tabla-scroll">
    <p><?= count($pedidos) ?> pedido(s)</p>
    <table>
        <tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Celular</th><th>Entrega</th><th class="numero">Vasos</th><th class="numero">Total</th><th>Estado</th><th></th></tr>
        <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= codigo_pedido($p["id"]) ?></td>
                <td><?= date("d/m/Y h:i a", strtotime($p["fecha"])) ?></td>
                <td><?= limpiar($p["cliente"]) ?></td>
                <td><?= limpiar($p["telefono"]) ?></td>
                <td><?= $p["entrega"] === "domicilio" ? "Domicilio" : "Recoge" ?></td>
                <td class="numero"><?= $p["vasos"] ?></td>
                <td class="numero"><?= pesos($p["total"]) ?></td>
                <td><span class="estado estado-<?= $p["estado"] ?>"><?= $p["estado"] ?></span></td>
                <td><a class="boton boton-chico" href="pedido.php?id=<?= $p["id"] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?>
            <tr><td colspan="9">No se encontraron pedidos.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require "pie.php"; ?>
