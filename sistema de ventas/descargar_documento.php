
<?php

session_start();

// Comprobar que la administradora haya iniciado sesion
if (!isset($_SESSION["administradora_id"])) {
    header("Location: index.php");
    exit();
}

require_once "config/conexion.php";

// Recibir el numero identificador del documento
$nro_doc = filter_input(INPUT_GET, "nro_doc", FILTER_VALIDATE_INT);

if (!$nro_doc || $nro_doc <= 0) {
    http_response_code(400);
    exit("El numero de documento no es valido");
}

// Buscar el documento y comprobar que pertenezca a una venta activa
$sql = "SELECT d.archivo, d.tipo_doc
        FROM documentacion d
        INNER JOIN ventas v ON v.nro_venta = d.nro_venta
        WHERE d.nro_doc = ? AND v.eliminado = 0";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $nro_doc);
$stmt->execute();

$resultado = $stmt->get_result();
$documento = $resultado->fetch_assoc();

if (!$documento) {
    http_response_code(404);
    exit("No se encontro el documento");
}

// El archivo debe estar dentro de la carpeta de documentos
$nombre_archivo = basename($documento["archivo"]);
$ruta_archivo = __DIR__ . "/documentos_ventas/" . $nombre_archivo;

if (!is_file($ruta_archivo)) {
    http_response_code(404);
    exit("El archivo no esta disponible en la carpeta");
}

// Descargar el archivo
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

$tipos_mime = [
    "pdf"  => "application/pdf",
    "jpg"  => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png"  => "image/png",
    "doc"  => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
];

$mime = $tipos_mime[$extension] ?? "application/octet-stream";

header("Content-Type: " . $mime);
header("Content-Disposition: attachment; filename=\"" . $nombre_archivo . "\"");
header("Content-Length: " . filesize($ruta_archivo));
header("X-Content-Type-Options: nosniff");

readfile($ruta_archivo);
exit();
