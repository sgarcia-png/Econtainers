
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
    // Comprobar que el cliente exista
    $buscar = $conexion->prepare(
        "SELECT DNI FROM cliente WHERE DNI = ?"
    );

    $buscar->bind_param("s", $dni);
    $buscar->execute();
    $buscar->store_result();

    $existe = $buscar->num_rows > 0;

    $buscar->close();

    if (!$existe) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "No se encontro el cliente"
        ], JSON_UNESCAPED_UNICODE);

        $conexion->close();
        exit;
    }

    // Actualizar los datos sin cambiar el DNI
    $actualizar = $conexion->prepare(
        "UPDATE cliente
         SET nombre = ?, apellido = ?, telefono = ?, email = ?
         WHERE DNI = ?"
    );

    $telefonoGuardar = $telefono !== "" ? $telefono : null;
    $emailGuardar = $email !== "" ? $email : null;

    $actualizar->bind_param(
        "sssss",
        $nombre,
        $apellido,
        $telefonoGuardar,
        $emailGuardar,
        $dni
    );

    $actualizar->execute();
    $actualizar->close();

    echo json_encode([
        "success" => true,
        "message" => "Cliente actualizado correctamente"
    ], JSON_UNESCAPED_UNICODE);

    $conexion->close();

} catch (Throwable $e) {
    error_log("Error al actualizar cliente: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No se pudo actualizar el cliente"
    ], JSON_UNESCAPED_UNICODE);
}
