
<?php

// =====================================================
// INICIO Y CONTROL DE LA SESION
// =====================================================

// Inicia sesion para poder guardar informacion de la administradora para utilizar 
// el sistema.
session_start();

// Comprueba si ya existe una sesion iniciada.
// Si la administradora ya ingreso, la envia a la pagina principal.
if (isset($_SESSION['administradora_id'])) {

    // Se dirige a principal.php.
    header("Location: principal.php");

    // Detiene la ejecucion para que no siga mostrando el formulario de inicio de 
    // sesion.
    exit();
}


// =====================================================
// CONEXION CON LA BASE DE DATOS
// =====================================================

// Incluye el archivo conexion.php, que contiene la conexion con la base de datos MySQL.
// __DIR__ representa la carpeta donde se encuentra el archivo index.php.
// require_once evita incluir el mismo archivo mas de una vez.
require_once __DIR__ . "/config/conexion.php";


// =====================================================
// VARIABLES PARA EL INICIO DE SESION
// =====================================================

// Guarda los posibles mensajes de error.
// Al principio se encuentra vacio porque todavia no ocurrio ningun error.
$error = "";

// Guarda el DNI ingresado por el usuario. Se utiliza para mantenerlo escrito si 
// ocurre un error.
$usuario = "";


// =====================================================
// COMPROBACION DEL ENVIO DEL FORMULARIO
// =====================================================

// Comprueba si el formulario fue enviado mediante POST.
// POST es el metodo utilizado para enviar los datos del formulario desde HTML hacia 
// PHP.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Obtiene el DNI enviado desde el formulario.
    // $_POST["usuario"] contiene el dato ingresado.
    // ?? "" significa que si no existe ese dato, se utiliza un texto vacio.
    // trim() elimina los espacios al principio y al final.
    $usuario = trim($_POST["usuario"] ?? "");

    // Obtiene la contraseña ingresada.
    // Si no se recibio ninguna contraseña, queda vacia.
    $contrasena = $_POST["contrasena"] ?? "";


    // =================================================
    // VALIDACION DE LOS CAMPOS DEL FORMULARIO
    // =================================================

    // Comprueba si el DNI o la contraseña estan vacios.
    if ($usuario === "" || $contrasena === "") {

        // Si falta alguno de los datos, muestra este mensaje.
        $error = "Ingresa tu DNI y tu contraseña";

    } else {


        // =============================================
        // BUSQUEDA DE LA ADMINISTRADORA EN MYSQL
        // =============================================

        // try permite intentar realizar una operacion que podria generar un error.
        try {

            // Prepara una consulta SQL para buscar a la administradora en la tabla administradora.
            // Busca los campos id, rol, dni y password.
            // El signo ? representa el dato que se enviara despues mediante bind_param().
            // LIMIT 1 hace que devuelva como maximo un registro.
            $consulta = $conexion->prepare(
                "SELECT id, rol, dni, password
                 FROM administradora
                 WHERE dni = ?
                 LIMIT 1"
            );

            // Vincula el DNI ingresado con el signo ? de la consulta.
            // La letra s indica que el dato se envia como texto.
            $consulta->bind_param("s", $usuario);

            // Ejecuta la consulta SQL en la base de datos.
            $consulta->execute();

            // Obtiene el resultado de la consulta.
            $resultado = $consulta->get_result();

            // Guarda los datos encontrados en un arreglo.
            // Si encuentra a la administradora, guarda sus datos.
            // Si no encuentra ningun registro, devuelve null.
            $administradora = $resultado->fetch_assoc();

            // Cierra la consulta porque ya termino de utilizarse.
            $consulta->close();


            // =============================================
            // VERIFICACION DEL DNI Y LA CONTRASEÑA
            // =============================================

            // Comprueba dos condiciones:
            // 1. Que se haya encontrado una administradora.
            // 2. Que la contraseña ingresada coincida con la contraseña cifrada que 
            // esta guardada en la base de datos.
            // password_verify() permite comprobar la contraseña sin necesidad de 
            // guardar la contraseña original.
            if (
                $administradora &&
                password_verify($contrasena, $administradora["password"])
            ) {

                // =========================================
                // CREACION DE LA SESION DE LA ADMINISTRADORA
                // =========================================

                // Genera un nuevo identificador de sesion por seguridad despues de 
                // iniciar sesion.
                // true elimina el identificador anterior.
                session_regenerate_id(true);

                // Guarda el ID de la administradora en la sesion.
                // Este dato permite identificar quien ingreso.
                $_SESSION["administradora_id"] = $administradora["id"];

                // Guarda el rol de la administradora en la sesion.
                $_SESSION["usuario"] = $administradora["rol"];


                // =========================================
                // REDIRECCION A LA PAGINA PRINCIPAL
                // =========================================

                // Si los datos son correctos, envia a la administradora a principal.php.
                header("Location: principal.php");

                // Detiene la ejecucion del archivo.
                exit();

            } else {

                // Si el DNI no existe o la contraseña es incorrecta, muestra un 
                // mensaje general.
                // Asi no se informa cual de los dos datos fallo.
                $error = "El usuario o la contraseña son incorrectos.";
            }


        // =============================================
        // CONTROL DE ERRORES DE LA BASE DE DATOS
        // =============================================

        // Si ocurre un error relacionado con MySQL, se ejecuta este bloque catch.
        // La variable $e contiene informacion del error.
        } catch (mysqli_sql_exception $e) {

            // Guarda el detalle del error en el registro de errores del servidor.
            error_log("Error durante el inicio de sesion: " . $e->getMessage());

            // Muestra un mensaje general para indicar que no se pudo completar el 
            // inicio de sesion.
            $error = "No se pudo iniciar sesion. Intenta nuevamente mas tarde";
        }
    }
}
?>

