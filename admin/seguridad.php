<?php
// =====================================================================
//  admin/seguridad.php — quién puede entrar al panel.
//
//  Hay dos roles:
//    admin    -> todo el panel
//    vendedor -> resumen, pedidos, marcar toppings/salsas agotados
//                y cambiar su propia clave
//
//  Cada página del panel empieza con UNA de estas dos líneas:
//      exigir_sesion();   // cualquiera que haya iniciado sesión
//      exigir_admin();    // solo el administrador
// =====================================================================

require_once __DIR__ . "/../includes/funciones.php";

// Huella de la clave: SHA-256 de (sal + clave).
// La "sal" es un texto al azar distinto para cada usuario: así dos personas
// con la misma clave tienen huellas distintas.
function huella_clave($clave, $sal)
{
    return hash("sha256", $sal . $clave);
}

function usuario_actual()
{
    return $_SESSION["usuario"] ?? null;
}

function es_admin()
{
    return (usuario_actual()["rol"] ?? "") === "admin";
}

function exigir_sesion()
{
    if (!usuario_actual()) {
        header("Location: login.php");
        exit;
    }
}

function exigir_admin()
{
    exigir_sesion();
    if (!es_admin()) {
        // Se devuelve al resumen con un aviso (la página pedida no se muestra).
        avisar("Esa sección es solo para el administrador.", "error");
        header("Location: index.php");
        exit;
    }
}

// Los formularios del panel solo aceptan POST para cambiar cosas.
function es_post()
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}
