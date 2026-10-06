<?php
// =====================================================================
//  guardar_pedido.php — recibe el pedido y lo guarda.
//
//  Su versión anterior guardaba el precio que mandaba el navegador
//  ($_POST["precio"]), que siempre llegaba en 0 y que cualquiera podía
//  cambiar con F12. Ahora el navegador solo dice QUÉ escogió el cliente,
//  y este archivo:
//    1. Revisa todos los datos otra vez (validación en el servidor).
//    2. Busca los precios en la base de datos y calcula el total.
//    3. Guarda el pedido dentro de una TRANSACCIÓN: o se guarda completo,
//       o no se guarda nada.
//    4. Arma el mensaje de WhatsApp con los nombres de la base de datos.
//
//  Protección contra bots y contra quien pide y no paga:
//    - El formulario tiene un campo invisible ("empresa"). Una persona no
//      lo ve y lo deja vacío; un robot que llena todo, lo llena.
//    - Un celular bloqueado desde el panel ya no puede pedir por aquí.
//    - Nadie puede tener más de 2 pedidos sin entregar al mismo tiempo.
//
//  Responde en JSON: {"ok": true, "codigo": "MWH-00013", ...}
//                 o  {"ok": false, "errores": ["..."]}
// =====================================================================
require "includes/funciones.php";

header("Content-Type: application/json; charset=utf-8");

function responder($datos, $codigo_http = 200)
{
    http_response_code($codigo_http);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(["ok" => false, "errores" => ["Esta página solo recibe pedidos."]], 405);
}

// El JavaScript manda los datos como JSON.
$entrada = json_decode(file_get_contents("php://input"), true);
if (!is_array($entrada)) {
    responder(["ok" => false, "errores" => ["Los datos del pedido llegaron dañados."]], 400);
}

// ---------------------------------------------------------------------
// 1. VALIDAR (las mismas reglas del JavaScript, porque el JS se puede saltar)
// ---------------------------------------------------------------------
$errores = [];

$cliente   = trim((string) ($entrada["cliente"] ?? ""));
$telefono  = trim((string) ($entrada["telefono"] ?? ""));
$entrega   = (string) ($entrada["entrega"] ?? "");
$direccion = trim((string) ($entrada["direccion"] ?? ""));
$notas     = trim((string) ($entrada["notas"] ?? ""));
$vasos     = $entrada["vasos"] ?? [];
$trampa    = trim((string) ($entrada["empresa"] ?? "")); // el campo invisible

if (!preg_match('/^[\p{L} ]{3,60}$/u', $cliente)) {
    $errores[] = "Escribe tu nombre (solo letras, mínimo 3).";
}
if (!preg_match('/^3\d{9}$/', $telefono)) {
    $errores[] = "El celular debe tener 10 números y empezar por 3.";
}
if (!in_array($entrega, ["recoger", "domicilio"], true)) {
    $errores[] = "Escoge cómo recibes el pedido.";
}
if ($entrega === "domicilio" && mb_strlen($direccion) < 5) {
    $errores[] = "Escribe la dirección del domicilio.";
}
if (mb_strlen($direccion) > 150 || mb_strlen($notas) > 200) {
    $errores[] = "La dirección o las notas son demasiado largas.";
}
if (!is_array($vasos) || count($vasos) < 1 || count($vasos) > 10) {
    $errores[] = "El pedido debe tener entre 1 y 10 vasos.";
    $vasos = [];
}

// Cada vaso se revisa contra la base de datos.
$total = 0;
$renglones = []; // lo que se va a guardar, ya revisado

foreach (array_values($vasos) as $n => $vaso) {
    $numero = $n + 1;

    if (!is_array($vaso)) {
        $errores[] = "El vaso $numero está dañado.";
        continue;
    }

    $producto_id = (int) ($vaso["producto_id"] ?? 0);
    $salsa_id    = (int) ($vaso["salsa_id"] ?? 0);
    $toppings    = is_array($vaso["toppings"] ?? null) ? array_map("intval", $vaso["toppings"]) : [];

    // El precio sale de AQUÍ, de la base de datos. Si el navegador mandó
    // un "precio", simplemente no se usa.
    $producto = consultar_uno(
        "SELECT id, nombre, toppings_incluidos, precio
           FROM productos WHERE id = ? AND activo = 1 AND disponible = 1",
        "i", [$producto_id]
    );
    if (!$producto) {
        $errores[] = "El vaso $numero ya no está disponible.";
        continue;
    }

    if (count($toppings) !== count(array_unique($toppings))) {
        $errores[] = "El vaso $numero tiene un topping repetido.";
        continue;
    }
    if (count($toppings) !== (int) $producto["toppings_incluidos"]) {
        $errores[] = "El vaso $numero debe llevar {$producto["toppings_incluidos"]} topping(s).";
        continue;
    }

    $nombres_toppings = [];
    foreach ($toppings as $topping_id) {
        $t = consultar_uno(
            "SELECT nombre FROM toppings WHERE id = ? AND activo = 1 AND disponible = 1",
            "i", [$topping_id]
        );
        if (!$t) {
            $errores[] = "Uno de los toppings del vaso $numero se agotó. Escoge otro.";
            continue 2;
        }
        $nombres_toppings[] = $t["nombre"];
    }

    $salsa = consultar_uno(
        "SELECT nombre FROM salsas WHERE id = ? AND activo = 1 AND disponible = 1",
        "i", [$salsa_id]
    );
    if (!$salsa) {
        $errores[] = "La salsa del vaso $numero no está disponible. Escoge otra.";
        continue;
    }

    $total += (int) $producto["precio"];
    $renglones[] = [
        "producto_id" => $producto["id"],
        "salsa_id"    => $salsa_id,
        "precio"      => (int) $producto["precio"],
        "toppings"    => $toppings,
        "texto"       => $producto["nombre"] . ": " . implode(", ", $nombres_toppings) .
                         " · salsa " . $salsa["nombre"] . " — " . pesos($producto["precio"]),
    ];
}

