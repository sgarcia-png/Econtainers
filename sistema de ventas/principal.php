
<?php

// =====================================================
// INICIO Y CONTROL DE ACCESO AL SISTEMA
// =====================================================

// Inicia la sesion de la administradora. Permite comprobar si ingreso correctamente 
// al sistema.
session_start();

// Comprueba si existe el identificador de la administradora.
// Si no existe, significa que no hay una sesion iniciada.
if (!isset($_SESSION["administradora_id"])) {

    // Si no inicio sesion, la envia a la pagina de ingreso.
    header("Location: index.php");

    // Detiene la ejecucion para impedir el acceso a la pagina.
    exit();
}

?>

<!-- =====================================================
     ESTRUCTURA PRINCIPAL DEL DOCUMENTO HTML
     ===================================================== -->

<!DOCTYPE html>
<html lang="es">

<head>

    <!-- Permite utilizar correctamente letras, acentos y caracteres especiales. -->
    <meta charset="UTF-8">

    <!-- Adapta el contenido a diferentes tamaños de pantalla. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador. -->
    <title>Sistema de Ventas - Pagina principal</title>

    <!-- Conecta el archivo CSS externo que contiene los estilos generales del sistema. -->
    <link rel="stylesheet" href="estilos.css">


    <!-- =================================================
         ESTILOS CSS PROPIOS DE ESTA PAGINA
         ================================================= -->

    <!-- Estos estilos complementan los que se encuentran en estilos.css. Se utilizan principalmente
         para la tabla de ultimas ventas y sus elementos. -->
    <style>

        /* Agrega un margen superior a la seccion de ultimas ventas. */
        .ventas-api {
            margin-top: 30px;
        }

        /* Contenedor de la tabla.Permite desplazarse horizontalmente si la tabla
           no entra completa en una pantalla pequeña. */
        .tabla-ventas-contenedor {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        /* Configuracion general de la tabla de ventas. */
        .tabla-ventas {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        /* Espaciado, alineacion y bordes de las celdas que contienen los datos de las ventas. */
        .tabla-ventas th,
        .tabla-ventas td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        /* Diseño de los encabezados de la tabla. Fondo azul y letras blancas. */
        .tabla-ventas th {
            background: #103E73;
            color: white;
        }

        /* Cambia el fondo de una fila cuando el cursor pasa por encima de ella. */
        .tabla-ventas tr:hover td {
            background: #f5f7fa;
        }

        /* Agrega separacion al texto que informa el estado de la consulta de ventas. */
        .estado-ventas {
            margin-top: 12px;
        }

        /* Diseño del boton que permite actualizar la informacion de la tabla de ventas. */
        .boton-recargar {
            margin-top: 12px;
            padding: 10px 16px;
            background: #103E73;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Cambia el color del boton cuando el cursor pasa por encima. */
        .boton-recargar:hover {
            background: #082B54;
        }

        /* Cambia la apariencia del boton cuando esta deshabilitado durante una consulta. */
        .boton-recargar:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        /* Accesos a los reportes. Organiza el contenido de las tarjetas en forma de columna. */
        .tarjeta-reporte {
            display: flex;
            flex-direction: column;
        }

        /* Ubica el boton de cada tarjeta de reporte al comienzo de la linea y lo mantiene
           en la parte inferior de la tarjeta. */
        .tarjeta-reporte .boton-tarjeta {
            align-self: flex-start;
            margin-top: auto;
        }

/* Contenedor del enlace de documentacion y el boton del ojo. */
.documentacion-detalle {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    white-space: nowrap;
}

/* Boton que muestra el detalle de la venta. */
.boton-ojo {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 21px;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Cambia el color del ojo cuando se pasa el mouse. */
.boton-ojo:hover {
    color: #082B54;
    transform: scale(1.15);
}

/* Recuadro que contiene el detalle de los contenedores. */
.detalle-venta {
    display: none;
    position: absolute;
    top: 30px;
    right: 0;
    width: 310px;
    max-width: 85vw;
    background: white;
    border: 1px solid #d5dce5;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    z-index: 1000;
    white-space: normal;
    color: #333;
}

/* Muestra el recuadro cuando se activa. */
.detalle-venta.mostrar {
    display: block;
}

/* Titulo del detalle. */
.detalle-venta h4 {
    margin: 0 0 12px;
    color: #103E73;
    font-size: 15px;
}

/* Tabla interna del detalle. */
.tabla-detalle-venta {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

/* Celdas de la tabla interna. */
.tabla-detalle-venta th,
.tabla-detalle-venta td {
    padding: 7px 5px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

/* Encabezados de la tabla interna. */
.tabla-detalle-venta th {
    background: #103E73;
    color: white;
}

/* Informacion final de la venta. */
.resumen-detalle-venta {
    margin-top: 12px;
    font-size: 13px;
    line-height: 1.7;
}

/* Mensaje de carga o error. */
.mensaje-detalle-venta {
    font-size: 13px;
    color: #555;
}

    </style>

</head>


<!-- =====================================================
     CUERPO DE LA PAGINA PRINCIPAL
     ===================================================== -->

<!-- principal-body identifica el cuerpo de esta pagina para aplicar los estilos correspondientes desde CSS. -->
<body class="principal-body">


    <!-- =================================================
         ENCABEZADO DEL SISTEMA
         ================================================= -->

    <!-- El encabezado contiene el logo y el nombre del usuario que utiliza el sistema. -->
    <header class="encabezado">

        <!-- Muestra el logo de ECONTAINERS.
             src indica donde se encuentra la imagen.
             alt contiene su descripcion.
             encabezado-logo permite darle estilo desde CSS. -->
        <img
            src="img/logo.png"
            alt="ECONTAINERS"
            class="encabezado-logo"
        >

        <!-- Muestra el usuario que ingreso al sistema. -->
        <div class="usuario">
            Usuario:
            <strong>Administradora</strong>
        </div>

    </header>


    <!-- =================================================
         CONTENIDO PRINCIPAL
         ================================================= -->

    <!-- main contiene las secciones principales y las tarjetas de acceso a las funcionalidades. -->
    <main class="contenido-principal">

        <!-- Titulo principal de la pagina. -->
        <h1 class="titulo-principal">
            Sistema de Ventas
        </h1>

        <!-- Descripcion que aparece debajo del titulo. -->
        <p class="descripcion-principal">
            Gestion y consulta de ventas de contenedores maritimos
        </p>


        <!-- =================================================
             SECCION DE ADMINISTRACION
             ================================================= -->

        <!-- Esta seccion agrupa las tarjetas desde las cuales se accede a las distintas funciones
             administrativas del sistema. -->
        <section class="seccion">

            <h2 class="seccion-titulo">
                Administracion
            </h2>

            <!-- Contenedor que agrupa todas las tarjetas de administracion. -->
            <div class="tarjetas">


                <!-- -----------------------------------------
                     TARJETA DE VENTAS
                     ----------------------------------------- -->

                <!-- Permite ingresar a la pantalla donde se registran nuevas ventas. -->
                <article class="tarjeta tarjeta-roja">

                    <h3>Ventas</h3>

                    <p>
                        Acceder a la pantalla para registrar nuevas ventas
                    </p>

                    <!-- Al hacer clic, abre nueva_venta.php, donde se encuentra el formulario
                         para cargar una nueva venta. -->
                    <a
                        href="nueva_venta.php"
                        class="boton-tarjeta"
                    >
                        Cargar venta
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE PAGOS
                     ----------------------------------------- -->

                <!-- Permite acceder al modulo de pagos. -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Pagos</h3>

                    <p>
                        Registrar pagos de clientes y consultar
                        los pagos realizados
                    </p>

                    <!-- Abre reporte_pagos.php, donde se pueden registrar y consultar los pagos. -->
                    <a
                        href="reporte_pagos.php"
                        class="boton-tarjeta"
                    >
                        Gestionar pagos
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE CLIENTES
                     ----------------------------------------- -->

                <!-- Permite acceder a la administracion de los clientes registrados. -->
                <article class="tarjeta tarjeta-naranja">

                    <h3>Clientes</h3>

                    <p>
                        Registrar, consultar, modificar y eliminar
                        clientes del sistema
                    </p>

                    <!-- Abre el archivo HTML que contiene el modulo de administracion de clientes. -->
                    <a
                        href="abm_clientes.html"
                        class="boton-tarjeta"
                    >
                        Administrar clientes
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE STOCK DE CONTENEDORES
                     ----------------------------------------- -->

                <!-- Permite ingresar al modulo donde se administra el stock de contenedores. -->
                <article class="tarjeta tarjeta-roja">

                    <h3>Stock de contenedores</h3>

                    <p>
                        Registrar, consultar y controlar los contenedores
                        disponibles y vendidos en los diferentes depósitos.
                    </p>

                    <!-- Abre stock.php, donde se encuentra la gestion del stock de contenedores. -->
                    <a
                        href="stock.php"
                        class="boton-tarjeta"
                    >
                        Gestionar stock
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE TIPOS DE CONTENEDORES
                     ----------------------------------------- -->

                <!-- Permite acceder a la administracion de los distintos tipos de contenedores. -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Tipos de contenedores</h3>

                    <p>
                        Administrar los tipos permitidos:
                        20 pies, 40 pies, 40HC y reefer
                    </p>

                    <!-- Abre abm_tipos_contenedor.html, donde se administran los tipos de contenedores. -->
                    <a
                        href="abm_tipos_contenedor.html"
                        class="boton-tarjeta"
                    >
                        Administrar tipos
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE REPORTE POR FECHA
                     ----------------------------------------- -->

                <!-- Permite consultar las ventas realizadas dentro de un periodo. -->
                <article class="tarjeta tarjeta-azul">

                    <h3>Reporte por fecha</h3>

                    <p>
                        Consultar las ventas realizadas entre dos fechas
                    </p>

                    <!-- Abre el reporte que permite seleccionar una fecha inicial y una fecha final. -->
                    <a
                        href="reporte_ventas_fecha.html"
                        class="boton-tarjeta"
                    >
                        Ver reporte
                    </a>

                </article>


                <!-- -----------------------------------------
                     TARJETA DE REPORTE POR ESTADO DE PAGO
                     ----------------------------------------- -->

                <!-- Permite consultar las ventas segun la situacion de sus pagos. -->
                <article class="tarjeta tarjeta-naranja">

                    <h3>Reporte por estado de pago</h3>

                    <p>
                        Consultar las ventas pendientes, parciales, pagadas o todas las ventas
                    </p>

                    <!-- Abre el reporte que permite filtrar las ventas segun su estado de pago. -->
                    <a
                        href="reporte_ventas_estado.html"
                        class="boton-tarjeta"
                    >
                        Ver reporte
                    </a>

                </article>

            </div>

        </section>


        <!-- =================================================
             SECCION DE CONTENEDORES MARITIMOS
             ================================================= -->

        <!-- Agrupa las tarjetas que permiten consultar las ventas segun el tipo de contenedor. -->
        <section class="seccion">

            <h2 class="seccion-titulo">
                Contenedores Maritimos
            </h2>

            <!-- Contenedor de las tarjetas de cada tipo. -->
            <div class="tarjetas">


                <!-- -----------------------------------------
                     CONTENEDORES DE 20 PIES
                     ----------------------------------------- -->

                <article class="tarjeta tarjeta-roja">

                    <h3>20 pies</h3>

                    <p>
                        Consultar ventas de contenedores de 20 pies
                    </p>

                    <!-- Abre ventas.php y envia el valor 20 mediante la direccion URL.
                         El archivo ventas.php utiliza ese dato para identificar el tipo de contenedor. -->
                    <a href="ventas.php?tipo=20" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>


                <!-- -----------------------------------------
                     CONTENEDORES DE 40 PIES
                     ----------------------------------------- -->

                <article class="tarjeta tarjeta-azul">

                    <h3>40 pies</h3>

                    <p>
                        Consultar ventas de contenedores de 40 pies
                    </p>

                    <!-- Envia tipo=40 a ventas.php para consultar las ventas de contenedores de 40 pies. -->
                    <a href="ventas.php?tipo=40" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>


                <!-- -----------------------------------------
                    CONTENEDORES DE 40 PIES HC
                     ----------------------------------------- -->

                <article class="tarjeta tarjeta-naranja">

                    <h3>40 pies HC</h3>

                    <p>
                        Consultar ventas de contenedores de 40 pies HC
                    </p>

                    <!-- Envia tipo=40hc a ventas.php para consultar las ventas de este tipo. -->
                    <a href="ventas.php?tipo=40hc" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>


                <!-- -----------------------------------------
                     CONTENEDORES REEFER
                     ----------------------------------------- -->

                <article class="tarjeta tarjeta-amarilla">

                    <h3>Reefer</h3>

                    <p>
                        Consultar ventas de contenedores refrigerados
                    </p>

                    <!-- Envia tipo=reefer a ventas.php para consultar las ventas de contenedores
                         refrigerados. -->
                    <a href="ventas.php?tipo=reefer" class="boton-tarjeta">
                        Ver ventas
                    </a>

                </article>

            </div>

        </section>


        <!-- =================================================
             SECCION DE ULTIMAS VENTAS OBTENIDAS DESDE LA API
             ================================================= -->

        <!-- Esta seccion muestra las ultimas ventas registradas.
             La informacion se obtiene de la base de datos mediante api/ventas.php y JavaScript. -->
        <section class="seccion ventas-api">

            <h2 class="seccion-titulo">
                ultimas ventas registradas
            </h2>

            <!-- Explica de donde se obtiene la informacion que aparece en la tabla. -->
            <p>
                Esta informacion se obtiene de la base de datos
                mediante la API y JavaScript
            </p>

            <!-- Boton que permite volver a consultar la API y actualizar los datos de la tabla.
                 Su id permite encontrarlo desde JavaScript. -->
            <button
                type="button"
                id="btnRecargarVentas"
                class="boton-recargar"
            >
                Actualizar ventas
            </button>

            <!-- Muestra mensajes sobre el estado de la consulta.
                 aria-live permite que los lectores de pantalla detecten cuando cambia este mensaje. -->
            <p
                id="estadoVentas"
                class="estado-ventas"
                aria-live="polite"
            >
                Cargando ventas...
            </p>

            <!-- Contenedor que permite desplazar la tabla horizontalmente cuando sea necesario. -->
            <div class="tabla-ventas-contenedor">

                <!-- Tabla donde se mostraran los datos recibidos desde la API. -->
                <table class="tabla-ventas">

                    <!-- Encabezados de las columnas de la tabla. -->
                    <thead>
                        <tr>
                            <th>N.º de venta</th>
                            <th>Fecha</th>
                            <th>Cliente (DNI)</th>
                            <th>Monto total</th>
                            <th>Estado de pago</th>
                            <th>Documentacion / Detalle</th>
                        </tr>
                    </thead>

                    <!-- Cuerpo de la tabla.
                         JavaScript utiliza este id para agregar
                         las filas con los datos recibidos de la API. -->
                    <tbody id="tablaVentas">

                        <!-- Mensaje inicial que aparece mientras se espera la informacion de la API. -->
                        <tr>
                            <td colspan="6">Cargando datos...</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </main>


    <!-- =====================================================
         PIE DE PAGINA
         ===================================================== -->

    <!-- Muestra el nombre del sistema al final de la pagina. -->
    <footer class="pie">
        Sistema de Ventas - ECONTAINERS
    </footer>


    <!-- =====================================================
         INICIO DEL CODIGO JAVASCRIPT
         ===================================================== -->

    <!-- JavaScript se encarga de comunicarse con la API, recibir las ventas y 
     mostrarlas en la tabla sin tener que recargar toda la pagina. -->
    <script>


        // =============================================
        // OBTENCION DE LOS ELEMENTOS HTML
        // =============================================

        // Busca el elemento tbody de la tabla.
        // Aqui se agregaran las filas con los datos de las ventas.
        const tablaVentas = document.getElementById("tablaVentas");

        // Busca el parrafo donde se muestran los mensajes sobre el estado de la 
        //consulta de ventas.
        const estadoVentas = document.getElementById("estadoVentas");

        // Busca el boton que permite actualizar las ventas.
        // Se utiliza para controlar cuando esta habilitado.
        const btnRecargarVentas =
            document.getElementById("btnRecargarVentas");


        // =============================================
        // FUNCION PARA CREAR CELDAS DE LA TABLA
        // =============================================

        // Esta funcion recibe un valor y crea una celda HTML.
        // Se utiliza para agregar los datos de cada venta dentro de las columnas 
        // correspondientes.
        function crearCelda(valor) {

            // Crea un nuevo elemento HTML de tipo td.
            const celda = document.createElement("td");

            // Coloca el valor recibido dentro de la celda.
            // textContent agrega texto sin interpretarlo como HTML.
            // ?? "" significa que si el valor es null o undefined,se coloca un texto
            // vacio.
            celda.textContent = valor ?? "";

            // Devuelve la celda creada para poder utilizarla en otras partes del 
            // programa.
            return celda;

        }


        // =============================================
        // FUNCION PARA FORMATEAR LOS MONTOS
        // =============================================

        // Recibe el monto de una venta y lo convierte al formato de moneda utilizado
        // en Argentina.
        function mostrarMonto(valor) {

            // Convierte el valor recibido en un numero.
            const monto = Number(valor);

            // Comprueba si el valor obtenido es un numero valido.
            if (!Number.isFinite(monto)) {

                // Si no es valido, devuelve este mensaje.
                return "No disponible";
            }

            // Convierte el numero al formato de moneda.
            // es-AR indica el formato utilizado en Argentina.
            // ARS indica que la moneda es el peso argentino.
            // minimumFractionDigits y maximumFractionDigits establecen que se 
            // muestren dos decimales.
            return monto.toLocaleString("es-AR", {
                style: "currency",
                currency: "ARS",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        }


        // =============================================
        // FUNCION PRINCIPAL PARA CARGAR LAS VENTAS
        // =============================================

        // async permite utilizar await dentro de la funcion.
        // Esta funcion consulta la API y muestra las ventas recibidas en la tabla.
        async function cargarVentas() {

            // Deshabilita el boton mientras se realiza
            // la consulta para evitar varios clics seguidos.
            btnRecargarVentas.disabled = true;

            // Informa que se estan consultando las ventas.
            estadoVentas.textContent = "Consultando ventas...";

            // Limpia las filas anteriores de la tabla antes de agregar los nuevos 
            // resultados.
            tablaVentas.replaceChildren();


            // =========================================
            // CONEXION CON LA API
            // =========================================

            // try permite intentar realizar la consulta.
            // Si ocurre un error, se ejecutara el bloque catch.
            try {

                // fetch realiza una solicitud al archivo api/ventas.php para obtener 
                // los datos.
                // await espera a que el servidor responda.
                const respuesta = await fetch("api/ventas.php", {

                    // GET indica que se solicita informacion al servidor, sin enviar 
                    // datos para modificarla.
                    method: "GET",

                    // Indica que JavaScript espera recibir una respuesta en formato 
                    // JSON.
                    headers: {
                        "Accept": "application/json"
                    },

                    // Evita utilizar una respuesta guardada anteriormente en la memoria caché.
                    // Asi se intenta obtener informacion actualizada.
                    cache: "no-store"

                });


                // =========================================
                // CONVERSION DE LA RESPUESTA A JSON
                // =========================================

                // Convierte la respuesta del servidor al formato JSON para poder 
                // utilizar sus datos.
                const resultado = await respuesta.json();


                // =========================================
                // CONTROL DE ERRORES DE LA RESPUESTA
                // =========================================

                // Comprueba si la respuesta HTTP fue correcta.
                // Si no lo fue, genera un error.
                if (!respuesta.ok) {

                    throw new Error(
                        "No se pudieron consultar las ventas"
                    );

                }


                // Comprueba si la API informo que ocurrio un error.
                // Si resultado.ok es false, genera un error.
                if (resultado.ok === false) {

                    throw new Error(
                        "La API informo un error al consultar las ventas"
                    );

                }


                // =========================================
                // OBTENCIOIN DEL ARREGLO DE VENTAS
                // =========================================

                // Comprueba como vienen organizados los datos.
                //
                // Si resultado ya es un arreglo, lo utiliza directamente.
                // Si no, intenta obtener los datos de la propiedad datos.
                // Si esa propiedad no existe, intenta utilizar data.
                //
                // Esto permite trabajar con diferentes formatos de respuesta de la API.
                const ventas = Array.isArray(resultado)
                    ? resultado
                    : (resultado.datos ?? resultado.data);


                // Comprueba que los datos recibidos sean un arreglo.
                // Si no lo son, significa que la respuesta de la API no tiene el 
                // formato esperado.
                if (!Array.isArray(ventas)) {

                    throw new Error(
                        "La respuesta de la API no tiene el formato esperado"
                    );

                }


                // =========================================
                // CONTROL CUANDO NO HAY VENTAS
                // =========================================

                // Comprueba si el arreglo esta vacio.
                // Esto significa que todavia no hay ventas registradas.
                if (ventas.length === 0) {

                    // Crea una nueva fila para la tabla.
                    const fila = document.createElement("tr");

                    // Crea una celda con un mensaje indicando
                    // que todavia no existen ventas.
                    const celda = crearCelda(
                        "Todavia no hay ventas registradas"
                    );

                    // Hace que la celda ocupe las seis columnas de la tabla.
                    celda.colSpan = 6;

                    // Agrega la celda a la fila.
                    fila.appendChild(celda);

                    // Agrega la fila a la tabla.
                    tablaVentas.appendChild(fila);

                    // Informa que la consulta se realizo correctamente.
                    estadoVentas.textContent =
                        "Consulta realizada correctamente";

                    // Sale de la funcion porque no hay ventas para mostrar.
                    return;

                }


                // =========================================
                // RECORRIDO DE LAS VENTAS RECIBIDAS
                // =========================================

                // forEach recorre cada venta que llego desde la API.
                // La variable venta representa una venta individual.
                ventas.forEach(function (venta) {

                    // Crea una nueva fila de la tabla para cada venta encontrada.
                    const fila = document.createElement("tr");


                    // Agrega el numero de venta a la primera columna.
                    // El dato se obtiene de venta, desde nro_venta.
                    fila.appendChild(crearCelda(venta.nro_venta));

                    // Agrega la fecha de la venta.
                    fila.appendChild(crearCelda(venta.fecha));

                    // Agrega el DNI del cliente.
                    fila.appendChild(crearCelda(venta.dni_cliente));

                    // Agrega el monto total de la venta.
                    // Antes utiliza mostrarMonto() para darle formato de pesos argentinos.
                    fila.appendChild(
                        crearCelda(mostrarMonto(venta.monto_total))
                    );

                    // Agrega el estado de pago de la venta.
                    fila.appendChild(crearCelda(venta.estado_pago));


                    
// =============================================
// CREACION DEL ENLACE Y DEL BOTON DEL OJO
// =============================================

// Crear la celda de documentacion y detalle.
const celdaDocumentacion = document.createElement("td");

// Crear un contenedor para los dos elementos.
const contenedorAcciones = document.createElement("div");

contenedorAcciones.className = "documentacion-detalle";


// =============================================
// ENLACE PARA VER DOCUMENTOS
// =============================================

// Crear el enlace.
const botonDocumentacion = document.createElement("a");

// Texto del enlace.
botonDocumentacion.textContent = "Ver documentos";

// Aplicar la clase CSS.
botonDocumentacion.className = "boton-documentacion";

// Enviar el numero de venta a documentacion.php.
const parametros = new URLSearchParams({
    nro_venta: String(venta.nro_venta)
});

// Establecer la direccion del enlace.
botonDocumentacion.href =
    "documentacion.php?" + parametros.toString();


// =============================================
// BOTON DEL OJO PARA VER EL DETALLE
// =============================================

// Crear el boton.
const botonOjo = document.createElement("button");

botonOjo.type = "button";

// Colocar el icono del ojo.
botonOjo.innerHTML = "&#128065;";

botonOjo.className = "boton-ojo";

// Agregar una descripcion al boton.
botonOjo.title = "Ver detalle de los contenedores";

botonOjo.setAttribute(
    "aria-label",
    "Ver detalle de la venta " + venta.nro_venta
);


// =============================================
// RECUADRO DEL DETALLE
// =============================================

// Crear el recuadro donde apareceran los datos.
const detalle = document.createElement("div");

detalle.className = "detalle-venta";

detalle.setAttribute("aria-live", "polite");

// Mensaje inicial.
detalle.textContent = "Cargando detalle...";


// =============================================
// FUNCION PARA CONSULTAR LOS CONTENEDORES
// =============================================

let detalleCargado = false;

async function cargarDetalle() {

    // Evitar consultar nuevamente si los datos ya se cargaron.
    if (detalleCargado) {
        return;
    }

    try {

        // Consultar la API enviando el numero de venta.
        const respuesta = await fetch(
            "api/detalle_venta.php?nro_venta=" +
            encodeURIComponent(venta.nro_venta)
        );

        // Convertir la respuesta a JSON.
        const resultado = await respuesta.json();

        // Comprobar si ocurrio un error.
        if (!respuesta.ok || resultado.ok === false) {
            throw new Error(
                resultado.mensaje || "No se pudo consultar el detalle"
            );
        }

        // Comprobar si la venta tiene contenedores.
        if (!Array.isArray(resultado.datos) ||
            resultado.datos.length === 0) {

            detalle.textContent =
                "Esta venta no tiene contenedores registrados.";

            detalleCargado = true;

            return;
        }

        // Crear el contenido del detalle.
        detalle.replaceChildren();

        // Agregar el titulo.
        const titulo = document.createElement("h4");

        titulo.textContent =
            "Detalle de la venta N.º " + resultado.nro_venta;

        detalle.appendChild(titulo);


        // Crear la tabla de contenedores.
        const tabla = document.createElement("table");

        tabla.className = "tabla-detalle-venta";

        // Crear los encabezados.
        const encabezado = document.createElement("thead");

        const filaEncabezado = document.createElement("tr");

        ["Codigo", "Tipo", "Deposito", "Precio"].forEach(function(texto) {

            const th = document.createElement("th");

            th.textContent = texto;

            filaEncabezado.appendChild(th);

        });

        encabezado.appendChild(filaEncabezado);

        tabla.appendChild(encabezado);


        // Crear el cuerpo de la tabla.
        const cuerpo = document.createElement("tbody");

        // Recorrer los contenedores de la venta.
        resultado.datos.forEach(function(contenedor) {

            const fila = document.createElement("tr");

            // Codigo del contenedor.
            let celda = document.createElement("td");

            celda.textContent = contenedor.codigo;

            fila.appendChild(celda);


            // Tipo de contenedor.
            celda = document.createElement("td");

            celda.textContent = contenedor.nombre_tipo;

            fila.appendChild(celda);


            // Deposito.
            celda = document.createElement("td");

            celda.textContent = contenedor.deposito;

            fila.appendChild(celda);


            // Precio del contenedor.
            celda = document.createElement("td");

            celda.textContent = mostrarMonto(contenedor.precio);

            fila.appendChild(celda);


            cuerpo.appendChild(fila);

        });

        tabla.appendChild(cuerpo);

        detalle.appendChild(tabla);


        // Crear el resumen de la venta.
        const resumen = document.createElement("div");

        resumen.className = "resumen-detalle-venta";

        // Mostrar la cantidad de contenedores.
        const cantidad = document.createElement("div");

        cantidad.textContent =
            "Cantidad de contenedores: " + resultado.cantidad;

        resumen.appendChild(cantidad);


        // Mostrar el total.
        const total = document.createElement("strong");

        total.textContent =
            "Total de la venta: " + mostrarMonto(resultado.total);

        resumen.appendChild(total);

        detalle.appendChild(resumen);

        // Indicar que los datos ya fueron cargados.
        detalleCargado = true;

    } catch (error) {

        // Mostrar un mensaje si ocurre un problema.
        detalle.textContent =
            error.message || "No se pudo cargar el detalle";

    }

}


// =============================================
// EVENTOS DEL BOTON DEL OJO
// =============================================

// Al pasar el mouse por el contenedor, mostrar el detalle.
contenedorAcciones.addEventListener("mouseenter", function() {

    detalle.classList.add("mostrar");

    cargarDetalle();

});

// Al retirar el mouse, ocultar el detalle si no esta fijado.
contenedorAcciones.addEventListener("mouseleave", function() {

    if (botonOjo.dataset.fijado !== "true") {

        detalle.classList.remove("mostrar");

    }

});

// Al hacer clic en el ojo, mostrar u ocultar el detalle.
botonOjo.addEventListener("click", function() {

    const estaFijado = botonOjo.dataset.fijado === "true";

    if (estaFijado) {

        botonOjo.dataset.fijado = "false";

        detalle.classList.remove("mostrar");

    } else {

        botonOjo.dataset.fijado = "true";

        detalle.classList.add("mostrar");

        cargarDetalle();

    }

});


// =============================================
// AGREGAR LOS ELEMENTOS A LA TABLA
// =============================================

// Agregar el enlace.
contenedorAcciones.appendChild(botonDocumentacion);

// Agregar el ojo.
contenedorAcciones.appendChild(botonOjo);

// Agregar el recuadro del detalle.
contenedorAcciones.appendChild(detalle);

// Agregar el contenedor a la celda.
celdaDocumentacion.appendChild(contenedorAcciones);

// Agregar la celda a la fila.
fila.appendChild(celdaDocumentacion);



                    // =====================================
                    // AGREGAR LA FILA A LA TABLA
                    // =====================================

                    // Una vez que se completaron todas las celdas,se agrega la fila completa a la tabla HTML.
                    tablaVentas.appendChild(fila);

                });


                // =========================================
                // MENSAJE DE CONSULTA EXITOSA
                // =========================================

                // Informa que las ventas se cargaron correctamente y muestra la cantidad de ventas 
                // recibidas.
                estadoVentas.textContent =
                    "Ventas cargadas correctamente: " +
                    ventas.length + ".";


            // =============================================
            // CONTROL DE ERRORES DE JAVASCRIPT Y LA API
            // =============================================

            // catch se ejecuta si ocurre algun error durante la consulta, la conversion o el 
            // procesamiento de los datos recibidos.
            } catch (error) {

                // Crea una fila para mostrar el mensaje de error dentro de la tabla.
                const fila = document.createElement("tr");

                // Crea una celda indicando que no se pudiero cargar las ventas.
                const celda = crearCelda(
                    "No se pudieron cargar las ventas"
                );

                // Hace que el mensaje ocupe las seis columnas.
                celda.colSpan = 6;

                // Agrega la celda a la fila.
                fila.appendChild(celda);

                // Muestra la fila de error en la tabla.
                tablaVentas.appendChild(fila);


                // Muestra el mensaje del error y agrega una indicacion para revisar el archivo de la API.
                estadoVentas.textContent =
                    error.message +
                    " Revisa que api/ventas.php este funcionando";


            // =============================================
            // FINALIZACION DE LA CONSULTA
            // =============================================

            // finally se ejecuta siempre, tanto si la consulta termino correctamente como si ocurrio un 
            // error.
            } finally {

                // Vuelve a habilitar el boton para que se pueda realizar otra consulta.
                btnRecargarVentas.disabled = false;

            }

        }


        // =============================================
        // EVENTOS PARA EJECUTAR LA CONSULTA
        // =============================================

        // addEventListener escucha cuando el usuario hace clic en el boton Actualizar ventas.
        // Cuando ocurre el clic, ejecuta la funcion cargarVentas.
        btnRecargarVentas.addEventListener("click", cargarVentas);

        // Ejecuta cargarVentas automaticamente cuando se carga
        // la pagina principal.
        // De esta manera, las ultimas ventas aparecen sin que el usuario tenga que presionar el boton.
        cargarVentas();

    </script>

<!-- Fin del cuerpo de la pagina. -->
</body>

<!-- Fin del documento HTML. -->
</html>
