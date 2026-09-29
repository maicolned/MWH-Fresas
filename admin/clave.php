<?php
// =====================================================================
//  admin/clave.php — cada persona cambia su propia clave.
//  Se crea una sal nueva y se guarda la huella; la clave nunca se guarda.
// =====================================================================
require "seguridad.php";
exigir_sesion();

$errores = [];

if (es_post()) {
    $actual = $_POST["actual"] ?? "";
    $nueva  = $_POST["nueva"] ?? "";
    $repite = $_POST["repite"] ?? "";

    $yo = consultar_uno("SELECT clave_hash, sal FROM usuarios WHERE id = ?", "i", [usuario_actual()["id"]]);

    if (!$yo || !hash_equals($yo["clave_hash"], huella_clave($actual, $yo["sal"]))) {
        $errores[] = "La clave actual no es correcta.";
    }
    if (mb_strlen($nueva) < 8) {
        $errores[] = "La clave nueva debe tener mínimo 8 caracteres.";
    }
    if ($nueva !== $repite) {
        $errores[] = "La clave nueva y su repetición no coinciden.";
    }
    if ($nueva === $actual) {
        $errores[] = "La clave nueva debe ser distinta de la actual.";
    }

    if (!$errores) {
        $sal = bin2hex(random_bytes(16)); // 32 caracteres al azar
        ejecutar(
            "UPDATE usuarios SET clave_hash = ?, sal = ? WHERE id = ?",
            "ssi", [huella_clave($nueva, $sal), $sal, usuario_actual()["id"]]
        );
        avisar("Clave cambiada. Desde ahora la vieja ya no sirve.");
        header("Location: index.php");
        exit;
    }
}

$titulo = "Mi clave";
$activa = "clave";
require "encabezado.php";
?>

<h1>Cambiar mi clave</h1>

<?php if ($errores): ?>
    <div class="aviso aviso-error"><?= implode("<br>", array_map("limpiar", $errores)) ?></div>
<?php endif; ?>

<form class="caja formulario" method="post" id="form-clave" novalidate>
    <div class="completo">
        <label for="actual">Clave actual</label>
        <input type="password" id="actual" name="actual" required autocomplete="current-password">
    </div>
    <div>
        <label for="nueva">Clave nueva (mínimo 8)</label>
        <input type="password" id="nueva" name="nueva" minlength="8" required autocomplete="new-password">
    </div>
    <div>
        <label for="repite">Repita la clave nueva</label>
        <input type="password" id="repite" name="repite" required autocomplete="new-password">
    </div>
    <p class="mensaje-error completo" id="error-form"></p>
    <div class="completo"><button type="submit">Cambiar clave</button></div>
</form>

<?php require "pie.php"; ?>
