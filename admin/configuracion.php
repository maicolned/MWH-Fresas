<?php
// =====================================================================
//  admin/configuracion.php — datos del negocio (solo admin).
//  Lo que se guarde aquí cambia de inmediato en toda la página,
//  sin tocar el código: nombre, lema, WhatsApp, ciudad, horario...
// =====================================================================
require "seguridad.php";
exigir_admin();

$filas = consultar("SELECT clave, valor, descripcion FROM configuracion ORDER BY FIELD(clave, 'nombre','lema','whatsapp','direccion','ciudad','horario','integrantes')");
$errores = [];

if (es_post()) {
    $nuevos = [];
    foreach ($filas as $f) {
        $nuevos[$f["clave"]] = trim($_POST[$f["clave"]] ?? "");
    }

    // Primero se revisa TODO; solo si no hay ningún error se guarda.
    // (Si se guardara mientras se revisa, un error en el último campo dejaría
    //  los primeros ya guardados: un cambio a medias.)
    foreach ($nuevos as $clave => $valor) {
        if ($valor === "" || mb_strlen($valor) > 255) {
            $errores[] = "El campo \"$clave\" no puede quedar vacío ni pasar de 255 letras.";
        }
    }
    if (!preg_match('/^573\d{9}$/', $nuevos["whatsapp"] ?? "")) {
        $errores[] = "El WhatsApp debe ser 57 seguido del celular de 10 números, sin espacios. Ej: 573238263347";
    }

    if (!$errores) {
        foreach ($nuevos as $clave => $valor) {
            ejecutar("UPDATE configuracion SET valor = ? WHERE clave = ?", "ss", [$valor, $clave]);
        }
        avisar("Configuración guardada. Ya se ve en la página.");
        header("Location: configuracion.php");
        exit;
    }

    // Si hubo errores, se muestra lo que la persona escribió para que lo corrija.
    foreach ($filas as &$f) {
        $f["valor"] = $nuevos[$f["clave"]];
    }
    unset($f);
}

$titulo = "Configuración";
$activa = "config";
require "encabezado.php";
?>

<h1>Configuración del negocio</h1>

<?php if ($errores): ?>
    <div class="aviso aviso-error"><?= implode("<br>", array_map("limpiar", $errores)) ?></div>
<?php endif; ?>

<form class="caja formulario" method="post">
    <?php foreach ($filas as $f): ?>
        <div class="<?= in_array($f["clave"], ["lema", "direccion", "integrantes"]) ? "completo" : "" ?>">
            <label for="<?= limpiar($f["clave"]) ?>"><?= limpiar($f["descripcion"]) ?></label>
            <input type="text" id="<?= limpiar($f["clave"]) ?>" name="<?= limpiar($f["clave"]) ?>" value="<?= limpiar($f["valor"]) ?>" maxlength="255" required>
        </div>
    <?php endforeach; ?>
    <div class="completo">
        <button type="submit">Guardar cambios</button>
    </div>
</form>

<?php require "pie.php"; ?>
