
<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/conexion.php";

try {

    // Obtener los filtros enviados desde JavaScript
    $tipo = strtolower(trim($_GET["tipo"] ?? ""));
    $fechaDesde = trim($_GET["fecha_desde"] ?? "");
    $fechaHasta = trim($_GET["fecha_hasta"] ?? "");

    // Validar que se hayan enviado las dos fechas o ninguna
    if (($fechaDesde === "") !== ($fechaHasta === "")) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Debes seleccionar la fecha desde y la fecha hasta"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Comprobar que las fechas sean validas
    if ($fechaDesde !== "" && $fechaHasta !== "") {

        $desde = explode("-", $fechaDesde);
        $hasta = explode("-", $fechaHasta);

        if (
            count($desde) !== 3 ||
            count($hasta) !== 3 ||
            !checkdate((int)$desde[1], (int)$desde[2], (int)$desde[0]) ||
            !checkdate((int)$hasta[1], (int)$hasta[2], (int)$hasta[0]) ||
            $fechaDesde > $fechaHasta
        ) {

            http_response_code(400);

            echo json_encode([
                "ok" => false,
                "mensaje" => "Las fechas no son validas o el rango esta invertido"
            ], JSON_UNESCAPED_UNICODE);

            exit();
        }
    }

    // Tipos de contenedores permitidos
    $filtros = [
        "20" => "
            LOWER(tc.nombre_tipo) LIKE '%20%'
        ",
        "40" => "
            LOWER(tc.nombre_tipo) LIKE '%40%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%hc%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%reefer%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%refriger%'
        ",
        "40hc" => "
            LOWER(tc.nombre_tipo) LIKE '%40%'
            AND LOWER(tc.nombre_tipo) LIKE '%hc%'
        ",
        "reefer" => "
            LOWER(tc.nombre_tipo) LIKE '%reefer%'
            OR LOWER(tc.nombre_tipo) LIKE '%refriger%'
        "
    ];

    // Comprobar que el tipo solicitado exista
    if ($tipo !== "" && !isset($filtros[$tipo])) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "El tipo de contenedor solicitado no es valido"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Consulta principal de ventas
    $sql = "
        SELECT
            v.nro_venta,
            v.fecha,
            v.monto_total,
            v.estado_pago,
            v.dni_cliente
        FROM ventas v
    ";

    $condiciones = [];

    // Agregar el filtro por fechas si se recibieron
    if ($fechaDesde !== "" && $fechaHasta !== "") {
        $condiciones[] = "v.fecha BETWEEN ? AND ?";
    }

    // Agregar el filtro por tipo de contenedor si se eligio
    if ($tipo !== "") {

        $condicion = $filtros[$tipo];

        $condiciones[] = "
            EXISTS (
                SELECT 1
                FROM renglon_venta rv
                INNER JOIN contenedor c
                    ON c.codigo = rv.codigo_contenedor
                INNER JOIN tipo_contenedor tc
                    ON tc.id_tipo = c.id_tipo
                WHERE rv.nro_venta = v.nro_venta
                  AND ($condicion)
            )
        ";
    }

    // Agregar las condiciones a la consulta
    if (count($condiciones) > 0) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }

    $sql .= " ORDER BY v.nro_venta DESC";

    // Ejecutar la consulta
    if ($fechaDesde !== "" && $fechaHasta !== "") {

        $consulta = $conexion->prepare($sql);
        $consulta->bind_param("ss", $fechaDesde, $fechaHasta);
        $consulta->execute();

        $resultado = $consulta->get_result();

    } else {

        $resultado = $conexion->query($sql);
    }

    $ventas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $ventas[] = $fila;
    }

    echo json_encode([
        "ok" => true,
        "datos" => $ventas
    ], JSON_UNESCAPED_UNICODE);

} catch (mysqli_sql_exception $e) {

    error_log("Error en la API de ventas: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar las ventas"
    ], JSON_UNESCAPED_UNICODE);
}
