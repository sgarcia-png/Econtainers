
<?php

// =====================================================
// CONFIGURACION DE LOS ERRORES DE MYSQL
// =====================================================

// Indica como debe actuar MySQLi cuando ocurre un error.
// MYSQLI_REPORT_ERROR muestra los errores de MySQLi.
// MYSQLI_REPORT_STRICT hace que los errores se puedan capturar mediante un bloque try-catch.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// =====================================================
// DATOS PARA CONECTARSE A LA BASE DE DATOS
// =====================================================

// Nombre del servidor donde se encuentra MySQL.
// generado por XAMPP , utilizamos localhost.
$servidor = "localhost";

// Nombre del usuario de MySQL.
$usuario = "root";

// Contraseña del usuario de MySQL.
$contrasena = "";

// Nombre de la base de datos que utiliza el sistema.
$base_datos = "sistema_ventas_db";


// =====================================================
// INTENTO DE CONEXION CON MYSQL
// =====================================================

// try permite intentar ejecutar una operacion que podria generar un error.
// En este caso, intenta conectarse a la base de datos.
try {

    // Crea una nueva conexion utilizando los datos que definimos anteriormente.
    // La conexion se guarda en la variable $conexion.
    $conexion = new mysqli(
        $servidor,
        $usuario,
        $contrasena,
        $base_datos
    );

    // Establece la codificacion de caracteres UTF-8.
    // Esto permite trabajar correctamente con letras, acentos y caracteres especiales.
    $conexion->set_charset("utf8mb4");


// =====================================================
// CONTROL DE ERRORES DE CONEXION
// =====================================================

// catch se ejecuta si ocurre un error durante el intento de conexion.
// La variable $e guarda la informacion del error.
} catch (mysqli_sql_exception $e) {

    // Guarda el detalle del error en el registro de errores del servidor.
    error_log("Error de conexion MySQL: " . $e->getMessage());

    // Establece el codigo de respuesta HTTP,que indica que ocurrio un error del servidor.
    http_response_code(500);

    // Detiene la ejecucion del programa y muestra un mensaje indicando que no se pudo
    // conectar con la base de datos.
    exit("No se pudo conectar con la base de datos");
}
