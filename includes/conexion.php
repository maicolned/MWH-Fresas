<?php
// =====================================================================
//  conexion.php — se conecta a la base de datos.
//  Es el mismo archivo que ustedes tenían, con dos cambios:
//   1. mysqli_report(...) hace que cualquier error de MySQL sea una
//      "excepción". Eso es lo que permite deshacer un pedido a medias
//      con rollback() (ver guardar_pedido.php).
//   2. utf8mb4 en vez de utf8, para que las tildes, la ñ y los emojis
//      se guarden bien.
// =====================================================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Igual que en XAMPP: usuario root y sin clave.
    $conexion = new mysqli("localhost", "root", "", "fresas_db");
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("No se pudo conectar a la base de datos. Revise que MySQL esté encendido en XAMPP y que la base fresas_db exista.");
}
