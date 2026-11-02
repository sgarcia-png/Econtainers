
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
$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$email = trim($_POST["email"] ?? "");

if ($dni === "" || $nombre === "" || $apellido === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "DNI, nombre y apellido son obligatorios"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

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

    $consulta->bind_param("s", $dni);
    $consulta->execute();
    $consulta->store_result();

    if ($consulta->num_rows > 0) {
        $consulta->close();

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Ya existe un cliente con ese DNI"
        ], JSON_UNESCAPED_UNICODE);

        $conexion->close();
        exit;
    }

    $consulta->close();

    // Guardar el cliente utilizando una consulta preparada
    $insertar = $conexion->prepare(
        "INSERT INTO cliente
        (DNI, nombre, apellido, telefono, email)
        VALUES (?, ?, ?, ?, ?)"
    );

    $telefonoGuardar = $telefono !== "" ? $telefono : null;
    $emailGuardar = $email !== "" ? $email : null;

    $insertar->bind_param(
        "sssss",
        $dni,
        $nombre,
        $apellido,
        $telefonoGuardar,
        $emailGuardar
    );

    $insertar->execute();
    $insertar->close();

    echo json_encode([
        "success" => true,
        "message" => "Cliente registrado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();

} catch (Throwable $e) {
    error_log("Error al registrar cliente: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No se pudo registrar el cliente"
    ], JSON_UNESCAPED_UNICODE);
}
