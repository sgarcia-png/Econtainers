
<?php

// Iniciar la sesion para comprobar que la administradora haya ingresado
session_start();

// Indicar que la respuesta de este archivo sera en formato JSON
header("Content-Type: application/json; charset=utf-8");

// Comprobar que la administradora haya iniciado sesion
if (!isset($_SESSION["administradora_id"])) {
    // Enviar el codigo 401, que indica que no hay autorizacion
    http_response_code(401);

    // Devolver un mensaje de error en formato JSON
    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesion para registrar un pago"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Incluir la conexion con la base de datos
require_once __DIR__ . "/../config/conexion.php";

// Comprobar que la solicitud recibida utilice el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Enviar el codigo 405, que indica que el metodo no esta permitido
    http_response_code(405);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Este metodo no esta permitido"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Recibir los datos enviados desde JavaScript en formato JSON
$datos = json_decode(file_get_contents("php://input"), true);

// Comprobar que los datos recibidos sean un arreglo valido
if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Obtener el numero de venta y comprobar que sea un numero entero
$nroVenta = filter_var(
    $datos["nro_venta"] ?? null,
    FILTER_VALIDATE_INT
);

// Recibir la fecha, el importe y la forma de pago
$fechaPago = $datos["fecha_pago"] ?? "";
$montoPago = $datos["monto_pago"] ?? "";
$formaPago = trim($datos["forma_pago"] ?? "");

// Guardar las unicas formas de pago permitidas
$formasPermitidas = [
    "Efectivo",
    "Transferencia",
    "Débito",
    "Crédito",
    "Depósito",
];

// Comprobar que se haya seleccionado un numero de venta valido
if (!$nroVenta || $nroVenta < 1) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Selecciona una venta valida"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Comprobar que la fecha recibida sea un texto
if (!is_string($fechaPago)) {
    $fechaPago = "";
}

// Intentar convertir el texto recibido en una fecha
$objetoFecha = DateTime::createFromFormat("!Y-m-d", $fechaPago);

// Comprobar que la fecha tenga el formato correcto
if (
    !$objetoFecha ||
    $objetoFecha->format("Y-m-d") !== $fechaPago
) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Ingresa una fecha valida"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Comprobar que el importe sea numerico, valido y mayor que cero
if (
    !is_numeric($montoPago) ||
    !is_finite((float)$montoPago) ||
    (float)$montoPago <= 0
) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "El importe debe ser mayor que cero"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Convertir el importe a numero decimal y dejarlo con dos decimales
$montoPago = round((float)$montoPago, 2);

// Comprobar que la forma de pago este dentro de las opciones permitidas
if (!in_array($formaPago, $formasPermitidas, true)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Selecciona una forma de pago valida"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// Variable para saber si se inicio una transaccion
$transaccionIniciada = false;

try {

    // Iniciar una transaccion en la base de datos
    $conexion->begin_transaction();

    // Indicar que la transaccion esta activa
    $transaccionIniciada = true;

    // Buscar la venta y bloquearla mientras se registra el pago
    // FOR UPDATE evita que otra operacion modifique esta venta al mismo tiempo
    $consultaVenta = $conexion->prepare("
        SELECT monto_total
        FROM ventas
        WHERE nro_venta = ?
          AND eliminado = 0
        FOR UPDATE
    ");

    // Asociar el numero de venta a la consulta
    $consultaVenta->bind_param("i", $nroVenta);

    // Ejecutar la consulta
    $consultaVenta->execute();

    // Obtener los datos de la venta encontrada
    $resultadoVenta = $consultaVenta->get_result();
    $venta = $resultadoVenta->fetch_assoc();

    // Cerrar la consulta
    $consultaVenta->close();

    // Comprobar que la venta exista y no este eliminada
    if (!$venta) {
        throw new Exception("La venta seleccionada no existe");
    }

    // Obtener el importe total de la venta
    $montoTotal = (float)$venta["monto_total"];

    // Sumar los pagos que ya tiene la venta
    // COALESCE devuelve cero si todavia no hay pagos registrados
    $consultaPagos = $conexion->prepare("
        SELECT COALESCE(SUM(monto_pago), 0) AS total_pagado
        FROM pago
        WHERE nro_venta = ?
    ");

    // Asociar el numero de venta a la consulta
    $consultaPagos->bind_param("i", $nroVenta);

    // Ejecutar la consulta
    $consultaPagos->execute();

    // Obtener el resultado de la suma de los pagos anteriores
    $resultadoPagos = $consultaPagos->get_result();
    $filaPagos = $resultadoPagos->fetch_assoc();

    // Cerrar la consulta
    $consultaPagos->close();

    // Obtener el total pagado hasta el momento
    $totalPagado = (float)$filaPagos["total_pagado"];

    // Calcular el dinero que todavia falta pagar
    $saldo = round($montoTotal - $totalPagado, 2);

    // Comprobar que la venta todavia tenga dinero pendiente
    if ($saldo <= 0) {
        throw new Exception("Esta venta ya esta pagada");
    }

    // Comprobar que el nuevo pago no supere el saldo pendiente
    if ($montoPago > $saldo) {
        throw new Exception(
            "El importe supera el saldo pendiente de $" .
            number_format($saldo, 2, ",", ".") . "."
        );
    }

    // Guardar el nuevo pago.
    // Se utiliza INSERT para agregar un registro a la tabla pago
    $insertarPago = $conexion->prepare("
        INSERT INTO pago (
            fecha_pago,
            monto_pago,
            forma_pago,
            nro_venta
        )
        VALUES (?, ?, ?, ?)
    ");

    // Asociar los datos del nuevo pago a la consulta
    // s = texto, d = numero decimal, i = numero entero
    $insertarPago->bind_param(
        "sdsi",
        $fechaPago,
        $montoPago,
        $formaPago,
        $nroVenta
    );

    // Ejecutar el registro del pago
    $insertarPago->execute();

    // Obtener el numero identificador que MySQL asigno al nuevo pago
    $nroPago = $conexion->insert_id;

    // Cerrar la consulta
    $insertarPago->close();

    // Calcular cuanto se pago en total incluyendo el nuevo pago
    $nuevoTotalPagado = round($totalPagado + $montoPago, 2);

    // Comprobar si se completo el pago de la venta
    if ($nuevoTotalPagado >= $montoTotal) {
        // Si se pago todo el importe, el estado sera Pagado
        $estado = "Pagado";
    } else {
        // Si todavia falta dinero, el estado sera Parcial
        $estado = "Parcial";
    }

    // Actualizar el estado de la venta en la tabla ventas
    $actualizarVenta = $conexion->prepare("
        UPDATE ventas
        SET estado_pago = ?
        WHERE nro_venta = ?
    ");

    // Asociar el nuevo estado y el numero de venta
    $actualizarVenta->bind_param("si", $estado, $nroVenta);

    // Ejecutar la actualizacion
    $actualizarVenta->execute();

    // Cerrar la consulta
    $actualizarVenta->close();

    // Calcular el saldo que queda despues del nuevo pago
    $nuevoSaldo = round($montoTotal - $nuevoTotalPagado, 2);

    // Confirmar la transaccion y guardar todos los cambios
    $conexion->commit();

    // Indicar que la transaccion ya termino correctamente
    $transaccionIniciada = false;

    // Devolver una respuesta indicando que el pago se registro correctamente
    echo json_encode([
        "ok" => true,
        "mensaje" => "El pago se registro correctamente",
        "datos" => [
            "nro_pago" => $nroPago,
            "nro_venta" => $nroVenta,
            "monto_pago" => $montoPago,
            "total_pagado" => $nuevoTotalPagado,
            "saldo" => $nuevoSaldo,
            "estado_pago" => $estado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    // Si la transaccion sigue activa, deshacer todos los cambios realizados
    if ($transaccionIniciada) {
        $conexion->rollback();
    }

    // Enviar el codigo 400 para informar que no se pudo realizar la operacion
    http_response_code(400);

    // Devolver el mensaje del error
    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    // Si la transaccion sigue activa, deshacer todos los cambios realizados
    if ($transaccionIniciada) {
        $conexion->rollback();
    }

    // Guardar los detalles del error en el registro del servidor
    error_log("Error al registrar pago: " . $e->getMessage());

    // Enviar el codigo 500, que indica un error interno del servidor
    http_response_code(500);

    // Devolver un mensaje general sin mostrar detalles internos
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo registrar el pago"
    ], JSON_UNESCAPED_UNICODE);
}
