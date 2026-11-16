
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir las fechas enviadas desde JavaScript
    $fechaDesde = trim($_GET["desde"] ?? "");
    $fechaHasta = trim($_GET["hasta"] ?? "");

    // Comprobar que las fechas tengan el formato correcto
    function fechaValida($fecha) {
        $fechaObjeto = DateTime::createFromFormat("!Y-m-d", $fecha);

        return $fechaObjeto !== false
            && $fechaObjeto->format("Y-m-d") === $fecha;
    }

    if (
        $fechaDesde === "" ||
        $fechaHasta === "" ||
        !fechaValida($fechaDesde) ||
        !fechaValida($fechaHasta)
    ) {
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Ingresa una fecha desde y una fecha hasta validas"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Comprobar que la fecha inicial no sea posterior a la final
    if ($fechaDesde > $fechaHasta) {
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "La fecha desde no puede ser posterior a la fecha hasta"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Consultar las ventas dentro del periodo seleccionado
    $consulta = $conexion->prepare(
        "SELECT
            nro_venta,
            fecha,
            dni_cliente,
            monto_total,
            estado_pago
         FROM ventas
         WHERE fecha >= ? AND fecha <= ?
         ORDER BY fecha DESC, nro_venta DESC"
    );

    $consulta->bind_param("ss", $fechaDesde, $fechaHasta);
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

    error_log("Error en el reporte de ventas por fecha: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo generar el reporte de ventas"
    ], JSON_UNESCAPED_UNICODE);
}
