
<?php

// Incluir el archivo que permite conectarse a la base de datos
require_once __DIR__ . "/config/conexion.php";

// Crear arreglos vacios para guardar los datos que se consultan
$clientes = [];
$contenedores = [];

// Variable para guardar posibles mensajes de error
$error = "";

try {

    // Buscar los clientes registrados en la base de datos
    $consultaClientes = $conexion->query(
        "SELECT DNI, nombre, apellido
         FROM cliente
         ORDER BY apellido, nombre"
    );

    // Recorrer los resultados y guardar cada cliente en el arreglo
    while ($fila = $consultaClientes->fetch_assoc()) {
        $clientes[] = $fila;
    }

    // Buscar solamente los contenedores que se encuentran disponibles
    // Tambien se obtiene el nombre del tipo y su valor
    $consultaContenedores = $conexion->query(
        "SELECT c.codigo, c.deposito, t.nombre_tipo, t.valor
         FROM contenedor AS c
         INNER JOIN tipo_contenedor AS t
             ON c.id_tipo = t.id_tipo
         WHERE c.estado = 'Disponible'
         ORDER BY t.nombre_tipo, c.codigo"
    );

    // Recorrer los resultados y guardar cada contenedor en el arreglo
    while ($fila = $consultaContenedores->fetch_assoc()) {
        $contenedores[] = $fila;
    }

// Si ocurre algun error durante las consultas, se ejecuta este bloque
} catch (Throwable $e) {

    // Guardar el detalle del error en el registro de errores del servidor
    error_log("Error al preparar el formulario de venta: " . $e->getMessage());

    // Mostrar un mensaje general en la pagina
    $error = "No se pudieron cargar los datos. Intenta nuevamente";
}

