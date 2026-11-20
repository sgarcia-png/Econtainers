
<?php

// Indicar que la respuesta de esta API se envia en formato JSON
header("Content-Type: application/json; charset=utf-8");

// Incluir el archivo de conexion con la base de datos
require_once __DIR__ . "/../config/conexion.php";

// Esta API solamente permite registrar ventas mediante POST
// Comprobar que el metodo de la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    // Codigo 405: el metodo utilizado no esta permitido
    http_response_code(405);

    // Enviar un mensaje de error en formato JSON
    echo json_encode([
        "ok" => false,
        "mensaje" => "Este archivo solamente acepta solicitudes POST."
    ], JSON_UNESCAPED_UNICODE);

    // Detener la ejecucion del archivo
    exit;
}

// Recibir los datos enviados desde JavaScript
// php://input permite leer el contenido que llega en la solicitud
// json_decode convierte el JSON recibido en un arreglo de PHP
$datos = json_decode(file_get_contents("php://input"), true);

// Comprobar que los datos tengan el formato esperado
// Se necesita el DNI del cliente y una lista de contenedores
if (
    !is_array($datos) ||
    empty($datos["dni_cliente"]) ||
    empty($datos["contenedores"]) ||
    !is_array($datos["contenedores"])
) {

    // Codigo 400: los datos recibidos son incorrectos o estan incompletos
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Faltan el DNI del cliente o los contenedores"
    ], JSON_UNESCAPED_UNICODE);

    // Detener el archivo si faltan datos necesarios
    exit;
}

// Obtener el DNI y quitar espacios innecesarios
$dni_cliente = trim((string) $datos["dni_cliente"]);

// Obtener la fecha enviada o utilizar la fecha actual si no se envio
$fecha = $datos["fecha"] ?? date("Y-m-d");

// Establecer el estado inicial de pago de la venta
$estado_pago = "Pendiente";

// Crear un arreglo donde se guardaran los codigos de los contenedores
$codigos = [];

// Validar la fecha recibida
// createFromFormat intenta crear una fecha con el formato indicado
$fechaValida = DateTime::createFromFormat("!Y-m-d", $fecha);

