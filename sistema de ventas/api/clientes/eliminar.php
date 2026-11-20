
<?php

// Indicar que la respuesta se enviara en formato JSON
header("Content-Type: application/json; charset=utf-8");

// Conectar este archivo con la base de datos
require_once __DIR__ . "/../../config/conexion.php";

// Comprobar que la solicitud recibida sea de tipo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Indicar que el metodo de solicitud no esta permitido
    http_response_code(405);

    // Enviar un mensaje de error
    echo json_encode([
        "success" => false,
        "message" => "Este servicio requiere una solicitud POST"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Recibir el DNI enviado desde el formulario
$dni = trim($_POST["DNI"] ?? "");

// Comprobar que se haya ingresado el DNI
if ($dni === "") {
    http_response_code(400);

    // Avisar que falta el DNI del cliente
    echo json_encode([
        "success" => false,
        "message" => "Se necesita el DNI del cliente"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    // Iniciar una transaccion para controlar las operaciones
    $conexion->begin_transaction();

    // Comprobar que el cliente exista
    // FOR UPDATE bloquea temporalmente el registro durante la transaccion
    $buscar = $conexion->prepare(
        "SELECT DNI FROM cliente WHERE DNI = ? FOR UPDATE"
    );

    // Colocar el DNI en la consulta
    $buscar->bind_param("s", $dni);

    // Ejecutar la busqueda
    $buscar->execute();

    // Guardar el resultado de la consulta
    $buscar->store_result();

    // Comprobar si se encontro el cliente
    $existe = $buscar->num_rows > 0;

    // Cerrar la consulta
    $buscar->close();

    // Si el cliente no existe, cancelar la transaccion
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

    // Comprobar si el cliente tiene ventas asociadas
    $buscarVentas = $conexion->prepare(
        "SELECT nro_venta FROM ventas WHERE dni_cliente = ? LIMIT 1"
    );

    // Colocar el DNI en la consulta
    $buscarVentas->bind_param("s", $dni);

    // Ejecutar la busqueda
    $buscarVentas->execute();

    // Guardar el resultado
    $buscarVentas->store_result();

    // Comprobar si se encontro alguna venta
    $tieneVentas = $buscarVentas->num_rows > 0;

    // Cerrar la consulta
    $buscarVentas->close();

    // Si tiene ventas, no permitir la eliminacion
    if ($tieneVentas) {
        // Cancelar la transaccion
        $conexion->rollback();

        // Indicar que no se puede realizar la operacion
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

    // Indicar que cliente se debe eliminar
    $eliminar->bind_param("s", $dni);

    // Ejecutar la eliminacion
    $eliminar->execute();

    // Cerrar la consulta
    $eliminar->close();

    // Confirmar los cambios realizados en la base de datos
    $conexion->commit();

    // Informar que el cliente se elimino correctamente
    echo json_encode([
        "success" => true,
        "message" => "Cliente eliminado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    // Cerrar la conexion
    $conexion->close();

} catch (Throwable $e) {
    // Si ocurre un error, intentar cancelar la transaccion
    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {
        // No exponer detalles internos
    }

    // Guardar el detalle del error en el registro de errores
    error_log("Error al eliminar cliente: " . $e->getMessage());

    // Indicar que ocurrio un error en el servidor
    http_response_code(500);

    // Enviar un mensaje general de error
    echo json_encode([
        "success" => false,
        "message" => "No se pudo eliminar el cliente"
    ], JSON_UNESCAPED_UNICODE);

    // Cerrar la conexion
    $conexion->close();
}
