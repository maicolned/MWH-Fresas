<?php
// =====================================================================
//  funciones.php — herramientas que usan todas las páginas.
//  Se incluye al principio de cada archivo PHP.
// =====================================================================

require_once __DIR__ . "/conexion.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Convierte un texto en algo seguro para mostrar en HTML.
// Si alguien escribe <script> en su nombre, se ve como texto y no se ejecuta.
function limpiar($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

// 17000 -> "$17.000"
function pesos($valor)
{
    return "$" . number_format((int) $valor, 0, ",", ".");
}

// Consulta SELECT con consulta preparada. Devuelve un arreglo de filas.
// Ejemplo: consultar("SELECT * FROM toppings WHERE id = ?", "i", [3]);
// Los "?" se reemplazan por los valores SIN pegarlos al texto del SQL:
// así no hay inyección SQL.
function consultar($sql, $tipos = "", $valores = [])
{
    global $conexion;
    $stmt = $conexion->prepare($sql);
    if ($tipos !== "") {
        $stmt->bind_param($tipos, ...$valores);
    }
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $filas;
}

// Igual que consultar(), pero devuelve una sola fila (o null si no hay).
function consultar_uno($sql, $tipos = "", $valores = [])
{
    $filas = consultar($sql, $tipos, $valores);
    return $filas[0] ?? null;
}

// INSERT, UPDATE o DELETE con consulta preparada.
// Devuelve el id del registro nuevo (en un INSERT) o las filas afectadas.
function ejecutar($sql, $tipos = "", $valores = [])
{
    global $conexion;
    $stmt = $conexion->prepare($sql);
    if ($tipos !== "") {
        $stmt->bind_param($tipos, ...$valores);
    }
    $stmt->execute();
    $resultado = $stmt->insert_id ?: $stmt->affected_rows;
    $stmt->close();
    return $resultado;
}

// Lee un dato de la tabla configuracion: config("whatsapp").
// La primera vez carga toda la tabla y la guarda para no volver a consultar.
function config($clave)
{
    static $datos = null;
    if ($datos === null) {
        $datos = [];
        foreach (consultar("SELECT clave, valor FROM configuracion") as $fila) {
            $datos[$fila["clave"]] = $fila["valor"];
        }
    }
    return $datos[$clave] ?? "";
}

// Enlace de WhatsApp con el mensaje ya escrito.
// wa.me funciona en el celular y en el computador.
function enlace_whatsapp($texto = "")
{
    $url = "https://wa.me/" . preg_replace('/\D/', '', config("whatsapp"));
    return $texto === "" ? $url : $url . "?text=" . rawurlencode($texto);
}

// Número de pedido para mostrarle al cliente: 12 -> "MWH-00012"
function codigo_pedido($id)
{
    return "MWH-" . str_pad((string) $id, 5, "0", STR_PAD_LEFT);
}

// Mensajes de una sola vez ("Guardado", "Error...") que sobreviven a una redirección.
function avisar($texto, $tipo = "ok")
{
    $_SESSION["aviso"] = ["texto" => $texto, "tipo" => $tipo];
}

function mostrar_aviso()
{
    if (!empty($_SESSION["aviso"])) {
        $a = $_SESSION["aviso"];
        unset($_SESSION["aviso"]);
        echo '<div class="aviso aviso-' . limpiar($a["tipo"]) . '">' . limpiar($a["texto"]) . '</div>';
    }
}

// Agrega "?v=fecha del archivo" a un CSS o JS: estilos.css?v=1790626001
// Cuando el archivo cambia, cambia el número, y el navegador se ve obligado a
// descargar la versión nueva en vez de usar la vieja que tenía guardada.
// $ruta va desde la raíz del proyecto: version("assets/css/estilos.css")
function version($ruta)
{
    $archivo = __DIR__ . "/../" . $ruta;
    return $ruta . "?v=" . (is_file($archivo) ? filemtime($archivo) : "1");
}
