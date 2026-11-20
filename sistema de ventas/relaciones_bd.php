
<?php

// Indicar que el resultado se mostrara como texto plano
header("Content-Type: text/plain; charset=utf-8");

// Conectar este archivo con la base de datos
require_once __DIR__ . "/config/conexion.php";

// Consulta para obtener las relaciones entre las tablas
$sql = "
    SELECT
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, CONSTRAINT_NAME
";

// Ejecutar la consulta
$resultado = $conexion->query($sql);

// Comprobar si se encontraron relaciones entre las tablas
if ($resultado->num_rows === 0) {
    // Mostrar un mensaje si no se encontraron claves foraneas
    echo "No se encontraron claves foraneas declaradas.\n";
    echo "Esto no significa necesariamente que las relaciones";
    echo " no existan a nivel del modelo conceptual.\n";
} else {
    // Mostrar el titulo de las relaciones encontradas
    echo "RELACIONES ENTRE TABLAS\n\n";

    // Recorrer todas las relaciones encontradas
    while ($fila = $resultado->fetch_assoc()) {
        // Mostrar el nombre de la tabla y su columna relacionada
        echo $fila["TABLE_NAME"] . ".";
        echo $fila["COLUMN_NAME"];

        // Mostrar una flecha para indicar la relacion
        echo " -> ";

        // Mostrar la tabla y la columna a la que hace referencia
        echo $fila["REFERENCED_TABLE_NAME"] . ".";
        echo $fila["REFERENCED_COLUMN_NAME"];

        // Mostrar el nombre de la clave foranea
        echo " (";
        echo $fila["CONSTRAINT_NAME"];
        echo ")\n";
    }
}

// Cerrar la conexion con la base de datos
$conexion->close();
