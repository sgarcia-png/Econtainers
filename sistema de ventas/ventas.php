<?php

session_start();

// Proteger la pantalla: solo puede ingresar la administradora
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

    <title>Ventas registradas - ECONTAINERS</title>

    <link rel="stylesheet" href="estilos.css">
</head>

<body class="principal-body">

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

    <main class="contenido-principal">

        <div class="ventas-encabezado">

            <div>
                <h1 class="titulo-principal">Ventas registradas</h1>

                <p class="descripcion-principal">
                    Consulta de las ventas almacenadas en el sistema
                </p>
            </div>

            <a href="principal.php" class="boton-volver">
                Volver al inicio
            </a>

        </div>

        <section class="resumen-ventas">

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Total de ventas</span>
                <strong id="totalVentas">—</strong>
            </div>

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Importe total</span>
                <strong id="importeVentas">—</strong>
            </div>

        </section>

        <section class="seccion">

            <h2 class="seccion-titulo">Filtrar ventas por fecha</h2>

            <form id="filtroFechas" class="filtro-fechas">

                <div class="campo-filtro">
                    <label for="fechaDesde">Fecha desde:</label>
                    <input type="date" id="fechaDesde">
                </div>

                <div class="campo-filtro">
                    <label for="fechaHasta">Fecha hasta:</label>
                    <input type="date" id="fechaHasta">
                </div>

                <div class="botones-filtro">

                    <button type="submit" class="boton-filtrar">
                        Filtrar
                    </button>

                    <button
                        type="button"
                        id="btnLimpiarFechas"
                        class="boton-limpiar"
                    >
                        Limpiar filtros
                    </button>

                </div>

            </form>

        </section>

        <section class="seccion">

            <h2 class="seccion-titulo">Listado de ventas</h2>

            <div id="estadoVentas" class="estado-ventas" role="status">
                Cargando ventas...
            </div>

            <div class="tabla-contenedor" id="contenedorTabla" hidden>

                <table class="tabla-ventas">

                    <thead>
                        <tr>
                            <th>N.º de venta</th>
                            <th>Fecha</th>
                            <th>DNI del cliente</th>
                            <th>Importe total</th>
                            <th>Estado del pago</th>
                            <th>Documentacion</th>
                        </tr>
                    </thead>

                    <tbody id="tablaVentas"></tbody>

                </table>

            </div>

            <button
                type="button"
                id="btnReintentar"
                class="boton-reintentar"
                hidden
            >
                Volver a cargar
            </button>

        </section>

    </main>

    <footer class="pie">
        Sistema de Ventas - ECONTAINERS
    </footer>

    <script>
        const estadoVentas = document.getElementById("estadoVentas");
        const tablaVentas = document.getElementById("tablaVentas");
        const contenedorTabla = document.getElementById("contenedorTabla");
        const totalVentas = document.getElementById("totalVentas");
        const importeVentas = document.getElementById("importeVentas");
        const btnReintentar = document.getElementById("btnReintentar");

        const filtroFechas = document.getElementById("filtroFechas");
        const fechaDesde = document.getElementById("fechaDesde");
        const fechaHasta = document.getElementById("fechaHasta");
        const btnLimpiarFechas = document.getElementById("btnLimpiarFechas");

        const formatoMoneda = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        async function cargarVentas() {

            const desde = fechaDesde.value;
            const hasta = fechaHasta.value;

            // Comprobar que se hayan elegido las dos fechas
            if ((desde === "") !== (hasta === "")) {
                estadoVentas.textContent =
                    "Seleccioná la fecha desde y la fecha hasta.";

                estadoVentas.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            // Comprobar que la fecha inicial no sea posterior a la final
            if (desde !== "" && hasta !== "" && desde > hasta) {
                estadoVentas.textContent =
                    "La fecha desde no puede ser posterior a la fecha hasta.";

                estadoVentas.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            estadoVentas.hidden = false;
            estadoVentas.textContent = "Cargando ventas...";
            contenedorTabla.hidden = true;
            btnReintentar.hidden = true;
            tablaVentas.replaceChildren();

            try {

                // Conservar el filtro por tipo si viene desde otra pantalla
                const parametros = new URLSearchParams(
                    window.location.search
                );

                const tipo = parametros.get("tipo") || "";

                // Preparar los filtros para enviarlos a la API
                const filtros = new URLSearchParams();

                if (tipo !== "") {
                    filtros.set("tipo", tipo);
                }

                if (desde !== "" && hasta !== "") {
                    filtros.set("fecha_desde", desde);
                    filtros.set("fecha_hasta", hasta);
                }

                const urlAPI = "api/ventas.php" +
                    (filtros.toString() !== ""
                        ? "?" + filtros.toString()
                        : "");

                // CONSULTAR LA API
                const respuesta = await fetch(urlAPI, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(
                        resultado.mensaje ||
                        "No se pudieron consultar las ventas."
                    );
                }

                if (!Array.isArray(resultado.datos)) {
                    throw new Error(
                        "La respuesta de la API no tiene el formato esperado."
                    );
                }

                const ventas = resultado.datos;

                // Actualizar el total de ventas encontradas
                totalVentas.textContent = ventas.length;

                // Calcular el importe total de las ventas encontradas
                const importeTotal = ventas.reduce(function (suma, venta) {
                    const importe = Number(venta.monto_total);

                    return suma +
                        (Number.isFinite(importe) ? importe : 0);
                }, 0);

                importeVentas.textContent =
                    formatoMoneda.format(importeTotal);

                // Si no hay resultados, mostrar un mensaje
                if (ventas.length === 0) {
                    estadoVentas.textContent =
                        "No hay ventas para el período seleccionado.";

                    estadoVentas.hidden = false;
                    return;
                }

                // Crear una fila por cada venta recibida
                ventas.forEach(function (venta) {

                    const fila = document.createElement("tr");

                    const valores = [
                        venta.nro_venta,
                        venta.fecha
                            ? String(venta.fecha)
                                .split("-")
                                .reverse()
                                .join("/")
                            : "—",
                        venta.dni_cliente,
                        formatoMoneda.format(
                            Number(venta.monto_total) || 0
                        ),
                        venta.estado_pago
                    ];

                    valores.forEach(function (valor, indice) {

                        const celda = document.createElement("td");

                        celda.textContent = valor ?? "—";

                        if (indice === 4) {
                            const estado = document.createElement("span");

                            estado.className = "estado-pago";
                            estado.textContent = valor || "Sin estado";

                            celda.replaceChildren(estado);
                        }

                        fila.appendChild(celda);
                    });

                    
                    // Crear la columna de documentacion
                    const celdaDocumentacion = document.createElement("td");

                    // Crear el enlace para abrir los documentos de la venta
                    const botonDocumentacion = document.createElement("a");

                    botonDocumentacion.textContent = "Ver documentos";
                    botonDocumentacion.className = "boton-documentacion";

                    const parametrosDocumento = new URLSearchParams({
                        nro_venta: String(venta.nro_venta)
                    });

                    botonDocumentacion.href =
                        "documentacion.php?" + parametrosDocumento.toString();

                    celdaDocumentacion.appendChild(botonDocumentacion);
                    fila.appendChild(celdaDocumentacion);

                    // Agregar la fila completa a la tabla
                    tablaVentas.appendChild(fila);

                });

                // Mostrar la tabla cuando hay resultados
                estadoVentas.hidden = true;
                contenedorTabla.hidden = false;

            } catch (error) {

                console.error("Error al cargar las ventas:", error);

                estadoVentas.textContent =
                    "No se pudieron cargar las ventas. " +
                    "Verificá la conexión con el servidor e intentá nuevamente.";

                estadoVentas.hidden = false;
                btnReintentar.hidden = false;
            }
        }

        // Aplicar el filtro al enviar el formulario
        filtroFechas.addEventListener("submit", function (evento) {
            evento.preventDefault();
            cargarVentas();
        });

        // Limpiar las fechas y volver a cargar todas las ventas
        btnLimpiarFechas.addEventListener("click", function () {
            fechaDesde.value = "";
            fechaHasta.value = "";
            cargarVentas();
        });

        // Volver a intentar si se produce un error
        btnReintentar.addEventListener("click", cargarVentas);

        // Cargar todas las ventas al abrir la pantalla
        cargarVentas();

    </script>

</body>
</html>
