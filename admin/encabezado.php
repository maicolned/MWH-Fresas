<?php
// =====================================================================
//  admin/encabezado.php — menú del panel, igual en todas sus páginas.
//  Las opciones marcadas "solo admin" no le aparecen al vendedor
//  (y además cada página lo revisa con exigir_admin()).
// =====================================================================

$titulo = $titulo ?? "Panel";
$activa = $activa ?? "";

$opciones = [
    // clave        archivo              texto           solo admin
    "resumen"   => ["index.php",         "Resumen",       false],
    "pedidos"   => ["pedidos.php",       "Pedidos",       false],
    "clientes"  => ["clientes.php",      "Clientes",      false],
    "productos" => ["productos.php",     "Productos",     true],
    "toppings"  => ["toppings.php",      "Toppings",      false],
    "salsas"    => ["salsas.php",        "Salsas",        false],
    "reportes"  => ["reportes.php",      "Reportes",      true],
    "config"    => ["configuracion.php", "Configuración", true],
    "clave"     => ["clave.php",         "Mi clave",      false],
];
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= limpiar($titulo) ?> - Panel <?= limpiar(config("nombre")) ?></title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../<?= version("assets/css/estilos.css") ?>">
</head>
<body class="panel-cuerpo">

    <header class="panel-encabezado">
        <a class="logo" href="index.php"><img src="../assets/img/logo.png" alt="<?= limpiar(config("nombre")) ?>"></a>
        <div class="usuario">
            <?= limpiar($usuario["nombre"]) ?> (<?= limpiar($usuario["rol"]) ?>)<br>
            <a href="../index.php" target="_blank">Ver la página</a> ·
            <a href="salir.php">Salir</a>
        </div>
    </header>

    <nav class="panel-menu">
        <?php foreach ($opciones as $clave => [$archivo, $texto, $solo_admin]): ?>
            <?php if ($solo_admin && !es_admin()) continue; ?>
            <a href="<?= $archivo ?>" class="<?= $clave === $activa ? "activo" : "" ?>"><?= $texto ?></a>
        <?php endforeach; ?>
    </nav>

    <main class="panel">
        <?php mostrar_aviso(); ?>
