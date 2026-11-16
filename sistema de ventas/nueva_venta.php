
<?php

require_once __DIR__ . "/config/conexion.php";

$clientes = [];
$contenedores = [];
$error = "";

try {
    // Buscar los clientes registrados
    $consultaClientes = $conexion->query(
        "SELECT DNI, nombre, apellido
         FROM cliente
         ORDER BY apellido, nombre"
    );

    while ($fila = $consultaClientes->fetch_assoc()) {
        $clientes[] = $fila;
    }

    // Buscar solamente los contenedores disponibles
    $consultaContenedores = $conexion->query(
        "SELECT c.codigo, c.deposito, t.nombre_tipo, t.valor
         FROM contenedor AS c
         INNER JOIN tipo_contenedor AS t
             ON c.id_tipo = t.id_tipo
         WHERE c.estado = 'Disponible'
         ORDER BY t.nombre_tipo, c.codigo"
    );

    while ($fila = $consultaContenedores->fetch_assoc()) {
        $contenedores[] = $fila;
    }

} catch (Throwable $e) {
    error_log("Error al preparar el formulario de venta: " . $e->getMessage());
    $error = "No se pudieron cargar los datos. Intenta nuevamente";
}

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar venta | ECONTAINERS</title>
    <link rel="stylesheet" href="estilos.css">

    <style>
        .formulario-venta {
            max-width: 950px;
            margin: 35px auto;
            padding: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        .formulario-venta h1 {
            color: #103E73;
            margin-top: 0;
        }

        .campo-venta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 22px;
        }

        .campo-venta label {
            font-weight: 600;
            color: #333;
        }

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

        .lista-contenedores {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            margin: 15px 0 25px;
        }

        .contenedor-opcion {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 9px;
            cursor: pointer;
        }

        .contenedor-opcion:has(input:checked) {
            border-color: #F28C00;
            background: #fff8ed;
        }

        .contenedor-opcion input {
            margin-top: 4px;
        }

        .contenedor-opcion span {
            display: block;
            line-height: 1.5;
        }

        .total-venta {
            padding: 18px;
            margin: 20px 0;
            border-radius: 9px;
            background: #f1f5f9;
            color: #103E73;
            font-size: 20px;
            font-weight: bold;
        }

        .acciones-venta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

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

        .boton-guardar {
            color: white;
            background: #F28C00;
        }

        .boton-guardar:hover {
            background: #d97b00;
        }

        .boton-cancelar {
            color: #103E73;
            background: #e9eef4;
        }

        .mensaje-venta {
            display: none;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 7px;
            line-height: 1.5;
        }

        .mensaje-venta.visible {
            display: block;
        }

        .mensaje-error {
            color: #842029;
            background: #f8d7da;
        }

        .mensaje-exito {
            color: #0f5132;
            background: #d1e7dd;
        }

        .sin-contenedores {
            color: #666;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

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

    <header class="encabezado">
        <div class="encabezado-logo">
            <a href="principal.php">
                <img src="img/logo.png" alt="ECONTAINERS SOLUTIONS">
            </a>
        </div>

        <div class="usuario">Administradora</div>
    </header>

    <main class="contenido-principal">

    <div class="ventas-encabezado">

        <div>
            <h1 class="titulo-principal">Cargar nueva venta</h1>

            <p class="descripcion-principal">
                Completa los datos para registrar una venta de contenedores
            </p>
        </div>

        <a href="principal.php" class="boton-volver">
            Volver al inicio
        </a>

    </div>

    <div class="formulario-venta">

        <div id="mensaje" class="mensaje-venta" role="status" aria-live="polite"></div>

        <?php if ($error !== ""): ?>

            <p class="mensaje-venta visible mensaje-error">
                <?= escapar($error) ?>
            </p>

        <?php else: ?>

            <form id="formularioVenta">

                <div class="campo-venta">
                    <label for="dni_cliente">Cliente</label>

                    <select id="dni_cliente" name="dni_cliente" required>
                        <option value="">Selecciona un cliente</option>

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

                    <?php if (count($clientes) === 0): ?>
                        <small>No hay clientes registrados. Primero agrega un cliente</small>
                    <?php endif; ?>
                </div>

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

                <h2>Seleccionar contenedores</h2>
                <p>Podes seleccionar uno o varios contenedores disponibles</p>

                <?php if (count($contenedores) > 0): ?>

                    <div class="lista-contenedores">

                        <?php foreach ($contenedores as $contenedor): ?>

                            <label class="contenedor-opcion">
                                <input
                                    type="checkbox"
                                    name="contenedores[]"
                                    value="<?= escapar($contenedor["codigo"]) ?>"
                                    data-precio="<?= escapar($contenedor["valor"]) ?>"
                                >

                                <span>
                                    <strong>
                                        <?= escapar($contenedor["codigo"]) ?>
                                    </strong>
                                    <br>

                                    <?= escapar($contenedor["nombre_tipo"]) ?>
                                    <br>

                                    Depósito:
                                    <?= escapar($contenedor["deposito"] ?: "No especificado") ?>
                                    <br>

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

                <?php else: ?>

                    <p class="sin-contenedores">
                        No hay contenedores disponibles para vender en este momento
                    </p>

                <?php endif; ?>

                <div class="total-venta">
                    Total estimado:
                    <span id="totalVenta">$0,00</span>
                </div>

                <p>
                    El importe definitivo sera calculado nuevamente por el servidor
                    utilizando los precios guardados en la base de datos
                </p>

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

    <script>
        const formulario = document.getElementById("formularioVenta");
        const totalVenta = document.getElementById("totalVenta");
        const mensaje = document.getElementById("mensaje");
        const botonGuardar = document.getElementById("botonGuardar");

        function formatoMoneda(valor) {
            return new Intl.NumberFormat("es-AR", {
                style: "currency",
                currency: "ARS"
            }).format(valor);
        }

        function calcularTotal() {
            let total = 0;

            document.querySelectorAll(
                'input[name="contenedores[]"]:checked'
            ).forEach(function (elemento) {
                total += Number(elemento.dataset.precio || 0);
            });

            totalVenta.textContent = formatoMoneda(total);
        }

        document.querySelectorAll(
            'input[name="contenedores[]"]'
        ).forEach(function (elemento) {
            elemento.addEventListener("change", calcularTotal);
        });

        function mostrarMensaje(texto, tipo) {
            mensaje.textContent = texto;
            mensaje.className = "mensaje-venta visible " + tipo;
        }

        if (formulario) {
            formulario.addEventListener("submit", async function (evento) {
                evento.preventDefault();

                const dni = document.getElementById("dni_cliente").value;
                const fecha = document.getElementById("fecha").value;

                const seleccionados = Array.from(
                    document.querySelectorAll(
                        'input[name="contenedores[]"]:checked'
                    )
                );

                if (!dni) {
                    mostrarMensaje(
                        "Selecciona un cliente",
                        "mensaje-error"
                    );
                    return;
                }

                if (seleccionados.length === 0) {
                    mostrarMensaje(
                        "Selecciona al menos un contenedor",
                        "mensaje-error"
                    );
                    return;
                }

                const datos = {
                    dni_cliente: dni,
                    fecha: fecha,
                    contenedores: seleccionados.map(function (elemento) {
                        return {
                            codigo: elemento.value
                        };
                    })
                };

                botonGuardar.disabled = true;
                botonGuardar.textContent = "Registrando...";
                mensaje.className = "mensaje-venta";

                try {
                    const respuesta = await fetch(
                        "api/registrar_venta.php",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(datos)
                        }
                    );

                    const resultado = await respuesta.json();

                    if (!respuesta.ok || !resultado.ok) {
                        throw new Error(
                            resultado.mensaje ||
                            "No se pudo registrar la venta"
                        );
                    }

                    mostrarMensaje(
                        "¡Venta registrada correctamente! Numero de venta: " +
                        resultado.datos.nro_venta +
                        ". Total: " +
                        formatoMoneda(Number(resultado.datos.monto_total)) +
                        ".",
                        "mensaje-exito"
                    );

                    formulario.reset();
                    document.getElementById("fecha").value =
                        new Date().toLocaleDateString("en-CA");

                    calcularTotal();

                    // Dar tiempo para leer la confirmacion antes de abrir el listado
                    setTimeout(function () {
                        window.location.href = "ventas.php";
                    }, 1800);

                } catch (error) {
                    mostrarMensaje(
                        error.message ||
                        "No se pudo conectar con el servidor.",
                        "mensaje-error"
                    );

                } finally {
                    botonGuardar.disabled = false;
                    botonGuardar.textContent = "Registrar venta";
                }
            });
        }
    </script>

</body>
</html>
