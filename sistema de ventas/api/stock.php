
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

try {

    // Consultar los contenedores y relacionarlos con sus tipos.
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

    // Crear un arreglo vacio para guardar los contenedores encontrados.
    $contenedores = [];

    // Recorrer los resultados de la consulta y guardar cada contenedor.
    while ($fila = $consulta->fetch_assoc()) {
        $contenedores[] = $fila;
    }

    // Enviar los datos obtenidos en formato JSON.
    echo json_encode(
        [
            "ok" => true,
            "datos" => $contenedores
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error al consultar el stock: " . $e->getMessage());

    // Indicar que ocurrio un error interno en el servidor.
    http_response_code(500);

    // Enviar un mensaje para informar que no se pudo consultar el stock.
    echo json_encode(
        [
            "ok" => false,
            "mensaje" => "No se pudo consultar el stock"
        ],
        JSON_UNESCAPED_UNICODE
    );
}
