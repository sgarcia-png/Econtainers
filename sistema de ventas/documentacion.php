
<?php

session_start();

if (!isset($_SESSION["administradora_id"])) {
    header("Location: index.php");
    exit();
}

require_once "config/conexion.php";

// Recibir el numero de venta
$nro_venta = filter_input(INPUT_GET, "nro_venta", FILTER_VALIDATE_INT);

if (!$nro_venta || $nro_venta <= 0) {
    exit("No se indico un numero de venta valido");
}

// Buscar la venta y los datos del cliente
$sql = "SELECT v.nro_venta, v.fecha, v.monto_total,
               c.nombre, c.apellido
        FROM ventas v
        INNER JOIN cliente c ON c.DNI = v.dni_cliente
        WHERE v.nro_venta = ? AND v.eliminado = 0";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $nro_venta);
$stmt->execute();

$resultado = $stmt->get_result();
$venta = $resultado->fetch_assoc();

if (!$venta) {
    exit("No se encontro la venta indicada");
}

$error_subida = "";

// Comprobar si se envio el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tipo_doc = $_POST["tipo_doc"] ?? "";

    $tipos_permitidos = [
        "Factura",
        "Comprobante de pago",
        "Contrato",
        "Documento de venta",
        "Otro"
    ];

    if (!in_array($tipo_doc, $tipos_permitidos, true)) {
        $error_subida = "Selecciona un tipo de documento valido";

    } elseif (
        !isset($_FILES["archivo"]) ||
        $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK
    ) {
        $error_subida = "No se pudo recibir el archivo. Intenta nuevamente";

    } elseif ($_FILES["archivo"]["size"] > 10 * 1024 * 1024) {
        $error_subida = "El archivo no puede superar los 10 MB";

    } else {

        $nombre_original = $_FILES["archivo"]["name"];

        $extension = strtolower(
            pathinfo($nombre_original, PATHINFO_EXTENSION)
        );

        $extensiones_permitidas = [
            "pdf", "jpg", "jpeg", "png", "doc", "docx"
        ];

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
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES["archivo"]["tmp_name"]);

        if (
            !in_array($extension, $extensiones_permitidas, true) ||
            !in_array($mime, $tipos_mime[$extension] ?? [], true)
        ) {
            $error_subida = "El archivo no es de un tipo permitido";

        } else {

            $carpeta = __DIR__ . "/documentos_ventas/";

            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            // Crear un nombre unico para el archivo
            $nombre_seguro = bin2hex(random_bytes(16))
                . "." . $extension;

            $ruta_destino = $carpeta . $nombre_seguro;

            if (
                move_uploaded_file(
                    $_FILES["archivo"]["tmp_name"],
                    $ruta_destino
                )
            ) {

                try {

                    $sql_doc = "INSERT INTO documentacion
                                (tipo_doc, archivo, nro_venta)
                                VALUES (?, ?, ?)";

                    $stmt_doc = $conexion->prepare($sql_doc);

                    $stmt_doc->bind_param(
                        "ssi",
                        $tipo_doc,
                        $nombre_seguro,
                        $nro_venta
                    );

                    $stmt_doc->execute();

                    header(
                        "Location: documentacion.php?nro_venta="
                        . $nro_venta . "&subido=1"
                    );

                    exit();

                } catch (Throwable $e) {

                    if (is_file($ruta_destino)) {
                        unlink($ruta_destino);
                    }

                    error_log(
                        "Error al guardar documentacion: "
                        . $e->getMessage()
                    );

                    $error_subida =
                        "No se pudo guardar el documento. Intenta nuevamente";
                }

            } else {

                $error_subida =
                    "No se pudo guardar el archivo en la carpeta";
            }
        }
    }
}

// Buscar los documentos de esta venta
$sql_documentos = "SELECT nro_doc, tipo_doc, archivo
                   FROM documentacion
                   WHERE nro_venta = ?
                   ORDER BY nro_doc DESC";

$stmt_documentos = $conexion->prepare($sql_documentos);
$stmt_documentos->bind_param("i", $nro_venta);
$stmt_documentos->execute();

$resultado_documentos = $stmt_documentos->get_result();
$documentos = $resultado_documentos->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Documentacion de ventas - ECONTAINERS</title>

    <link rel="stylesheet" href="estilos.css">

    <style>
        /* Contenido principal de la pagina */
        .documentacion-contenido {
            min-height: calc(100vh - 82px);
            padding: 35px 40px 50px;
            background-color: var(--gris-fondo, #f4f6f9);
            text-align: left;
        }

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

        .documentacion-campo label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            font-weight: 600;
        }

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

        .documentacion-campo select:focus,
        .documentacion-campo input:focus {
            outline: 2px solid rgba(242, 140, 0, 0.25);
            border-color: var(--naranja-econtainers, #f28c00);
        }

        .documentacion-ayuda {
            display: block;
            margin-top: 7px;
            color: #6b7280;
            font-size: 12px;
        }

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

        .documentacion-exito {
            color: #166534;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

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

        .documentacion-tabla tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .documentacion-tabla tbody tr:hover {
            background-color: #fff7ed;
        }

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

        .documentacion-vacia {
            padding: 25px;
            border: 1px dashed #cbd5e1;
            border-radius: 7px;
            background-color: #f8fafc;
            color: #64748b;
            text-align: center;
        }

        /* Adaptacion para celulares */
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

        <img
            src="img/logo.png"
            alt="ECONTAINERS SOLUTIONS"
            class="encabezado-logo"
        >

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

                    <div class="documentacion-dato">
                        <span>Numero de venta</span>
                        <strong>
                            #<?= htmlspecialchars((string)$venta["nro_venta"]) ?>
                        </strong>
                    </div>

                    <div class="documentacion-dato">
                        <span>Fecha</span>
                        <strong>
                            <?= htmlspecialchars((string)$venta["fecha"]) ?>
                        </strong>
                    </div>

                    <div class="documentacion-dato">
                        <span>Cliente</span>
                        <strong>
                            <?= htmlspecialchars($venta["nombre"] . " " . $venta["apellido"]) ?>
                        </strong>
                    </div>

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

                <?php if (isset($_GET["subido"]) && $_GET["subido"] === "1"): ?>
                    <div class="documentacion-mensaje documentacion-exito">
                        ¡El documento se guardo correctamente!
                    </div>
                <?php endif; ?>

                <?php if ($error_subida !== ""): ?>
                    <div class="documentacion-mensaje documentacion-error">
                        <?= htmlspecialchars($error_subida) ?>
                    </div>
                <?php endif; ?>

                <form
                    action="documentacion.php?nro_venta=<?= (int)$nro_venta ?>"
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="documentacion-formulario">

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

                            <small class="documentacion-ayuda">
                                Formatos permitidos: PDF, JPG, PNG, DOC y DOCX. Maximo 10 MB
                            </small>
                        </div>

                    </div>

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

                <?php if (count($documentos) > 0): ?>

                    <div class="documentacion-tabla-contenedor">

                        <table class="documentacion-tabla">

                            <thead>
                                <tr>
                                    <th>Tipo de documento</th>
                                    <th>Nombre del archivo</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($documentos as $documento): ?>

                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($documento["tipo_doc"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($documento["archivo"]) ?>
                                        </td>

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

                    <div class="documentacion-vacia">
                        Todavia no hay documentos guardados para esta venta
                    </div>

                <?php endif; ?>

            </section>

        </div>
    </main>

</body>
</html>
