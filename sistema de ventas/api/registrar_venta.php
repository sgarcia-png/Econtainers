
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

// Esta API solamente permite registrar ventas mediante POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Este archivo solamente acepta solicitudes POST."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Recibir los datos enviados desde JavaScript
$datos = json_decode(file_get_contents("php://input"), true);

// Comprobar que los datos tengan el formato esperado
if (
    !is_array($datos) ||
    empty($datos["dni_cliente"]) ||
    empty($datos["contenedores"]) ||
    !is_array($datos["contenedores"])
) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Faltan el DNI del cliente o los contenedores"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dni_cliente = trim((string) $datos["dni_cliente"]);
$fecha = $datos["fecha"] ?? date("Y-m-d");
$estado_pago = "Pendiente";
$codigos = [];

// Validar la fecha
$fechaValida = DateTime::createFromFormat("!Y-m-d", $fecha);

if (
    !$fechaValida ||
    $fechaValida->format("Y-m-d") !== $fecha
) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "La fecha debe tener el formato AAAA-MM-DD"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Comprobar que cada contenedor tenga un codigo valido
// y que no se repita dentro de la misma venta
foreach ($datos["contenedores"] as $contenedor) {

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

    $codigo = trim($contenedor["codigo"]);

    if (isset($codigos[$codigo])) {
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "No se puede repetir un contenedor en la venta"
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $codigos[$codigo] = true;
}

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
    $conexion->begin_transaction();

    // Verificar que el cliente exista
    $consultaCliente = $conexion->prepare(
        "SELECT DNI
         FROM cliente
         WHERE DNI = ?"
    );

    $consultaCliente->bind_param("s", $dni_cliente);
    $consultaCliente->execute();

    if ($consultaCliente->get_result()->num_rows === 0) {
        throw new DomainException("El cliente indicado no existe");
    }

    $consultaCliente->close();

    $contenedoresVenta = [];
    $monto_total = 0;

    // Verificar cada contenedor y obtener su precio desde MySQL
    $consultaContenedor = $conexion->prepare(
        "SELECT c.codigo, c.estado, t.valor
         FROM contenedor AS c
         INNER JOIN tipo_contenedor AS t
             ON c.id_tipo = t.id_tipo
         WHERE c.codigo = ?
         FOR UPDATE"
    );

    foreach (array_keys($codigos) as $codigo) {

        $consultaContenedor->bind_param("s", $codigo);
        $consultaContenedor->execute();

        $resultado = $consultaContenedor->get_result();
        $fila = $resultado->fetch_assoc();

        if (!$fila) {
            throw new DomainException(
                "No existe el contenedor " . $codigo . "."
            );
        }

        if ($fila["estado"] !== "Disponible") {
            throw new DomainException(
                "El contenedor " . $codigo . " no esta disponible"
            );
        }

        $precio = (float) $fila["valor"];

        if ($precio <= 0) {
            throw new DomainException(
                "El precio del contenedor " . $codigo . " no es valido"
            );
        }

        $contenedoresVenta[] = [
            "codigo" => $fila["codigo"],
            "precio" => $precio
        ];

        $monto_total += $precio;
    }

    $consultaContenedor->close();

    // Crear la venta. MySQL genera nro_venta automaticamente
    $insertarVenta = $conexion->prepare(
        "INSERT INTO ventas
            (fecha, monto_total, estado_pago, dni_cliente)
         VALUES (?, ?, ?, ?)"
    );

    $insertarVenta->bind_param(
        "sdss",
        $fecha,
        $monto_total,
        $estado_pago,
        $dni_cliente
    );

    $insertarVenta->execute();

    $nro_venta = $conexion->insert_id;
    $insertarVenta->close();

    // Preparar el registro de los contenedores de la venta
    $insertarRenglon = $conexion->prepare(
        "INSERT INTO renglon_venta
            (precio, codigo_contenedor, nro_venta)
         VALUES (?, ?, ?)"
    );

    $actualizarContenedor = $conexion->prepare(
        "UPDATE contenedor
         SET estado = 'Vendido'
         WHERE codigo = ? AND estado = 'Disponible'"
    );

    foreach ($contenedoresVenta as $item) {

        $precio = $item["precio"];
        $codigo = $item["codigo"];

        $insertarRenglon->bind_param(
            "dsi",
            $precio,
            $codigo,
            $nro_venta
        );

        $insertarRenglon->execute();

        // Cambiar el estado para impedir una segunda venta
        $actualizarContenedor->bind_param("s", $codigo);
        $actualizarContenedor->execute();

        if ($actualizarContenedor->affected_rows !== 1) {
            throw new DomainException(
                "No se pudo actualizar el estado del contenedor " .
                $codigo . "."
            );
        }
    }

    $insertarRenglon->close();
    $actualizarContenedor->close();

    // Confirmar todos los cambios
    $conexion->commit();

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

} catch (DomainException $e) {

    // Deshacer los cambios si los datos no son validos
    $conexion->rollback();

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    // Deshacer los cambios si ocurre un error inesperado
    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {
        error_log("Error al deshacer la transaccion");
    }

    error_log("Error al registrar venta: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Ocurrio un error al registrar la venta"
    ], JSON_UNESCAPED_UNICODE);
}
