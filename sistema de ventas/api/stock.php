<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    $consulta = $conexion->query(
        "SELECT
            c.codigo,
            c.deposito,
            c.estado,
            t.id_tipo,
            t.nombre_tipo,
            t.valor
         FROM contenedor c
         INNER JOIN tipo_contenedor t
            ON c.id_tipo = t.id_tipo
         ORDER BY c.codigo"
    );

    $contenedores = [];

    while ($fila = $consulta->fetch_assoc()) {
        $contenedores[] = $fila;
    }

    echo json_encode(
        [
            "ok" => true,
            "datos" => $contenedores
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    error_log("Error al consultar el stock: " . $e->getMessage());

    http_response_code(500);

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => "No se pudo consultar el stock"
        ],
        JSON_UNESCAPED_UNICODE
    );
}