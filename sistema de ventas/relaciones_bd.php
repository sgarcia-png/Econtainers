
<?php

header("Content-Type: text/plain; charset=utf-8");

require_once __DIR__ . "/config/conexion.php";

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

$resultado = $conexion->query($sql);

if ($resultado->num_rows === 0) {
    echo "No se encontraron claves foraneas declaradas.\n";
    echo "Esto no significa necesariamente que las relaciones";
    echo " no existan a nivel del modelo conceptual.\n";
} else {
    echo "RELACIONES ENTRE TABLAS\n\n";

    while ($fila = $resultado->fetch_assoc()) {
        echo $fila["TABLE_NAME"] . ".";
        echo $fila["COLUMN_NAME"];
        echo " -> ";
        echo $fila["REFERENCED_TABLE_NAME"] . ".";
        echo $fila["REFERENCED_COLUMN_NAME"];
        echo " (";
        echo $fila["CONSTRAINT_NAME"];
        echo ")\n";
    }
}

$conexion->close();

