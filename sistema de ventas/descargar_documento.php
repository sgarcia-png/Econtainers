
<?php

// Iniciar la sesion para comprobar que el usuario haya ingresado al sistema.
session_start();

// Comprobar que la administradora haya iniciado sesion
// Si no existe una sesion activa, volver a la pantalla de ingreso.
if (!isset($_SESSION["administradora_id"])) {
    header("Location: index.php");
    exit();
}

// Conectar el sistema con la base de datos.
require_once "config/conexion.php";

// Recibir el numero identificador del documento
// FILTER_VALIDATE_INT comprueba que el dato recibido sea un numero entero.
$nro_doc = filter_input(INPUT_GET, "nro_doc", FILTER_VALIDATE_INT);

// Comprobar que se haya recibido un numero de documento valido.
// Si no es valido, responder con el codigo HTTP 400.
if (!$nro_doc || $nro_doc <= 0) {
    http_response_code(400);
    exit("El numero de documento no es valido");
}

// Buscar el documento y comprobar que pertenezca a una venta activa.
// Se relacionan las tablas documentacion y ventas mediante el numero de venta.
$sql = "SELECT d.archivo, d.tipo_doc
        FROM documentacion d
        INNER JOIN ventas v ON v.nro_venta = d.nro_venta
        WHERE d.nro_doc = ? AND v.eliminado = 0";

// Preparar la consulta para buscar el documento de forma segura.
$stmt = $conexion->prepare($sql);

// Asociar el numero identificador del documento.
// La letra i indica que el dato es un numero entero.
$stmt->bind_param("i", $nro_doc);

// Ejecutar la consulta.
$stmt->execute();

// Obtener el resultado de la consulta.
$resultado = $stmt->get_result();

// Guardar los datos del documento encontrado.
$documento = $resultado->fetch_assoc();

// Comprobar que el documento exista y pertenezca a una venta activa.
// Si no se encuentra, responder con el codigo HTTP 404.
if (!$documento) {
    http_response_code(404);
    exit("No se encontro el documento");
}

// El archivo debe estar dentro de la carpeta de documentos
// basename obtiene solamente el nombre del archivo, sin otras rutas.
$nombre_archivo = basename($documento["archivo"]);

// Construir la ruta completa donde se encuentra guardado el archivo.
$ruta_archivo = __DIR__ . "/documentos_ventas/" . $nombre_archivo;

// Comprobar que el archivo exista realmente en la carpeta.
// Si no esta disponible, responder con el codigo HTTP 404.
if (!is_file($ruta_archivo)) {
    http_response_code(404);
    exit("El archivo no esta disponible en la carpeta");
}

// Descargar el archivo
// Obtener la extension del archivo y convertirla a minusculas.
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

// Relacionar cada extension con el tipo MIME correspondiente.
// Esto permite indicar al navegador que clase de archivo se esta enviando.
$tipos_mime = [
    "pdf"  => "application/pdf",
    "jpg"  => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png"  => "image/png",
    "doc"  => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
];

// Buscar el tipo MIME segun la extension.
// Si no se encuentra, utilizar application/octet-stream como tipo general.
$mime = $tipos_mime[$extension] ?? "application/octet-stream";

// Enviar una cabecera que indica el formato del archivo.
header("Content-Type: " . $mime);

// Indicar que el archivo debe descargarse y no mostrarse directamente en el navegador.
// Tambien se informa el nombre que tendra el archivo descargado.
header("Content-Disposition: attachment; filename=\"" . $nombre_archivo . "\"");

// Informar el tamaño del archivo en bytes.
header("Content-Length: " . filesize($ruta_archivo));

// Indicar al navegador que no intente interpretar el archivo con otro formato.
header("X-Content-Type-Options: nosniff");

// Leer el archivo y enviarlo al navegador para que pueda descargarse.
readfile($ruta_archivo);

// Detener la ejecucion despues de enviar el archivo.
exit();
