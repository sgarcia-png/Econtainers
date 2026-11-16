
<?php

session_start();

// Si ya inicio sesion, ir directamente a la pagina principal
if (isset($_SESSION['administradora_id'])) {
    header("Location: principal.php");
    exit();
}

require_once __DIR__ . "/config/conexion.php";

$error = "";
$usuario = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($usuario === "" || $contrasena === "") {

        $error = "Ingresa tu DNI y tu contraseña";

    } else {

        try {
            $consulta = $conexion->prepare(
                "SELECT id, rol, dni, password
                 FROM administradora
                 WHERE dni = ?
                 LIMIT 1"
            );

            $consulta->bind_param("s", $usuario);
            $consulta->execute();

            $resultado = $consulta->get_result();
            $administradora = $resultado->fetch_assoc();

            $consulta->close();

            if (
                $administradora &&
                password_verify($contrasena, $administradora["password"])
            ) {

                // Crear un identificador de sesion nuevo por seguridad
                session_regenerate_id(true);

                $_SESSION["administradora_id"] = $administradora["id"];
                $_SESSION["usuario"] = $administradora["rol"];

                header("Location: principal.php");
                exit();

            } else {

                // Mensaje generico para no revelar que dato fallo
                $error = "El usuario o la contraseña son incorrectos.";
            }

        } catch (mysqli_sql_exception $e) {

            error_log("Error durante el inicio de sesion: " . $e->getMessage());

            $error = "No se pudo iniciar sesion. Intenta nuevamente mas tarde";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas - Inicio de sesion</title>
    <link rel="stylesheet" href="estilos.css">
</head>

<body class="login-body">

    <main class="login-contenedor">

        <img src="img/logo.png" alt="ECONTAINERS" class="login-logo">

        <h1 class="login-titulo">Sistema de Ventas</h1>

        <p class="login-subtitulo">
            Ingreso al sistema de gestion de ventas
        </p>

        <?php if ($error !== ""): ?>
            <p
                role="alert"
                style="color: #E30613; text-align: center; margin: 15px 0;"
            >
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form
            class="login-formulario"
            method="POST"
            action="index.php"
            autocomplete="on"
        >

            <label for="usuario">Usuario (DNI)</label>

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

            <label for="contrasena">Contraseña</label>

            <input
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Ingrese su contraseña"
                autocomplete="current-password"
                required
            >

            <button type="submit" class="boton-principal">
                Ingresar
            </button>

        </form>

        <p class="login-pie">
            ECONTAINERS - Sistema de Ventas
        </p>

    </main>

</body>
</html>