<!-- =====================================================
     ESTRUCTURA HTML DE LA PAGINA
     ===================================================== -->

<!DOCTYPE html>
<html lang="es">

<head>

    <!-- Permite utilizar caracteres especiales y acentos. -->
    <meta charset="UTF-8">

    <!-- Adapta el diseño a diferentes tamaños de pantalla,
         como computadoras, tablets y celulares. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Titulo que aparece en la pestaña del navegador. -->
    <title>Sistema de Ventas - Inicio de sesion</title>

    <!-- Conecta el archivo de estilos CSS para darle
         diseño y colores a la pagina de inicio de sesion. -->
    <link rel="stylesheet" href="estilos.css">

</head>


<!-- =====================================================
     CUERPO DE LA PAGINA
     ===================================================== -->

<!-- login-body es una clase de CSS que permite aplicar los estilos correspondientes al fondo de la pagina. -->
<body class="login-body">

    <!-- Contenedor principal donde se encuentra todo el contenido del inicio de sesion. -->
    <main class="login-contenedor">


        <!-- =============================================
             LOGO Y TITULOS DEL SISTEMA
             ============================================= -->

        <!-- Muestra el logo de ECONTAINERS.
             src indica la ubicacion de la imagen.
             alt contiene un texto alternativo.
             login-logo permite aplicar estilos desde CSS. -->
        <img src="img/logo.png" alt="ECONTAINERS" class="login-logo">

        <!-- Titulo principal que aparece en el formulario. -->
        <h1 class="login-titulo">Sistema de Ventas</h1>

        <!-- Texto que explica para que se utiliza la pagina de inicio de sesion. -->
        <p class="login-subtitulo">
            Ingreso al sistema de gestion de ventas
        </p>


        <!-- =============================================
             MUESTRA DE MENSAJES DE ERROR
             ============================================= -->

        <!-- Comprueba si la variable $error contiene
             algun mensaje. Si esta vacia, no muestra nada. -->
        <?php if ($error !== ""): ?>

            <!-- Muestra el mensaje de error en pantalla.
                 role="alert" permite identificarlo como un mensaje importante para lectores de pantalla.
                 style aplica color rojo y alineacion centrada. -->
            <p
                role="alert"
                style="color: #E30613; text-align: center; margin: 15px 0;"
            >

                <!-- htmlspecialchars evita que el contenido se interprete como codigo HTML. -->
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>

            </p>

        <!-- Fin de la condicion que muestra los errores. -->
        <?php endif; ?>


        <!-- =============================================
             FORMULARIO DE INICIO DE SESION
             ============================================= -->

        <!-- Formulario donde la administradora ingresa
             su DNI y contraseña.

             method="POST": envia los datos mediante POST.
             action="index.php": los datos se envian nuevamente a este mismo archivo.
             autocomplete="on": permite utilizar el autocompletado del navegador. -->
        <form
            class="login-formulario"
            method="POST"
            action="index.php"
            autocomplete="on"
        >

            <!-- Texto que identifica el campo del DNI.
                 for="usuario" lo relaciona con el input que tiene id="usuario". -->
            <label for="usuario">Usuario (DNI)</label>

            <!-- Campo donde se ingresa el DNI.

                 type="text": permite ingresar texto.
                 id="usuario": identifica el campo.
                 name="usuario": nombre utilizado por PHP para recibir el dato mediante $_POST.
                 placeholder: muestra un texto de ayuda.
                 value: mantiene el DNI ingresado anteriormente.
                 htmlspecialchars: protege el contenido mostrado.
                 autocomplete="username": indica que es el usuario.
                 inputmode="numeric": sugiere un teclado numerico.
                 required: obliga a completar el campo. -->
            <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Ingrese su DNI"
                value="<?= htmlspecialchars($usuario, ENT_QUOTES, "UTF-8") ?>"
                autocomplete="username"
                inputmode="numeric"
                required
            >

            <!-- Texto que identifica el campo de contraseña. -->
            <label for="contrasena">Contraseña</label>

            <!-- Campo para ingresar la contraseña.

                 type="password": oculta los caracteres ingresados.
                 name="contrasena": permite que PHP reciba la contraseña mediante $_POST.
                 placeholder: muestra un texto de ayuda.
                 autocomplete="current-password": indica que
                 se trata de la contraseña actual.
                 required: obliga a completar este campo. -->
            <input
                type="password"
                name="contrasena"
                placeholder="Ingrese su contraseña"
                autocomplete="current-password"
                required
            >

            <!-- Boton que envia el formulario.
                 type="submit" indica que al presionarlo se envian los datos para que PHP los compruebe.
                 boton-principal aplica los estilos del CSS. -->
            <button type="submit" class="boton-principal">
                Ingresar
            </button>

        <!-- Fin del formulario de inicio de sesion. -->
        </form>


        <!-- =============================================
             PIE DEL FORMULARIO
             ============================================= -->

        <!-- Texto que aparece al final del formulario. -->
        <p class="login-pie">
            ECONTAINERS - Sistema de Ventas
        </p>

    </main>

<!-- Fin del contenido visible de la pagina. -->
</body>

<!-- Fin del documento HTML. -->
</html>
