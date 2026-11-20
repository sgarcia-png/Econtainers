
<?php

// Iniciar la sesion para poder verificar si la administradora ingreso al sistema.
session_start();

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Comprobar que haya una administradora con la sesion iniciada.
if (!isset($_SESSION["administradora_id"])) {
    // Indicar que no tiene autorizacion para realizar la consulta.
    http_response_code(401);

    // Enviar un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesion"
    ], JSON_UNESCAPED_UNICODE);

    // Detener la ejecucion del archivo.
    exit();
}

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

try {
    // Consulta SQL para obtener las ventas que todavia tienen saldo pendiente.
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

    // Ejecutar la consulta en la base de datos.
    $resultado = $conexion->query($sql);

    // Crear un arreglo vacio para guardar las ventas encontradas.
    $ventas = [];

    // Recorrer los resultados y guardar cada venta dentro del arreglo.
    while ($fila = $resultado->fetch_assoc()) {
        $ventas[] = $fila;
    }

    // Enviar las ventas encontradas en formato JSON.
    echo json_encode([
        "ok" => true,
        "datos" => $ventas
    ], JSON_UNESCAPED_UNICODE);

    // Cerrar la conexion con la base de datos.
    $conexion->close();

} catch (Throwable $e) {
    // Guardar los detalles del error en el registro de errores del servidor.
    error_log("Error al consultar ventas pendientes: " . $e->getMessage());

    // Indicar que ocurrio un error interno en el servidor.
    http_response_code(500);

    // Enviar un mensaje de error sin mostrar detalles internos.
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar las ventas pendientes"
    ], JSON_UNESCAPED_UNICODE);
}
