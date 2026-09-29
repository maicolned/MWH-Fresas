<?php
// =====================================================================
//  admin/ingredientes.php — la pantalla de toppings y la de salsas.
//
//  Las tablas "toppings" y "salsas" tienen exactamente las mismas columnas,
//  así que en vez de escribir dos veces el mismo código, toppings.php y
//  salsas.php le dicen a este archivo con qué tabla trabajar:
//
//      $tabla = "toppings"; $singular = "topping"; $plural = "Toppings";
//      require "ingredientes.php";
//
//  OJO: el nombre de la tabla va pegado al SQL (no se puede usar "?" para
//  nombres de tablas). Es seguro porque lo escribimos nosotros en el
//  código, NUNCA sale de algo que escriba el usuario.
//
//  Permisos: el vendedor solo puede marcar agotado/disponible.
//            El admin además crea, edita y quita del menú.
// =====================================================================
require_once "seguridad.php";
exigir_sesion();

if (!in_array($tabla ?? "", ["toppings", "salsas"], true)) {
    exit("Tabla no permitida.");
}

$editar_id = (int) ($_GET["editar"] ?? 0);
$form = ["id" => 0, "nombre" => "", "color" => "#E54895", "forma" => "chispas"];

// Solo los toppings tienen forma: es cómo se dibujan en el vaso (assets/js/vaso.js).
// Las salsas no la necesitan: siempre se dibujan como salsa chorreando.
$con_forma = $tabla === "toppings";
$formas = [
    "barquillo" => "Barquillo (palito enrollado)",
    "galleta"   => "Galleta (trozos y moronas)",
    "gomita"    => "Gomita (gusanito)",
    "masmelo"   => "Masmelo (cubito)",
    "queso"     => "Queso (rallado)",
    "chispas"   => "Chispas (bolitas)",
];
$errores = [];

if (es_post()) {
    $accion = $_POST["accion"] ?? "";
    $id = (int) ($_POST["id"] ?? 0);

    if ($accion === "disponible") {
        // Esto lo pueden hacer los dos roles: se acabó el queso, se marca agotado.
        ejecutar("UPDATE $tabla SET disponible = NOT disponible WHERE id = ?", "i", [$id]);
        avisar("Disponibilidad actualizada. La página ya lo muestra.");
        header("Location: $tabla.php");
        exit;
    }

    // Todo lo demás es solo para el administrador.
    exigir_admin();

    if ($accion === "activo") {
        ejecutar("UPDATE $tabla SET activo = NOT activo WHERE id = ?", "i", [$id]);
        avisar("Actualizado.");
        header("Location: $tabla.php");
        exit;
    }

    if ($accion === "guardar") {
        $form = [
            "id"     => $id,
            "nombre" => trim($_POST["nombre"] ?? ""),
            "color"  => strtoupper(trim($_POST["color"] ?? "")),
            "forma"  => $_POST["forma"] ?? "chispas",
        ];

        if (!preg_match('/^[\p{L} ]{3,40}$/u', $form["nombre"])) {
            $errores[] = "El nombre debe tener entre 3 y 40 letras.";
        }
        if (!preg_match('/^#[0-9A-F]{6}$/', $form["color"])) {
            $errores[] = "El color debe tener la forma #RRGGBB.";
        }
        if ($con_forma && !isset($formas[$form["forma"]])) {
            $errores[] = "Esa forma de topping no existe.";
        }
        if (consultar_uno("SELECT id FROM $tabla WHERE nombre = ? AND id <> ?", "si", [$form["nombre"], $id])) {
            $errores[] = "Ya existe uno con ese nombre.";
        }

        if (!$errores) {
            if ($id) {
                if ($con_forma) {
                    ejecutar("UPDATE toppings SET nombre = ?, color = ?, forma = ? WHERE id = ?", "sssi", [$form["nombre"], $form["color"], $form["forma"], $id]);
                } else {
                    ejecutar("UPDATE $tabla SET nombre = ?, color = ? WHERE id = ?", "ssi", [$form["nombre"], $form["color"], $id]);
                }
                avisar("Guardado. Si cambió el color, el vaso de la página ya lo usa.");
            } else {
                if ($con_forma) {
                    ejecutar("INSERT INTO toppings (nombre, color, forma) VALUES (?, ?, ?)", "sss", [$form["nombre"], $form["color"], $form["forma"]]);
                } else {
                    ejecutar("INSERT INTO $tabla (nombre, color) VALUES (?, ?)", "ss", [$form["nombre"], $form["color"]]);
                }
                avisar("Agregado.");
            }
            header("Location: $tabla.php");
            exit;
        }
        $editar_id = $id;
    }
}

