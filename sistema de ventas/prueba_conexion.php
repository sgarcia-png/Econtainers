
<?php

header("Content-Type: text/plain; charset=utf-8");

require_once __DIR__ . "/config/conexion.php";

echo "CONEXION EXITOSA\n";
echo "PHP se conecto correctamente con MySQL.\n";
echo "Base de datos: sistema_ventas_db\n";

$resultado = $conexion->query("SHOW TABLES");

echo "\nTablas encontradas:\n";

while ($fila = $resultado->fetch_row()) {
    echo "- " . $fila[0] . "\n";
}

$conexion->close();
