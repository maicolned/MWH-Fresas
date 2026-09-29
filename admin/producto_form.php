<?php
// =====================================================================
//  admin/producto_form.php — crear o editar un producto (solo admin).
//    producto_form.php        -> producto nuevo
//    producto_form.php?id=2   -> editar el producto 2
// =====================================================================
require "seguridad.php";
exigir_admin();

$id = (int) ($_GET["id"] ?? 0);
$producto = [
    "nombre" => "", "descripcion" => "", "toppings_incluidos" => 1,
    "precio" => "", "imagen" => "",
];

if ($id) {
    $producto = consultar_uno("SELECT * FROM productos WHERE id = ?", "i", [$id]);
    if (!$producto) {
        avisar("Ese producto no existe.", "error");
        header("Location: productos.php");
        exit;
    }
}

$errores = [];

if (es_post()) {
    $producto["nombre"]             = trim($_POST["nombre"] ?? "");
    $producto["descripcion"]        = trim($_POST["descripcion"] ?? "");
    $producto["toppings_incluidos"] = (int) ($_POST["toppings_incluidos"] ?? 0);
    $producto["precio"]             = (int) ($_POST["precio"] ?? 0);

    // --- Validación en el servidor (la del navegador está en assets/js/admin.js) ---
    if (mb_strlen($producto["nombre"]) < 3 || mb_strlen($producto["nombre"]) > 60) {
        $errores[] = "El nombre debe tener entre 3 y 60 letras.";
    }
    if (mb_strlen($producto["descripcion"]) > 200) {
        $errores[] = "La descripción puede tener máximo 200 letras.";
    }
    if ($producto["toppings_incluidos"] < 1 || $producto["toppings_incluidos"] > 4) {
        $errores[] = "Los toppings incluidos deben ser entre 1 y 4.";
    }
    if ($producto["precio"] < 1000 || $producto["precio"] > 200000) {
        $errores[] = "El precio debe estar entre $1.000 y $200.000.";
    }
    $repetido = consultar_uno("SELECT id FROM productos WHERE nombre = ? AND id <> ?", "si", [$producto["nombre"], $id]);
    if ($repetido) {
        $errores[] = "Ya existe un producto con ese nombre.";
    }

    // --- La foto (opcional al editar) ---
    // No se confía en el nombre ni en la extensión que trae el archivo:
    // getimagesize() abre el archivo y revisa que de verdad sea una imagen.
    $foto_nueva = null;
    if (!empty($_FILES["imagen"]["name"])) {
        $archivo = $_FILES["imagen"];
        $permitidas = [IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_WEBP => "webp"];
        $info = $archivo["error"] === UPLOAD_ERR_OK ? @getimagesize($archivo["tmp_name"]) : false;

        if ($archivo["error"] === UPLOAD_ERR_INI_SIZE || $archivo["error"] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = "La foto es más pesada de lo que permite PHP (upload_max_filesize en php.ini).";
        } elseif ($archivo["error"] !== UPLOAD_ERR_OK) {
            $errores[] = "La foto no se pudo subir (código de error " . $archivo["error"] . ").";
        } elseif ($archivo["size"] > 5 * 1024 * 1024) {
            $errores[] = "La foto pesa más de 5 MB. Redúzcala antes de subirla.";
        } elseif (!$info || !isset($permitidas[$info[2]])) {
            $errores[] = "El archivo no es una foto JPG, PNG o WEBP.";
        } else {
            // Nombre nuevo inventado por nosotros, con la extensión del tipo REAL.
            $foto_nueva = "assets/img/subidas/producto_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $permitidas[$info[2]];
        }
    } elseif (!$id) {
        $errores[] = "El producto nuevo necesita una foto.";
    }

    // Guardar la foto en la carpeta. Si NO se puede (casi siempre es porque
    // Apache no tiene permiso de escribir en assets/img/subidas), se avisa y
    // no se toca la base de datos: así el producto nunca queda apuntando a
    // una foto que no existe.
    if (!$errores && $foto_nueva) {
        $destino = __DIR__ . "/../" . $foto_nueva;
        if (!is_dir(dirname($destino)) || !is_writable(dirname($destino))) {
            $errores[] = "No se puede guardar la foto: la carpeta assets/img/subidas no tiene permiso de escritura. "
                       . "En Mac: chmod 777 assets/img/subidas";
        } elseif (!move_uploaded_file($archivo["tmp_name"], $destino)) {
            $errores[] = "La foto no se pudo guardar en assets/img/subidas. Revise los permisos de la carpeta.";
        } else {
            $foto_vieja = $producto["imagen"];
            $producto["imagen"] = $foto_nueva;
        }
    }

    if (!$errores) {
        if ($id) {
            ejecutar(
                "UPDATE productos SET nombre = ?, descripcion = ?, toppings_incluidos = ?, precio = ?, imagen = ? WHERE id = ?",
                "ssiisi",
                [$producto["nombre"], $producto["descripcion"], $producto["toppings_incluidos"], $producto["precio"], $producto["imagen"], $id]
            );
            // La foto vieja se borra solo si era una subida desde el panel
            // (las fotos originales de assets/img/ no se tocan).
            if (!empty($foto_vieja) && str_starts_with($foto_vieja, "assets/img/subidas/")) {
                @unlink(__DIR__ . "/../" . $foto_vieja);
            }
            avisar("Producto actualizado. Los pedidos viejos conservan el precio que tenían.");
        } else {
            ejecutar(
                "INSERT INTO productos (nombre, descripcion, toppings_incluidos, precio, imagen) VALUES (?, ?, ?, ?, ?)",
                "ssiis",
                [$producto["nombre"], $producto["descripcion"], $producto["toppings_incluidos"], $producto["precio"], $producto["imagen"]]
            );
            avisar("Producto creado.");
        }
        header("Location: productos.php");
        exit;
    }
}

