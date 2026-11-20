
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

// Recibir los datos enviados desde JavaScript y convertirlos en un arreglo.
$datos = json_decode(file_get_contents("php://input"), true);

// Comprobar que los datos recibidos tengan el formato esperado.
if (!is_array($datos)) {

    // Indicar que los datos enviados no son correctos.
    http_response_code(400);

    // Devolver un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar el archivo para evitar que continue ejecutandose.
    exit;
}

// Obtener el identificador del tipo de contenedor.
$idTipo = $datos["id_tipo"] ?? "";

// Obtener el nombre del tipo y eliminar los espacios al principio y al final.
$nombreRecibido = trim($datos["nombre_tipo"] ?? "");

// Obtener el valor recibido.
$valor = $datos["valor"] ?? "";

// Crear una lista con los nombres de tipos permitidos y sus nombres correspondientes.
$tiposPermitidos = [
    "20 pies" => "20 pies",
    "40 pies" => "40 pies",
    "40 pies hc" => "40 pies HC",
    "reefer" => "reefer"
];

// Convertir el nombre recibido a minusculas para facilitar su comparacion.
$nombreNormalizado = strtolower($nombreRecibido);

// Comprobar que el identificador sea numerico y mayor que cero.
if (!is_numeric($idTipo) || (int)$idTipo <= 0) {

    // Indicar que se recibio un identificador incorrecto.
    http_response_code(400);

    // Devolver el mensaje de error.
    echo json_encode([
        "ok" => false,
        "mensaje" => "El identificador del tipo no es valido"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar la ejecucion del archivo.
    exit;
}

// Este bloque esta comentado, por lo tanto actualmente no se ejecuta.
/*if (!isset($tiposPermitidos[$nombreNormalizado])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Solo se permiten los tipos 20 pies, 40 pies, 40HC y reefer"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}*/

// Comprobar que el valor sea numerico y mayor que cero.
if (!is_numeric($valor) || (float)$valor <= 0) {

    // Indicar que el valor recibido no es valido.
    http_response_code(400);

    // Devolver el mensaje de error.
    echo json_encode([
        "ok" => false,
        "mensaje" => "El valor debe ser un numero mayor que cero"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar la ejecucion del archivo.
    exit;
}

// Convertir el identificador a numero entero.
$idTipo = (int)$idTipo;

// Esta linea esta comentada y actualmente no se ejecuta.
//$nombreTipo = $tiposPermitidos[$nombreNormalizado];

// Convertir el valor recibido a numero decimal.
$valor = (float)$valor;

// Comenzar el bloque donde se realiza la consulta a MySQL y se controlan sus errores.
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
    // Preparar la consulta que actualiza el valor del tipo seleccionado.
    $consulta = $conexion->prepare(
        "UPDATE tipo_contenedor
         SET  valor = ?
         WHERE id_tipo = ?"
    );

    // Asociar el valor decimal y el identificador con los signos de pregunta.
    $consulta->bind_param("di", $valor, $idTipo);

    // Ejecutar la actualizacion en la base de datos.
    $consulta->execute();

    // Devolver una respuesta indicando que la operacion fue correcta.
    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor actualizado correctamente"
    ], JSON_UNESCAPED_UNICODE);

// Capturar los errores que se produzcan durante las consultas a MySQL.
} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error al actualizar tipo de contenedor: " . $e->getMessage());

    // Indicar que ocurrio un error interno en el servidor.
    http_response_code(500);

    // Devolver un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo actualizar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}
