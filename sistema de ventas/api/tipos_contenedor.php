
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

try {

    // Consultar los tipos de contenedores guardados en MySQL
    // Obtener el identificador, el nombre y el valor de cada tipo de contenedor.
    $consulta = $conexion->query(
        "SELECT id_tipo, nombre_tipo, valor
         FROM tipo_contenedor
         ORDER BY id_tipo"
    );

    // Crear un arreglo vacio donde se guardaran los tipos encontrados.
    $tipos = [];

    // Recorrer los resultados de la consulta, uno por uno.
    while ($fila = $consulta->fetch_assoc()) {

        // Agregar cada tipo de contenedor al arreglo.
        $tipos[] = $fila;
    }

    // Devolver los resultados en formato JSON
    // Enviar todos los tipos encontrados para que JavaScript pueda utilizarlos.
    echo json_encode(
        [
            "ok" => true,
            "datos" => $tipos
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error en la API de tipos de contenedor: " . $e->getMessage());

    // Indicar que ocurrio un error interno en el servidor.
    http_response_code(500);

    // Enviar una respuesta indicando que no se pudieron obtener los tipos.
    echo json_encode(
        [
            "ok" => false,
            "mensaje" => "No se pudieron consultar los tipos de contenedores"
        ],
        JSON_UNESCAPED_UNICODE
    );
}
