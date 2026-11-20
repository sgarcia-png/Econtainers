
<?php

// Iniciar la sesion para comprobar que el usuario haya ingresado al sistema
session_start();

// Proteger la pantalla: solo puede ingresar la administradora
if (!isset($_SESSION["administradora_id"])) {
    // Si no hay una sesion iniciada, volver al inicio de sesion
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <!-- Configuracion general de la pagina -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador -->
    <title>Ventas registradas - ECONTAINERS</title>

    <!-- Conectar la hoja de estilos principal -->
    <link rel="stylesheet" href="estilos.css">

</head>

<body class="principal-body">

    <!-- Encabezado con el logo y el usuario que ingreso al sistema -->
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

    <!-- Contenido principal de la pantalla -->
    <main class="contenido-principal">

        <!-- Titulo, descripcion y boton para volver al menu -->
        <div class="ventas-encabezado">

            <div>
                <h1 class="titulo-principal">Ventas registradas</h1>

                <p class="descripcion-principal">
                    Consulta de las ventas almacenadas en el sistema
                </p>
            </div>

            <!-- Enlace para regresar a la pagina principal -->
            <a href="principal.php" class="boton-volver">
                Volver al inicio
            </a>

        </div>

        <!-- Seccion que muestra un resumen de las ventas -->
        <section class="resumen-ventas">

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Total de ventas</span>

                <!-- JavaScript coloca aqui la cantidad de ventas encontradas -->
                <strong id="totalVentas">—</strong>
            </div>

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Importe total</span>

                <!-- JavaScript coloca aqui la suma de los importes -->
                <strong id="importeVentas">—</strong>
            </div>

        </section>

        <!-- Seccion para filtrar las ventas por fecha -->
        <section class="seccion">

            <h2 class="seccion-titulo">Filtrar ventas por fecha</h2>

            <form id="filtroFechas" class="filtro-fechas">

                <!-- Fecha inicial del periodo que se quiere consultar -->
                <div class="campo-filtro">
                    <label for="fechaDesde">Fecha desde:</label>
                    <input type="date" id="fechaDesde">
                </div>

                <!-- Fecha final del periodo que se quiere consultar -->
                <div class="campo-filtro">
                    <label for="fechaHasta">Fecha hasta:</label>
                    <input type="date" id="fechaHasta">
                </div>

                <div class="botones-filtro">

                    <!-- Boton para aplicar el filtro -->
                    <button type="submit" class="boton-filtrar">
                        Filtrar
                    </button>

                    <!-- Boton para borrar las fechas seleccionadas -->
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

        <!-- Seccion donde se muestra la tabla de ventas -->
        <section class="seccion">

            <h2 class="seccion-titulo">Listado de ventas</h2>

            <!-- Mensaje que informa si se estan cargando las ventas o si ocurrio un error -->
            <div id="estadoVentas" class="estado-ventas" role="status">
                Cargando ventas...
            </div>

            <!-- Contenedor de la tabla, oculto hasta que haya resultados -->
            <div class="tabla-contenedor" id="contenedorTabla" hidden>

                <table class="tabla-ventas">

                    <!-- Titulos de las columnas -->
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

                    <!-- JavaScript agrega las ventas dentro de este cuerpo -->
                    <tbody id="tablaVentas"></tbody>

                </table>

            </div>

            <!-- Boton que aparece cuando ocurre un error al cargar las ventas -->
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

    <!-- Pie de pagina -->
    <footer class="pie">
        Sistema de Ventas - ECONTAINERS
    </footer>

    <!-- Codigo JavaScript que consulta y muestra las ventas -->
    <script>

        // Obtener los elementos de la pagina mediante sus identificadores
        const estadoVentas = document.getElementById("estadoVentas");
        const tablaVentas = document.getElementById("tablaVentas");
        const contenedorTabla = document.getElementById("contenedorTabla");
        const totalVentas = document.getElementById("totalVentas");
        const importeVentas = document.getElementById("importeVentas");
        const btnReintentar = document.getElementById("btnReintentar");

        // Obtener los campos y botones del filtro por fechas
        const filtroFechas = document.getElementById("filtroFechas");
        const fechaDesde = document.getElementById("fechaDesde");
        const fechaHasta = document.getElementById("fechaHasta");
        const btnLimpiarFechas = document.getElementById("btnLimpiarFechas");

        // Configurar el formato de moneda argentina
        const formatoMoneda = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Funcion principal que consulta las ventas y las muestra en la tabla
        async function cargarVentas() {

            // Obtener las fechas que selecciono el usuario
            const desde = fechaDesde.value;
            const hasta = fechaHasta.value;

            // Comprobar que se hayan elegido las dos fechas o ninguna
            if ((desde === "") !== (hasta === "")) {

                // Mostrar un mensaje si falta completar una de las fechas
                estadoVentas.textContent =
                    "Seleccioná la fecha desde y la fecha hasta.";

                estadoVentas.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            // Comprobar que la fecha inicial no sea posterior a la final
            if (desde !== "" && hasta !== "" && desde > hasta) {

                // Mostrar un mensaje si el rango de fechas es incorrecto
                estadoVentas.textContent =
                    "La fecha desde no puede ser posterior a la fecha hasta.";

                estadoVentas.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            // Mostrar el mensaje de carga y ocultar la tabla anterior
            estadoVentas.hidden = false;
            estadoVentas.textContent = "Cargando ventas...";
            contenedorTabla.hidden = true;
            btnReintentar.hidden = true;

            // Borrar las filas anteriores para mostrar los nuevos resultados
            tablaVentas.replaceChildren();

            try {

                // Conservar el filtro por tipo si viene desde otra pantalla
                // Por ejemplo, desde una tarjeta de contenedores del menu principal
                const parametros = new URLSearchParams(
                    window.location.search
                );

                // Obtener el tipo de contenedor enviado en la URL
                const tipo = parametros.get("tipo") || "";

                // Preparar los filtros que se enviaran a la API
                const filtros = new URLSearchParams();

                // Agregar el tipo solamente si fue recibido
                if (tipo !== "") {
                    filtros.set("tipo", tipo);
                }

                // Agregar las fechas solamente si se seleccionaron las dos
                if (desde !== "" && hasta !== "") {
                    filtros.set("fecha_desde", desde);
                    filtros.set("fecha_hasta", hasta);
                }

                // Construir la direccion de la API con los filtros correspondientes
                const urlAPI = "api/ventas.php" +
                    (filtros.toString() !== ""
                        ? "?" + filtros.toString()
                        : "");

                // CONSULTAR LA API
                // fetch envia una solicitud GET para obtener las ventas
                const respuesta = await fetch(urlAPI, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                // Convertir la respuesta de la API a un objeto JavaScript
                const resultado = await respuesta.json();

                // Comprobar si la respuesta contiene algun error
                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(
                        resultado.mensaje ||
                        "No se pudieron consultar las ventas."
                    );
                }

                // Comprobar que los datos recibidos sean una lista
                if (!Array.isArray(resultado.datos)) {
                    throw new Error(
                        "La respuesta de la API no tiene el formato esperado."
                    );
                }

                // Guardar la lista de ventas recibidas
                const ventas = resultado.datos;

                // Actualizar el total de ventas encontradas
                totalVentas.textContent = ventas.length;

                // Calcular el importe total de todas las ventas encontradas
                // reduce recorre la lista y va acumulando los importes
                const importeTotal = ventas.reduce(function (suma, venta) {

                    // Convertir el importe recibido a numero
                    const importe = Number(venta.monto_total);

                    // Sumar el importe si es un numero valido
                    return suma +
                        (Number.isFinite(importe) ? importe : 0);
                }, 0);

                // Mostrar el importe total con formato de moneda argentina
                importeVentas.textContent =
                    formatoMoneda.format(importeTotal);

                // Si no hay resultados, mostrar un mensaje y terminar la funcion
                if (ventas.length === 0) {
                    estadoVentas.textContent =
                        "No hay ventas para el período seleccionado.";

                    estadoVentas.hidden = false;
                    return;
                }

                // Recorrer todas las ventas recibidas desde la API
                ventas.forEach(function (venta) {

                    // Crear una nueva fila para la venta
                    const fila = document.createElement("tr");

                    // Preparar los valores que se mostraran en las columnas
                    const valores = [
                        venta.nro_venta,

                        // Cambiar el formato de la fecha de AAAA-MM-DD a DD/MM/AAAA
                        venta.fecha
                            ? String(venta.fecha)
                                .split("-")
                                .reverse()
                                .join("/")
                            : "—",

                        venta.dni_cliente,

                        // Mostrar el importe con formato de moneda
                        formatoMoneda.format(
                            Number(venta.monto_total) || 0
                        ),

                        venta.estado_pago
                    ];

                    // Recorrer los valores para crear las celdas de la fila
                    valores.forEach(function (valor, indice) {

                        // Crear una celda de la tabla
                        const celda = document.createElement("td");

                        // Colocar el valor dentro de la celda
                        celda.textContent = valor ?? "—";

                        // Comprobar si se esta creando la columna del estado de pago
                        if (indice === 4) {

                            // Crear un elemento para mostrar el estado
                            const estado = document.createElement("span");

                            estado.className = "estado-pago";
                            estado.textContent = valor || "Sin estado";

                            // Colocar el estado dentro de la celda
                            celda.replaceChildren(estado);
                        }

                        // Agregar la celda a la fila
                        fila.appendChild(celda);
                    });


                    // Crear la columna de documentacion
                    const celdaDocumentacion = document.createElement("td");

                    // Crear el enlace para abrir los documentos de la venta
                    const botonDocumentacion = document.createElement("a");

                    // Colocar el texto y la clase del enlace
                    botonDocumentacion.textContent = "Ver documentos";
                    botonDocumentacion.className = "boton-documentacion";

                    // Preparar el numero de venta que se enviara a la pagina de documentacion
                    const parametrosDocumento = new URLSearchParams({
                        nro_venta: String(venta.nro_venta)
                    });

                    // Crear el enlace incluyendo el numero de venta
                    botonDocumentacion.href =
                        "documentacion.php?" + parametrosDocumento.toString();

                    // Agregar el enlace a la celda
                    celdaDocumentacion.appendChild(botonDocumentacion);

                    // Agregar la celda de documentacion a la fila
                    fila.appendChild(celdaDocumentacion);

                    // Agregar la fila completa a la tabla
                    tablaVentas.appendChild(fila);

                });

                // Mostrar la tabla cuando hay resultados
                estadoVentas.hidden = true;
                contenedorTabla.hidden = false;

            } catch (error) {

                // Mostrar el error en la consola del navegador
                console.error("Error al cargar las ventas:", error);

                // Informar al usuario que no se pudieron cargar las ventas
                estadoVentas.textContent =
                    "No se pudieron cargar las ventas. " +
                    "Verificá la conexión con el servidor e intentá nuevamente.";

                estadoVentas.hidden = false;

                // Mostrar el boton para volver a intentar
                btnReintentar.hidden = false;
            }
        }

        // Aplicar el filtro cuando se envia el formulario de fechas
        filtroFechas.addEventListener("submit", function (evento) {

            // Evitar que la pagina se recargue al enviar el formulario
            evento.preventDefault();

            // Volver a consultar las ventas con las fechas seleccionadas
            cargarVentas();
        });

        // Limpiar las fechas y volver a cargar todas las ventas
        btnLimpiarFechas.addEventListener("click", function () {

            // Dejar vacios los dos campos de fecha
            fechaDesde.value = "";
            fechaHasta.value = "";

            // Consultar nuevamente las ventas sin el filtro de fechas
            cargarVentas();
        });

        // Volver a intentar la consulta cuando se presiona el boton
        btnReintentar.addEventListener("click", cargarVentas);

        // Cargar todas las ventas al abrir la pantalla
        cargarVentas();

    </script>

</body>
</html>
