
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

// Recibir los datos enviados desde JavaScript y convertirlos en un arreglo.
$datos = json_decode(file_get_contents("php://input"), true);

// Obtener el identificador del tipo si los datos recibidos son un arreglo.
$idTipo = is_array($datos) ? ($datos["id_tipo"] ?? "") : "";

// Comprobar que el identificador sea numerico y mayor que cero.
if (!is_numeric($idTipo) || (int)$idTipo <= 0) {

    // Indicar que se recibio un dato incorrecto.
    http_response_code(400);

    // Devolver un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "El identificador del tipo no es valido"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar la ejecucion del archivo.
    exit;
}

// Convertir el identificador recibido a numero entero.
$idTipo = (int)$idTipo;

// Comenzar el bloque donde se realizan las consultas y se controlan los errores.
try {
    // Comprobar que el tipo exista
    // Preparar una consulta para buscar el tipo de contenedor por su identificador.
    $consulta = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE id_tipo = ?"
    );

    // Asociar el identificador con el signo de pregunta de la consulta.
    $consulta->bind_param("i", $idTipo);

    // Ejecutar la consulta.
    $consulta->execute();

    // Comprobar si no se encontro ningun registro.
    if ($consulta->get_result()->num_rows === 0) {

        // Indicar que el registro no fue encontrado.
        http_response_code(404);

        // Devolver un mensaje informando que el tipo no existe.
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se encontro el tipo de contenedor"
        ], JSON_UNESCAPED_UNICODE);

        // Finalizar la ejecucion del archivo.
        exit;
    }

    // Comprobar que ningun contenedor este usando ese tipo
    // Buscar si existe algun contenedor relacionado con el tipo seleccionado.
    $consulta = $conexion->prepare(
        "SELECT codigo FROM contenedor WHERE id_tipo = ? LIMIT 1"
    );

    // Asociar el identificador del tipo con la consulta.
    $consulta->bind_param("i", $idTipo);

    // Ejecutar la consulta.
    $consulta->execute();

    // Comprobar si se encontro algun contenedor que utilice ese tipo.
    if ($consulta->get_result()->num_rows > 0) {

        // Indicar que no se puede realizar la eliminacion.
        http_response_code(409);

        // Devolver un mensaje explicando el motivo.
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se puede eliminar porque hay contenedores asociados a este tipo"
        ], JSON_UNESCAPED_UNICODE);

        // Finalizar la ejecucion del archivo.
        exit;
    }

    // Eliminar el tipo si no esta siendo utilizado
    // Preparar la consulta para eliminar el tipo de contenedor seleccionado.
    $consulta = $conexion->prepare(
        "DELETE FROM tipo_contenedor WHERE id_tipo = ?"
    );

    // Asociar el identificador con el signo de pregunta.
    $consulta->bind_param("i", $idTipo);

    // Ejecutar la eliminacion en la base de datos.
    $consulta->execute();

    // Devolver una respuesta indicando que la eliminacion fue correcta.
    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor eliminado correctamente"
    ], JSON_UNESCAPED_UNICODE);

// Capturar los errores que puedan ocurrir durante las consultas a MySQL.
} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error al eliminar tipo de contenedor: " . $e->getMessage());

    // Indicar que ocurrio un error interno del servidor.
    http_response_code(500);

    // Devolver un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo eliminar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}
