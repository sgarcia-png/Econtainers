
<?php

// Indicar que el resultado se mostrara como texto plano
header("Content-Type: text/plain; charset=utf-8");

// Conectar este archivo con la base de datos
require_once __DIR__ . "/config/conexion.php";

// Mostrar un mensaje indicando que la conexion funciono
echo "CONEXION EXITOSA\n";
echo "PHP se conecto correctamente con MySQL.\n";
echo "Base de datos: sistema_ventas_db\n";

// Consultar las tablas que existen en la base de datos
$resultado = $conexion->query("SHOW TABLES");

// Mostrar el titulo de la lista de tablas
echo "\nTablas encontradas:\n";

// Recorrer todas las tablas encontradas
while ($fila = $resultado->fetch_row()) {
    // Mostrar el nombre de cada tabla
    echo "- " . $fila[0] . "\n";
}

// Cerrar la conexion con la base de datos
$conexion->close();
