
<?php

// Iniciar la sesion para poder comprobar si la administradora ingreso al sistema.
session_start();

// Verificar que haya una administradora con la sesion iniciada.
if (!isset($_SESSION["administradora_id"])) {
    // Si no inicio sesion, enviarla nuevamente a la pagina de ingreso.
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador. -->
    <title>Stock de contenedores</title>

    <!-- Conectar la hoja de estilos principal del sistema. -->
    <link rel="stylesheet" href="estilos.css">

    <!-- Estilos propios de la pagina de stock. -->
    <style>
        /* Configurar el espacio principal de la pagina. */
        .stock-contenedor {
            max-width: 1200px;
            margin: 35px auto;
            padding: 20px;
        }

        /* Organizar el titulo y el boton para volver al menu. */
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

        /* Dar formato al boton para volver al menu principal. */
        .stock-volver {
            display: inline-block;
            padding: 10px 18px;
            background: #103E73;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        /* Organizar las tres tarjetas del resumen del stock. */
        .stock-resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        /* Dar formato a cada tarjeta del resumen. */
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

        /* Dar formato a los paneles del formulario y la tabla. */
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

        /* Organizar los campos del formulario en dos columnas. */
        .stock-formulario {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        /* Organizar cada campo con su etiqueta y su entrada de datos. */
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

        /* Diferenciar el campo del valor porque no se puede editar manualmente. */
        .stock-valor {
            background: #f2f4f7 !important;
            color: #103E73;
            font-weight: bold;
        }

        /* Dar formato al boton para registrar el contenedor. */
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

        /* Cambiar el color del boton cuando se pasa el cursor. */
        .stock-boton:hover {
            background: #d97900;
        }

        /* Permitir desplazamiento horizontal si la tabla no entra en la pantalla. */
        .stock-tabla-contenedor {
            overflow-x: auto;
        }

        /* Configurar el aspecto general de la tabla. */
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

        /* Diferenciar los encabezados de la tabla. */
        .stock-tabla th {
            background: #103E73;
            color: white;
        }

        /* Cambiar el fondo de una fila cuando se pasa el cursor. */
        .stock-tabla tr:hover {
            background: #f5f7fa;
        }

        /* Dar formato a los mensajes que aparecen debajo del formulario. */
        .stock-mensaje {
            margin-top: 15px;
            font-weight: bold;
        }

        /* Mostrar en verde los contenedores disponibles. */
        .stock-disponible {
            color: #16803c;
            font-weight: bold;
        }

        /* Mostrar en rojo los contenedores vendidos. */
        .stock-vendido {
            color: #c62828;
            font-weight: bold;
        }

        /* Adaptar la pagina a pantallas mas chicas. */
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

    <!-- Contenido principal de la pagina. -->
    <main class="stock-contenedor">

        <!-- Encabezado con el titulo y el acceso al menu principal. -->
        <div class="stock-encabezado">
            <h1>Stock de contenedores</h1>

            <a href="principal.php" class="stock-volver">
                Volver al menú principal
            </a>
        </div>

        <!-- Resumen del stock -->
        <section class="stock-resumen">

            <!-- Mostrar la cantidad total de contenedores registrados. -->
            <div class="stock-tarjeta">
                <h3>Total de contenedores</h3>
                <p id="total-stock">0</p>
            </div>

            <!-- Mostrar la cantidad de contenedores disponibles. -->
            <div class="stock-tarjeta">
                <h3>Disponibles</h3>
                <p id="total-disponibles">0</p>
            </div>

            <!-- Mostrar la cantidad de contenedores vendidos. -->
            <div class="stock-tarjeta">
                <h3>Vendidos</h3>
                <p id="total-vendidos">0</p>
            </div>

        </section>

        <!-- Formulario para registrar -->
        <section class="stock-panel">

            <h2>Registrar contenedor</h2>

            <!-- Formulario que se procesa con JavaScript. -->
            <form id="form-stock" class="stock-formulario">

                <!-- Campo para ingresar el codigo del contenedor. -->
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

                <!-- Selector para elegir el tipo de contenedor. -->
                <div class="stock-campo">
                    <label for="tipo">Tipo de contenedor</label>
                    <select id="tipo" required>
                        <option value="">Seleccionar tipo</option>
                    </select>
                </div>

                <!-- Campo donde se muestra automaticamente el valor del tipo elegido. -->
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

                <!-- Selector para indicar en que deposito se encuentra. -->
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

                <!-- Boton que envia los datos para registrar el contenedor. -->
                <button type="submit" class="stock-boton">
                    Registrar contenedor
                </button>

            </form>

            <!-- Lugar donde JavaScript muestra mensajes de resultado o error. -->
            <p id="mensaje-stock" class="stock-mensaje"></p>

        </section>

        <!-- Tabla de contenedores -->
        <section class="stock-panel">

            <h2>Contenedores registrados</h2>

            <div class="stock-tabla-contenedor">

                <!-- Tabla que se completa con los datos recibidos desde la API. -->
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

                    <!-- JavaScript coloca las filas de los contenedores en este cuerpo. -->
                    <tbody id="tabla-stock">
                        <tr>
                            <td colspan="5">Cargando contenedores...</td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </section>

    </main>

    <!-- Codigo JavaScript encargado de las consultas y acciones del stock. -->
    <script>

        // Obtener los elementos HTML que se van a utilizar desde JavaScript.
        const formulario = document.getElementById("form-stock");
        const selectorTipo = document.getElementById("tipo");
        const campoValor = document.getElementById("valor");
        const mensaje = document.getElementById("mensaje-stock");
        const tabla = document.getElementById("tabla-stock");

        // Arreglo donde se guardan los tipos de contenedores recibidos desde la API.
        let tiposContenedor = [];

        // Mostrar los valores como dinero
        function mostrarValor(valor) {
            // Convertir el numero a formato de moneda argentina.
            return new Intl.NumberFormat("es-AR", {
                style: "currency",
                currency: "ARS",
                minimumFractionDigits: 2
            }).format(Number(valor));
        }

        // Consultar los tipos de contenedores
        async function cargarTipos() {

            try {

                // Pedir los tipos de contenedores al archivo de la API.
                const respuesta = await fetch("api/tipos_contenedor.php");

                // Convertir la respuesta recibida a un objeto JavaScript.
                const resultado = await respuesta.json();

                // Comprobar si la API devolvio un resultado correcto.
                if (!resultado.ok) {
                    throw new Error("No se pudieron cargar los tipos");
                }

                // Guardar los tipos recibidos para utilizarlos despues.
                tiposContenedor = resultado.datos;

                // Recorrer los tipos para agregarlos al selector del formulario.
                tiposContenedor.forEach(function(tipo) {

                    // Crear una nueva opcion para el selector.
                    const opcion = document.createElement("option");

                    // Guardar el identificador del tipo como valor de la opcion.
                    opcion.value = tipo.id_tipo;

                    // Mostrar el nombre del tipo en la pagina.
                    opcion.textContent = tipo.nombre_tipo;

                    // Agregar la opcion al selector.
                    selectorTipo.appendChild(opcion);

                });

            } catch (error) {

                // Mostrar un mensaje si no se pudieron cargar los tipos.
                mensaje.textContent = "Error al cargar los tipos de contenedores.";
                mensaje.style.color = "#c62828";

            }
        }

        // Mostrar el valor cuando se elige un tipo
        selectorTipo.addEventListener("change", function() {

            // Buscar dentro del arreglo el tipo que selecciono el usuario.
            const tipoSeleccionado = tiposContenedor.find(function(tipo) {
                return tipo.id_tipo == selectorTipo.value;
            });

            // Si se encontro el tipo, mostrar su valor con formato de moneda.
            if (tipoSeleccionado) {
                campoValor.value = mostrarValor(tipoSeleccionado.valor);
            } else {
                // Si no hay un tipo seleccionado, mostrar el texto inicial.
                campoValor.value = "Seleccioná un tipo";
            }

        });

        // Consultar y mostrar el stock
        async function cargarStock() {

            try {

                // Consultar el archivo de la API que devuelve los contenedores registrados.
                const respuesta = await fetch("api/stock.php");

                // Convertir la respuesta de la API a un objeto JavaScript.
                const resultado = await respuesta.json();

                // Comprobar si la consulta fue correcta.
                if (!resultado.ok) {
                    throw new Error("No se pudo consultar el stock");
                }

                // Guardar en una variable la lista de contenedores recibida.
                const contenedores = resultado.datos;

                // Limpiar la tabla antes de cargar los datos actualizados.
                tabla.replaceChildren();

                // Crear contadores para los contenedores disponibles y vendidos.
                let disponibles = 0;
                let vendidos = 0;

                // Comprobar si la lista esta vacia.
                if (contenedores.length === 0) {

                    // Crear una fila y una celda para informar que no hay contenedores.
                    const fila = document.createElement("tr");
                    const celda = document.createElement("td");

                    // Hacer que la celda ocupe las cinco columnas de la tabla.
                    celda.colSpan = 5;
                    celda.textContent = "Todavía no hay contenedores registrados.";

                    // Agregar la celda a la fila y la fila a la tabla.
                    fila.appendChild(celda);
                    tabla.appendChild(fila);

                }

                // Recorrer todos los contenedores recibidos desde la API.
                contenedores.forEach(function(contenedor) {

                    // Si el estado es Disponible, aumentar el contador de disponibles.
                    if (contenedor.estado === "Disponible") {
                        disponibles++;
                    }

                    // Si el estado es Vendido, aumentar el contador de vendidos.
                    if (contenedor.estado === "Vendido") {
                        vendidos++;
                    }

                    // Crear una fila nueva para mostrar los datos del contenedor.
                    const fila = document.createElement("tr");

                    // Preparar los datos que se van a mostrar en las columnas.
                    const datosFila = [
                        contenedor.codigo,
                        contenedor.nombre_tipo,
                        mostrarValor(contenedor.valor),
                        contenedor.deposito,
                        contenedor.estado
                    ];

                    // Recorrer los datos para crear una celda por cada columna.
                    datosFila.forEach(function(dato, indice) {

                        // Crear una celda y colocar el dato correspondiente.
                        const celda = document.createElement("td");
                        celda.textContent = dato;

                        // Si se esta mostrando el estado, asignar un color segun su valor.
                        if (indice === 4) {
                            celda.className = contenedor.estado === "Disponible"
                                ? "stock-disponible"
                                : "stock-vendido";
                        }

                        // Agregar la celda a la fila.
                        fila.appendChild(celda);

                    });

                    // Agregar la fila completa a la tabla.
                    tabla.appendChild(fila);

                });

                // Actualizar el total de contenedores registrados.
                document.getElementById("total-stock").textContent =
                    contenedores.length;

                // Actualizar la cantidad de contenedores disponibles.
                document.getElementById("total-disponibles").textContent =
                    disponibles;

                // Actualizar la cantidad de contenedores vendidos.
                document.getElementById("total-vendidos").textContent =
                    vendidos;

            } catch (error) {

                // Si ocurre un error, limpiar la tabla para mostrar el mensaje.
                tabla.replaceChildren();

                // Crear una fila y una celda para informar el problema.
                const fila = document.createElement("tr");
                const celda = document.createElement("td");

                // Hacer que el mensaje ocupe todas las columnas de la tabla.
                celda.colSpan = 5;
                celda.textContent = "No se pudo cargar el stock.";

                // Agregar el mensaje a la tabla.
                fila.appendChild(celda);
                tabla.appendChild(fila);

            }
        }

        // Registrar un contenedor nuevo
        formulario.addEventListener("submit", async function(event) {

            // Evitar que el formulario se envie de la manera tradicional.
            event.preventDefault();

            // Mostrar que el sistema esta procesando el registro.
            mensaje.textContent = "Registrando...";
            mensaje.style.color = "#103E73";

            // Reunir los datos que se van a enviar al servidor.
            const datos = {
                codigo: document.getElementById("codigo").value.trim(),
                id_tipo: selectorTipo.value,
                deposito: document.getElementById("deposito").value
            };

            try {

                // Enviar los datos al archivo PHP encargado de registrar el contenedor.
                const respuesta = await fetch("api/registrar_contenedor.php", {

                    // Indicar que se enviaran datos mediante el metodo POST.
                    method: "POST",

                    // Indicar que los datos enviados estan en formato JSON.
                    headers: {
                        "Content-Type": "application/json"
                    },

                    // Convertir los datos de JavaScript a JSON para enviarlos.
                    body: JSON.stringify(datos)

                });

                // Leer la respuesta que devuelve el archivo PHP.
                const resultado = await respuesta.json();

                // Si el servidor informa un error, mostrar el mensaje recibido.
                if (!resultado.ok) {
                    throw new Error(resultado.mensaje);
                }

                // Mostrar el mensaje de registro correcto.
                mensaje.textContent = resultado.mensaje;
                mensaje.style.color = "#16803c";

                // Limpiar los campos del formulario despues del registro.
                formulario.reset();

                // Volver a mostrar el texto inicial en el campo del valor.
                campoValor.value = "Seleccioná un tipo";

                // Volver a consultar el stock para actualizar la tabla y los totales.
                await cargarStock();

            } catch (error) {

                // Mostrar el mensaje de error si no se pudo registrar el contenedor.
                mensaje.textContent = error.message;
                mensaje.style.color = "#c62828";

            }

        });

        // Cargar los datos al abrir la pagina
        // Consultar los tipos para completar el selector del formulario.
        cargarTipos();

        // Consultar los contenedores para completar la tabla y el resumen.
        cargarStock();

    </script>

</body>
</html>
