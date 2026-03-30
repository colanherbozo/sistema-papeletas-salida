<?php
session_start();
include("includes/db.php");

// Validar sesión activa
if (
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['rol']) ||
    !isset($_SESSION['id_usuario'])
) {
    header("Location: login.php");
    exit();
}

// Obtener datos de sesión
$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
$rol = strtolower($_SESSION['rol']);
$nombreRol = ucfirst($rol);
$cargo = isset($_SESSION['cargo']) ? htmlspecialchars($_SESSION['cargo'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 40px 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .dashboard h1 {
            margin-bottom: 15px;
            font-size: 24px;
            color: #333;
        }

        .dashboard p {
            margin-top: -10px;
            color: #666;
            font-size: 15px;
        }

        .cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
            width: 250px;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: scale(1.03);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .card a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }

        .logout {
            margin-top: 40px;
        }

        .logout a {
            color: #e74c3c;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <h1>Bienvenido, <?php echo $usuario; ?> (<?php echo $nombreRol; ?>)</h1>
    <?php if (!empty($cargo)): ?>
        <p><?php echo $cargo; ?></p>
    <?php endif; ?>

    <div class="cards">
        <?php if ($rol === 'usuario'): ?>
            <div class="card">
                <h3>Solicitar Papeleta</h3>
                <a href="solicitar_papeleta.php">Ir</a>
            </div>
            <div class="card">
                <h3>Historial</h3>
                <a href="historial_usuario.php">Ver</a>
            </div>
        <?php elseif ($rol === 'autorizador'): ?>
            <div class="card">
                <h3>Aprobar Papeletas</h3>
                <a href="aprobar_papeleta.php">Ir</a>
            </div>
            <div class="card">
                <h3>Historial de Aprobaciones</h3>
                <a href="historial_autorizador.php">Ver</a>
            </div>
        <?php elseif ($rol === 'administrador'): ?>
            <div class="card">
                <h3>Gestión de Usuarios</h3>
                <a href="usuarios.php">Administrar</a>
            </div>
            <div class="card">
                <h3>Ver Logs</h3>
                <a href="logs.php">Ver</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="logout">
        <a href="logout.php">Cerrar sesión</a>
    </div>
</div>
</body>
</html>
