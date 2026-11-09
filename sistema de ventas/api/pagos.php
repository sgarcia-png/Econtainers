
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["administradora_id"])) {
    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesion para consultar los pagos"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

require_once __DIR__ . "/../config/conexion.php";

try {

    $fechaDesde = trim($_GET["fecha_desde"] ?? "");
    $fechaHasta = trim($_GET["fecha_hasta"] ?? "");
    $formaPago = trim($_GET["forma_pago"] ?? "");

    // Formas de pago disponibles en el sistema
    $formasPago = [
    "Efectivo",
    "Transferencia",
    "Débito",
    "Crédito",
    "Depósito"
];
    // Validar las fechas recibidas
    $validarFecha = function ($fecha) {

        if ($fecha === "") {
            return true;
        }

        $objetoFecha = DateTime::createFromFormat("!Y-m-d", $fecha);

        return $objetoFecha !== false
            && $objetoFecha->format("Y-m-d") === $fecha;
    };

    if (!$validarFecha($fechaDesde) || !$validarFecha($fechaHasta)) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "El formato de fecha no es valido"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    if (
        $fechaDesde !== "" &&
        $fechaHasta !== "" &&
        $fechaDesde > $fechaHasta
    ) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "La fecha inicial no puede ser posterior a la final"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Validar la forma de pago
    if (
        $formaPago !== "" &&
        !in_array($formaPago, $formasPago, true)
    ) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "La forma de pago no es valida"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Consultar los pagos y sus ventas relacionadas
    $sql = "
        SELECT
            p.nro_pago,
            p.fecha_pago,
            p.monto_pago,
            p.forma_pago,
            p.nro_venta,
            v.dni_cliente,
            v.estado_pago
        FROM pago AS p
        INNER JOIN ventas AS v
            ON p.nro_venta = v.nro_venta
        WHERE v.eliminado = 0
    ";

    $parametros = [];
    $tipos = "";

    // Aplicar filtro por fecha inicial
    if ($fechaDesde !== "") {

        $sql .= " AND p.fecha_pago >= ?";

        $parametros[] = $fechaDesde;
        $tipos .= "s";
    }

    // Aplicar filtro por fecha final
    if ($fechaHasta !== "") {

        $sql .= " AND p.fecha_pago <= ?";

        $parametros[] = $fechaHasta;
        $tipos .= "s";
    }

    // Aplicar filtro por forma de pago
    if ($formaPago !== "") {

        $sql .= " AND p.forma_pago = ?";

        $parametros[] = $formaPago;
        $tipos .= "s";
    }

    // Ordenar los pagos por fecha y numero
    $sql .= " ORDER BY p.fecha_pago DESC, p.nro_pago DESC";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta");
    }

    if (count($parametros) > 0) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();

    $pagos = [];

    while ($fila = $resultado->fetch_assoc()) {
        $pagos[] = $fila;
    }

    $stmt->close();
    $conexion->close();

    // Enviar los pagos y las formas de pago a JavaScript
    echo json_encode([
        "ok" => true,
        "datos" => $pagos,
        "formas_pago" => $formasPago
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    error_log("Error al consultar pagos: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar los pagos"
    ], JSON_UNESCAPED_UNICODE);
}
