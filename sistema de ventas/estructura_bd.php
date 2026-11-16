
<?php

header("Content-Type: text/plain; charset=utf-8");

require_once __DIR__ . "/config/conexion.php";

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

$resultado = $conexion->query($sql);

$tabla_actual = "";

while ($fila = $resultado->fetch_assoc()) {

    if ($tabla_actual !== $fila["TABLE_NAME"]) {
        $tabla_actual = $fila["TABLE_NAME"];

        echo "\n====================================\n";
        echo "TABLA: " . $tabla_actual . "\n";
        echo "====================================\n";
    }

    echo "Columna: " . $fila["COLUMN_NAME"];
    echo " | Tipo: " . $fila["COLUMN_TYPE"];
    echo " | Permite NULL: " . $fila["IS_NULLABLE"];
    echo " | Clave: " . ($fila["COLUMN_KEY"] ?: "Ninguna");
    echo " | Extra: " . ($fila["EXTRA"] ?: "Ninguno");
    echo "\n";
}

$conexion->close();

