
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../config/conexion.php";

try {
    // Consultar los clientes registrados
    $consulta = "
        SELECT DNI, nombre, apellido, telefono, email
        FROM cliente
        ORDER BY apellido, nombre
    ";

    $resultado = $conexion->query($consulta);

    $clientes = [];

    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = $fila;
    }

    // Responder con los datos en formato JSON
    echo json_encode([
        "success" => true,
        "data" => $clientes,
        "message" => "Clientes consultados correctamente"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $conexion->close();

} catch (Throwable $e) {
    error_log("Error al consultar clientes: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data" => [],
        "message" => "No se pudieron consultar los clientes"
    ], JSON_UNESCAPED_UNICODE);
}
