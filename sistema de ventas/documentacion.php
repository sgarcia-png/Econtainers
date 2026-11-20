
<?php

// Iniciar la sesion para comprobar que el usuario haya ingresado al sistema.
session_start();

// Verificar que exista una administradora conectada.
// Si no hay una sesion iniciada, volver a la pantalla de ingreso.
if (!isset($_SESSION["administradora_id"])) {
    header("Location: index.php");
    exit();
}

// Conectar el sistema con la base de datos.
require_once "config/conexion.php";

// Recibir el numero de venta
// FILTER_VALIDATE_INT comprueba que el dato recibido sea un numero entero.
$nro_venta = filter_input(INPUT_GET, "nro_venta", FILTER_VALIDATE_INT);

// Comprobar que se haya recibido un numero de venta valido.
if (!$nro_venta || $nro_venta <= 0) {
    exit("No se indico un numero de venta valido");
}

// Buscar la venta y los datos del cliente
// Se relacionan las tablas ventas y cliente mediante el DNI del cliente.
// El signo ? se utiliza para colocar el numero de venta de forma segura.
$sql = "SELECT v.nro_venta, v.fecha, v.monto_total,
               c.nombre, c.apellido
        FROM ventas v
        INNER JOIN cliente c ON c.DNI = v.dni_cliente
        WHERE v.nro_venta = ? AND v.eliminado = 0";

// Preparar la consulta para evitar colocar directamente el dato recibido en el SQL.
$stmt = $conexion->prepare($sql);

// Asociar el numero de venta a la consulta.
// La letra i indica que el dato es un numero entero.
$stmt->bind_param("i", $nro_venta);

// Ejecutar la consulta.
$stmt->execute();

// Obtener el resultado de la consulta.
$resultado = $stmt->get_result();

// Guardar los datos de la venta en un arreglo asociativo.
$venta = $resultado->fetch_assoc();

// Comprobar que la venta exista y que no haya sido eliminada.
if (!$venta) {
    exit("No se encontro la venta indicada");
}

// Variable que se utiliza para guardar los mensajes de error al subir archivos.
$error_subida = "";

