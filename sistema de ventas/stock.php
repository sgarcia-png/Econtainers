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

    <title>Stock de contenedores</title>

    <link rel="stylesheet" href="estilos.css">

    <style>
        .stock-contenedor {
            max-width: 1200px;
            margin: 35px auto;
            padding: 20px;
        }

        .stock-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stock-encabezado h1 {
            color: #103E73;
            margin: 0;
        }

        .stock-volver {
            display: inline-block;
            padding: 10px 18px;
            background: #103E73;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .stock-resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stock-tarjeta {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            border-left: 5px solid #F28C00;
        }

        .stock-tarjeta h3 {
            margin: 0 0 10px;
            color: #555;
            font-size: 16px;
        }

        .stock-tarjeta p {
            margin: 0;
            color: #103E73;
            font-size: 28px;
            font-weight: bold;
        }

        .stock-panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            margin-bottom: 25px;
        }

        .stock-panel h2 {
            color: #103E73;
            margin-top: 0;
        }

        .stock-formulario {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .stock-campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .stock-campo label {
            font-weight: bold;
            color: #333;
        }

        .stock-campo input,
        .stock-campo select {
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: white;
        }

        .stock-valor {
            background: #f2f4f7 !important;
            color: #103E73;
            font-weight: bold;
        }

        .stock-boton {
            grid-column: 1 / -1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #F28C00;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .stock-boton:hover {
            background: #d97900;
        }

        .stock-tabla-contenedor {
            overflow-x: auto;
        }

        .stock-tabla {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .stock-tabla th,
        .stock-tabla td {
            padding: 13px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .stock-tabla th {
            background: #103E73;
            color: white;
        }

        .stock-tabla tr:hover {
            background: #f5f7fa;
        }

        .stock-mensaje {
            margin-top: 15px;
            font-weight: bold;
        }

        .stock-disponible {
            color: #16803c;
            font-weight: bold;
        }

        .stock-vendido {
            color: #c62828;
            font-weight: bold;
        }

        @media (max-width: 700px) {
            .stock-resumen {
                grid-template-columns: 1fr;
            }

            .stock-formulario {
                grid-template-columns: 1fr;
            }

            .stock-boton {
                grid-column: 1;
            }
        }
    </style>
</head>

<body>

    <main class="stock-contenedor">

        <div class="stock-encabezado">
            <h1>Stock de contenedores</h1>

            <a href="principal.php" class="stock-volver">
                Volver al menú principal
            </a>
        </div>

        <!-- Resumen del stock -->
        <section class="stock-resumen">

            <div class="stock-tarjeta">
                <h3>Total de contenedores</h3>
                <p id="total-stock">0</p>
            </div>

            <div class="stock-tarjeta">
                <h3>Disponibles</h3>
                <p id="total-disponibles">0</p>
            </div>

            <div class="stock-tarjeta">
                <h3>Vendidos</h3>
                <p id="total-vendidos">0</p>
            </div>

        </section>

        <!-- Formulario para registrar -->
        <section class="stock-panel">

            <h2>Registrar contenedor</h2>

            <form id="form-stock" class="stock-formulario">

                <div class="stock-campo">
                    <label for="codigo">Código del contenedor</label>
                    <input
                        type="text"
                        id="codigo"
                        maxlength="30"
                        placeholder="Ejemplo: ABCD1234567"
                        required
                    >
                </div>

                <div class="stock-campo">
                    <label for="tipo">Tipo de contenedor</label>
                    <select id="tipo" required>
                        <option value="">Seleccionar tipo</option>
                    </select>
                </div>

                <div class="stock-campo">
                    <label for="valor">Valor</label>
                    <input
                        type="text"
                        id="valor"
                        class="stock-valor"
                        value="Seleccioná un tipo"
                        readonly
                    >
                </div>

                <div class="stock-campo">
                    <label for="deposito">Depósito</label>
                    <select id="deposito" required>
                        <option value="">Seleccionar depósito</option>
                        <option value="Bahía Blanca">Bahía Blanca</option>
                        <option value="Pringles">Pringles</option>
                        <option value="Junín">Junín</option>
                        <option value="Santiago">Santiago</option>
                    </select>
                </div>

                <button type="submit" class="stock-boton">
                    Registrar contenedor
                </button>

            </form>

            <p id="mensaje-stock" class="stock-mensaje"></p>

        </section>

        <!-- Tabla de contenedores -->
        <section class="stock-panel">

            <h2>Contenedores registrados</h2>

            <div class="stock-tabla-contenedor">

                <table class="stock-tabla">

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Depósito</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-stock">
                        <tr>
                            <td colspan="5">Cargando contenedores...</td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <script>

        const formulario = document.getElementById("form-stock");
        const selectorTipo = document.getElementById("tipo");
        const campoValor = document.getElementById("valor");
        const mensaje = document.getElementById("mensaje-stock");
        const tabla = document.getElementById("tabla-stock");

        let tiposContenedor = [];

        // Mostrar los valores como dinero
        function mostrarValor(valor) {
            return new Intl.NumberFormat("es-AR", {
                style: "currency",
                currency: "ARS",
                minimumFractionDigits: 2
            }).format(Number(valor));
        }

        // Consultar los tipos de contenedores
        async function cargarTipos() {

            try {

                const respuesta = await fetch("api/tipos_contenedor.php");
                const resultado = await respuesta.json();

                if (!resultado.ok) {
                    throw new Error("No se pudieron cargar los tipos");
                }

                tiposContenedor = resultado.datos;

                tiposContenedor.forEach(function(tipo) {

                    const opcion = document.createElement("option");

                    opcion.value = tipo.id_tipo;
                    opcion.textContent = tipo.nombre_tipo;

                    selectorTipo.appendChild(opcion);

                });

            } catch (error) {

                mensaje.textContent = "Error al cargar los tipos de contenedores.";
                mensaje.style.color = "#c62828";

            }
        }

        // Mostrar el valor cuando se elige un tipo
        selectorTipo.addEventListener("change", function() {

            const tipoSeleccionado = tiposContenedor.find(function(tipo) {
                return tipo.id_tipo == selectorTipo.value;
            });

            if (tipoSeleccionado) {
                campoValor.value = mostrarValor(tipoSeleccionado.valor);
            } else {
                campoValor.value = "Seleccioná un tipo";
            }

        });

        // Consultar y mostrar el stock
        async function cargarStock() {

            try {

                const respuesta = await fetch("api/stock.php");
                const resultado = await respuesta.json();

                if (!resultado.ok) {
                    throw new Error("No se pudo consultar el stock");
                }

                const contenedores = resultado.datos;

                tabla.replaceChildren();

                let disponibles = 0;
                let vendidos = 0;

                if (contenedores.length === 0) {

                    const fila = document.createElement("tr");
                    const celda = document.createElement("td");

                    celda.colSpan = 5;
                    celda.textContent = "Todavía no hay contenedores registrados.";

                    fila.appendChild(celda);
                    tabla.appendChild(fila);

                }

                contenedores.forEach(function(contenedor) {

                    if (contenedor.estado === "Disponible") {
                        disponibles++;
                    }

                    if (contenedor.estado === "Vendido") {
                        vendidos++;
                    }

                    const fila = document.createElement("tr");

                    const datosFila = [
                        contenedor.codigo,
                        contenedor.nombre_tipo,
                        mostrarValor(contenedor.valor),
                        contenedor.deposito,
                        contenedor.estado
                    ];

                    datosFila.forEach(function(dato, indice) {

                        const celda = document.createElement("td");
                        celda.textContent = dato;

                        if (indice === 4) {
                            celda.className = contenedor.estado === "Disponible"
                                ? "stock-disponible"
                                : "stock-vendido";
                        }

                        fila.appendChild(celda);

                    });

                    tabla.appendChild(fila);

                });

                document.getElementById("total-stock").textContent =
                    contenedores.length;

                document.getElementById("total-disponibles").textContent =
                    disponibles;

                document.getElementById("total-vendidos").textContent =
                    vendidos;

            } catch (error) {

                tabla.replaceChildren();

                const fila = document.createElement("tr");
                const celda = document.createElement("td");

                celda.colSpan = 5;
                celda.textContent = "No se pudo cargar el stock.";

                fila.appendChild(celda);
                tabla.appendChild(fila);

            }
        }

        // Registrar un contenedor nuevo
        formulario.addEventListener("submit", async function(event) {

            event.preventDefault();

            mensaje.textContent = "Registrando...";
            mensaje.style.color = "#103E73";

            const datos = {
                codigo: document.getElementById("codigo").value.trim(),
                id_tipo: selectorTipo.value,
                deposito: document.getElementById("deposito").value
            };

            try {

                const respuesta = await fetch("api/registrar_contenedor.php", {

                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify(datos)

                });

                const resultado = await respuesta.json();

                if (!resultado.ok) {
                    throw new Error(resultado.mensaje);
                }

                mensaje.textContent = resultado.mensaje;
                mensaje.style.color = "#16803c";

                formulario.reset();
                campoValor.value = "Seleccioná un tipo";

                await cargarStock();

            } catch (error) {

                mensaje.textContent = error.message;
                mensaje.style.color = "#c62828";

            }

        });

        // Cargar los datos al abrir la página
        cargarTipos();
        cargarStock();

    </script>

</body>
</html>