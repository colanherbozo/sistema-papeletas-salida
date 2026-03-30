<?php
session_start();
include("includes/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = strtoupper(trim($_POST['dni']));

    $sql = "SELECT id_usuario, nombre_completo, tipo_usuario, estado, cargo
            FROM usuarios
            WHERE TRIM(UPPER(dni)) = TRIM(UPPER(:dni))";

    $stmt = oci_parse($conn, $sql);
    if (!$stmt) {
        $e = oci_error($conn);
        die("Error al preparar la consulta: " . $e['message']);
    }

    oci_bind_by_name($stmt, ":dni", $dni);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        die("Error al ejecutar la consulta: " . $e['message']);
    }

    $usuario = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS);

    if (
        $usuario &&
        strtolower($usuario['ESTADO']) === 'activo' &&
        in_array(strtolower($usuario['TIPO_USUARIO']), ['usuario', 'autorizador', 'administrador'])
    ) {
        $_SESSION['usuario'] = $usuario['NOMBRE_COMPLETO'];
        $_SESSION['id_usuario'] = $usuario['ID_USUARIO'];
        $_SESSION['rol'] = strtolower($usuario['TIPO_USUARIO']);
        $_SESSION['dni'] = $dni;
        $_SESSION['cargo'] = $usuario['CARGO'];

        oci_free_statement($stmt);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "DNI incorrecto, usuario inactivo o tipo de usuario no válido.";
    }

    oci_free_statement($stmt);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .login-form {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .login-form button {
            width: 100%;
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .login-form button:hover {
            background-color: #2980b9;
        }

        .login-form .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="login-form">
    <h2>Iniciar Sesión</h2>
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="POST">
        <label for="dni">DNI:</label>
        <input type="text" name="dni" id="dni" required>

        <button type="submit">Ingresar</button>
    </form>
</div>
</body>
</html>