// Comprobar si se envio el formulario
// El metodo POST se utiliza cuando la administradora envia un documento.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Recibir el tipo de documento seleccionado.
    // Si no se envio el dato, se utiliza un texto vacio.
    $tipo_doc = $_POST["tipo_doc"] ?? "";

    // Lista de tipos de documentos que el sistema permite guardar.
    $tipos_permitidos = [
        "Factura",
        "Comprobante de pago",
        "Contrato",
        "Documento de venta",
        "Otro"
    ];

    // Comprobar que el tipo de documento seleccionado este permitido.
    if (!in_array($tipo_doc, $tipos_permitidos, true)) {
        $error_subida = "Selecciona un tipo de documento valido";

    // Comprobar que se haya recibido un archivo y que no tenga errores de subida.
    } elseif (
        !isset($_FILES["archivo"]) ||
        $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK
    ) {
        $error_subida = "No se pudo recibir el archivo. Intenta nuevamente";

    // Comprobar que el archivo no supere los 10 MB.
    } elseif ($_FILES["archivo"]["size"] > 10 * 1024 * 1024) {
        $error_subida = "El archivo no puede superar los 10 MB";

    } else {

        // Obtener el nombre original del archivo seleccionado.
        $nombre_original = $_FILES["archivo"]["name"];

        // Obtener la extension del archivo y convertirla a minusculas.
        // Por ejemplo: PDF pasa a ser pdf.
        $extension = strtolower(
            pathinfo($nombre_original, PATHINFO_EXTENSION)
        );

        // Lista de extensiones que el sistema permite subir.
        $extensiones_permitidas = [
            "pdf", "jpg", "jpeg", "png", "doc", "docx"
        ];

        // Relacionar cada extension con los tipos MIME que puede tener.
        // Esto permite comprobar el formato real del archivo.
        $tipos_mime = [
            "pdf" => ["application/pdf"],
            "jpg" => ["image/jpeg"],
            "jpeg" => ["image/jpeg"],
            "png" => ["image/png"],
            "doc" => [
                "application/msword",
                "application/x-ole-storage"
            ],
            "docx" => [
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/zip"
            ]
        ];

        // Revisar el tipo real del archivo
        // finfo obtiene el formato del archivo a partir de su contenido.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES["archivo"]["tmp_name"]);

        // Comprobar que la extension y el formato real esten permitidos.
        if (
            !in_array($extension, $extensiones_permitidas, true) ||
            !in_array($mime, $tipos_mime[$extension] ?? [], true)
        ) {
            $error_subida = "El archivo no es de un tipo permitido";

        } else {

            // Indicar la carpeta donde se van a guardar los archivos.
            // __DIR__ representa la ubicacion de este archivo PHP.
            $carpeta = __DIR__ . "/documentos_ventas/";

            // Comprobar si la carpeta existe.
            // Si no existe, crearla.
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            // Crear un nombre unico para el archivo
            // random_bytes genera datos aleatorios y bin2hex los convierte en texto.
            // Se utiliza para evitar que dos archivos tengan el mismo nombre.
            $nombre_seguro = bin2hex(random_bytes(16))
                . "." . $extension;

            // Unir la carpeta y el nombre para formar la ruta completa del archivo.
            $ruta_destino = $carpeta . $nombre_seguro;

            // Mover el archivo desde la carpeta temporal hasta la carpeta del sistema.
            if (
                move_uploaded_file(
                    $_FILES["archivo"]["tmp_name"],
                    $ruta_destino
                )
            ) {

                try {

                    // Preparar la consulta para guardar los datos del documento.
                    // Se guarda el tipo, el nombre del archivo y el numero de venta.
                    $sql_doc = "INSERT INTO documentacion
                                (tipo_doc, archivo, nro_venta)
                                VALUES (?, ?, ?)";

                    $stmt_doc = $conexion->prepare($sql_doc);

                    // Asociar los datos a la consulta.
                    // s significa texto y i significa numero entero.
                    $stmt_doc->bind_param(
                        "ssi",
                        $tipo_doc,
                        $nombre_seguro,
                        $nro_venta
                    );

                    // Ejecutar el INSERT para guardar el documento en la base de datos.
                    $stmt_doc->execute();

                    // Volver a la pagina de documentacion de la misma venta.
                    // El parametro subido=1 permite mostrar un mensaje de exito.
                    header(
                        "Location: documentacion.php?nro_venta="
                        . $nro_venta . "&subido=1"
                    );

                    // Detener la ejecucion despues de la redireccion.
                    exit();

                } catch (Throwable $e) {

                    // Si ocurrio un error al guardar en la base de datos,
                    // eliminar el archivo que ya se habia subido.
                    if (is_file($ruta_destino)) {
                        unlink($ruta_destino);
                    }

                    // Guardar el detalle del error en el registro de errores de PHP.
                    error_log(
                        "Error al guardar documentacion: "
                        . $e->getMessage()
                    );

                    // Mostrar un mensaje sencillo en la pagina.
                    $error_subida =
                        "No se pudo guardar el documento. Intenta nuevamente";
                }

            } else {

                // Mostrar un mensaje si no se pudo mover el archivo a la carpeta.
                $error_subida =
                    "No se pudo guardar el archivo en la carpeta";
            }
        }
    }
}

// Buscar los documentos de esta venta
// Se buscan solamente los archivos que pertenecen al numero de venta recibido.
// Se ordenan desde el documento mas reciente hasta el mas antiguo.
$sql_documentos = "SELECT nro_doc, tipo_doc, archivo
                   FROM documentacion
                   WHERE nro_venta = ?
                   ORDER BY nro_doc DESC";

// Preparar la consulta.
$stmt_documentos = $conexion->prepare($sql_documentos);

// Asociar el numero de venta.
$stmt_documentos->bind_param("i", $nro_venta);

