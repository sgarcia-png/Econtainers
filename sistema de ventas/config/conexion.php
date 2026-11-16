
<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "sistema_ventas_db";

try {
    $conexion = new mysqli(
        $servidor,
        $usuario,
        $contrasena,
        $base_datos
    );

    $conexion->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("Error de conexion MySQL: " . $e->getMessage());

    http_response_code(500);
    exit("No se pudo conectar con la base de datos");
}
