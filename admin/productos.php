<?php
// =====================================================================
//  admin/productos.php — listado de productos (solo admin).
//  Desde aquí se marca "agotado hoy" y se quita/devuelve al menú.
//  Los productos NO se borran: se desactivan (borrado lógico), porque
//  hay pedidos viejos que los usan y no pueden quedar huérfanos.
// =====================================================================
require "seguridad.php";
exigir_admin();

if (es_post()) {
    $id = (int) ($_POST["id"] ?? 0);
    $accion = $_POST["accion"] ?? "";

    if ($accion === "disponible") {
        // NOT disponible: si estaba en 1 queda en 0, y al revés.
        ejecutar("UPDATE productos SET disponible = NOT disponible WHERE id = ?", "i", [$id]);
        avisar("Disponibilidad actualizada.");
    } elseif ($accion === "activo") {
        ejecutar("UPDATE productos SET activo = NOT activo WHERE id = ?", "i", [$id]);
        avisar("Producto actualizado.");
    }
    header("Location: productos.php");
    exit;
}

$buscar = trim($_GET["buscar"] ?? "");
$productos = consultar(
    "SELECT p.*, COUNT(d.id) AS vendidos
       FROM productos p
       LEFT JOIN detalle_pedidos d ON d.producto_id = p.id
      WHERE p.nombre LIKE ?
      GROUP BY p.id
      ORDER BY p.activo DESC, p.precio",
    "s", ["%" . addcslashes($buscar, "%_\\") . "%"]
);

$titulo = "Productos";
$activa = "productos";
require "encabezado.php";
?>

<h1>Productos</h1>

<form class="filtros" method="get">
    <input type="search" name="buscar" value="<?= limpiar($buscar) ?>" placeholder="Buscar producto">
    <button type="submit">Buscar</button>
    <a class="boton" href="producto_form.php">+ Nuevo producto</a>
</form>

<div class="caja tabla-scroll">
    <table>
        <tr><th>Foto</th><th>Nombre</th><th class="numero">Toppings</th><th class="numero">Precio</th><th class="numero">Vendidos</th><th>Estado</th><th>Acciones</th></tr>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><?php if ($p["imagen"]): ?><img class="miniatura" src="../<?= limpiar($p["imagen"]) ?>" alt=""><?php endif; ?></td>
                <td><?= limpiar($p["nombre"]) ?></td>
                <td class="numero"><?= $p["toppings_incluidos"] ?></td>
                <td class="numero"><?= pesos($p["precio"]) ?></td>
                <td class="numero"><?= $p["vendidos"] ?></td>
                <td>
                    <?php if (!$p["activo"]): ?>
                        <span class="estado estado-cancelado">fuera del menú</span>
                    <?php elseif ($p["disponible"]): ?>
                        <span class="estado estado-entregado">disponible</span>
                    <?php else: ?>
                        <span class="estado estado-pendiente">agotado hoy</span>
                    <?php endif; ?>
                </td>
                <td class="botones-fila">
                    <a class="boton boton-chico" href="producto_form.php?id=<?= $p["id"] ?>">Editar</a>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $p["id"] ?>">
                        <input type="hidden" name="accion" value="disponible">
                        <button class="boton-chico boton-gris"><?= $p["disponible"] ? "Marcar agotado" : "Marcar disponible" ?></button>
                    </form>
                    <form method="post" data-confirmar="<?= $p["activo"] ? "¿Quitar este producto del menú? Los pedidos viejos no se afectan." : "¿Volver a poner este producto en el menú?" ?>">
                        <input type="hidden" name="id" value="<?= $p["id"] ?>">
                        <input type="hidden" name="accion" value="activo">
                        <button class="boton-chico boton-gris"><?= $p["activo"] ? "Quitar del menú" : "Volver al menú" ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$productos): ?>
            <tr><td colspan="7">No hay productos con ese nombre.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require "pie.php"; ?>
