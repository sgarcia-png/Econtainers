<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idTipo = $datos["id_tipo"] ?? "";
$nombreRecibido = trim($datos["nombre_tipo"] ?? "");
$valor = $datos["valor"] ?? "";

$tiposPermitidos = [
    "20 pies" => "20 pies",
    "40 pies" => "40 pies",
    "40 pies hc" => "40 pies HC",
    "reefer" => "reefer"
];

$nombreNormalizado = strtolower($nombreRecibido);

if (!is_numeric($idTipo) || (int)$idTipo <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "El identificador del tipo no es valido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*if (!isset($tiposPermitidos[$nombreNormalizado])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Solo se permiten los tipos 20 pies, 40 pies, 40HC y reefer"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}*/

if (!is_numeric($valor) || (float)$valor <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "El valor debe ser un numero mayor que cero"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idTipo = (int)$idTipo;
//$nombreTipo = $tiposPermitidos[$nombreNormalizado];
$valor = (float)$valor;

try {
    // Comprobar que el tipo exista
   /* $consulta = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE id_tipo = ?"
    );
    $consulta->bind_param("i", $idTipo);
    $consulta->execute();

    if ($consulta->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se encontro el tipo de contenedor"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }*/

    // Comprobar que el nombre no pertenezca a otro registro
    /*$consulta = $conexion->prepare(
        "SELECT id_tipo
         FROM tipo_contenedor
         WHERE nombre_tipo = ? AND id_tipo <> ?"
    );
    $consulta->bind_param("si", $nombreTipo, $idTipo);
    $consulta->execute();

    if ($consulta->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ese tipo ya esta registrado en otro registro"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }*/

    // Actualizar el nombre y el valor
    $consulta = $conexion->prepare(
        "UPDATE tipo_contenedor
         SET  valor = ?
         WHERE id_tipo = ?"
    );
    $consulta->bind_param("di", $valor, $idTipo);
    $consulta->execute();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor actualizado correctamente"
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {
    error_log("Error al actualizar tipo de contenedor: " . $e->getMessage());
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo actualizar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}