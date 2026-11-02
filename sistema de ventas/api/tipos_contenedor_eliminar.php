<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

$datos = json_decode(file_get_contents("php://input"), true);
$idTipo = is_array($datos) ? ($datos["id_tipo"] ?? "") : "";

if (!is_numeric($idTipo) || (int)$idTipo <= 0) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "El identificador del tipo no es valido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idTipo = (int)$idTipo;

try {
    // Comprobar que el tipo exista
    $consulta = $conexion->prepare(
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
    }

    // Comprobar que ningun contenedor este usando ese tipo
    $consulta = $conexion->prepare(
        "SELECT codigo FROM contenedor WHERE id_tipo = ? LIMIT 1"
    );
    $consulta->bind_param("i", $idTipo);
    $consulta->execute();

    if ($consulta->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se puede eliminar porque hay contenedores asociados a este tipo"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Eliminar el tipo si no esta siendo utilizado
    $consulta = $conexion->prepare(
        "DELETE FROM tipo_contenedor WHERE id_tipo = ?"
    );
    $consulta->bind_param("i", $idTipo);
    $consulta->execute();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor eliminado correctamente"
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {
    error_log("Error al eliminar tipo de contenedor: " . $e->getMessage());
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo eliminar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}