$titulo = $id ? "Editar producto" : "Nuevo producto";
$activa = "productos";
require "encabezado.php";
?>

<h1><?= $titulo ?></h1>

<?php if ($errores): ?>
    <div class="aviso aviso-error"><?= implode("<br>", array_map("limpiar", $errores)) ?></div>
<?php endif; ?>

<form class="caja formulario" method="post" enctype="multipart/form-data" id="form-producto" novalidate>
    <div>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= limpiar($producto["nombre"]) ?>" maxlength="60" required>
    </div>
    <div>
        <label for="precio">Precio (sin puntos)</label>
        <input type="number" id="precio" name="precio" value="<?= limpiar($producto["precio"]) ?>" min="1000" max="200000" step="100" required>
    </div>
    <div>
        <label for="toppings_incluidos">Toppings que incluye</label>
        <input type="number" id="toppings_incluidos" name="toppings_incluidos" value="<?= limpiar($producto["toppings_incluidos"]) ?>" min="1" max="4" required>
    </div>
    <div>
        <label for="imagen">Foto <?= $id ? "(déjela vacía para conservar la actual)" : "" ?></label>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
        <!-- Vista previa: muestra la foto actual, y al escoger otra la cambia
             al instante (assets/js/admin.js). Todavía no se ha guardado nada. -->
        <img class="vista-previa" id="vista-previa"
             src="<?= $producto["imagen"] ? "../" . limpiar($producto["imagen"]) : "" ?>"
             alt="Vista previa de la foto" <?= $producto["imagen"] ? "" : "hidden" ?>>
        <small id="nota-previa"><?= $producto["imagen"] ? "Foto actual" : "" ?></small>
    </div>
    <div class="completo">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" maxlength="200" rows="3"><?= limpiar($producto["descripcion"]) ?></textarea>
    </div>
    <p class="mensaje-error completo" id="error-form"></p>
    <div class="completo botones-fila">
        <button type="submit">Guardar</button>
        <a class="boton boton-gris" href="productos.php">Cancelar</a>
    </div>
</form>

<?php require "pie.php"; ?>
