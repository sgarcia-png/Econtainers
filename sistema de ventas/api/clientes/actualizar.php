
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

    // Finalizar la ejecucion
    exit;
}

// Recibir los datos enviados desde el formulario
// trim elimina los espacios que puedan quedar al principio o al final
$dni = trim($_POST["DNI"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$email = trim($_POST["email"] ?? "");

// Comprobar que los campos obligatorios no esten vacios
if ($dni === "" || $nombre === "" || $apellido === "") {
    // Indicar que faltan datos obligatorios
    http_response_code(400);

    // Enviar el mensaje de error
    echo json_encode([
        "success" => false,
        "message" => "DNI, nombre y apellido son obligatorios"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Comprobar que el correo tenga un formato valido si fue ingresado
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    // Avisar que el correo electronico no es valido
    echo json_encode([
        "success" => false,
        "message" => "El correo electronico no es valido"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    // Comprobar que el cliente exista
    $buscar = $conexion->prepare(
        "SELECT DNI FROM cliente WHERE DNI = ?"
    );

    // Colocar el DNI recibido en la consulta
    $buscar->bind_param("s", $dni);

    // Ejecutar la consulta
    $buscar->execute();

    // Guardar el resultado de la busqueda
    $buscar->store_result();

    // Comprobar si se encontro algun cliente
    $existe = $buscar->num_rows > 0;

    // Cerrar la consulta
    $buscar->close();

    // Si el cliente no existe, informar el error
    if (!$existe) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No se encontro el cliente"
        ], JSON_UNESCAPED_UNICODE);

        // Cerrar la conexion con la base de datos
        $conexion->close();
        exit;
    }

    // Actualizar los datos sin cambiar el DNI
    $actualizar = $conexion->prepare(
        "UPDATE cliente
         SET nombre = ?, apellido = ?, telefono = ?, email = ?
         WHERE DNI = ?"
    );

    // Si el telefono o el correo estan vacios, guardar NULL
    $telefonoGuardar = $telefono !== "" ? $telefono : null;
    $emailGuardar = $email !== "" ? $email : null;

    // Colocar los nuevos datos en la consulta
    $actualizar->bind_param(
        "sssss",
        $nombre,
        $apellido,
        $telefonoGuardar,
        $emailGuardar,
        $dni
    );

    // Ejecutar la actualizacion
    $actualizar->execute();

    // Cerrar la consulta
    $actualizar->close();

    // Informar que los datos se actualizaron correctamente
    echo json_encode([
        "success" => true,
        "message" => "Cliente actualizado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    // Cerrar la conexion con la base de datos
    $conexion->close();

} catch (Throwable $e) {
    // Guardar el detalle del error en el registro de errores
    error_log("Error al actualizar cliente: " . $e->getMessage());

    // Indicar que ocurrio un error en el servidor
    http_response_code(500);

    // Enviar un mensaje general de error
    echo json_encode([
        "success" => false,
        "message" => "No se pudo actualizar el cliente"
    ], JSON_UNESCAPED_UNICODE);
}
