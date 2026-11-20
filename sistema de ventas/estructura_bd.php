
<?php

// Indicar que la respuesta se mostrara como texto plano
header("Content-Type: text/plain; charset=utf-8");

// Conectar este archivo con la base de datos
require_once __DIR__ . "/config/conexion.php";

// Consulta para obtener la estructura de las tablas de la base de datos
$sql = "
    SELECT
        TABLE_NAME,
        COLUMN_NAME,
        COLUMN_TYPE,
        IS_NULLABLE,
        COLUMN_KEY,
        EXTRA
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME, ORDINAL_POSITION
";

// Ejecutar la consulta
$resultado = $conexion->query($sql);

// Variable para guardar el nombre de la tabla que se esta mostrando
$tabla_actual = "";

// Recorrer los resultados de la consulta
while ($fila = $resultado->fetch_assoc()) {

    // Comprobar si se encontro una tabla diferente a la anterior
    if ($tabla_actual !== $fila["TABLE_NAME"]) {
        // Actualizar el nombre de la tabla actual
        $tabla_actual = $fila["TABLE_NAME"];

        // Mostrar el nombre de la tabla y separadores
        echo "\n====================================\n";
        echo "TABLA: " . $tabla_actual . "\n";
        echo "====================================\n";
    }

    // Mostrar el nombre de la columna
    echo "Columna: " . $fila["COLUMN_NAME"];

    // Mostrar el tipo de dato de la columna
    echo " | Tipo: " . $fila["COLUMN_TYPE"];

    // Indicar si la columna permite valores NULL
    echo " | Permite NULL: " . $fila["IS_NULLABLE"];

    // Mostrar el tipo de clave o indicar que no tiene
    echo " | Clave: " . ($fila["COLUMN_KEY"] ?: "Ninguna");

    // Mostrar informacion adicional de la columna
    echo " | Extra: " . ($fila["EXTRA"] ?: "Ninguno");

    // Pasar a una nueva linea
    echo "\n";
}

// Cerrar la conexion con la base de datos
$conexion->close();
