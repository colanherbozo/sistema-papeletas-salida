<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'administrador') {
    header("Location: login.php");
    exit();
}
include("includes/db.php");

// Consulta para obtener logs con unión a usuarios
$sql = "SELECT l.id_log,
               u.nombre_completo AS usuario,
               l.accion,               
               TO_CHAR(l.fecha_hora, 'DD/MM/YYYY HH24:MI:SS') AS fecha_hora,
                l.descripcion
        FROM logs l
         JOIN usuarios u ON l.id_usuario = u.id_usuario
        ORDER BY l.fecha_hora DESC";

$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Auditoría de Actividades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .contenedor {
            max-width: 1000px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #dcdde1;
            padding: 12px 10px;
            text-align: center;
        }

        table th {
            background-color: #3498db;
            color: #ecf0f1;
        }

        table tr:nth-child(even) {
            background-color: #f2f6fa;
        }

        .descripcion {
            font-style: italic;
            color: #555;
            font-size: 0.9em;
        }

        a {
            display: inline-block;
            margin-top: 25px;
            color: #2980b9;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.85em;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <h2>Auditoría del Proceso de Control de Papeletas</h2>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = oci_fetch_assoc($stmt)): ?>
                    <tr>
                        <td><?= $row['ID_LOG'] ?></td>
                        <td><?= htmlspecialchars($row['USUARIO']) ?></td>
                        <td><?= htmlspecialchars($row['ACCION']) ?></td>
                        <td class="descripcion">
                            <?php
                            if (is_object($row['DESCRIPCION']) && method_exists($row['DESCRIPCION'], 'load')) {
                                echo htmlspecialchars($row['DESCRIPCION']->load());
                            } else {
                                echo htmlspecialchars($row['DESCRIPCION']);
                            }
                            ?>
                        </td>
                        <td><?= $row['FECHA_HORA'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="text-align:center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
                ← Volver al inicio
            </a>
        </div>

        <div class="footer">
            Sistema de Control de Papeletas · Transparencia y Seguridad Institucional
        </div>
</body>

</html>