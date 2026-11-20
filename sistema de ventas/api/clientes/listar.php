
<?php

// Indicar que la respuesta se enviara en formato JSON
header("Content-Type: application/json; charset=utf-8");

// Conectar este archivo con la base de datos
require_once __DIR__ . "/../../config/conexion.php";

try {
    // Consultar los clientes registrados
    $consulta = "
        SELECT DNI, nombre, apellido, telefono, email
        FROM cliente
        ORDER BY apellido, nombre
    ";

    // Ejecutar la consulta en la base de datos
    $resultado = $conexion->query($consulta);

    // Crear un arreglo vacio para guardar los clientes
    $clientes = [];

    // Recorrer los resultados y guardar cada cliente en el arreglo
    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = $fila;
    }

    // Responder con los datos en formato JSON
    echo json_encode([
        "success" => true,
        "data" => $clientes,
        "message" => "Clientes consultados correctamente"
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Cerrar la conexion con la base de datos
    $conexion->close();

} catch (Throwable $e) {
    // Guardar el detalle del error en el registro de errores
    error_log("Error al consultar clientes: " . $e->getMessage());

    // Indicar que ocurrio un error en el servidor
    http_response_code(500);

    // Enviar una respuesta de error en formato JSON
    echo json_encode([
        "success" => false,
        "data" => [],
        "message" => "No se pudieron consultar los clientes"
    ], JSON_UNESCAPED_UNICODE);
}
