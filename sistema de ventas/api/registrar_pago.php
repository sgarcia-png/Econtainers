
<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["administradora_id"])) {
    http_response_code(401);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes iniciar sesion para registrar un pago"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Este metodo no esta permitido"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);

if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos enviados no son validos"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$nroVenta = filter_var(
    $datos["nro_venta"] ?? null,
    FILTER_VALIDATE_INT
);

$fechaPago = $datos["fecha_pago"] ?? "";
$montoPago = $datos["monto_pago"] ?? "";
$formaPago = trim($datos["forma_pago"] ?? "");

$formasPermitidas = [
    "Efectivo",
    "Transferencia",
    "Débito",
    "Crédito",
    "Depósito",
];

if (!$nroVenta || $nroVenta < 1) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Selecciona una venta valida"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

if (!is_string($fechaPago)) {
    $fechaPago = "";
}

$objetoFecha = DateTime::createFromFormat("!Y-m-d", $fechaPago);

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

$montoPago = round((float)$montoPago, 2);

if (!in_array($formaPago, $formasPermitidas, true)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Selecciona una forma de pago valida"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$transaccionIniciada = false;

try {
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    // Buscar la venta y bloquearla mientras se registra el pago
    $consultaVenta = $conexion->prepare("
        SELECT monto_total
        FROM ventas
        WHERE nro_venta = ?
          AND eliminado = 0
        FOR UPDATE
    ");

    $consultaVenta->bind_param("i", $nroVenta);
    $consultaVenta->execute();

    $resultadoVenta = $consultaVenta->get_result();
    $venta = $resultadoVenta->fetch_assoc();

    $consultaVenta->close();

    if (!$venta) {
        throw new Exception("La venta seleccionada no existe");
    }

    $montoTotal = (float)$venta["monto_total"];

    // Sumar los pagos que ya tiene la venta
    $consultaPagos = $conexion->prepare("
        SELECT COALESCE(SUM(monto_pago), 0) AS total_pagado
        FROM pago
        WHERE nro_venta = ?
    ");

    $consultaPagos->bind_param("i", $nroVenta);
    $consultaPagos->execute();

    $resultadoPagos = $consultaPagos->get_result();
    $filaPagos = $resultadoPagos->fetch_assoc();

    $consultaPagos->close();

    $totalPagado = (float)$filaPagos["total_pagado"];
    $saldo = round($montoTotal - $totalPagado, 2);

    if ($saldo <= 0) {
        throw new Exception("Esta venta ya esta pagada");
    }

    if ($montoPago > $saldo) {
        throw new Exception(
            "El importe supera el saldo pendiente de $" .
            number_format($saldo, 2, ",", ".") . "."
        );
    }

    // Guardar el nuevo pago.
    $insertarPago = $conexion->prepare("
        INSERT INTO pago (
            fecha_pago,
            monto_pago,
            forma_pago,
            nro_venta
        )
        VALUES (?, ?, ?, ?)
    ");

    $insertarPago->bind_param(
        "sdsi",
        $fechaPago,
        $montoPago,
        $formaPago,
        $nroVenta
    );

    $insertarPago->execute();

    $nroPago = $conexion->insert_id;

    $insertarPago->close();

    // Calcular cuanto se pago en total
    $nuevoTotalPagado = round($totalPagado + $montoPago, 2);

    if ($nuevoTotalPagado >= $montoTotal) {
        $estado = "Pagado";
    } else {
        $estado = "Parcial";
    }

    // Actualizar el estado de la venta
    $actualizarVenta = $conexion->prepare("
        UPDATE ventas
        SET estado_pago = ?
        WHERE nro_venta = ?
    ");

    $actualizarVenta->bind_param("si", $estado, $nroVenta);
    $actualizarVenta->execute();
    $actualizarVenta->close();

    $nuevoSaldo = round($montoTotal - $nuevoTotalPagado, 2);

    $conexion->commit();
    $transaccionIniciada = false;

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
    if ($transaccionIniciada) {
        $conexion->rollback();
    }

    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($transaccionIniciada) {
        $conexion->rollback();
    }

    error_log("Error al registrar pago: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudo registrar el pago"
    ], JSON_UNESCAPED_UNICODE);
}
