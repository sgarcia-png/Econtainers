
<?php

session_start();

if (!isset($_SESSION["administradora_id"])) {
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistema de Ventas - Pagina principal</title>

    <link rel="stylesheet" href="estilos.css">

    <style>
        .ventas-api {
            margin-top: 30px;
        }

        .tabla-ventas-contenedor {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        .tabla-ventas {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .tabla-ventas th,
        .tabla-ventas td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .tabla-ventas th {
            background: #103E73;
            color: white;
        }

        .tabla-ventas tr:hover td {
            background: #f5f7fa;
        }

        .estado-ventas {
            margin-top: 12px;
        }

        .boton-recargar {
            margin-top: 12px;
            padding: 10px 16px;
            background: #103E73;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .boton-recargar:hover {
            background: #082B54;
        }

        .boton-recargar:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        /* Accesos a los reportes */
        .tarjeta-reporte {
            display: flex;
            flex-direction: column;
        }

        .tarjeta-reporte .boton-tarjeta {
            align-self: flex-start;
            margin-top: auto;
        }
    </style>

</head>

<body class="principal-body">

    <!-- ENCABEZADO -->
    <header class="encabezado">

        <img
            src="img/logo.png"
            alt="ECONTAINERS"
            class="encabezado-logo"
        >

        <div class="usuario">
            Usuario:
            <strong>Administradora</strong>
        </div>

    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="contenido-principal">

        <h1 class="titulo-principal">
            Sistema de Ventas
        </h1>

        <p class="descripcion-principal">
            Gestion y consulta de ventas de contenedores maritimos
        </p>


        <!-- ADMINISTRACION -->
        <section class="seccion">

            <h2 class="seccion-titulo">
                Administracion
            </h2>

            <div class="tarjetas">

                <!-- 1. VENTAS -->
                <article class="tarjeta tarjeta-roja">

                    <h3>Ventas</h3>

                    <p>
                        Acceder a la pantalla para registrar nuevas ventas
                    </p>

                    <a
                        href="nueva_venta.php"
                        class="boton-tarjeta"
                    >
                        Cargar venta
                    </a>

                </article>


                <!-- 2. PAGOS -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Pagos</h3>

                    <p>
                        Registrar pagos de clientes y consultar
                        los pagos realizados
                    </p>

                    <a
                        href="reporte_pagos.php"
                        class="boton-tarjeta"
                    >
                        Gestionar pagos
                    </a>

                </article>


                <!-- 3. CLIENTES -->
                <article class="tarjeta tarjeta-naranja">

                    <h3>Clientes</h3>

                    <p>
                        Registrar, consultar, modificar y eliminar
                        clientes del sistema
                    </p>

                    <a
                        href="abm_clientes.html"
                        class="boton-tarjeta"
                    >
                        Administrar clientes
                    </a>

                </article>


                <!-- 4. STOCK DE CONTENEDORES -->
                <article class="tarjeta tarjeta-roja">

                    <h3>Stock de contenedores</h3>

                    <p>
                        Registrar, consultar y controlar los contenedores
                        disponibles y vendidos en los diferentes depósitos.
                    </p>

                    <a
                        href="stock.php"
                        class="boton-tarjeta"
                    >
                        Gestionar stock
                    </a>

                </article>


                <!-- 5. TIPOS DE CONTENEDORES -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Tipos de contenedores</h3>

                    <p>
                        Administrar los tipos permitidos:
                        20 pies, 40 pies, 40HC y reefer
                    </p>

                    <a
                        href="abm_tipos_contenedor.html"
                        class="boton-tarjeta"
                    >
                        Administrar tipos
                    </a>

                </article>


                <!-- 6. REPORTE POR FECHA -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Reporte por fecha</h3>

                    <p>
                        Consultar las ventas realizadas entre dos fechas
                    </p>

                    <a
                        href="reporte_ventas_fecha.html"
                        class="boton-tarjeta"
                    >
                        Ver reporte
                    </a>

                </article>


                <!-- 7. REPORTE POR ESTADO DE PAGO -->
                <article class="tarjeta tarjeta-naranja">

                    <h3>Reporte por estado de pago</h3>

                    <p>
                        Consultar las ventas pendientes, parciales, pagadas o todas las ventas
                    </p>

                    <a
                        href="reporte_ventas_estado.html"
                        class="boton-tarjeta"
                    >
                        Ver reporte
                    </a>

                </article>

            </div>

        </section>


        <!-- CONTENEDORES MARITIMOS -->
        <section class="seccion">

            <h2 class="seccion-titulo">
                Contenedores Maritimos
            </h2>

            <div class="tarjetas">

                <article class="tarjeta tarjeta-roja">

                    <h3>20 pies</h3>

                    <p>
                        Consultar ventas de contenedores de 20 pies
                    </p>

                    <a href="ventas.php?tipo=20" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>

                <article class="tarjeta tarjeta-azul">

                    <h3>40 pies</h3>

                    <p>
                        Consultar ventas de contenedores de 40 pies
                    </p>

                    <a href="ventas.php?tipo=40" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>

                <article class="tarjeta tarjeta-naranja">

                    <h3>40 pies HC</h3>

                    <p>
                        Consultar ventas de contenedores de 40 pies HC
                    </p>

                    <a href="ventas.php?tipo=40hc" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>

                <article class="tarjeta tarjeta-amarilla">

                    <h3>Reefer</h3>

                    <p>
                        Consultar ventas de contenedores refrigerados
                    </p>

                    <a href="ventas.php?tipo=reefer" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>

            </div>

        </section>


        <!-- ULTIMAS VENTAS CONSULTADAS DESDE LA API -->
        <section class="seccion ventas-api">

            <h2 class="seccion-titulo">
                ultimas ventas registradas
            </h2>

            <p>
                Esta informacion se obtiene de la base de datos
                mediante la API y JavaScript
            </p>

            <button
                type="button"
                id="btnRecargarVentas"
                class="boton-recargar"
            >
                Actualizar ventas
            </button>

            <p
                id="estadoVentas"
                class="estado-ventas"
                aria-live="polite"
            >
                Cargando ventas...
            </p>

            <div class="tabla-ventas-contenedor">

                <table class="tabla-ventas">

                    <thead>
                        <tr>
                            <th>N.º de venta</th>
                            <th>Fecha</th>
                            <th>Cliente (DNI)</th>
                            <th>Monto total</th>
                            <th>Estado de pago</th>
                            <th>Documentacion</th>
                        </tr>
                    </thead>

                    <tbody id="tablaVentas">

                        <tr>
                            <td colspan="6">Cargando datos...</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>


    <!-- PIE DE PAGINA -->
    <footer class="pie">
        Sistema de Ventas - ECONTAINERS
    </footer>


    <script>

        const tablaVentas = document.getElementById("tablaVentas");

        const estadoVentas = document.getElementById("estadoVentas");

        const btnRecargarVentas =
            document.getElementById("btnRecargarVentas");


        function crearCelda(valor) {

            const celda = document.createElement("td");

            celda.textContent = valor ?? "";

            return celda;

        }


        function mostrarMonto(valor) {

            const monto = Number(valor);

            if (!Number.isFinite(monto)) {
                return "No disponible";
            }

            return monto.toLocaleString("es-AR", {
                style: "currency",
                currency: "ARS",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        }


        async function cargarVentas() {

            btnRecargarVentas.disabled = true;

            estadoVentas.textContent = "Consultando ventas...";

            tablaVentas.replaceChildren();

            try {

                const respuesta = await fetch("api/ventas.php", {

                    method: "GET",

                    headers: {
                        "Accept": "application/json"
                    },

                    cache: "no-store"

                });


                const resultado = await respuesta.json();


                if (!respuesta.ok) {

                    throw new Error(
                        "No se pudieron consultar las ventas"
                    );

                }


                if (resultado.ok === false) {

                    throw new Error(
                        "La API informo un error al consultar las ventas"
                    );

                }


                const ventas = Array.isArray(resultado)
                    ? resultado
                    : (resultado.datos ?? resultado.data);


                if (!Array.isArray(ventas)) {

                    throw new Error(
                        "La respuesta de la API no tiene el formato esperado"
                    );

                }


                if (ventas.length === 0) {

                    const fila = document.createElement("tr");

                    const celda = crearCelda(
                        "Todavia no hay ventas registradas"
                    );

                    celda.colSpan = 6;

                    fila.appendChild(celda);

                    tablaVentas.appendChild(fila);

                    estadoVentas.textContent =
                        "Consulta realizada correctamente";

                    return;

                }


                ventas.forEach(function (venta) {

                    const fila = document.createElement("tr");


                    fila.appendChild(crearCelda(venta.nro_venta));

                    fila.appendChild(crearCelda(venta.fecha));

                    fila.appendChild(crearCelda(venta.dni_cliente));

                    fila.appendChild(
                        crearCelda(mostrarMonto(venta.monto_total))
                    );

                    fila.appendChild(crearCelda(venta.estado_pago));


                    // Crear la celda de documentacion

                    const celdaDocumentacion = document.createElement("td");


                    // Crear el enlace para abrir los documentos de esta venta

                    const botonDocumentacion = document.createElement("a");

                    botonDocumentacion.textContent = "Ver documentos";

                    botonDocumentacion.className = "boton-documentacion";


                    const parametros = new URLSearchParams({
                        nro_venta: String(venta.nro_venta)
                    });


                    botonDocumentacion.href =
                        "documentacion.php?" + parametros.toString();


                    celdaDocumentacion.appendChild(botonDocumentacion);

                    fila.appendChild(celdaDocumentacion);


                    // Agregar la fila completa

                    tablaVentas.appendChild(fila);

                });


                estadoVentas.textContent =
                    "Ventas cargadas correctamente: " +
                    ventas.length + ".";


            } catch (error) {

                const fila = document.createElement("tr");

                const celda = crearCelda(
                    "No se pudieron cargar las ventas"
                );

                celda.colSpan = 6;

                fila.appendChild(celda);

                tablaVentas.appendChild(fila);


                estadoVentas.textContent =
                    error.message +
                    " Revisa que api/ventas.php este funcionando";


            } finally {

                btnRecargarVentas.disabled = false;

            }

        }


        btnRecargarVentas.addEventListener("click", cargarVentas);

        cargarVentas();

    </script>

</body>
</html>