// ---------------------------------------------------------------------
// ¿Quién pide? Revisión del celular contra la tabla clientes.
// Se hace solo si el celular es válido (si no, ya hay un error arriba).
// ---------------------------------------------------------------------
const MAX_SIN_ENTREGAR = 2;

if ($trampa !== "") {
    // Lo llenó un robot. No se le explica por qué (para no ayudarle).
    responder(["ok" => false, "errores" => ["No se pudo recibir el pedido."]], 422);
}

if (!$errores) {
    $cliente_guardado = consultar_uno(
        "SELECT c.estado,
                (SELECT COUNT(*) FROM pedidos p
                  WHERE p.telefono = c.telefono AND p.estado IN ('pendiente','preparando')) AS sin_entregar
           FROM clientes c WHERE c.telefono = ?",
        "s", [$telefono]
    );
    if ($cliente_guardado && $cliente_guardado["estado"] === "bloqueado") {
        $errores[] = "No pudimos recibir tu pedido por la página. Escríbenos por WhatsApp y lo revisamos.";
    } elseif ($cliente_guardado && $cliente_guardado["sin_entregar"] >= MAX_SIN_ENTREGAR) {
        $errores[] = "Ya tienes " . $cliente_guardado["sin_entregar"] . " pedidos sin entregar. "
                   . "Escríbenos por WhatsApp para confirmarlos antes de hacer otro.";
    }
}

// Si hay cualquier error, NO se guarda nada.
if ($errores) {
    responder(["ok" => false, "errores" => $errores], 422);
}

// ---------------------------------------------------------------------
// 2. GUARDAR dentro de una transacción
//    Un pedido son varios INSERT (el pedido, cada vaso, cada topping).
//    Si alguno falla a mitad de camino, rollback() deshace los que ya
//    se habían hecho: nunca queda un pedido a medias.
// ---------------------------------------------------------------------
$conexion->begin_transaction();
try {
    // El cliente: si es la primera vez que pide, se crea su registro.
    // Si ya existía, NO se le cambia el nombre (si no, cualquiera que sepa
    // el celular de otra persona le cambiaría el nombre).
    ejecutar(
        "INSERT INTO clientes (telefono, nombre) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE telefono = telefono",
        "ss", [$telefono, $cliente]
    );

    $pedido_id = ejecutar(
        "INSERT INTO pedidos (cliente, telefono, entrega, direccion, notas, total)
         VALUES (?, ?, ?, ?, ?, ?)",
        "sssssi",
        [$cliente, $telefono, $entrega,
         $entrega === "domicilio" ? $direccion : null,
         $notas === "" ? null : $notas,
         $total]
    );

    foreach ($renglones as $r) {
        $detalle_id = ejecutar(
            "INSERT INTO detalle_pedidos (pedido_id, producto_id, salsa_id, precio) VALUES (?, ?, ?, ?)",
            "iiii", [$pedido_id, $r["producto_id"], $r["salsa_id"], $r["precio"]]
        );
        foreach ($r["toppings"] as $topping_id) {
            ejecutar(
                "INSERT INTO detalle_toppings (detalle_id, topping_id) VALUES (?, ?)",
                "ii", [$detalle_id, $topping_id]
            );
        }
    }

    $conexion->commit();
} catch (mysqli_sql_exception $e) {
    $conexion->rollback();
    error_log("Error guardando pedido: " . $e->getMessage());
    responder(["ok" => false, "errores" => ["No se pudo guardar el pedido. Inténtalo de nuevo."]], 500);
}

// ---------------------------------------------------------------------
// 3. Mensaje de WhatsApp ya escrito
// ---------------------------------------------------------------------
$codigo = codigo_pedido($pedido_id);

$mensaje = "Hola " . config("nombre") . " 🍓 Quiero hacer el pedido $codigo\n\n";
foreach ($renglones as $i => $r) {
    $mensaje .= ($i + 1) . ". " . $r["texto"] . "\n";
}
$mensaje .= "\nTotal: " . pesos($total) . "\n";
$mensaje .= "Nombre: $cliente\nCelular: $telefono\n";
$mensaje .= $entrega === "domicilio" ? "Domicilio: $direccion\n" : "Paso a recogerlo\n";
if ($notas !== "") {
    $mensaje .= "Notas: $notas\n";
}

responder([
    "ok"       => true,
    "codigo"   => $codigo,
    "total"    => $total,
    "whatsapp" => enlace_whatsapp($mensaje),
]);
