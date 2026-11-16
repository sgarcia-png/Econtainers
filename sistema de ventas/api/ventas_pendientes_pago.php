
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["administradora_id"])) {
    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesion"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

require_once __DIR__ . "/../config/conexion.php";

try {
    $sql = "
        SELECT
            v.nro_venta,
            v.fecha,
            v.dni_cliente,
            v.monto_total,
            COALESCE(SUM(p.monto_pago), 0) AS total_pagado,
            (
                v.monto_total - COALESCE(SUM(p.monto_pago), 0)
            ) AS saldo
        FROM ventas AS v
        LEFT JOIN pago AS p
            ON p.nro_venta = v.nro_venta
        WHERE v.eliminado = 0
        GROUP BY
            v.nro_venta,
            v.fecha,
            v.dni_cliente,
            v.monto_total
        HAVING saldo > 0
        ORDER BY v.nro_venta DESC
    ";

    $resultado = $conexion->query($sql);
    $ventas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $ventas[] = $fila;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $ventas
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();

} catch (Throwable $e) {
    error_log("Error al consultar ventas pendientes: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar las ventas pendientes"
    ], JSON_UNESCAPED_UNICODE);
}