// Funcion para proteger los datos que se muestran en el HTML
// Evita que se interpreten como codigo HTML o JavaScript
function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <!-- Configuracion general de la pagina -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador -->
    <title>Cargar venta | ECONTAINERS</title>

    <!-- Conectar la hoja de estilos principal -->
    <link rel="stylesheet" href="estilos.css">

    <!-- Estilos propios del formulario de venta -->
    <style>

        /* Contenedor principal del formulario */
        .formulario-venta {
            max-width: 950px;
            margin: 35px auto;
            padding: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        /* Estilo del titulo del formulario */
        .formulario-venta h1 {
            color: #103E73;
            margin-top: 0;
        }

        /* Organizar cada campo del formulario en columna */
        .campo-venta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        /* Estilo de los nombres de los campos */
        .campo-venta label {
            font-weight: 600;
            color: #333;
        }

        /* Estilo de los campos para escribir y seleccionar */
        .campo-venta input,
        .campo-venta select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
            background: #fff;
        }

        /* Mostrar las opciones de contenedores en una cuadricula */
        .lista-contenedores {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            margin: 15px 0 25px;
        }

        /* Apariencia de cada opcion de contenedor */
        .contenedor-opcion {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 9px;
            cursor: pointer;
        }

        /* Cambiar el aspecto de la opcion cuando esta seleccionada */
        .contenedor-opcion:has(input:checked) {
            border-color: #F28C00;
            background: #fff8ed;
        }

        /* Separar un poco la casilla del texto */
        .contenedor-opcion input {
            margin-top: 4px;
        }

        /* Mostrar los datos del contenedor en diferentes lineas */
        .contenedor-opcion span {
            display: block;
            line-height: 1.5;
        }

        /* Recuadro donde se muestra el total estimado */
        .total-venta {
            padding: 18px;
            margin: 20px 0;
            border-radius: 9px;
            background: #f1f5f9;
            color: #103E73;
            font-size: 20px;
            font-weight: bold;
        }

        /* Organizar los botones del formulario */
        .acciones-venta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        /* Estilo general de los botones */
        .boton-venta {
            padding: 12px 20px;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        /* Color del boton para guardar la venta */
        .boton-guardar {
            color: white;
            background: #F28C00;
        }

        /* Cambiar el color cuando el cursor pasa sobre el boton */
        .boton-guardar:hover {
            background: #d97b00;
        }

        /* Estilo del boton para cancelar o volver */
        .boton-cancelar {
            color: #103E73;
            background: #e9eef4;
        }

        /* Estilo general de los mensajes */
        .mensaje-venta {
            display: none;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 7px;
            line-height: 1.5;
        }

        /* Mostrar el mensaje cuando tiene la clase visible */
        .mensaje-venta.visible {
            display: block;
        }

        /* Estilo para los mensajes de error */
        .mensaje-error {
            color: #842029;
            background: #f8d7da;
        }

        /* Estilo para los mensajes de confirmacion */
        .mensaje-exito {
            color: #0f5132;
            background: #d1e7dd;
        }

        /* Mensaje que aparece cuando no hay contenedores disponibles */
        .sin-contenedores {
            color: #666;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        /* Adaptar el formulario para pantallas pequeñas */
        @media (max-width: 600px) {
            .formulario-venta {
                margin: 20px 12px;
                padding: 20px;
            }
        }

        /* Logo del encabezado */
            .encabezado-logo {
                width: 190px;
                background-color: transparent;
                padding: 0;
                border-radius: 0;
            }

            .encabezado-logo img {
                width: 100%;
                max-width: 100%;
                height: auto;
                object-fit: contain;
                display: block;
            }
    </style>
</head>

<body class="principal-body">

    <!-- Encabezado con el logo y el usuario -->
    <header class="encabezado">

        <div class="encabezado-logo">

            <!-- Al hacer clic en el logo se vuelve a la pagina principal -->
            <a href="principal.php">
                <img src="img/logo.png" alt="ECONTAINERS SOLUTIONS">
            </a>

        </div>

        <div class="usuario">Administradora</div>
    </header>

    <!-- Contenido principal de la pagina -->
    <main class="contenido-principal">

    <!-- Titulo, descripcion y boton para volver -->
    <div class="ventas-encabezado">

        <div>
            <h1 class="titulo-principal">Cargar nueva venta</h1>

            <p class="descripcion-principal">
                Completa los datos para registrar una venta de contenedores
            </p>
        </div>

        <!-- Enlace para regresar al menu principal -->
        <a href="principal.php" class="boton-volver">
            Volver al inicio
        </a>

    </div>

    <!-- Formulario donde se cargan los datos de la venta -->
    <div class="formulario-venta">

        <!-- Lugar donde JavaScript muestra mensajes de error o confirmacion -->
        <div id="mensaje" class="mensaje-venta" role="status" aria-live="polite"></div>

        <!-- Si hubo un error al cargar los datos, se muestra el mensaje -->
        <?php if ($error !== ""): ?>

            <p class="mensaje-venta visible mensaje-error">
                <?= escapar($error) ?>
            </p>

        <!-- Si no hubo errores, se muestra el formulario -->
        <?php else: ?>

            <form id="formularioVenta">

                <!-- Campo para seleccionar al cliente -->
                <div class="campo-venta">
                    <label for="dni_cliente">Cliente</label>

                    <select id="dni_cliente" name="dni_cliente" required>
                        <option value="">Selecciona un cliente</option>

                        <!-- Mostrar todos los clientes que se cargaron desde la base de datos -->
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= escapar($cliente["DNI"]) ?>">
                                <?= escapar(
                                    $cliente["apellido"] . ", " .
                                    $cliente["nombre"] . " - DNI: " .
                                    $cliente["DNI"]
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Avisar si todavia no hay clientes registrados -->
                    <?php if (count($clientes) === 0): ?>
                        <small>No hay clientes registrados. Primero agrega un cliente</small>
                    <?php endif; ?>
                </div>

                <!-- Campo para seleccionar la fecha de la venta -->
                <div class="campo-venta">
                    <label for="fecha">Fecha de la venta</label>
                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= date("Y-m-d") ?>"
                        required
                    >
                </div>

                <!-- Seccion donde aparecen los contenedores disponibles -->
                <h2>Seleccionar contenedores</h2>
                <p>Podes seleccionar uno o varios contenedores disponibles</p>

                <!-- Comprobar si existen contenedores disponibles -->
                <?php if (count($contenedores) > 0): ?>

                    <div class="lista-contenedores">

                        <!-- Recorrer todos los contenedores disponibles -->
                        <?php foreach ($contenedores as $contenedor): ?>

                            <!-- Cada contenedor aparece como una opcion seleccionable -->
                            <label class="contenedor-opcion">

                                <input
                                    type="checkbox"
                                    name="contenedores[]"
                                    value="<?= escapar($contenedor["codigo"]) ?>"
                                    data-precio="<?= escapar($contenedor["valor"]) ?>"
                                >

                                <span>
                                    <!-- Mostrar el codigo del contenedor -->
                                    <strong>
                                        <?= escapar($contenedor["codigo"]) ?>
                                    </strong>
                                    <br>

                                    <!-- Mostrar el tipo de contenedor -->
                                    <?= escapar($contenedor["nombre_tipo"]) ?>
                                    <br>

                                    <!-- Mostrar el deposito donde se encuentra -->
                                    Depósito:
                                    <?= escapar($contenedor["deposito"] ?: "No especificado") ?>
                                    <br>

                                    <!-- Mostrar el precio del contenedor con formato de moneda -->
                                    Precio:
                                    $<?= number_format(
                                        (float) $contenedor["valor"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>
                                </span>
                            </label>

                        <?php endforeach; ?>

                    </div>

                <!-- Si no hay contenedores, mostrar un aviso -->
                <?php else: ?>

                    <p class="sin-contenedores">
                        No hay contenedores disponibles para vender en este momento
                    </p>

                <?php endif; ?>

                <!-- Mostrar el total que se va calculando al seleccionar contenedores -->
                <div class="total-venta">
                    Total estimado:
                    <span id="totalVenta">$0,00</span>
                </div>

                <!-- Aclarar que el servidor vuelve a calcular el importe definitivo -->
                <p>
                    El importe definitivo sera calculado nuevamente por el servidor
                    utilizando los precios guardados en la base de datos
                </p>

                <!-- Boton para enviar el formulario -->
                <div class="acciones-venta">
                    <button
                        type="submit"
                        id="botonGuardar"
                        class="boton-venta boton-guardar"
                        <?= count($clientes) === 0 || count($contenedores) === 0 ? "disabled" : "" ?>
                    >
                        Registrar venta
                    </button>


                </div>

            </form>

        <?php endif; ?>

        </div>

    </main>

    <!-- Codigo JavaScript que controla el calculo y el envio de la venta -->
    <script>

        // Obtener los elementos del formulario mediante sus identificadores
        const formulario = document.getElementById("formularioVenta");
        const totalVenta = document.getElementById("totalVenta");
        const mensaje = document.getElementById("mensaje");
        const botonGuardar = document.getElementById("botonGuardar");

        // Funcion que convierte un numero al formato de moneda argentina
        function formatoMoneda(valor) {
            return new Intl.NumberFormat("es-AR", {
                style: "currency",
                currency: "ARS"
            }).format(valor);
        }

        // Funcion que suma los precios de los contenedores seleccionados
        function calcularTotal() {
            let total = 0;

            // Buscar las casillas que estan seleccionadas
            document.querySelectorAll(
                'input[name="contenedores[]"]:checked'
            ).forEach(function (elemento) {

                // Sumar el precio de cada contenedor al total
                total += Number(elemento.dataset.precio || 0);
            });

            // Mostrar el resultado en la pagina
            totalVenta.textContent = formatoMoneda(total);
        }

        // Detectar cuando se selecciona o se desmarca un contenedor
        document.querySelectorAll(
            'input[name="contenedores[]"]'
        ).forEach(function (elemento) {
            elemento.addEventListener("change", calcularTotal);
        });

        // Funcion para mostrar mensajes en el formulario
        function mostrarMensaje(texto, tipo) {
            mensaje.textContent = texto;
            mensaje.className = "mensaje-venta visible " + tipo;
        }

        // Comprobar que el formulario exista antes de agregar el evento
        if (formulario) {

            // Detectar cuando el usuario intenta registrar la venta
            formulario.addEventListener("submit", async function (evento) {

                // Evitar que el navegador envie el formulario de la forma tradicional
                evento.preventDefault();

                // Obtener el DNI del cliente y la fecha seleccionada
                const dni = document.getElementById("dni_cliente").value;
                const fecha = document.getElementById("fecha").value;

                // Obtener todos los contenedores que fueron seleccionados
                const seleccionados = Array.from(
                    document.querySelectorAll(
                        'input[name="contenedores[]"]:checked'
                    )
                );

                // Comprobar que se haya seleccionado un cliente
                if (!dni) {
                    mostrarMensaje(
                        "Selecciona un cliente",
                        "mensaje-error"
                    );
                    return;
                }

                // Comprobar que se haya seleccionado al menos un contenedor
                if (seleccionados.length === 0) {
                    mostrarMensaje(
                        "Selecciona al menos un contenedor",
                        "mensaje-error"
                    );
                    return;
                }

                // Preparar los datos que se enviaran al servidor
                const datos = {
                    dni_cliente: dni,
                    fecha: fecha,

                    // Crear una lista con los codigos de los contenedores seleccionados
                    contenedores: seleccionados.map(function (elemento) {
                        return {
                            codigo: elemento.value
                        };
                    })
                };

                // Desactivar el boton para evitar que se envie la venta varias veces
                botonGuardar.disabled = true;
                botonGuardar.textContent = "Registrando...";

                // Ocultar el mensaje anterior
                mensaje.className = "mensaje-venta";

                try {

                    // Enviar los datos a la API que registra la venta
                    const respuesta = await fetch(
                        "api/registrar_venta.php",
                        {
                            method: "POST",

                            // Indicar que los datos se envian en formato JSON
                            headers: {
                                "Content-Type": "application/json"
                            },

                            // Convertir los datos de JavaScript a formato JSON
                            body: JSON.stringify(datos)
                        }
                    );

                    // Leer la respuesta que devuelve el servidor
                    const resultado = await respuesta.json();

                    // Comprobar si la respuesta indica un error
                    if (!respuesta.ok || !resultado.ok) {
                        throw new Error(
                            resultado.mensaje ||
                            "No se pudo registrar la venta"
                        );
                    }

                    // Mostrar la confirmacion con el numero y el total de la venta
                    mostrarMensaje(
                        "¡Venta registrada correctamente! Numero de venta: " +
                        resultado.datos.nro_venta +
                        ". Total: " +
                        formatoMoneda(Number(resultado.datos.monto_total)) +
                        ".",
                        "mensaje-exito"
                    );

                    // Limpiar los campos del formulario
                    formulario.reset();

                    // Volver a colocar la fecha actual
                    document.getElementById("fecha").value =
                        new Date().toLocaleDateString("en-CA");

                    // Actualizar el total para que vuelva a mostrar cero
                    calcularTotal();

                    // Dar tiempo para leer la confirmacion antes de abrir el listado
                    setTimeout(function () {
                        window.location.href = "ventas.php";
                    }, 1800);

                } catch (error) {

                    // Mostrar el mensaje de error si no se pudo registrar la venta
                    mostrarMensaje(
                        error.message ||
                        "No se pudo conectar con el servidor.",
                        "mensaje-error"
                    );

                } finally {

                    // Volver a habilitar el boton y colocar su texto original
                    botonGuardar.disabled = false;
                    botonGuardar.textContent = "Registrar venta";
                }
            });
        }
    </script>

</body>
</html>
