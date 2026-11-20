
<?php

// Indicar que la respuesta sera en formato JSON.
header("Content-Type: application/json; charset=utf-8");

// Iniciar la sesion.
session_start();

// Comprobar que la administradora haya iniciado sesion.
if (!isset($_SESSION["administradora_id"])) {

    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No tenes permiso para consultar esta informacion"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Conectar con la base de datos.
require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir el numero de venta desde JavaScript.
    $nro_venta = filter_input(
        INPUT_GET,
        "nro_venta",
        FILTER_VALIDATE_INT
    );

    // Comprobar que el numero de venta sea valido.
    if (!$nro_venta || $nro_venta <= 0) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "El numero de venta no es valido"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Consultar los contenedores correspondientes a la venta.
    $consulta = $conexion->prepare(
        "SELECT
            c.codigo,
            tc.nombre_tipo,
            c.deposito,
            rv.precio
         FROM renglon_venta rv
         INNER JOIN contenedor c
            ON c.codigo = rv.codigo_contenedor
         INNER JOIN tipo_contenedor tc
            ON tc.id_tipo = c.id_tipo
         WHERE rv.nro_venta = ?
         ORDER BY c.codigo"
    );

    // Asociar el numero de venta con la consulta.
    $consulta->bind_param("i", $nro_venta);

    // Ejecutar la consulta.
    $consulta->execute();

    // Obtener los resultados.
    $resultado = $consulta->get_result();

    // Crear un arreglo para guardar los contenedores.
    $contenedores = [];

    // Variable para sumar los precios.
    $total = 0;

    // Recorrer los contenedores encontrados.
    while ($fila = $resultado->fetch_assoc()) {

        // Sumar el precio de cada contenedor.
        $total += (float)$fila["precio"];

        // Agregar el contenedor al arreglo.
        $contenedores[] = $fila;
    }

    // Devolver los datos de la venta.
    echo json_encode([
        "ok" => true,
        "nro_venta" => $nro_venta,
        "cantidad" => count($contenedores),
        "total" => $total,
        "datos" => $contenedores
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {

    // Guardar el detalle del error.
    error_log("Error al consultar el detalle de la venta: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo consultar el detalle de la venta"
    ], JSON_UNESCAPED_UNICODE);
}
