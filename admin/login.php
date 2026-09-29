<?php
// =====================================================================
//  admin/login.php — ingreso del personal.
// =====================================================================
require "seguridad.php";

if (usuario_actual()) {
    header("Location: index.php");
    exit;
}

$error = "";
$correo = "";

if (es_post()) {
    $correo = trim($_POST["correo"] ?? "");
    $clave  = $_POST["clave"] ?? "";

    // Consulta preparada: si alguien escribe ' OR 1=1 -- en el correo,
    // se busca un correo que se llama literalmente así, y no existe.
    $u = consultar_uno(
        "SELECT id, nombre, correo, clave_hash, sal, rol FROM usuarios WHERE correo = ? AND activo = 1",
        "s", [$correo]
    );

    // hash_equals compara las dos huellas sin dar pistas por el tiempo que tarda.
    if ($u && hash_equals($u["clave_hash"], huella_clave($clave, $u["sal"]))) {
        session_regenerate_id(true); // nueva sesión al entrar (evita robo de sesión)
        $_SESSION["usuario"] = [
            "id"     => $u["id"],
            "nombre" => $u["nombre"],
            "rol"    => $u["rol"],
        ];
        header("Location: index.php");
        exit;
    }

    // Mismo mensaje si falla el correo o la clave: no se le dice al
    // intruso cuál de los dos acertó.
    $error = "Correo o clave incorrectos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso - <?= limpiar(config("nombre")) ?></title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../<?= version("assets/css/estilos.css") ?>">
</head>
<body class="panel-cuerpo">
    <form class="login" method="post" id="form-login" novalidate>
        <img src="../assets/img/logo.png" alt="<?= limpiar(config("nombre")) ?>">
        <h1>Ingreso del personal</h1>

        <?php if ($error): ?>
            <div class="aviso aviso-error"><?= limpiar($error) ?></div>
        <?php endif; ?>

        <div class="campo">
            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" value="<?= limpiar($correo) ?>" required autocomplete="username">
        </div>
        <div class="campo">
            <label for="clave">Clave</label>
            <input type="password" id="clave" name="clave" required autocomplete="current-password">
        </div>
        <p class="mensaje-error" id="error-form"></p>
        <button type="submit">Entrar</button>
        <p style="margin-top:15px"><a href="../index.php">Volver a la página</a></p>
    </form>
    <script src="../<?= version("assets/js/admin.js") ?>"></script>
</body>
</html>
