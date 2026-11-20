
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
$dni = trim($_POST["DNI"] ?? "");
$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$email = trim($_POST["email"] ?? "");

// Comprobar que los campos obligatorios no esten vacios
if ($dni === "" || $nombre === "" || $apellido === "") {
    http_response_code(400);

    // Informar que faltan datos obligatorios
    echo json_encode([
        "success" => false,
        "message" => "DNI, nombre y apellido son obligatorios"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Comprobar que el correo tenga un formato valido si fue ingresado
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    // Informar que el correo electronico no es valido
    echo json_encode([
        "success" => false,
        "message" => "El correo electronico no es valido"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    // Comprobar si el DNI ya esta registrado
    $consulta = $conexion->prepare(
        "SELECT DNI FROM cliente WHERE DNI = ?"
    );

    // Colocar el DNI recibido en la consulta
    $consulta->bind_param("s", $dni);

    // Ejecutar la busqueda
    $consulta->execute();

    // Guardar el resultado de la consulta
    $consulta->store_result();

    // Si el DNI ya existe, no permitir registrar otro cliente
    if ($consulta->num_rows > 0) {
        // Cerrar la consulta
        $consulta->close();

        // Indicar que existe un conflicto con los datos
        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Ya existe un cliente con ese DNI"
        ], JSON_UNESCAPED_UNICODE);

        // Cerrar la conexion
        $conexion->close();
        exit;
    }

    // Cerrar la consulta de busqueda
    $consulta->close();

    // Guardar el cliente utilizando una consulta preparada
    $insertar = $conexion->prepare(
        "INSERT INTO cliente
        (DNI, nombre, apellido, telefono, email)
        VALUES (?, ?, ?, ?, ?)"
    );

    // Si el telefono o el correo estan vacios, guardar NULL
    $telefonoGuardar = $telefono !== "" ? $telefono : null;
    $emailGuardar = $email !== "" ? $email : null;

    // Colocar los datos del cliente en la consulta
    $insertar->bind_param(
        "sssss",
        $dni,
        $nombre,
        $apellido,
        $telefonoGuardar,
        $emailGuardar
    );

    // Ejecutar el registro del cliente
    $insertar->execute();

    // Cerrar la consulta
    $insertar->close();

    // Informar que el cliente se registro correctamente
    echo json_encode([
        "success" => true,
        "message" => "Cliente registrado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    // Cerrar la conexion con la base de datos
    $conexion->close();

} catch (Throwable $e) {
    // Guardar el detalle del error en el registro de errores
    error_log("Error al registrar cliente: " . $e->getMessage());

    // Indicar que ocurrio un error en el servidor
    http_response_code(500);

    // Enviar un mensaje general de error
    echo json_encode([
        "success" => false,
        "message" => "No se pudo registrar el cliente"
    ], JSON_UNESCAPED_UNICODE);
}