if (
    !$fechaValida ||
    $fechaValida->format("Y-m-d") !== $fecha
) {

    // Codigo 400 porque la fecha no tiene el formato correcto
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "La fecha debe tener el formato AAAA-MM-DD"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Recorrer los contenedores recibidos desde JavaScript
// Comprobar que cada uno tenga un codigo valido y que no se repita
foreach ($datos["contenedores"] as $contenedor) {

    // Verificar que cada elemento sea un arreglo
    // y que tenga un codigo de tipo texto
    if (
        !is_array($contenedor) ||
        empty($contenedor["codigo"]) ||
        !is_string($contenedor["codigo"])
    ) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Cada contenedor debe incluir su codigo"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Obtener el codigo y quitar espacios al principio y al final
    $codigo = trim($contenedor["codigo"]);

    // Comprobar que el mismo codigo no haya sido agregado antes
    if (isset($codigos[$codigo])) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "No se puede repetir un contenedor en la venta"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // Guardar el codigo como una clave del arreglo
    $codigos[$codigo] = true;
}

// Comprobar que la venta tenga al menos un contenedor
if (count($codigos) === 0) {

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "La venta debe incluir al menos un contenedor"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    // Iniciar una transaccion para guardar todo junto
    // Esto permite confirmar todos los cambios o deshacerlos si algo falla
    $conexion->begin_transaction();

    // Verificar que el cliente exista en la base de datos
    $consultaCliente = $conexion->prepare(
        "SELECT DNI
         FROM cliente
         WHERE DNI = ?"
    );

    // Reemplazar el signo ? por el DNI recibido
    $consultaCliente->bind_param("s", $dni_cliente);

    // Ejecutar la consulta
    $consultaCliente->execute();

    // Si no se encuentra el cliente, generar un error
    if ($consultaCliente->get_result()->num_rows === 0) {
        throw new DomainException("El cliente indicado no existe");
    }

    // Cerrar la consulta
    $consultaCliente->close();

    // Arreglo donde se guardaran los contenedores y sus precios
    $contenedoresVenta = [];

    // Variable que acumula el precio de todos los contenedores
    $monto_total = 0;

    // Preparar la consulta para buscar cada contenedor
    // Tambien obtiene su precio desde la tabla tipo_contenedor
    $consultaContenedor = $conexion->prepare(
        "SELECT c.codigo, c.estado, t.valor
         FROM contenedor AS c
         INNER JOIN tipo_contenedor AS t
             ON c.id_tipo = t.id_tipo
         WHERE c.codigo = ?
         FOR UPDATE"
    );

    // Recorrer los codigos de los contenedores seleccionados
    foreach (array_keys($codigos) as $codigo) {

        // Colocar el codigo en la consulta
        $consultaContenedor->bind_param("s", $codigo);

        // Ejecutar la consulta
        $consultaContenedor->execute();

        // Obtener el resultado de la consulta
        $resultado = $consultaContenedor->get_result();

        // Obtener los datos del contenedor encontrado
        $fila = $resultado->fetch_assoc();

        // Comprobar que el contenedor exista
        if (!$fila) {
            throw new DomainException(
                "No existe el contenedor " . $codigo . "."
            );
        }

        // Comprobar que el contenedor siga disponible
        if ($fila["estado"] !== "Disponible") {
            throw new DomainException(
                "El contenedor " . $codigo . " no esta disponible"
            );
        }

        // Obtener el precio guardado en la base de datos
        $precio = (float) $fila["valor"];

        // Comprobar que el precio sea mayor que cero
        if ($precio <= 0) {
            throw new DomainException(
                "El precio del contenedor " . $codigo . " no es valido"
            );
        }

        // Guardar el codigo y el precio del contenedor
        $contenedoresVenta[] = [
            "codigo" => $fila["codigo"],
            "precio" => $precio
        ];

        // Sumar el precio al importe total de la venta
        $monto_total += $precio;
    }

    // Cerrar la consulta de contenedores
    $consultaContenedor->close();

    // Crear el registro principal de la venta
    // MySQL genera nro_venta automaticamente
    $insertarVenta = $conexion->prepare(
        "INSERT INTO ventas
            (fecha, monto_total, estado_pago, dni_cliente)
         VALUES (?, ?, ?, ?)"
    );

    // Enviar los valores a la consulta
    // s = texto, d = numero decimal
    $insertarVenta->bind_param(
        "sdss",
        $fecha,
        $monto_total,
        $estado_pago,
        $dni_cliente
    );

    // Ejecutar el INSERT para guardar la venta
    $insertarVenta->execute();

    // Obtener el numero de venta generado automaticamente por MySQL
    $nro_venta = $conexion->insert_id;

    // Cerrar la consulta
    $insertarVenta->close();

    // Preparar el registro de los contenedores que forman parte de la venta
    $insertarRenglon = $conexion->prepare(
        "INSERT INTO renglon_venta
            (precio, codigo_contenedor, nro_venta)
         VALUES (?, ?, ?)"
    );

    // Preparar la consulta que cambia el estado del contenedor
    // Solo modifica contenedores que todavia esten disponibles
    $actualizarContenedor = $conexion->prepare(
        "UPDATE contenedor
         SET estado = 'Vendido'
         WHERE codigo = ? AND estado = 'Disponible'"
    );

    // Recorrer los contenedores que se van a registrar en la venta
    foreach ($contenedoresVenta as $item) {

        // Obtener el precio y el codigo de cada contenedor
        $precio = $item["precio"];
        $codigo = $item["codigo"];

        // Relacionar el precio, el codigo y el numero de venta
        $insertarRenglon->bind_param(
            "dsi",
            $precio,
            $codigo,
            $nro_venta
        );

        // Guardar el contenedor en la tabla renglon_venta
        $insertarRenglon->execute();

        // Cambiar el estado para impedir una segunda venta
        $actualizarContenedor->bind_param("s", $codigo);
        $actualizarContenedor->execute();

        // Comprobar que se haya actualizado exactamente un contenedor
        if ($actualizarContenedor->affected_rows !== 1) {
            throw new DomainException(
                "No se pudo actualizar el estado del contenedor " .
                $codigo . "."
            );
        }
    }

    // Cerrar las consultas utilizadas para guardar los renglones y actualizar el stock
    $insertarRenglon->close();
    $actualizarContenedor->close();

    // Confirmar todos los cambios realizados en la transaccion
    $conexion->commit();

    // Enviar una respuesta indicando que la venta se registro correctamente
    echo json_encode([
        "ok" => true,
        "mensaje" => "La venta se registro correctamente",
        "datos" => [
            "nro_venta" => $nro_venta,
            "fecha" => $fecha,
            "monto_total" => $monto_total,
            "estado_pago" => $estado_pago,
            "cantidad_contenedores" => count($contenedoresVenta)
        ]
    ], JSON_UNESCAPED_UNICODE);

// Capturar errores relacionados con los datos de la venta
} catch (DomainException $e) {

    // Deshacer los cambios si los datos no son validos
    // De esta manera no queda una venta guardada a medias
    $conexion->rollback();

    // Codigo 400: los datos de la solicitud no son validos
    http_response_code(400);

    // Enviar el motivo del error
    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

// Capturar otros errores inesperados
} catch (Throwable $e) {

    // Deshacer los cambios si ocurre un error inesperado
    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {

        // Guardar un mensaje si tambien falla el proceso de deshacer
        error_log("Error al deshacer la transaccion");
    }

    // Guardar el detalle del error en el registro del servidor
    error_log("Error al registrar venta: " . $e->getMessage());

    // Codigo 500: ocurrio un error interno del servidor
    http_response_code(500);

    // Enviar un mensaje general sin mostrar detalles internos
    echo json_encode([
        "ok" => false,
        "mensaje" => "Ocurrio un error al registrar la venta"
    ], JSON_UNESCAPED_UNICODE);
}
