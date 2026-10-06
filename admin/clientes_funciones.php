<?php
// =====================================================================
//  admin/clientes_funciones.php — lo que se sabe de cada celular.
//
//  Se incluye en las páginas del panel que muestran pedidos. La idea:
//  al ver un pedido, saber de una vez si quien pide es de confianza.
//
//    ★ Primera vez   nunca había pedido.
//    ✔ Verificado    ya recibió (y pagó) al menos un pedido, o el
//                    negocio lo marcó como verificado.
//    ⚠ Sospechoso    tiene 2 o más pedidos cancelados y ninguno
//                    entregado: puede ser un bot o alguien que pide y
//                    no paga. Conviene llamarlo antes de preparar.
//    🚫 Bloqueado     el negocio lo bloqueó: la página no le recibe
//                    más pedidos (ver guardar_pedido.php).
// =====================================================================

require_once __DIR__ . "/seguridad.php";

// Consulta del historial de UN celular: sus datos y cuántos pedidos tiene
// de cada estado. SUM(p.estado = 'entregado') cuenta los que cumplen,
// porque en MySQL una comparación verdadera vale 1 y una falsa vale 0.
function historial_cliente($telefono)
{
    return consultar_uno(
        "SELECT c.telefono, c.nombre, c.estado, c.notas, c.primer_pedido,
                COUNT(p.id)                                   AS total,
                COALESCE(SUM(p.estado = 'entregado'), 0)      AS entregados,
                COALESCE(SUM(p.estado = 'cancelado'), 0)      AS cancelados,
                COALESCE(SUM(p.estado IN ('pendiente','preparando')), 0) AS pendientes,
                MAX(p.fecha)                                  AS ultimo
           FROM clientes c
           LEFT JOIN pedidos p ON p.telefono = c.telefono
          WHERE c.telefono = ?
          GROUP BY c.telefono",
        "s", [$telefono]
    );
}

// Parte de SQL para pegarle a cualquier consulta de pedidos el historial
// de su cliente (se usa en el resumen y en el listado de pedidos).
// Devuelve las columnas estado_cliente, entregados, cancelados, total_pedidos.
const SQL_HISTORIAL = "
    LEFT JOIN clientes c ON c.telefono = p.telefono
    LEFT JOIN (SELECT telefono,
                      COUNT(*)                AS total_pedidos,
                      SUM(estado = 'entregado') AS entregados,
                      SUM(estado = 'cancelado') AS cancelados
                 FROM pedidos GROUP BY telefono) h ON h.telefono = p.telefono";

// Decide la etiqueta. Recibe una fila con: estado (o estado_cliente),
// entregados, cancelados y total (o total_pedidos).
// Devuelve [clase CSS, texto corto, explicación].
function etiqueta_cliente($c)
{
    $estado     = $c["estado_cliente"] ?? $c["estado"] ?? "nuevo";
    $entregados = (int) ($c["entregados"] ?? 0);
    $cancelados = (int) ($c["cancelados"] ?? 0);
    $total      = (int) ($c["total_pedidos"] ?? $c["total"] ?? 0);

    if ($estado === "bloqueado") {
        return ["bloqueado", "🚫 Bloqueado", "El negocio bloqueó este número. No preparar sin confirmar."];
    }
    if ($estado === "verificado" || $entregados > 0) {
        return ["verificado", "✔ Verificado", "Ya recibió $entregados pedido(s). Cliente de confianza."];
    }
    if ($cancelados >= 2) {
        return ["sospechoso", "⚠ Sospechoso", "$cancelados pedidos cancelados y ninguno entregado. Llamar antes de preparar."];
    }
    if ($total <= 1) {
        return ["primera", "★ Primera vez", "Es su primer pedido. Confirmar por WhatsApp antes de preparar."];
    }
    return ["nuevo", "Nuevo", "Tiene $total pedidos y todavía ninguno entregado."];
}

// HTML de la etiqueta, listo para pegar en una tabla.
function mostrar_etiqueta($c)
{
    [$clase, $texto, $explicacion] = etiqueta_cliente($c);
    return '<span class="etiqueta-cliente ec-' . $clase . '" title="' . limpiar($explicacion) . '">' . $texto . '</span>';
}

// Cambia el estado y la nota de un cliente (desde clientes.php o pedido.php).
function guardar_cliente($telefono, $estado, $notas)
{
    if (!in_array($estado, ["nuevo", "verificado", "bloqueado"], true)) {
        return "Ese estado no existe.";
    }
    $notas = trim($notas);
    if (mb_strlen($notas) > 200) {
        return "La nota puede tener máximo 200 letras.";
    }
    if (!consultar_uno("SELECT telefono FROM clientes WHERE telefono = ?", "s", [$telefono])) {
        return "Ese cliente no existe.";
    }
    ejecutar(
        "UPDATE clientes SET estado = ?, notas = ?, actualizado = NOW() WHERE telefono = ?",
        "sss", [$estado, $notas === "" ? null : $notas, $telefono]
    );
    return "";
}
