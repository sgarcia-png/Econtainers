
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    // Consultar los tipos de contenedores guardados en MySQL
    $consulta = $conexion->query(
        "SELECT id_tipo, nombre_tipo, valor
         FROM tipo_contenedor
         ORDER BY id_tipo"
    );

    $tipos = [];

    while ($fila = $consulta->fetch_assoc()) {
        $tipos[] = $fila;
    }

    // Devolver los resultados en formato JSON
    echo json_encode(
        [
            "ok" => true,
            "datos" => $tipos
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    error_log("Error en la API de tipos de contenedor: " . $e->getMessage());

    http_response_code(500);

    echo json_encode(
        [
            "ok" => false,
            "mensaje" => "No se pudieron consultar los tipos de contenedores"
        ],
        JSON_UNESCAPED_UNICODE
    );
}
