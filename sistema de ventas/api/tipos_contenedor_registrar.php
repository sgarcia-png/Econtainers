<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

// Recibir los datos enviados desde JavaScript
$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombreRecibido = trim($datos["nombre_tipo"] ?? "");
$valor = $datos["valor"] ?? "";

$nombreNormalizado = strtolower($nombreRecibido);

// Estos son los unicos tipos permitidos en el sistema
$tiposPermitidos = [
    "20 pies" => "20 pies",
    "40 pies" => "40 pies",
    "40hc" => "40HC",
    "reefer" => "reefer"
];

if (!isset($tiposPermitidos[$nombreNormalizado])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Solo se permiten los tipos 20 pies, 40 pies, 40HC y reefer"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_numeric($valor) || (float)$valor <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "El valor debe ser un numero mayor que cero"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombreTipo = $tiposPermitidos[$nombreNormalizado];
$valor = (float)$valor;

try {
    // Comprobar que el tipo todavia no exista
    $consulta = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE nombre_tipo = ?"
    );
    $consulta->bind_param("s", $nombreTipo);
    $consulta->execute();
    $resultado = $consulta->get_result();

    if ($resultado->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ese tipo de contenedor ya esta registrado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Guardar el nuevo tipo
    $consulta = $conexion->prepare(
        "INSERT INTO tipo_contenedor (nombre_tipo, valor) VALUES (?, ?)"
    );
    $consulta->bind_param("sd", $nombreTipo, $valor);
    $consulta->execute();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor registrado correctamente"
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {
    error_log("Error al registrar tipo de contenedor: " . $e->getMessage());
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo registrar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}