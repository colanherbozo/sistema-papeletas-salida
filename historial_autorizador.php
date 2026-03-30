<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'autorizador') {
    header("Location: login.php");
    exit();
}
include("includes/db.php");

// Consulta corregida a la estructura actual
$sql = "SELECT p.id_papeleta, u.nombre_completo, p.fecha_solicitud, 
               p.hora_salida, p.hora_retorno, tm.descripcion AS motivo, 
               p.observaciones, p.estado_papeleta,
               (SELECT h.comentarios FROM historial h WHERE h.id_papeleta = p.id_papeleta 
                ORDER BY h.fecha_aprobacion DESC 
                FETCH FIRST 1 ROWS ONLY ) AS comentarios
        FROM papeletas_salida p
        INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN tipos_motivo tm ON p.id_motivo = tm.id_motivo
        WHERE LOWER(p.estado_papeleta) != 'pendiente'
        ORDER BY p.fecha_solicitud DESC";

$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Autorizador</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        .contenedor {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #3498db;
            color: white;
        }

        .aprobado {
            color: green;
            font-weight: bold;
        }

        .rechazado {
            color: red;
            font-weight: bold;
        }
        .cancelado {
            color: orange; 
            font-weight: bold
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Historial de Papeletas Revisadas</h2>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Salida</th>
            <th>Retorno</th>
            <th>Motivo</th>
            <th>Observaciones</th>
            <th>Comentarios</th>
            <th>Estado</th>
        </tr>
        <?php while ($row = oci_fetch_assoc($stmt)): ?>
            <tr>
                <td><?= htmlspecialchars($row['NOMBRE_COMPLETO']) ?></td>
                <td><?= htmlspecialchars($row['FECHA_SOLICITUD']) ?></td>
                <td><?= htmlspecialchars($row['HORA_SALIDA']) ?></td>
                <td><?= htmlspecialchars($row['HORA_RETORNO']) ?></td>
                <td><?= htmlspecialchars($row['MOTIVO'] ?? 'Sin motivo') ?></td>
                <td><?= htmlspecialchars(($row['OBSERVACIONES']) instanceof OCILob ? $row['OBSERVACIONES']->load() : $row['OBSERVACIONES']) ?></td>
                <td><?= htmlspecialchars($row['COMENTARIOS']) ?></td>
                <td class="<?= strtolower($row['ESTADO_PAPELETA']) ?>">
                    <?= ucfirst(strtolower($row['ESTADO_PAPELETA'])) ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align:center; margin-top: 30px;">
    <a href="dashboard.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
        ← Volver al inicio
    </a>
</div>
</body>
</html>
