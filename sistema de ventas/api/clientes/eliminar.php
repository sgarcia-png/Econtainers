
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Este servicio requiere una solicitud POST"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dni = trim($_POST["DNI"] ?? "");

if ($dni === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Se necesita el DNI del cliente"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $conexion->begin_transaction();

    // Comprobar que el cliente exista
    $buscar = $conexion->prepare(
        "SELECT DNI FROM cliente WHERE DNI = ? FOR UPDATE"
    );

    $buscar->bind_param("s", $dni);
    $buscar->execute();
    $buscar->store_result();

    $existe = $buscar->num_rows > 0;
    $buscar->close();

    if (!$existe) {
        $conexion->rollback();

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No se encontro el cliente"
        ], JSON_UNESCAPED_UNICODE);

        $conexion->close();
        exit;
    }

    // Comprobar si tiene ventas asociadas
    $buscarVentas = $conexion->prepare(
        "SELECT nro_venta FROM ventas WHERE dni_cliente = ? LIMIT 1"
    );

    $buscarVentas->bind_param("s", $dni);
    $buscarVentas->execute();
    $buscarVentas->store_result();

    $tieneVentas = $buscarVentas->num_rows > 0;
    $buscarVentas->close();

    if ($tieneVentas) {
        $conexion->rollback();

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "No se puede eliminar este cliente porque tiene ventas asociadas"
        ], JSON_UNESCAPED_UNICODE);

        $conexion->close();
        exit;
    }

    // Eliminar solamente si no tiene ventas relacionadas
    $eliminar = $conexion->prepare(
        "DELETE FROM cliente WHERE DNI = ?"
    );

    $eliminar->bind_param("s", $dni);
    $eliminar->execute();

    $eliminar->close();

    $conexion->commit();

    echo json_encode([
        "success" => true,
        "message" => "Cliente eliminado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();

} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {
        // No exponer detalles internos
    }

    error_log("Error al eliminar cliente: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No se pudo eliminar el cliente"
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();
}
