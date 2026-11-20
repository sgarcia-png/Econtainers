
<?php

// =====================================================
// CONFIGURACION DE LA RESPUESTA DE LA API
// =====================================================

// Indica que este archivo devuelve informacion en formato JSON.
// utf-8 permite utilizar correctamente acentos y caracteres especiales en los datos que se envian como 
// respuesta.
header("Content-Type: application/json; charset=utf-8");


// =====================================================
// CONEXION CON LA BASE DE DATOS
// =====================================================

// Incluye el archivo conexion.php, que establece la conexion entre PHP y la base de datos MySQL.
require_once __DIR__ . "/../config/conexion.php";


// =====================================================
// INICIO DEL CONTROL DE ERRORES
// =====================================================

// try permite intentar ejecutar las consultas y operaciones.
// Si ocurre un error de MySQL, se ejecutara el bloque catch.
try {

    // =============================================
    // OBTENCION DE LOS FILTROS
    // =============================================

    // Obtiene el tipo de contenedor enviado desde la URL.
    // strtolower() convierte las letras a minusculas.
    // trim() elimina los espacios al principio y al final.
    // Si no se envio el parametro, utiliza un texto vacio.
    $tipo = strtolower(trim($_GET["tipo"] ?? ""));

    // Obtiene la fecha inicial enviada desde el formulario o JavaScript.
    $fechaDesde = trim($_GET["fecha_desde"] ?? "");

    // Obtiene la fecha final enviada desde el formulario o JavaScript.
    $fechaHasta = trim($_GET["fecha_hasta"] ?? "");


    // =============================================
    // VALIDACION DE LAS FECHAS
    // =============================================

    // Comprueba que se hayan enviado las dos fechas o que no se haya enviado ninguna.
    // Si se envia solamente una fecha, la condicion se cumple.
    if (($fechaDesde === "") !== ($fechaHasta === "")) {

        // Establece el codigo HTTP 400, que indica que la solicitud contiene datos incorrectos.
        http_response_code(400);

        // Devuelve una respuesta JSON indicando el problema.
        // ok false informa que la operacion no fue correcta.
        echo json_encode([
            "ok" => false,
            "mensaje" => "Debes seleccionar la fecha desde y la fecha hasta"
        ], JSON_UNESCAPED_UNICODE);

        // Detiene la ejecucion porque las fechas no son correctas.
        exit();
    }


    // =============================================
    // COMPROBACION DE QUE LAS FECHAS SEAN VALIDAS
    // =============================================

    // Comprueba que las dos fechas hayan sido ingresadas.
    if ($fechaDesde !== "" && $fechaHasta !== "") {

        // Separa la fecha inicial en tres partes:
        // año, mes y dia.
        // explode() utiliza el guion como separador.
        $desde = explode("-", $fechaDesde);

        // Realiza lo mismo con la fecha final.
        $hasta = explode("-", $fechaHasta);

        // Comprueba varias condiciones:
        // 1. Que cada fecha tenga tres partes.
        // 2. Que el mes y el dia correspondan a una fecha real.
        // 3. Que la fecha inicial no sea posterior a la fecha final.
        if (
            count($desde) !== 3 ||
            count($hasta) !== 3 ||
            !checkdate((int)$desde[1], (int)$desde[2], (int)$desde[0]) ||
            !checkdate((int)$hasta[1], (int)$hasta[2], (int)$hasta[0]) ||
            $fechaDesde > $fechaHasta
        ) {

            // Informa que la solicitud contiene fechas incorrectas.
            http_response_code(400);

            // Devuelve un mensaje de error en formato JSON.
            echo json_encode([
                "ok" => false,
                "mensaje" => "Las fechas no son validas o el rango esta invertido"
            ], JSON_UNESCAPED_UNICODE);

            // Detiene el programa para no continuar con una consulta que utiliza fechas incorrectas.
            exit();
        }
    }


    // =============================================
    // DEFINICION DE LOS TIPOS DE CONTENEDORES
    // =============================================

    // Este arreglo contiene las condiciones SQL que permiten identificar cada tipo de contenedor.
    //
    // La clave es el valor que se recibe desde la URL.
    // El contenido es la condicion que se utiliza para buscar ese tipo en la base de datos.
    $filtros = [

        // Busca contenedores cuyo nombre contenga 20.
        "20" => "
            LOWER(tc.nombre_tipo) LIKE '%20%'
        ",

        // Busca contenedores de 40 pies.
        // Excluye los tipos HC y Reefer para no confundirlos.
        "40" => "
            LOWER(tc.nombre_tipo) LIKE '%40%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%hc%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%reefer%'
            AND LOWER(tc.nombre_tipo) NOT LIKE '%refriger%'
        ",

        // Busca contenedores de 40 pies HC.
        "40hc" => "
            LOWER(tc.nombre_tipo) LIKE '%40%'
            AND LOWER(tc.nombre_tipo) LIKE '%hc%'
        ",

        // Busca contenedores Reefer o refrigerados.
        "reefer" => "
            LOWER(tc.nombre_tipo) LIKE '%reefer%'
            OR LOWER(tc.nombre_tipo) LIKE '%refriger%'
        "
    ];


    // =============================================
    // VALIDACION DEL TIPO DE CONTENEDOR
    // =============================================

    // Comprueba si se recibio un tipo de contenedor y si ese tipo existe dentro del arreglo $filtros.
    // Si no existe, significa que se solicito un tipo no permitido.
    if ($tipo !== "" && !isset($filtros[$tipo])) {

        // Establece el codigo HTTP 400 por solicitud incorrecta.
        http_response_code(400);

        // Devuelve un mensaje indicando que el tipo no es valido.
        echo json_encode([
            "ok" => false,
            "mensaje" => "El tipo de contenedor solicitado no es valido"
        ], JSON_UNESCAPED_UNICODE);

        // Detiene la ejecucion.
        exit();
    }


    // =============================================
    // CONSULTA PRINCIPAL DE VENTAS
    // =============================================

    // Se prepara la consulta SQL que obtiene los datos principales de cada venta.
    //
    // v es un alias de la tabla ventas.
    // Los alias permiten escribir las consultas de forma mas corta.
    //
    // Se seleccionan:
    // - Numero de venta.
    // - Fecha.
    // - Monto total.
    // - Estado de pago.
    // - DNI del cliente.
    $sql = "
        SELECT
            v.nro_venta,
            v.fecha,
            v.monto_total,
            v.estado_pago,
            v.dni_cliente
        FROM ventas v
    ";

    // Crea un arreglo vacio donde se guardaran las condiciones que se agregaran a la consulta SQL.
    $condiciones = [];


    // =============================================
    // AGREGAR EL FILTRO POR FECHAS
    // =============================================

    // Si se recibieron las dos fechas, agrega una condicion para buscar las ventas realizadas dentro de
    // ese periodo.
    // BETWEEN permite buscar valores entre dos limites.
    // Los signos ? se completaran posteriormente con bind_param().
    if ($fechaDesde !== "" && $fechaHasta !== "") {
        $condiciones[] = "v.fecha BETWEEN ? AND ?";
    }


    // =============================================
    // AGREGAR EL FILTRO POR TIPO DE CONTENEDOR
    // =============================================

    // Comprueba si se selecciono un tipo de contenedor.
    if ($tipo !== "") {

        // Obtiene la condicion SQL correspondiente al tipo seleccionado en el arreglo $filtros.
        $condicion = $filtros[$tipo];

        // Agrega una condicion EXISTS a la consulta.
        //
        // EXISTS comprueba si existe al menos un registro relacionado con la venta que cumpla las 
        // condiciones.
        //
        // Se relacionan tres tablas:
        // renglon_venta: contiene los contenedores de cada venta.
        // contenedor: contiene los datos de cada contenedor.
        // tipo_contenedor: contiene los tipos de contenedores.
        //
        // Los INNER JOIN permiten relacionar esas tablas mediante sus claves correspondientes.
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


    // =============================================
    // INCORPORAR LAS CONDICIONES A LA CONSULTA
    // =============================================

    // Comprueba si se agrego alguna condicion.
    if (count($condiciones) > 0) {

        // Agrega WHERE a la consulta SQL.
        // implode() une todas las condiciones del arreglo utilizando AND como separador.
        //
        // Esto permite combinar filtros de fecha y tipo de contenedor cuando ambos fueron seleccionados.
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }


    // Ordena los resultados por numero de venta de mayor a menor, mostrando primero las mas recientes.
    $sql .= " ORDER BY v.nro_venta DESC";


    // =============================================
    // EJECUCION DE LA CONSULTA SQL
    // =============================================

    // Comprueba si se recibieron las dos fechas.
    if ($fechaDesde !== "" && $fechaHasta !== "") {

        // Prepara la consulta SQL.
        // Esto permite enviar los valores de las fechas de forma separada y segura.
        $consulta = $conexion->prepare($sql);

        // Vincula las dos fechas con los signos ? de la consulta.
        // Las dos letras s indican que ambos valores son textos.
        $consulta->bind_param("ss", $fechaDesde, $fechaHasta);

        // Ejecuta la consulta preparada.
        $consulta->execute();

        // Obtiene los resultados de la consulta.
        $resultado = $consulta->get_result();

    } else {

        // Si no se seleccionaron fechas, ejecuta directamente la consulta SQL construida.
        // Se utiliza la conexion con la base de datos.
        $resultado = $conexion->query($sql);
    }


    // =============================================
    // ALMACENAMIENTO DE LAS VENTAS EN UN ARREGLO
    // =============================================

    // Crea un arreglo vacio donde se guardaran todos los registros de ventas obtenidos.
    $ventas = [];

    // Recorre los resultados de la consulta uno por uno.
    // fetch_assoc() obtiene cada registro como un arreglo asociativo, utilizando los nombres de las 
    // columnas.
    while ($fila = $resultado->fetch_assoc()) {

        // Agrega cada venta encontrada al arreglo $ventas.
        $ventas[] = $fila;
    }


    // =============================================
    // DEVOLUCION DE LOS RESULTADOS EN FORMATO JSON
    // =============================================

    // Convierte el resultado en formato JSON para que JavaScript pueda recibir y utilizar los datos.
    //
    // ok true indica que la consulta se realizo correctamente.
    // datos contiene el arreglo con todas las ventas encontradas.
    // JSON_UNESCAPED_UNICODE permite conservar correctamente los caracteres especiales y acentos.
    echo json_encode([
        "ok" => true,
        "datos" => $ventas
    ], JSON_UNESCAPED_UNICODE);


// =====================================================
// CONTROL DE ERRORES DE MYSQL
// =====================================================

// Si ocurre un error relacionado con MySQL durante alguna de las operaciones anteriores, se ejecuta este
// bloque catch.
} catch (mysqli_sql_exception $e) {

    // Guarda el detalle del error en el registro de errores del servidor.
    error_log("Error en la API de ventas: " . $e->getMessage());

    // Establece el codigo HTTP 500, que indica un error interno del servidor.
    http_response_code(500);

    // Devuelve una respuesta JSON indicando que no se pudieron consultar las ventas.
    echo json_encode([
        "ok" => false,
        "mensaje" => "No se pudieron consultar las ventas"
    ], JSON_UNESCAPED_UNICODE);
}