// Ejecutar la consulta.
$stmt_documentos->execute();

// Obtener todos los documentos encontrados.
$resultado_documentos = $stmt_documentos->get_result();

// Guardar los documentos en un arreglo para mostrarlos en la tabla.
$documentos = $resultado_documentos->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documentacion de ventas - ECONTAINERS</title>

    <!-- Conectar los estilos generales del sistema. -->
    <link rel="stylesheet" href="estilos.css">

    <style>
        /* Contenido principal de la pagina */
        .documentacion-contenido {
            min-height: calc(100vh - 82px);
            padding: 35px 40px 50px;
            background-color: var(--gris-fondo, #f4f6f9);
            text-align: left;
        }

        /* Limitar el ancho del contenido y centrarlo en la pagina. */
        .documentacion-contenedor {
            max-width: 1150px;
            margin: 0 auto;
        }

        /* Titulo y boton para volver */
        .documentacion-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .documentacion-encabezado h1 {
            margin: 0 0 8px;
            color: var(--azul-oscuro, #082b54);
            font-size: 30px;
        }

        .documentacion-descripcion {
            margin: 0;
            color: var(--gris-texto, #666);
            font-size: 15px;
        }

        /* Apariencia del boton para volver al menu principal. */
        .boton-volver-documentacion {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 6px;
            background-color: var(--azul-oscuro, #082b54);
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        /* Cambiar el color del boton cuando se pasa el cursor. */
        .boton-volver-documentacion:hover {
            background-color: #164c80;
        }

        /* Tarjetas blancas */
        .documentacion-tarjeta {
            background-color: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e6e9ee;
        }

        /* Estilos de los titulos dentro de las tarjetas. */
        .documentacion-tarjeta h2 {
            margin: 0 0 20px;
            padding-bottom: 13px;
            border-bottom: 3px solid var(--naranja-econtainers, #f28c00);
            color: var(--azul-oscuro, #082b54);
            font-size: 21px;
        }

        /* Datos de la venta */
        .documentacion-datos {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        /* Apariencia de cada dato de la venta. */
        .documentacion-dato {
            padding: 15px;
            background-color: #f7f9fc;
            border: 1px solid #e5eaf0;
            border-radius: 7px;
        }

        .documentacion-dato span {
            display: block;
            margin-bottom: 7px;
            color: #667085;
            font-size: 13px;
        }

        .documentacion-dato strong {
            color: var(--azul-oscuro, #082b54);
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        /* Formulario */
        .documentacion-formulario {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 20px;
            align-items: end;
        }

        /* Estilos de los nombres de los campos del formulario. */
        .documentacion-campo label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            font-weight: 600;
        }

        /* Estilos de la lista de tipos y del selector de archivos. */
        .documentacion-campo select,
        .documentacion-campo input[type="file"] {
            width: 100%;
            min-height: 45px;
            padding: 10px;
            border: 1px solid #cfd5dd;
            border-radius: 6px;
            background-color: white;
            color: #333;
            font-size: 14px;
            box-sizing: border-box;
        }

        /* Resaltar el campo cuando la administradora lo selecciona. */
        .documentacion-campo select:focus,
        .documentacion-campo input:focus {
            outline: 2px solid rgba(242, 140, 0, 0.25);
            border-color: var(--naranja-econtainers, #f28c00);
        }

        /* Texto que explica los formatos y el peso permitido. */
        .documentacion-ayuda {
            display: block;
            margin-top: 7px;
            color: #6b7280;
            font-size: 12px;
        }

        /* Apariencia del boton para subir documentos. */
        .boton-subir-documentacion {
            margin-top: 22px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            background-color: var(--naranja-econtainers, #f28c00);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .boton-subir-documentacion:hover {
            background-color: #d97706;
        }

        /* Mensajes */
        .documentacion-mensaje {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Estilos para el mensaje de carga correcta. */
        .documentacion-exito {
            color: #166534;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        /* Estilos para los mensajes de error. */
        .documentacion-error {
            color: #991b1b;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
        }

        /* Tabla */
        .documentacion-tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        /* Estilos generales de la tabla de documentos. */
        .documentacion-tabla {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .documentacion-tabla th {
            padding: 15px;
            background-color: var(--azul-oscuro, #082b54);
            color: white;
            text-align: left;
            white-space: nowrap;
        }

        .documentacion-tabla td {
            padding: 14px 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            overflow-wrap: anywhere;
        }

        /* Dar un fondo diferente a las filas pares de la tabla. */
        .documentacion-tabla tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Cambiar el fondo de una fila cuando se pasa el cursor. */
        .documentacion-tabla tbody tr:hover {
            background-color: #fff7ed;
        }

        /* Apariencia del enlace para descargar un documento. */
        .boton-descargar-documento {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 5px;
            background-color: var(--naranja-econtainers, #f28c00);
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: 0.2s;
        }

        .boton-descargar-documento:hover {
            background-color: #d97706;
        }

        /* Mensaje que aparece cuando la venta no tiene documentos. */
        .documentacion-vacia {
            padding: 25px;
            border: 1px dashed #cbd5e1;
            border-radius: 7px;
            background-color: #f8fafc;
            color: #64748b;
            text-align: center;
        }

        /* Adaptacion para celulares */
        /* Cuando la pantalla es chica, se acomodan los elementos en una columna. */
        @media (max-width: 700px) {
            .documentacion-contenido {
                padding: 25px 15px;
            }

            .documentacion-encabezado h1 {
                font-size: 25px;
            }

            .documentacion-tarjeta {
                padding: 18px;
            }

            .documentacion-datos,
            .documentacion-formulario {
                grid-template-columns: 1fr;
            }

            .documentacion-tabla th,
            .documentacion-tabla td {
                padding: 11px;
            }

            .boton-subir-documentacion {
                width: 100%;
            }
        }
    </style>
</head>

<body class="principal-body">

    <!-- Encabezado del sistema -->
    <header class="encabezado">

        <!-- Mostrar el logo de ECONTAINERS. -->
        <img
            src="img/logo.png"
            alt="ECONTAINERS SOLUTIONS"
            class="encabezado-logo"
        >

        <!-- Mostrar el usuario que ingreso al sistema. -->
        <div class="usuario">
            Usuario:
            <strong>Administradora</strong>
        </div>

    </header>

    <main class="documentacion-contenido">
        <div class="documentacion-contenedor">

            <!-- Titulo -->
            <div class="documentacion-encabezado">
                <div>
                    <h1>Documentacion de la venta</h1>
                    <p class="documentacion-descripcion">
                        Consulta y guarda los archivos relacionados con esta venta
                    </p>
                </div>

                <!-- Enlace para volver a la pagina principal. -->
                <a
                    href="principal.php"
                    class="boton-volver-documentacion"
                >
                    Volver al inicio
                </a>
            </div>

            <!-- Datos de la venta -->
            <section class="documentacion-tarjeta">

                <h2>Datos de la venta</h2>

                <div class="documentacion-datos">

                    <!-- Mostrar el numero de venta recibido por GET. -->
                    <div class="documentacion-dato">
                        <span>Numero de venta</span>
                        <strong>
                            #<?= htmlspecialchars((string)$venta["nro_venta"]) ?>
                        </strong>
                    </div>

                    <!-- Mostrar la fecha de la venta. -->
                    <div class="documentacion-dato">
                        <span>Fecha</span>
                        <strong>
                            <?= htmlspecialchars((string)$venta["fecha"]) ?>
                        </strong>
                    </div>

                    <!-- Mostrar el nombre y apellido del cliente. -->
                    <div class="documentacion-dato">
                        <span>Cliente</span>
                        <strong>
                            <?= htmlspecialchars($venta["nombre"] . " " . $venta["apellido"]) ?>
                        </strong>
                    </div>

                    <!-- Mostrar el importe total con formato de moneda. -->
                    <div class="documentacion-dato">
                        <span>Importe total</span>
                        <strong>
                            $<?= number_format((float)$venta["monto_total"], 2, ",", ".") ?>
                        </strong>
                    </div>

                </div>

            </section>

            <!-- Formulario para agregar documentos -->
            <section class="documentacion-tarjeta">

                <h2>Agregar documentacion</h2>

                <!-- Mostrar el mensaje de exito despues de subir un documento. -->
                <?php if (isset($_GET["subido"]) && $_GET["subido"] === "1"): ?>
                    <div class="documentacion-mensaje documentacion-exito">
                        ¡El documento se guardo correctamente!
                    </div>
                <?php endif; ?>

                <!-- Mostrar el mensaje de error si ocurrio algun problema. -->
                <?php if ($error_subida !== ""): ?>
                    <div class="documentacion-mensaje documentacion-error">
                        <?= htmlspecialchars($error_subida) ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario que envia el tipo y el archivo al mismo PHP. -->
                <!-- multipart/form-data es necesario para enviar archivos. -->
                <form
                    action="documentacion.php?nro_venta=<?= (int)$nro_venta ?>"
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="documentacion-formulario">

                        <!-- Lista para elegir el tipo de documento. -->
                        <div class="documentacion-campo">
                            <label for="tipo_doc">
                                Tipo de documento
                            </label>

                            <select name="tipo_doc" id="tipo_doc" required>
                                <option value="">Selecciona una opcion</option>
                                <option value="Factura">Factura</option>
                                <option value="Comprobante de pago">Comprobante de pago</option>
                                <option value="Contrato">Contrato</option>
                                <option value="Documento de venta">Documento de venta</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <!-- Campo para seleccionar el archivo desde la computadora. -->
                        <div class="documentacion-campo">
                            <label for="archivo">
                                Seleccionar archivo
                            </label>

                            <input
                                type="file"
                                name="archivo"
                                id="archivo"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                required
                            >

                            <!-- Informar los formatos y el peso maximo permitido. -->
                            <small class="documentacion-ayuda">
                                Formatos permitidos: PDF, JPG, PNG, DOC y DOCX. Maximo 10 MB
                            </small>
                        </div>

                    </div>

                    <!-- Boton que envia el formulario para subir el documento. -->
                    <button
                        type="submit"
                        class="boton-subir-documentacion"
                    >
                        Subir documento
                    </button>

                </form>

            </section>

            <!-- Tabla de documentos guardados -->
            <section class="documentacion-tarjeta">

                <h2>Documentos guardados</h2>

                <!-- Comprobar si hay documentos para mostrar. -->
                <?php if (count($documentos) > 0): ?>

                    <div class="documentacion-tabla-contenedor">

                        <table class="documentacion-tabla">

                            <!-- Titulos de las columnas. -->
                            <thead>
                                <tr>
                                    <th>Tipo de documento</th>
                                    <th>Nombre del archivo</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>

                            <tbody>

                                <!-- Recorrer el arreglo y mostrar cada documento en una fila. -->
                                <?php foreach ($documentos as $documento): ?>

                                    <tr>
                                        <!-- Mostrar el tipo de documento. -->
                                        <td>
                                            <?= htmlspecialchars($documento["tipo_doc"]) ?>
                                        </td>

                                        <!-- Mostrar el nombre seguro con el que se guardo el archivo. -->
                                        <td>
                                            <?= htmlspecialchars($documento["archivo"]) ?>
                                        </td>

                                        <!-- Crear el enlace para descargar el documento. -->
                                        <td>
                                            <a
                                                href="descargar_documento.php?nro_doc=<?= (int)$documento["nro_doc"] ?>"
                                                class="boton-descargar-documento"
                                            >
                                                Descargar
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <!-- Mostrar este mensaje cuando la venta todavia no tiene documentos. -->
                    <div class="documentacion-vacia">
                        Todavia no hay documentos guardados para esta venta
                    </div>

                <?php endif; ?>

            </section>

        </div>
    </main>

</body>
</html>
