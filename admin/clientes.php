<?php
// =====================================================================
//  admin/clientes.php — los celulares que han pedido.
//
//  Sirve para responder: ¿este número ya nos había comprado? ¿es la
//  primera vez? ¿es un bot o alguien que pide y no paga?
//  Aquí se verifica, se bloquea o se le deja una nota a cada número.
//  (Lo pueden usar el admin y el vendedor: los dos atienden pedidos.)
// =====================================================================
require "clientes_funciones.php";
exigir_sesion();

if (es_post()) {
    $error = guardar_cliente($_POST["telefono"] ?? "", $_POST["estado"] ?? "", $_POST["notas"] ?? "");
    avisar($error ?: "Cliente actualizado.", $error ? "error" : "ok");
    // Se vuelve a la misma búsqueda que estaba abierta.
    header("Location: clientes.php?" . http_build_query([
        "buscar" => $_POST["buscar"] ?? "",
        "tipo"   => $_POST["tipo"] ?? "",
    ]));
    exit;
}

$buscar = trim($_GET["buscar"] ?? "");
$tipo   = $_GET["tipo"] ?? "";
$tipos  = [
    ""           => "Todos",
    "primera"    => "★ Primera vez",
    "verificado" => "✔ Verificados",
    "nuevo"      => "Nuevos (aún sin entregar)",
    "sospechoso" => "⚠ Sospechosos",
    "bloqueado"  => "🚫 Bloqueados",
];
if (!isset($tipos[$tipo])) {
    $tipo = "";
}

// Todos los clientes con su historial. LEFT JOIN para que también salgan
// los que no tengan pedidos (por ejemplo, si se borraran de prueba).
$clientes = consultar(
    "SELECT c.telefono, c.nombre, c.estado, c.notas, c.primer_pedido,
            COUNT(p.id)                              AS total,
            COALESCE(SUM(p.estado = 'entregado'), 0) AS entregados,
            COALESCE(SUM(p.estado = 'cancelado'), 0) AS cancelados,
            COALESCE(SUM(CASE WHEN p.estado = 'entregado' THEN p.total END), 0) AS comprado,
            MAX(p.fecha)                             AS ultimo
       FROM clientes c
       LEFT JOIN pedidos p ON p.telefono = c.telefono
      WHERE c.nombre LIKE ? OR c.telefono LIKE ?
      GROUP BY c.telefono
      ORDER BY ultimo DESC",
    "ss", array_fill(0, 2, "%" . addcslashes($buscar, "%_\\") . "%")
);

// El filtro por tipo usa la misma regla de la etiqueta (etiqueta_cliente),
// así lo que se filtra es exactamente lo que se ve.
$conteo = array_fill_keys(array_keys($tipos), 0);
foreach ($clientes as $c) {
    $conteo[etiqueta_cliente($c)[0]]++;
}
$conteo[""] = count($clientes);
if ($tipo !== "") {
    $clientes = array_values(array_filter($clientes, fn($c) => etiqueta_cliente($c)[0] === $tipo));
}

$titulo = "Clientes";
$activa = "clientes";
require "encabezado.php";
?>

<h1>Clientes</h1>

<p class="ayuda">Cada número que pide por la página queda aquí. Al entregar un pedido, el número queda
<strong>verificado</strong> solo. Un número <strong>bloqueado</strong> ya no puede pedir por la página.</p>

<form class="filtros" method="get">
    <input type="search" name="buscar" value="<?= limpiar($buscar) ?>" placeholder="Nombre o celular">
    <select name="tipo">
        <?php foreach ($tipos as $valor => $texto): ?>
            <option value="<?= $valor ?>" <?= $valor === $tipo ? "selected" : "" ?>><?= $texto ?> (<?= $conteo[$valor] ?>)</option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Buscar</button>
    <?php if ($buscar !== "" || $tipo !== ""): ?>
        <a class="boton boton-gris" href="clientes.php">Quitar filtro</a>
    <?php endif; ?>
</form>

<div class="caja tabla-scroll">
    <table>
        <tr>
            <th>Cliente</th><th>Tipo</th>
            <th class="numero">Pedidos</th><th class="numero">Entregados</th><th class="numero">Cancelados</th>
            <th class="numero">Ha comprado</th><th>Último pedido</th><th>Cambiar estado y nota</th>
        </tr>
        <?php foreach ($clientes as $c): ?>
            <tr>
                <td>
                    <?= limpiar($c["nombre"]) ?><br>
                    <a href="https://wa.me/57<?= limpiar($c["telefono"]) ?>" target="_blank" rel="noopener"><?= limpiar($c["telefono"]) ?></a>
                    · <a href="pedidos.php?buscar=<?= limpiar($c["telefono"]) ?>">ver pedidos</a>
                </td>
                <td><?= mostrar_etiqueta($c) ?></td>
                <td class="numero"><?= $c["total"] ?></td>
                <td class="numero"><?= $c["entregados"] ?></td>
                <td class="numero"><?= $c["cancelados"] ?></td>
                <td class="numero"><?= pesos($c["comprado"]) ?></td>
                <td><?= $c["ultimo"] ? date("d/m/Y", strtotime($c["ultimo"])) : "—" ?></td>
                <td>
                    <form method="post" class="form-cliente">
                        <input type="hidden" name="telefono" value="<?= limpiar($c["telefono"]) ?>">
                        <input type="hidden" name="buscar" value="<?= limpiar($buscar) ?>">
                        <input type="hidden" name="tipo" value="<?= limpiar($tipo) ?>">
                        <select name="estado" aria-label="Estado del cliente">
                            <option value="nuevo" <?= $c["estado"] === "nuevo" ? "selected" : "" ?>>Nuevo</option>
                            <option value="verificado" <?= $c["estado"] === "verificado" ? "selected" : "" ?>>Verificado</option>
                            <option value="bloqueado" <?= $c["estado"] === "bloqueado" ? "selected" : "" ?>>Bloqueado</option>
                        </select>
                        <input type="text" name="notas" value="<?= limpiar($c["notas"]) ?>" maxlength="200" placeholder="Nota (opcional)" aria-label="Nota">
                        <button type="submit" class="boton-chico">Guardar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?>
            <tr><td colspan="8">No hay clientes con ese filtro.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require "pie.php"; ?>
