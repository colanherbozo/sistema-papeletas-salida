<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'usuario') {
    header("Location: login.php");
    exit();
}

include("includes/db.php");

$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT p.id_papeleta, p.fecha_solicitud, p.hora_salida, p.hora_retorno,
               m.descripcion AS motivo,p.observaciones, p.estado_papeleta
        FROM papeletas_salida p
        LEFT JOIN tipos_motivo m ON p.id_motivo = m.id_motivo
        WHERE p.id_usuario = :id_usuario 
        ORDER BY p.fecha_solicitud DESC";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id_usuario", $id_usuario);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    die("❌ Error al ejecutar la consulta: " . $e['message']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Papeletas</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        .contenedor {
            max-width: 900px;
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

        .estado-pendiente { color: orange; font-weight: bold; }
        .estado-aprobado { color: green; font-weight: bold; }
        .estado-rechazado { color: red; font-weight: bold; }
        .estado-anulado { color: gray; font-weight: bold; }
        .estado-cancelado {color:rgb(255, 25, 0); font-weight: bold;}

        .no-disponible {
            color: #000;
            font-weight: bold;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-warning { background-color: #f1c40f; color: white; }
        .btn-danger  { background-color: #e74c3c; color: white; }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Historial de Solicitudes</h2>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Salida</th>
            <th>Retorno</th>
            <th>Motivo</th>
            <th>Observaciones</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)): ?>
            <tr>
                <td><?= htmlspecialchars($row['FECHA_SOLICITUD']) ?></td>
                <td><?= htmlspecialchars($row['HORA_SALIDA']) ?></td>
                <td><?= htmlspecialchars($row['HORA_RETORNO']) ?></td>
                <td><?= htmlspecialchars($row['MOTIVO'] ?? 'Sin motivo') ?></td>
                <td><?= htmlspecialchars( ($row['OBSERVACIONES']) instanceof OCILob ? $row['OBSERVACIONES']->load() : $row['OBSERVACIONES']) ?></td>
                <td class="estado-<?= strtolower($row['ESTADO_PAPELETA']) ?>">
                    <?= ucfirst(strtolower($row['ESTADO_PAPELETA'])) ?>
                </td>
                <td>
                    <?php if (strtolower($row['ESTADO_PAPELETA']) === 'pendiente'): ?>
                        <form method="GET" action="editar_papeleta.php" style="display:inline;">
                            <input type="hidden" name="id_papeleta" value="<?= $row['ID_PAPELETA'] ?>">
                            <button class="btn btn-warning" type="submit">Editar</button>
                        </form>
                        <form method="POST" action="solicitar_papeleta.php" style="display:inline;">
                            <input type="hidden" name="id_papeleta" value="<?= $row['ID_PAPELETA'] ?>">
                            <button class="btn btn-danger" name="eliminar" onclick="return confirm('¿Estás seguro de cancelar esta papeleta?')">Cancelar</button>
                        </form>
                    <?php else: ?>
                        <span class="no-disponible">No disponible</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align: center; margin-top: 20px;">
        <a href="dashboard.php" style="color: #3498db; font-weight: bold;">← Volver al inicio</a>
    </div>
</div>
</body>
</html>
