<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'autorizador') {
    header("Location: login.php");
    exit();
}

include("includes/db.php");

$id_autorizador = $_SESSION['id_usuario'];
$mensaje = "";

// Procesar aprobación o rechazo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $accion = $_POST['accion'];
    $comentario = trim($_POST['comentario']);

    if (empty($comentario)) {
        $mensaje = "❌ Debes ingresar un comentario para continuar.";
    } else {
        $estado = $accion == 'aprobar' ? 'Aprobado' : 'Rechazado';

        // ✅ Establecer CLIENT_IDENTIFIER para los triggers
        $setClient = oci_parse($conn, "BEGIN DBMS_SESSION.SET_IDENTIFIER(:id); END;");
        oci_bind_by_name($setClient, ":id", $id_autorizador);
        oci_execute($setClient);
        oci_free_statement($setClient);

        // Actualiza la papeleta
        $sqlUpdate = "UPDATE papeletas_salida SET estado_papeleta = :estado WHERE id_papeleta = :id";
        $stmtUpdate = oci_parse($conn, $sqlUpdate);
        oci_bind_by_name($stmtUpdate, ":estado", $estado);
        oci_bind_by_name($stmtUpdate, ":id", $id);
        oci_execute($stmtUpdate);
        oci_free_statement($stmtUpdate);

        // Llama al procedimiento para registrar historial
        $sqlHist = "BEGIN registrar_historial(
                        :p_id_papeleta,
                        :p_id_usuario_autorizador,
                        SYSDATE,
                        :p_estado_final,
                        :p_comentarios
                    ); END;";
        $stmtHist = oci_parse($conn, $sqlHist);
        oci_bind_by_name($stmtHist, ":p_id_papeleta", $id);
        oci_bind_by_name($stmtHist, ":p_id_usuario_autorizador", $id_autorizador);
        oci_bind_by_name($stmtHist, ":p_estado_final", $estado);
        oci_bind_by_name($stmtHist, ":p_comentarios", $comentario);

        if (oci_execute($stmtHist)) {
            $mensaje = "✅ Solicitud procesada con éxito.";
        } else {
            $e = oci_error($stmtHist);
            $mensaje = "❌ Error: " . $e['message'];
        }

        oci_free_statement($stmtHist);
    }
}

// Obtener papeletas pendientes
$sql = "SELECT ps.id_papeleta, u.nombre_completo, ps.fecha_solicitud,
               ps.hora_salida, ps.hora_retorno, tm.descripcion AS motivo,
        ps.observaciones FROM papeletas_salida ps
        INNER JOIN usuarios u ON ps.id_usuario = u.id_usuario
        LEFT JOIN tipos_motivo tm ON ps.id_motivo = tm.id_motivo
        WHERE LOWER(ps.estado_papeleta) = 'pendiente'
        ORDER BY ps.fecha_solicitud DESC";

$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobar Papeletas</title>
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
        .mensaje {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: green;
        }
        .error {
            color: red;
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
        textarea {
            width: 90%;
            height: 50px;
            resize: vertical;
        }
        form {
            display: inline-block;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }
        .btn-aprobar {
            background-color: #2ecc71;
        }
        .btn-rechazar {
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Papeletas Pendientes</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '❌') !== false ? 'error' : '' ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Usuario</th>
            <th>Fecha</th>
            <th>Salida</th>
            <th>Retorno</th>
            <th>Motivo</th>
            <th>Observaciones</th>
            <th>Comentario</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = oci_fetch_assoc($stmt)): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $row['ID_PAPELETA'] ?>">
                    <td><?= htmlspecialchars($row['NOMBRE_COMPLETO']) ?></td>
                    <td><?= htmlspecialchars($row['FECHA_SOLICITUD']) ?></td>
                    <td><?= htmlspecialchars($row['HORA_SALIDA']) ?></td>
                    <td><?= htmlspecialchars($row['HORA_RETORNO']) ?></td>
                    <td><?= htmlspecialchars($row['MOTIVO'] ?? 'Sin motivo') ?></td>
                    <td><?= htmlspecialchars(($row['OBSERVACIONES']) instanceof OCILob ? $row['OBSERVACIONES']->load() : $row['OBSERVACIONES']) ?></td>
                    <td>
                        <textarea name="comentario" required placeholder="Comentario..."></textarea>
                    </td>
                    <td>
                        <button type="submit" name="accion" value="aprobar" class="btn btn-aprobar">Aprobar</button>
                        <button type="submit" name="accion" value="rechazar" class="btn btn-rechazar">Rechazar</button>
                    </td>
                </form>
            </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align:center; margin-top: 30px;">
        <a href="dashboard.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
            ← Volver al inicio
        </a>
    </div>
</div>
</body>
</html>
