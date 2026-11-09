
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

    <title>Reporte de pagos - ECONTAINERS</title>

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
                <h1 class="titulo-principal">Reporte de pagos</h1>

                <p class="descripcion-principal">
                    Consulta los pagos registrados y sus ventas relacionadas
                </p>
            </div>

            <a href="principal.php" class="boton-volver">
                Volver al inicio
            </a>

        </div>
        
<section class="seccion">
    <h2 class="seccion-titulo">Registrar un pago</h2>

    <form id="formularioPago" class="formulario-pago">

        <div class="campo-pago">
            <label for="ventaPago">Venta:</label>
            <select id="ventaPago" required>
                <option value="">Cargando ventas...</option>
            </select>
            <small id="saldoVenta">Selecciona una venta</small>
        </div>

        <div class="campo-pago">
            <label for="fechaPago">Fecha del pago:</label>
            <input type="date" id="fechaPago" required>
        </div>

        <div class="campo-pago">
            <label for="montoPago">Importe pagado:</label>
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
            <select id="formaPagoNueva" required>
                <option value="">Selecciona una forma</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Débito">Debito</option>
                <option value="Crédito">Credito</option>
                <option value="Depósito">Deposito</option>
            </select>
        </div>

        <button type="submit" class="boton-filtrar" id="btnGuardarPago">
            Registrar pago
        </button>

    </form>

    <div id="mensajePago" class="estado-ventas" role="status" hidden></div>
