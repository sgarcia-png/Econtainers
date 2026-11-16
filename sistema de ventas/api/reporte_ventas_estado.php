
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir el estado seleccionado
    $estado = trim($_GET["estado"] ?? "");

    // Solo aceptar los estados permitidos
    $estadosPermitidos = ["Pendiente", "Parcial", "Pagado"];

    if (
        $estado !== "" &&
        !in_array($estado, $estadosPermitidos, true)
    ) {
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "El estado seleccionado no es valido"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Preparar la consulta de ventas
    $sql = "
        SELECT
            nro_venta,
            fecha,
            dni_cliente,
            monto_total,
            estado_pago
        FROM ventas
    ";

    if ($estado !== "") {
        $sql .= " WHERE estado_pago = ?";
    }

    $sql .= " ORDER BY fecha DESC, nro_venta DESC";

    $consulta = $conexion->prepare($sql);

    // Aplicar el filtro si se selecciono un estado
    if ($estado !== "") {
        $consulta->bind_param("s", $estado);
    }

    $consulta->execute();
    $resultado = $consulta->get_result();

    $ventas = [];
    $totalImportes = 0;

    while ($fila = $resultado->fetch_assoc()) {
        $ventas[] = $fila;
        $totalImportes += (float)$fila["monto_total"];
    }

    echo json_encode([
        "ok" => true,
        "datos" => $ventas,
        "cantidad" => count($ventas),
        "total_importes" => $totalImportes
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {

    error_log("Error en el reporte por estado: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo generar el reporte"
    ], JSON_UNESCAPED_UNICODE);
}
