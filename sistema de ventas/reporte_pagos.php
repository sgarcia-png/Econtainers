
<?php

// Iniciar la sesion para comprobar si la administradora ingreso al sistema
session_start();

// Comprobar que la administradora haya iniciado sesion
if (!isset($_SESSION["administradora_id"])) {
    // Si no inicio sesion, volver a la pantalla de ingreso
    header("Location: index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador -->
    <title>Reporte de pagos - ECONTAINERS</title>

    <!-- Conectar el archivo de estilos de la pagina -->
    <link rel="stylesheet" href="estilos.css">
</head>

<body class="principal-body">

    <!-- Encabezado de la pagina -->
    <header class="encabezado">

        <!-- Logo de ECONTAINERS -->
        <img
            src="img/logo.png"
            alt="ECONTAINERS"
            class="encabezado-logo"
        >

        <!-- Mostrar el usuario que ingreso al sistema -->
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
                <h1 class="titulo-principal">Reporte de pagos</h1>

                <p class="descripcion-principal">
                    Consulta los pagos registrados y sus ventas relacionadas
                </p>
            </div>

            <!-- Enlace para regresar al menu principal -->
            <a href="principal.php" class="boton-volver">
                Volver al inicio
            </a>

        </div>
        
<!-- Seccion que contiene el formulario para registrar un pago -->
<section class="seccion">
    <h2 class="seccion-titulo">Registrar un pago</h2>

    <!-- Formulario para ingresar los datos de un nuevo pago -->
    <form id="formularioPago" class="formulario-pago">

        <div class="campo-pago">
            <label for="ventaPago">Venta:</label>

            <!-- Las ventas pendientes se cargan desde JavaScript -->
            <select id="ventaPago" required>
                <option value="">Cargando ventas...</option>
            </select>

            <!-- Aqui se muestra el saldo pendiente de la venta seleccionada -->
            <small id="saldoVenta">Selecciona una venta</small>
        </div>

        <div class="campo-pago">
            <label for="fechaPago">Fecha del pago:</label>

            <!-- Campo para seleccionar la fecha en que se realiza el pago -->
            <input type="date" id="fechaPago" required>
        </div>

        <div class="campo-pago">
            <label for="montoPago">Importe pagado:</label>

            <!-- Campo numerico para ingresar el importe del pago -->
            <input
                type="number"
                id="montoPago"
                min="0.01"
                step="0.01"
                required
                placeholder="Ej.: 500000"
            >
        </div>

        <div class="campo-pago">
            <label for="formaPagoNueva">Forma de pago:</label>

            <!-- Opciones de formas de pago permitidas por el sistema -->
            <select id="formaPagoNueva" required>
                <option value="">Selecciona una forma</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Débito">Debito</option>
                <option value="Crédito">Credito</option>
                <option value="Depósito">Deposito</option>
            </select>
        </div>

        <!-- Boton que envia el formulario para registrar el pago -->
        <button type="submit" class="boton-filtrar" id="btnGuardarPago">
            Registrar pago
        </button>

    </form>

    <!-- Espacio donde JavaScript muestra mensajes sobre el registro -->
    <div id="mensajePago" class="estado-ventas" role="status" hidden></div>
</section>


        <!-- Seccion que muestra los totales de los pagos encontrados -->
        <section class="resumen-ventas">

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Total de pagos</span>

                <!-- JavaScript actualiza este valor -->
                <strong id="totalPagos">—</strong>
            </div>

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Importe total pagado</span>

                <!-- JavaScript muestra la suma de los importes -->
                <strong id="importePagos">—</strong>
            </div>

        </section>

        <!-- Seccion para filtrar los pagos registrados -->
        <section class="seccion">

            <h2 class="seccion-titulo">Filtrar pagos</h2>

            <form id="filtroPagos" class="filtro-fechas">

                <div class="campo-filtro">
                    <label for="fechaDesde">Fecha desde:</label>

                    <!-- Fecha inicial del filtro -->
                    <input type="date" id="fechaDesde">
                </div>

                <div class="campo-filtro">
                    <label for="fechaHasta">Fecha hasta:</label>

                    <!-- Fecha final del filtro -->
                    <input type="date" id="fechaHasta">
                </div>

                <div class="campo-filtro">
                    <label for="formaPago">Forma de pago:</label>

                    <!-- Las opciones se cargan desde la API -->
                    <select id="formaPago">
                        <option value="">Todas las formas de pago</option>
                    </select>
                </div>

                <div class="botones-filtro">

                    <!-- Boton para aplicar los filtros seleccionados -->
                    <button type="submit" class="boton-filtrar">
                        Filtrar
                    </button>

                    <!-- Boton para borrar los filtros y mostrar todos los pagos -->
                    <button
                        type="button"
                        id="btnLimpiar"
                        class="boton-limpiar"
                    >
                        Limpiar filtros
                    </button>

                </div>

            </form>

        </section>

        <!-- Seccion donde se muestran los pagos encontrados -->
        <section class="seccion">

            <h2 class="seccion-titulo">Pagos registrados</h2>

            <!-- Mensaje que indica si los pagos se estan cargando o si ocurrio un error -->
            <div id="estadoPagos" class="estado-ventas" role="status">
                Cargando pagos...
            </div>

            <!-- Contenedor de la tabla, oculto hasta que haya datos para mostrar -->
            <div class="tabla-contenedor" id="contenedorTabla" hidden>

                <table class="tabla-ventas">

                    <!-- Titulos de las columnas de la tabla -->
                    <thead>
                        <tr>
                            <th>N.º de pago</th>
                            <th>Fecha del pago</th>
                            <th>N.º de venta</th>
                            <th>DNI del cliente</th>
                            <th>Forma de pago</th>
                            <th>Importe pagado</th>
                            <th>Estado de la venta</th>
                        </tr>
                    </thead>

                    <!-- JavaScript agrega las filas con los pagos recibidos -->
                    <tbody id="tablaPagos"></tbody>

                </table>

            </div>

            <!-- Boton que permite volver a intentar la carga si ocurre un error -->
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

    <!-- Codigo JavaScript encargado de la carga, consulta y registro de pagos -->
    <script>

        // Obtener los elementos HTML que se van a utilizar desde JavaScript
        const estadoPagos = document.getElementById("estadoPagos");
        const tablaPagos = document.getElementById("tablaPagos");
        const contenedorTabla = document.getElementById("contenedorTabla");

        // Obtener los elementos donde se muestran los totales
        const totalPagos = document.getElementById("totalPagos");
        const importePagos = document.getElementById("importePagos");

        // Obtener los campos utilizados para filtrar los pagos
        const fechaDesde = document.getElementById("fechaDesde");
        const fechaHasta = document.getElementById("fechaHasta");
        const formaPago = document.getElementById("formaPago");

        // Obtener el formulario y los botones de los filtros
        const filtroPagos = document.getElementById("filtroPagos");
        const btnLimpiar = document.getElementById("btnLimpiar");
        const btnReintentar = document.getElementById("btnReintentar");

        // Configurar el formato de moneda para pesos argentinos
        const formatoMoneda = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Cargar las formas de pago disponibles desde la API
        async function cargarFormasPago(formas) {

            // Guardar la opcion que estaba seleccionada
            const formaSeleccionada = formaPago.value;

            // Borrar las opciones anteriores del selector
            formaPago.replaceChildren();

            // Crear la primera opcion para mostrar todas las formas de pago
            const opcionTodas = document.createElement("option");
            opcionTodas.value = "";
            opcionTodas.textContent = "Todas las formas de pago";
            formaPago.appendChild(opcionTodas);

            // Recorrer las formas recibidas y agregarlas al selector
            formas.forEach(function (forma) {
                const opcion = document.createElement("option");
                opcion.value = forma;
                opcion.textContent = forma;
                formaPago.appendChild(opcion);
            });

            // Comprobar si la forma de pago anterior sigue estando disponible
            if ([...formaPago.options].some(
                opcion => opcion.value === formaSeleccionada
            )) {
                // Mantener la opcion seleccionada
                formaPago.value = formaSeleccionada;
            }
        }

        // Funcion principal para consultar y mostrar los pagos
        async function cargarPagos() {

            // Obtener las fechas ingresadas en los filtros
            const desde = fechaDesde.value;
            const hasta = fechaHasta.value;

            // Comprobar que se hayan ingresado las dos fechas o ninguna
            if ((desde === "") !== (hasta === "")) {
                estadoPagos.textContent =
                    "Selecciona la fecha desde y la fecha hasta";

                // Mostrar el mensaje y ocultar la tabla
                estadoPagos.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            // Comprobar que la fecha inicial no sea posterior a la final
            if (desde !== "" && hasta !== "" && desde > hasta) {
                estadoPagos.textContent =
                    "La fecha desde no puede ser posterior a la fecha hasta";

                estadoPagos.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            // Mostrar el mensaje de carga y limpiar los datos anteriores
            estadoPagos.hidden = false;
            estadoPagos.textContent = "Cargando pagos...";
            contenedorTabla.hidden = true;
            btnReintentar.hidden = true;
            tablaPagos.replaceChildren();

            try {
                // Crear un objeto para preparar los filtros que se enviaran a la API
                const filtros = new URLSearchParams();

                // Agregar las fechas si se ingresaron las dos
                if (desde !== "" && hasta !== "") {
                    filtros.set("fecha_desde", desde);
                    filtros.set("fecha_hasta", hasta);
                }

                // Agregar la forma de pago si se selecciono alguna
                if (formaPago.value !== "") {
                    filtros.set("forma_pago", formaPago.value);
                }

                // Construir la direccion de la API con los filtros seleccionados
                const urlAPI = "api/pagos.php" +
                    (filtros.toString() !== ""
                        ? "?" + filtros.toString()
                        : "");

                // Enviar una solicitud GET para consultar los pagos
                const respuesta = await fetch(urlAPI, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    },
                    cache: "no-store"
                });

                // Convertir la respuesta recibida a un objeto JavaScript
                const resultado = await respuesta.json();

                // Comprobar que la solicitud y la respuesta hayan sido correctas
                if (!respuesta.ok || !resultado.ok) {
                    throw new Error(
                        resultado.mensaje ||
                        "No se pudieron consultar los pagos"
                    );
                }

                // Comprobar que los datos recibidos tengan el formato esperado
                if (
                    !Array.isArray(resultado.datos) ||
                    !Array.isArray(resultado.formas_pago)
                ) {
                    throw new Error(
                        "La API devolvio una respuesta inesperada."
                    );
                }

                // Actualizar las opciones del filtro con las formas recibidas
                await cargarFormasPago(resultado.formas_pago);

                // Actualizar el resumen con los pagos encontrados
                const pagos = resultado.datos;

                // Mostrar la cantidad de pagos encontrados
                totalPagos.textContent = pagos.length;

                // Sumar los importes de todos los pagos recibidos
                const importeTotal = pagos.reduce(function (suma, pago) {
                    // Convertir el importe recibido a numero
                    const importe = Number(pago.monto_pago);

                    // Sumar el importe si es un numero valido
                    return suma +
                        (Number.isFinite(importe) ? importe : 0);
                }, 0);

                // Mostrar el importe total con formato de pesos argentinos
                importePagos.textContent =
                    formatoMoneda.format(importeTotal);

                // Si no hay pagos, mostrar un mensaje y no crear la tabla
                if (pagos.length === 0) {
                    estadoPagos.textContent =
                        "No hay pagos para los filtros seleccionados";

                    estadoPagos.hidden = false;
                    return;
                }

                // Crear la tabla con los datos recibidos
                pagos.forEach(function (pago) {

                    // Crear una nueva fila para cada pago
                    const fila = document.createElement("tr");

                    // Convertir la fecha del formato AAAA-MM-DD a DD/MM/AAAA
                    const fecha = pago.fecha_pago
                        ? String(pago.fecha_pago)
                            .split("-")
                            .reverse()
                            .join("/")
                        : "—";

                    // Guardar los datos que se mostraran en las columnas
                    const valores = [
                        pago.nro_pago,
                        fecha,
                        pago.nro_venta,
                        pago.dni_cliente,
                        pago.forma_pago,
                        formatoMoneda.format(
                            Number(pago.monto_pago) || 0
                        ),
                        pago.estado_pago
                    ];

                    // Recorrer los datos para crear las celdas de la fila
                    valores.forEach(function (valor, indice) {
                        const celda = document.createElement("td");

                        // Colocar el valor dentro de la celda
                        celda.textContent = valor ?? "—";

                        // Si es la columna del estado, agregarle un elemento span
                        if (indice === 6) {
                            const estado = document.createElement("span");
                            estado.className = "estado-pago";
                            estado.textContent = valor || "Sin estado";
                            celda.replaceChildren(estado);
                        }

                        // Agregar la celda a la fila
                        fila.appendChild(celda);
                    });

                    // Agregar la fila completa a la tabla
                    tablaPagos.appendChild(fila);
                });

                // Si la carga fue correcta, ocultar el mensaje y mostrar la tabla
                estadoPagos.hidden = true;
                contenedorTabla.hidden = false;

            } catch (error) {

                // Mostrar el error en la consola para facilitar su revision
                console.error("Error al cargar los pagos:", error);

                // Informar al usuario que no se pudieron cargar los pagos
                estadoPagos.textContent =
                    "No se pudieron cargar los pagos. " +
                    "Verifica la conexion con el servidor e intenta nuevamente";

                estadoPagos.hidden = false;

                // Mostrar el boton para volver a intentar la carga
                btnReintentar.hidden = false;
            }
        }

        // Detectar cuando se envia el formulario de filtros
        filtroPagos.addEventListener("submit", function (evento) {

            // Evitar que el formulario recargue la pagina
            evento.preventDefault();

            // Consultar los pagos con los filtros seleccionados
            cargarPagos();
        });

        // Detectar cuando se hace clic en limpiar filtros
        btnLimpiar.addEventListener("click", function () {

            // Borrar los valores de los filtros
            fechaDesde.value = "";
            fechaHasta.value = "";
            formaPago.value = "";

            // Volver a consultar todos los pagos
            cargarPagos();
        });

        // Si se hace clic en volver a cargar, ejecutar nuevamente la consulta
        btnReintentar.addEventListener("click", cargarPagos);

        // Obtener los elementos del formulario para registrar un pago
const formularioPago = document.getElementById("formularioPago");
const ventaPago = document.getElementById("ventaPago");
const fechaPago = document.getElementById("fechaPago");
const montoPago = document.getElementById("montoPago");
const formaPagoNueva = document.getElementById("formaPagoNueva");
const saldoVenta = document.getElementById("saldoVenta");
const mensajePago = document.getElementById("mensajePago");
const btnGuardarPago = document.getElementById("btnGuardarPago");

// Arreglo donde se guardan las ventas que tienen saldo pendiente
let ventasPendientes = [];

// Funcion para mostrar un importe con formato de pesos argentinos
function mostrarDinero(valor) {
    return formatoMoneda.format(Number(valor) || 0);
}

// Consultar las ventas que todavia tienen un saldo pendiente
async function cargarVentasPendientes() {
    try {
        // Solicitar las ventas pendientes a la API
        const respuesta = await fetch("api/ventas_pendientes_pago.php", {
            headers: { "Accept": "application/json" },
            cache: "no-store"
        });

        // Convertir la respuesta a un objeto JavaScript
        const resultado = await respuesta.json();

        // Comprobar que la solicitud haya sido correcta
        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No se pudieron cargar las ventas");
        }

        // Guardar las ventas recibidas en el arreglo
        ventasPendientes = resultado.datos;

        // Limpiar las opciones anteriores del selector
        ventaPago.replaceChildren();

        // Crear la primera opcion del selector
        const opcionInicial = document.createElement("option");
        opcionInicial.value = "";
        opcionInicial.textContent = "Selecciona una venta";
        ventaPago.appendChild(opcionInicial);

        // Recorrer las ventas pendientes y crear una opcion para cada una
        ventasPendientes.forEach(function (venta) {
            const opcion = document.createElement("option");

            // Guardar el numero de venta como valor de la opcion
            opcion.value = venta.nro_venta;

            // Guardar el saldo pendiente como un dato adicional de la opcion
            opcion.dataset.saldo = venta.saldo;

            // Mostrar el numero de venta, DNI del cliente y saldo pendiente
            opcion.textContent =
                "Venta #" + venta.nro_venta +
                " - DNI " + venta.dni_cliente +
                " - Saldo: " + mostrarDinero(venta.saldo);

            // Agregar la opcion al selector
            ventaPago.appendChild(opcion);
        });

        // Comprobar si existen ventas pendientes
        if (ventasPendientes.length === 0) {

            // Informar que no hay ventas para registrar pagos
            opcionInicial.textContent = "No hay ventas con saldo pendiente";
            saldoVenta.textContent = "No hay pagos pendientes para registrar";

            // Deshabilitar el boton de registro
            btnGuardarPago.disabled = true;
        } else {

            // Permitir registrar un pago si hay ventas pendientes
            btnGuardarPago.disabled = false;
        }

    } catch (error) {

        // Mostrar el error en la consola
        console.error(error);

        // Limpiar el selector de ventas
        ventaPago.replaceChildren();

        // Mostrar una opcion indicando que no se pudieron cargar las ventas
        const opcion = document.createElement("option");
        opcion.value = "";
        opcion.textContent = "No se pudieron cargar las ventas";
        ventaPago.appendChild(opcion);

        // Informar el problema y deshabilitar el registro
        saldoVenta.textContent = "Intenta volver a cargar la pagina";
        btnGuardarPago.disabled = true;
    }
}

// Detectar cuando se selecciona una venta
ventaPago.addEventListener("change", function () {

    // Obtener la opcion que fue seleccionada
    const opcion = ventaPago.selectedOptions[0];

    // Si no se selecciono una venta, mostrar el mensaje inicial
    if (!opcion || !opcion.value) {
        saldoVenta.textContent = "Selecciona una venta";

        // Quitar el limite maximo del importe
        montoPago.removeAttribute("max");
        return;
    }

    // Obtener el saldo pendiente guardado en la opcion seleccionada
    const saldo = Number(opcion.dataset.saldo);

    // Mostrar el saldo pendiente y establecerlo como importe maximo
    saldoVenta.textContent = "Saldo pendiente: " + mostrarDinero(saldo);
    montoPago.max = saldo.toFixed(2);
});

// Detectar cuando se envia el formulario para registrar un pago
formularioPago.addEventListener("submit", async function (evento) {

    // Evitar que el formulario recargue la pagina
    evento.preventDefault();

    // Mostrar un mensaje mientras se registra el pago
    mensajePago.hidden = false;
    mensajePago.textContent = "Registrando pago...";

    // Deshabilitar el boton para evitar envios repetidos
    btnGuardarPago.disabled = true;

    try {
        // Enviar los datos del pago a la API mediante una solicitud POST
        const respuesta = await fetch("api/registrar_pago.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },

            // Convertir los datos del formulario a formato JSON
            body: JSON.stringify({
                nro_venta: ventaPago.value,
                fecha_pago: fechaPago.value,
                monto_pago: montoPago.value,
                forma_pago: formaPagoNueva.value
            })
        });

        // Convertir la respuesta recibida a un objeto JavaScript
        const resultado = await respuesta.json();

        // Comprobar si el registro fue correcto
        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No se pudo registrar el pago");
        }

        // Mostrar los datos actualizados del pago registrado
        mensajePago.textContent =
            "Pago registrado correctamente. " +
            "Total pagado: " + mostrarDinero(resultado.datos.total_pagado) +
            ". Saldo restante: " + mostrarDinero(resultado.datos.saldo) +
            ". Estado: " + resultado.datos.estado_pago + ".";

        // Limpiar los campos del formulario
        formularioPago.reset();

        // Colocar nuevamente la fecha actual
        fechaPago.value = new Date().toLocaleDateString("en-CA");

        // Actualizar las ventas pendientes y el reporte de pagos
        await cargarVentasPendientes();
        await cargarPagos();

    } catch (error) {

        // Mostrar el mensaje de error si no se pudo registrar el pago
        mensajePago.textContent = error.message;

    } finally {

    // Volver a habilitar o deshabilitar el boton segun las ventas pendientes
    btnGuardarPago.disabled = ventasPendientes.length === 0;
}
});

// Colocar la fecha actual como valor inicial del campo
fechaPago.value = new Date().toLocaleDateString("en-CA");

// Cargar las ventas pendientes al abrir la pagina
cargarVentasPendientes();

        // Cargar los pagos al abrir la pantalla
        cargarPagos();
    </script>

</body>
</html>