</section>


        <section class="resumen-ventas">

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Total de pagos</span>
                <strong id="totalPagos">—</strong>
            </div>

            <div class="resumen-venta">
                <span class="resumen-etiqueta">Importe total pagado</span>
                <strong id="importePagos">—</strong>
            </div>

        </section>

        <section class="seccion">

            <h2 class="seccion-titulo">Filtrar pagos</h2>

            <form id="filtroPagos" class="filtro-fechas">

                <div class="campo-filtro">
                    <label for="fechaDesde">Fecha desde:</label>
                    <input type="date" id="fechaDesde">
                </div>

                <div class="campo-filtro">
                    <label for="fechaHasta">Fecha hasta:</label>
                    <input type="date" id="fechaHasta">
                </div>

                <div class="campo-filtro">
                    <label for="formaPago">Forma de pago:</label>

                    <select id="formaPago">
                        <option value="">Todas las formas de pago</option>
                    </select>
                </div>

                <div class="botones-filtro">

                    <button type="submit" class="boton-filtrar">
                        Filtrar
                    </button>

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

        <section class="seccion">

            <h2 class="seccion-titulo">Pagos registrados</h2>

            <div id="estadoPagos" class="estado-ventas" role="status">
                Cargando pagos...
            </div>

            <div class="tabla-contenedor" id="contenedorTabla" hidden>

                <table class="tabla-ventas">

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

                    <tbody id="tablaPagos"></tbody>

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
        const estadoPagos = document.getElementById("estadoPagos");
        const tablaPagos = document.getElementById("tablaPagos");
        const contenedorTabla = document.getElementById("contenedorTabla");

        const totalPagos = document.getElementById("totalPagos");
        const importePagos = document.getElementById("importePagos");

        const fechaDesde = document.getElementById("fechaDesde");
        const fechaHasta = document.getElementById("fechaHasta");
        const formaPago = document.getElementById("formaPago");

        const filtroPagos = document.getElementById("filtroPagos");
        const btnLimpiar = document.getElementById("btnLimpiar");
        const btnReintentar = document.getElementById("btnReintentar");

        const formatoMoneda = new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Cargar las formas de pago disponibles desde la API
        async function cargarFormasPago(formas) {
            const formaSeleccionada = formaPago.value;

            formaPago.replaceChildren();

            const opcionTodas = document.createElement("option");
            opcionTodas.value = "";
            opcionTodas.textContent = "Todas las formas de pago";
            formaPago.appendChild(opcionTodas);

            formas.forEach(function (forma) {
                const opcion = document.createElement("option");
                opcion.value = forma;
                opcion.textContent = forma;
                formaPago.appendChild(opcion);
            });

            if ([...formaPago.options].some(
                opcion => opcion.value === formaSeleccionada
            )) {
                formaPago.value = formaSeleccionada;
            }
        }

        async function cargarPagos() {
            const desde = fechaDesde.value;
            const hasta = fechaHasta.value;

            if ((desde === "") !== (hasta === "")) {
                estadoPagos.textContent =
                    "Selecciona la fecha desde y la fecha hasta";

                estadoPagos.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            if (desde !== "" && hasta !== "" && desde > hasta) {
                estadoPagos.textContent =
                    "La fecha desde no puede ser posterior a la fecha hasta";

                estadoPagos.hidden = false;
                contenedorTabla.hidden = true;
                btnReintentar.hidden = true;
                return;
            }

            estadoPagos.hidden = false;
            estadoPagos.textContent = "Cargando pagos...";
            contenedorTabla.hidden = true;
            btnReintentar.hidden = true;
            tablaPagos.replaceChildren();

            try {
                const filtros = new URLSearchParams();

                if (desde !== "" && hasta !== "") {
                    filtros.set("fecha_desde", desde);
                    filtros.set("fecha_hasta", hasta);
                }

                if (formaPago.value !== "") {
                    filtros.set("forma_pago", formaPago.value);
                }

                const urlAPI = "api/pagos.php" +
                    (filtros.toString() !== ""
                        ? "?" + filtros.toString()
                        : "");

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
                        "No se pudieron consultar los pagos"
                    );
                }

                if (
                    !Array.isArray(resultado.datos) ||
                    !Array.isArray(resultado.formas_pago)
                ) {
                    throw new Error(
                        "La API devolvio una respuesta inesperada."
                    );
                }

                await cargarFormasPago(resultado.formas_pago);

                // Actualizar el resumen con los pagos encontrados
                const pagos = resultado.datos;

                totalPagos.textContent = pagos.length;

                const importeTotal = pagos.reduce(function (suma, pago) {
                    const importe = Number(pago.monto_pago);

                    return suma +
                        (Number.isFinite(importe) ? importe : 0);
                }, 0);

                importePagos.textContent =
                    formatoMoneda.format(importeTotal);

                if (pagos.length === 0) {
                    estadoPagos.textContent =
                        "No hay pagos para los filtros seleccionados";

                    estadoPagos.hidden = false;
                    return;
                }

                // Crear la tabla con los datos recibidos
                pagos.forEach(function (pago) {
                    const fila = document.createElement("tr");

                    const fecha = pago.fecha_pago
                        ? String(pago.fecha_pago)
                            .split("-")
                            .reverse()
                            .join("/")
                        : "—";

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

                    valores.forEach(function (valor, indice) {
                        const celda = document.createElement("td");

                        celda.textContent = valor ?? "—";

                        if (indice === 6) {
                            const estado = document.createElement("span");
                            estado.className = "estado-pago";
                            estado.textContent = valor || "Sin estado";
                            celda.replaceChildren(estado);
                        }

                        fila.appendChild(celda);
                    });

                    tablaPagos.appendChild(fila);
                });

                estadoPagos.hidden = true;
                contenedorTabla.hidden = false;

            } catch (error) {
                console.error("Error al cargar los pagos:", error);

                estadoPagos.textContent =
                    "No se pudieron cargar los pagos. " +
                    "Verifica la conexion con el servidor e intenta nuevamente";

                estadoPagos.hidden = false;
                btnReintentar.hidden = false;
            }
        }

        filtroPagos.addEventListener("submit", function (evento) {
            evento.preventDefault();
            cargarPagos();
        });

        btnLimpiar.addEventListener("click", function () {
            fechaDesde.value = "";
            fechaHasta.value = "";
            formaPago.value = "";
            cargarPagos();
        });

        btnReintentar.addEventListener("click", cargarPagos);

