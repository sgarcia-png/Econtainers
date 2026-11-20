
<?php

// Indicar que la respuesta del archivo sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo que permite conectarse a la base de datos.
require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir los datos enviados desde JavaScript
    // Leer los datos enviados desde el formulario y convertirlos a un arreglo.
    $datos = json_decode(file_get_contents("php://input"), true);

    // Comprobar que los datos recibidos tengan el formato esperado.
    if (!is_array($datos)) {
        throw new Exception("Los datos enviados no son válidos");
    }

    // Obtener el codigo y el deposito, eliminando espacios al principio y al final.
    $codigo = trim($datos["codigo"] ?? "");

    // Obtener y comprobar que el identificador del tipo sea un numero entero.
    $id_tipo = filter_var(
        $datos["id_tipo"] ?? null,
        FILTER_VALIDATE_INT
    );

    // Obtener el deposito seleccionado.
    $deposito = trim($datos["deposito"] ?? "");

    // Comprobar que se hayan completado los datos
    // Verificar que ninguno de los campos obligatorios este vacio.
    if ($codigo === "" || !$id_tipo || $deposito === "") {
        throw new Exception("Completá todos los campos");
    }

    // Comprobar la cantidad de caracteres del código
    // Verificar que el codigo no supere el limite permitido.
    if (strlen($codigo) > 30) {
        throw new Exception("El código no puede superar los 30 caracteres");
    }

    // Depósitos permitidos
    // Lista de depositos que se pueden seleccionar en el formulario.
    $depositos_validos = [
        "Bahía Blanca",
        "Pringles",
        "Junín",
        "Santiago"
    ];

    // Comprobar que el deposito recibido este dentro de la lista permitida.
    if (!in_array($deposito, $depositos_validos, true)) {
        throw new Exception("El depósito seleccionado no es válido");
    }

    // Comprobar que el tipo exista
    // Preparar una consulta para comprobar que el tipo exista en la base de datos.
    $consulta_tipo = $conexion->prepare(
        "SELECT id_tipo FROM tipo_contenedor WHERE id_tipo = ?"
    );

    // Reemplazar el signo de pregunta por el identificador recibido.
    $consulta_tipo->bind_param("i", $id_tipo);

    // Ejecutar la consulta.
    $consulta_tipo->execute();

    // Obtener el resultado de la consulta.
    $resultado_tipo = $consulta_tipo->get_result();

    // Comprobar si no se encontro ningun tipo con ese identificador.
    if ($resultado_tipo->num_rows === 0) {
        throw new Exception("El tipo de contenedor seleccionado no existe");
    }

    // Registrar el contenedor como Disponible
    // Establecer el estado inicial del contenedor.
    $estado = "Disponible";

    // Preparar la consulta para insertar el nuevo contenedor en la tabla.
    $insertar = $conexion->prepare(
        "INSERT INTO contenedor (codigo, deposito, id_tipo, estado)
         VALUES (?, ?, ?, ?)"
    );

    // Asociar los valores con los signos de pregunta de la consulta.
    $insertar->bind_param(
        "ssis",
        $codigo,
        $deposito,
        $id_tipo,
        $estado
    );

    // Ejecutar la insercion en la base de datos.
    $insertar->execute();

    // Enviar una respuesta indicando que el registro fue correcto.
    echo json_encode(
        [
            "ok" => true,
            "mensaje" => "El contenedor se registró correctamente"
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (mysqli_sql_exception $e) {

    // Guardar los detalles del error en el registro del servidor.
    error_log("Error al registrar contenedor: " . $e->getMessage());

    // Indicar que ocurrio un error interno en el servidor.
    http_response_code(500);

    // Comprobar si el error se produjo porque el codigo ya existe.
    if ($e->getCode() == 1062) {
        $mensaje = "Ya existe un contenedor con ese código";
    } else {
        // Si es otro error de MySQL, mostrar un mensaje general.
        $mensaje = "No se pudo registrar el contenedor";
    }

    // Enviar el mensaje de error en formato JSON.
    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $mensaje
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Exception $e) {

    // Indicar que se recibieron datos incorrectos o incompletos.
    http_response_code(400);

    // Enviar el mensaje que explica el problema.
    echo json_encode(
        [
            "ok" => false,
            "mensaje" => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}
