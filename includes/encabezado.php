<?php
// =====================================================================
//  encabezado.php — el <head> y el menú. Es IGUAL en todas las páginas.
//  Antes cada página tenía su propia copia del menú (y algunas quedaron
//  dañadas). Ahora se escribe una sola vez y cada página lo incluye:
//
//      $titulo = "Productos";      // lo que sale en la pestaña
//      $activa = "productos";      // qué opción del menú se resalta
//      require "includes/encabezado.php";
// =====================================================================

$titulo = $titulo ?? "Inicio";
$activa = $activa ?? "";

$menu = [
    "inicio"      => ["index.php", "Inicio"],
    "nosotros"    => ["nosotros.php", "Nosotros"],
    "productos"   => ["productos.php", "Productos"],
    "personaliza" => ["personaliza.php", "Personaliza"],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= limpiar($titulo) ?> - <?= limpiar(config("nombre")) ?></title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <!-- Letras de Google: Dancing Script para los títulos y Poppins para leer.
         Sin internet no pasa nada: el navegador usa una letra parecida. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= version("assets/css/estilos.css") ?>">
</head>
<body>

    <!-- MENÚ -->
    <header>
        <a class="logo" href="index.php">
            <img src="assets/img/logo.png" alt="<?= limpiar(config("nombre")) ?>">
        </a>

        <nav>
            <?php foreach ($menu as $clave => [$archivo, $texto]): ?>
                <a href="<?= $archivo ?>" class="<?= $clave === $activa ? "activo" : "" ?>"><?= $texto ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
