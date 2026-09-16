<?php

include "conexion.php";

$toppings = $_POST["toppings"] ?? "";
$salsa = $_POST["salsa"] ?? "";
$producto = $_POST["producto"] ?? "Fresas con crema";
$precio = $_POST["precio"] ?? 0;

$sql = "INSERT INTO pedidos (producto, toppings, salsa, precio, fecha)
        VALUES (?, ?, ?, ?, NOW())";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssd", $producto, $toppings, $salsa, $precio);

if ($stmt->execute()) {
    echo "Pedido guardado correctamente";
} else {
    echo "Error al guardar el pedido";
}

$stmt->close();
$conexion->close();

?>