const formularioPago = document.getElementById("formularioPago");
const ventaPago = document.getElementById("ventaPago");
const fechaPago = document.getElementById("fechaPago");
const montoPago = document.getElementById("montoPago");
const formaPagoNueva = document.getElementById("formaPagoNueva");
const saldoVenta = document.getElementById("saldoVenta");
const mensajePago = document.getElementById("mensajePago");
const btnGuardarPago = document.getElementById("btnGuardarPago");

let ventasPendientes = [];

function mostrarDinero(valor) {
    return formatoMoneda.format(Number(valor) || 0);
}

async function cargarVentasPendientes() {
    try {
        const respuesta = await fetch("api/ventas_pendientes_pago.php", {
            headers: { "Accept": "application/json" },
            cache: "no-store"
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No se pudieron cargar las ventas");
        }

        ventasPendientes = resultado.datos;

        ventaPago.replaceChildren();

        const opcionInicial = document.createElement("option");
        opcionInicial.value = "";
        opcionInicial.textContent = "Selecciona una venta";
        ventaPago.appendChild(opcionInicial);

        ventasPendientes.forEach(function (venta) {
            const opcion = document.createElement("option");

            opcion.value = venta.nro_venta;
            opcion.dataset.saldo = venta.saldo;

            opcion.textContent =
                "Venta #" + venta.nro_venta +
                " - DNI " + venta.dni_cliente +
                " - Saldo: " + mostrarDinero(venta.saldo);

            ventaPago.appendChild(opcion);
        });

        if (ventasPendientes.length === 0) {
            opcionInicial.textContent = "No hay ventas con saldo pendiente";
            saldoVenta.textContent = "No hay pagos pendientes para registrar";
            btnGuardarPago.disabled = true;
        } else {
            btnGuardarPago.disabled = false;
        }

    } catch (error) {
        console.error(error);
        ventaPago.replaceChildren();

        const opcion = document.createElement("option");
        opcion.value = "";
        opcion.textContent = "No se pudieron cargar las ventas";
        ventaPago.appendChild(opcion);

        saldoVenta.textContent = "Intenta volver a cargar la pagina";
        btnGuardarPago.disabled = true;
    }
}

ventaPago.addEventListener("change", function () {
    const opcion = ventaPago.selectedOptions[0];

    if (!opcion || !opcion.value) {
        saldoVenta.textContent = "Selecciona una venta";
        montoPago.removeAttribute("max");
        return;
    }

    const saldo = Number(opcion.dataset.saldo);

    saldoVenta.textContent = "Saldo pendiente: " + mostrarDinero(saldo);
    montoPago.max = saldo.toFixed(2);
});

formularioPago.addEventListener("submit", async function (evento) {
    evento.preventDefault();

    mensajePago.hidden = false;
    mensajePago.textContent = "Registrando pago...";
    btnGuardarPago.disabled = true;

    try {
        const respuesta = await fetch("api/registrar_pago.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                nro_venta: ventaPago.value,
                fecha_pago: fechaPago.value,
                monto_pago: montoPago.value,
                forma_pago: formaPagoNueva.value
            })
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.mensaje || "No se pudo registrar el pago");
        }

        mensajePago.textContent =
            "Pago registrado correctamente. " +
            "Total pagado: " + mostrarDinero(resultado.datos.total_pagado) +
            ". Saldo restante: " + mostrarDinero(resultado.datos.saldo) +
            ". Estado: " + resultado.datos.estado_pago + ".";

        formularioPago.reset();
        fechaPago.value = new Date().toLocaleDateString("en-CA");

        await cargarVentasPendientes();
        await cargarPagos();

    } catch (error) {
        mensajePago.textContent = error.message;
    } finally {
    btnGuardarPago.disabled = ventasPendientes.length === 0;
}
});

fechaPago.value = new Date().toLocaleDateString("en-CA");
cargarVentasPendientes();

        // Cargar los pagos al abrir la pantalla
        cargarPagos();
    </script>

</body>
</html>
