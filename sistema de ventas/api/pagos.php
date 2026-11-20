
<?php

// Iniciar la sesion para poder comprobar si la administradora ingreso al sistema
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
        "mensaje" => "Debes iniciar sesion para consultar los pagos"
    ], JSON_UNESCAPED_UNICODE);

    // Detener la ejecucion del archivo
    exit();
}

// Incluir la conexion con la base de datos
require_once __DIR__ . "/../config/conexion.php";

try {

    // Recibir los filtros enviados desde JavaScript por la URL
    // Si no se envia alguno, se utiliza un texto vacio
    $fechaDesde = trim($_GET["fecha_desde"] ?? "");
    $fechaHasta = trim($_GET["fecha_hasta"] ?? "");
    $formaPago = trim($_GET["forma_pago"] ?? "");

    // Formas de pago disponibles en el sistema
    $formasPago = [
    "Efectivo",
    "Transferencia",
    "Débito",
    "Crédito",
    "Depósito"
];
    // Validar las fechas recibidas
    // Esta funcion comprueba que la fecha tenga el formato correcto
    $validarFecha = function ($fecha) {

        // Si no se ingreso una fecha, se considera valida
        if ($fecha === "") {
            return true;
        }

        // Intentar convertir el texto recibido en una fecha
        $objetoFecha = DateTime::createFromFormat("!Y-m-d", $fecha);

        // Comprobar que se haya creado la fecha y que conserve el formato esperado
        return $objetoFecha !== false
            && $objetoFecha->format("Y-m-d") === $fecha;
    };

    // Si alguna fecha tiene un formato incorrecto, devolver un error
    if (!$validarFecha($fechaDesde) || !$validarFecha($fechaHasta)) {

        // Enviar el codigo 400, que indica que los datos recibidos no son validos
        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "El formato de fecha no es valido"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Comprobar que la fecha inicial no sea posterior a la fecha final
    if (
        $fechaDesde !== "" &&
        $fechaHasta !== "" &&
        $fechaDesde > $fechaHasta
    ) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "La fecha inicial no puede ser posterior a la final"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Validar la forma de pago
    // Solo se permiten las opciones que aparecen en el arreglo $formasPago
    if (
        $formaPago !== "" &&
        !in_array($formaPago, $formasPago, true)
    ) {

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "La forma de pago no es valida"
        ], JSON_UNESCAPED_UNICODE);

        exit();
    }

    // Consultar los pagos y sus ventas relacionadas
    // Se utiliza INNER JOIN para relacionar cada pago con su venta
    // Tambien se excluyen las ventas que fueron eliminadas
    $sql = "
        SELECT
            p.nro_pago,
            p.fecha_pago,
            p.monto_pago,
            p.forma_pago,
            p.nro_venta,
            v.dni_cliente,
            v.estado_pago
        FROM pago AS p
        INNER JOIN ventas AS v
            ON p.nro_venta = v.nro_venta
        WHERE v.eliminado = 0
    ";

    // Crear un arreglo para guardar los valores de los filtros
    $parametros = [];

    // Guardar los tipos de datos que se enviaran en la consulta
    $tipos = "";

    // Aplicar filtro por fecha inicial
    if ($fechaDesde !== "") {

        // Agregar una condicion para buscar desde la fecha indicada
        $sql .= " AND p.fecha_pago >= ?";

        // Guardar la fecha como parametro
        $parametros[] = $fechaDesde;

        // La letra s indica que el parametro es de tipo texto
        $tipos .= "s";
    }

    // Aplicar filtro por fecha final
    if ($fechaHasta !== "") {

        // Agregar una condicion para buscar hasta la fecha indicada
        $sql .= " AND p.fecha_pago <= ?";

        // Guardar la fecha como parametro
        $parametros[] = $fechaHasta;

        $tipos .= "s";
    }

    // Aplicar filtro por forma de pago
    if ($formaPago !== "") {

        // Agregar una condicion para buscar una forma de pago especifica
        $sql .= " AND p.forma_pago = ?";

        // Guardar la forma de pago como parametro
        $parametros[] = $formaPago;

        $tipos .= "s";
    }

    // Ordenar los pagos por fecha y numero
    // Los pagos mas recientes aparecen primero
    $sql .= " ORDER BY p.fecha_pago DESC, p.nro_pago DESC";

    // Preparar la consulta para ejecutarla de forma segura
    $stmt = $conexion->prepare($sql);

    // Comprobar que la consulta se haya preparado correctamente
    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta");
    }

    // Si se recibieron filtros, asociar sus valores a los signos de pregunta
    if (count($parametros) > 0) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    // Ejecutar la consulta en la base de datos
    $stmt->execute();

    // Obtener el resultado de la consulta
    $resultado = $stmt->get_result();

    // Crear un arreglo vacio para guardar los pagos encontrados
    $pagos = [];

    // Recorrer todos los resultados y guardar cada pago en el arreglo
    while ($fila = $resultado->fetch_assoc()) {
        $pagos[] = $fila;
    }

    // Cerrar la consulta y la conexion con la base de datos
    $stmt->close();
    $conexion->close();

    // Enviar los pagos y las formas de pago a JavaScript
    // "datos" contiene los pagos encontrados
    // "formas_pago" contiene las opciones permitidas para los filtros
    echo json_encode([
        "ok" => true,
        "datos" => $pagos,
        "formas_pago" => $formasPago
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {

    // Guardar el error real en el registro del servidor
    // Esto permite revisar el problema sin mostrar detalles internos al usuario
    error_log("Error al consultar pagos: " . $e->getMessage());

    // Enviar el codigo 500, que indica un error interno del servidor
    http_response_code(500);

    // Devolver un mensaje general de error en formato JSON
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar los pagos"
    ], JSON_UNESCAPED_UNICODE);
}
