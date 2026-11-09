<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir los datos enviados desde JavaScript
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        throw new Exception("Los datos enviados no son válidos");
    }

    $codigo = trim($datos["codigo"] ?? "");
    $id_tipo = filter_var(
        $datos["id_tipo"] ?? null,
        FILTER_VALIDATE_INT
    );
    $deposito = trim($datos["deposito"] ?? "");

    // Comprobar que se hayan completado los datos
    if ($codigo === "" || !$id_tipo || $deposito === "") {
        throw new Exception("Completá todos los campos");
    }

    // Comprobar la cantidad de caracteres del código
    if (strlen($codigo) > 30) {
        throw new Exception("El código no puede superar los 30 caracteres");
    }

    // Depósitos permitidos
    $depositos_validos = [
        "Bahía Blanca",
        "Pringles",
        "Junín",
        "Santiago"
    ];

    if (!in_array($deposito, $depositos_validos, true)) {
        throw new Exception("El depósito seleccionado no es válido");
    }

    // Comprobar que el tipo exista
    $consulta_tipo = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE id_tipo = ?"
    );

    $consulta_tipo->bind_param("i", $id_tipo);
    $consulta_tipo->execute();

    $resultado_tipo = $consulta_tipo->get_result();

    if ($resultado_tipo->num_rows === 0) {
        throw new Exception("El tipo de contenedor seleccionado no existe");
    }

    // Registrar el contenedor como Disponible
    $estado = "Disponible";

    $insertar = $conexion->prepare(
        "INSERT INTO contenedor (codigo, deposito, id_tipo, estado)
         VALUES (?, ?, ?, ?)"
    );

    $insertar->bind_param(
        "ssis",
        $codigo,
        $deposito,
        $id_tipo,
        $estado
    );

    $insertar->execute();

    echo json_encode(
        [
            "ok" => true,
            "mensaje" => "El contenedor se registró correctamente"
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    error_log("Error al registrar contenedor: " . $e->getMessage());

    http_response_code(500);

    if ($e->getCode() == 1062) {
        $mensaje = "Ya existe un contenedor con ese código";
    } else {
        $mensaje = "No se pudo registrar el contenedor";
    }

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}