// Si se va a editar, se cargan sus datos en el formulario.
if ($editar_id && !$errores && es_admin()) {
    $fila = consultar_uno("SELECT * FROM $tabla WHERE id = ?", "i", [$editar_id]);
    if ($fila) {
        $form = $fila;
    }
}

// Cuántas veces se ha pedido cada uno (LEFT JOIN: aparecen también los que nunca se han pedido).
$columna = $tabla === "toppings" ? "dt.topping_id" : "d.salsa_id";
$desde   = $tabla === "toppings" ? "LEFT JOIN detalle_toppings dt ON dt.topping_id = x.id" : "LEFT JOIN detalle_pedidos d ON d.salsa_id = x.id";
$lista = consultar(
    "SELECT x.*, COUNT($columna) AS pedidos
       FROM $tabla x
       $desde
      GROUP BY x.id
      ORDER BY x.activo DESC, x.nombre"
);

$titulo = $plural;
$activa = $tabla;
require "encabezado.php";
?>

<h1><?= $plural ?></h1>

<div class="caja tabla-scroll">
    <table>
        <tr><th>Color</th><th>Nombre</th><th class="numero">Veces pedido</th><th>Estado</th><th>Acciones</th></tr>
        <?php foreach ($lista as $x): ?>
            <tr>
                <td><span class="punto" style="background: <?= limpiar($x["color"]) ?>; width:24px; height:24px"></span></td>
                <td>
                    <?= limpiar($x["nombre"]) ?>
                    <?php if ($con_forma): ?><br><small><?= limpiar($formas[$x["forma"]] ?? $x["forma"]) ?></small><?php endif; ?>
                </td>
                <td class="numero"><?= $x["pedidos"] ?></td>
                <td>
                    <?php if (!$x["activo"]): ?>
                        <span class="estado estado-cancelado">fuera del menú</span>
                    <?php elseif ($x["disponible"]): ?>
                        <span class="estado estado-entregado">disponible</span>
                    <?php else: ?>
                        <span class="estado estado-pendiente">agotado hoy</span>
                    <?php endif; ?>
                </td>
                <td class="botones-fila">
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $x["id"] ?>">
                        <input type="hidden" name="accion" value="disponible">
                        <button class="boton-chico boton-gris"><?= $x["disponible"] ? "Se acabó hoy" : "Ya hay otra vez" ?></button>
                    </form>
                    <?php if (es_admin()): ?>
                        <a class="boton boton-chico" href="<?= $tabla ?>.php?editar=<?= $x["id"] ?>">Editar</a>
                        <form method="post" data-confirmar="<?= $x["activo"] ? "¿Quitarlo del menú? Los pedidos viejos no se afectan." : "¿Volverlo a poner en el menú?" ?>">
                            <input type="hidden" name="id" value="<?= $x["id"] ?>">
                            <input type="hidden" name="accion" value="activo">
                            <button class="boton-chico boton-gris"><?= $x["activo"] ? "Quitar del menú" : "Volver al menú" ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php if (es_admin()): ?>
    <h2><?= $form["id"] ? "Editar " . limpiar($form["nombre"]) : "Agregar $singular" ?></h2>

    <?php if ($errores): ?>
        <div class="aviso aviso-error"><?= implode("<br>", array_map("limpiar", $errores)) ?></div>
    <?php endif; ?>

    <form class="caja formulario" method="post" id="form-ingrediente" novalidate>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= (int) $form["id"] ?>">
        <div>
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= limpiar($form["nombre"]) ?>" maxlength="40" required>
        </div>
        <div>
            <label for="color">Color en el dibujo del vaso</label>
            <input type="color" id="color" name="color" value="<?= limpiar($form["color"]) ?>">
        </div>
        <?php if ($con_forma): ?>
            <div>
                <label for="forma">Cómo se dibuja en el vaso</label>
                <select id="forma" name="forma">
                    <?php foreach ($formas as $valor => $texto): ?>
                        <option value="<?= $valor ?>" <?= ($form["forma"] ?? "") === $valor ? "selected" : "" ?>><?= $texto ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <p class="mensaje-error completo" id="error-form"></p>
        <div class="completo botones-fila">
            <button type="submit">Guardar</button>
            <?php if ($form["id"]): ?><a class="boton boton-gris" href="<?= $tabla ?>.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php require "pie.php"; ?>
