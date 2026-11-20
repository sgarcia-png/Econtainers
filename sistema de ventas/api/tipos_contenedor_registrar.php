
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

// Recibir los datos enviados desde JavaScript
// Leer los datos enviados desde el formulario y convertirlos en un arreglo.
$datos = json_decode(file_get_contents("php://input"), true);

// Comprobar que los datos recibidos tengan el formato esperado.
if (!is_array($datos)) {

    // Indicar que los datos recibidos son incorrectos.
    http_response_code(400);

    // Devolver un mensaje de error en formato JSON.
    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar la ejecucion del archivo.
    exit;
}

// Obtener el nombre del tipo y eliminar los espacios al principio y al final.
$nombreRecibido = trim($datos["nombre_tipo"] ?? "");

// Obtener el valor recibido desde el formulario.
$valor = $datos["valor"] ?? "";

// Convertir el nombre recibido a minusculas para facilitar su comparacion.
$nombreNormalizado = strtolower($nombreRecibido);

// Estos son los unicos tipos permitidos en el sistema
// Crear una lista con los nombres de los tipos permitidos y sus nombres correspondientes.
$tiposPermitidos = [
    "20 pies" => "20 pies",
    "40 pies" => "40 pies",
    "40hc" => "40HC",
    "reefer" => "reefer"
];

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

    // Devolver un mensaje explicando el problema.
    echo json_encode([
        "ok" => false,
        "mensaje" => "El valor debe ser un numero mayor que cero"
    ], JSON_UNESCAPED_UNICODE);

    // Finalizar la ejecucion del archivo.
    exit;
}

// Obtener el nombre correspondiente al tipo seleccionado en la lista.
$nombreTipo = $tiposPermitidos[$nombreNormalizado];

// Convertir el valor recibido a numero decimal.
$valor = (float)$valor;

// Comenzar el bloque donde se realizan las consultas y se controlan los errores.
try {
    // Comprobar que el tipo todavia no exista
    // Buscar si ya existe un registro con el mismo nombre.
    $consulta = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE nombre_tipo = ?"
    );

    // Asociar el nombre del tipo con el signo de pregunta.
    $consulta->bind_param("s", $nombreTipo);

    // Ejecutar la consulta.
    $consulta->execute();

    // Obtener los resultados de la busqueda.
    $resultado = $consulta->get_result();

    // Comprobar si se encontro un tipo con ese nombre.
    if ($resultado->num_rows > 0) {

        // Indicar que ya existe un tipo con ese nombre.
        http_response_code(409);

        // Devolver un mensaje informando que no se puede repetir el tipo.
        echo json_encode([
            "ok" => false,
            "mensaje" => "Ese tipo de contenedor ya esta registrado"
        ], JSON_UNESCAPED_UNICODE);

        // Finalizar la ejecucion para evitar registrar un tipo duplicado.
        exit;
    }

    // Guardar el nuevo tipo
    // Preparar la consulta para insertar el nombre y el valor en la tabla.
    $consulta = $conexion->prepare(
        "INSERT INTO tipo_contenedor (nombre_tipo, valor) VALUES (?, ?)"
    );

    // Asociar el nombre del tipo y su valor con los signos de pregunta.
    $consulta->bind_param("sd", $nombreTipo, $valor);

    // Ejecutar la insercion en la base de datos.
    $consulta->execute();

    // Devolver una respuesta indicando que el registro fue correcto.
    echo json_encode([
        "ok" => true,
        "mensaje" => "Tipo de contenedor registrado correctamente"
    ], JSON_UNESCAPED_UNICODE);

// Capturar los errores que puedan ocurrir durante las consultas a MySQL.
} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error al registrar tipo de contenedor: " . $e->getMessage());

    // Indicar que ocurrio un error interno del servidor.
    http_response_code(500);

    // Devolver un mensaje indicando que no se pudo completar el registro.
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo registrar el tipo de contenedor"
    ], JSON_UNESCAPED_UNICODE);
}
