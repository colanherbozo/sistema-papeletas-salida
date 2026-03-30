<?php
session_start();
include("includes/db.php");

// Validar sesión activa y rol
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$motivos = [];
$id_usuario = $_SESSION['id_usuario'];

$stmt_id = oci_parse($conn, "BEGIN DBMS_SESSION.SET_IDENTIFIER(:id_usuario); END;");
oci_bind_by_name($stmt_id, ":id_usuario", $_SESSION['id_usuario']);
oci_execute($stmt_id);

// Obtener motivos
$consultaMotivos = oci_parse($conn, "SELECT id_motivo, descripcion FROM tipos_motivo");
oci_execute($consultaMotivos);
while ($row = oci_fetch_array($consultaMotivos, OCI_ASSOC)) {
    $motivos[] = $row;
}
oci_free_statement($consultaMotivos);

// Insertar
if (isset($_POST['enviar'])) {
    $id_motivo = $_POST['motivo'];
    $fecha_solicitud = $_POST['fecha'];
    $hora_salida = $_POST['hora_salida'];
    $hora_retorno = $_POST['hora_retorno'];
    $observaciones = trim($_POST['observaciones']) !== '' ? $_POST['observaciones'] : 'No hay observaciones';
    $creado_por = $_SESSION['usuario']; // NUEVO

    $sql = "BEGIN insertar_papeleta(
                :id_usuario,
                :id_motivo,
                TO_DATE(:fecha_solicitud, 'YYYY-MM-DD'),
                :hora_salida,
                :hora_retorno,
                :observaciones,
                :creado_por
            ); END;";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_usuario', $id_usuario);
    oci_bind_by_name($stmt, ':id_motivo', $id_motivo);
    oci_bind_by_name($stmt, ':fecha_solicitud', $fecha_solicitud);
    oci_bind_by_name($stmt, ':hora_salida', $hora_salida);
    oci_bind_by_name($stmt, ':hora_retorno', $hora_retorno);
    oci_bind_by_name($stmt, ':observaciones', $observaciones);
    oci_bind_by_name($stmt, ':creado_por', $creado_por); // NUEVO

    if (oci_execute($stmt)) {
        $mensaje = "✅ Papeleta solicitada exitosamente.";
    } else {
        $e = oci_error($stmt);
        $mensaje = "❌ Error al registrar: " . $e['message'];
    }
    oci_free_statement($stmt);
    
}

// Actualizar
if (isset($_POST['actualizar'])) {
    $id_papeleta = $_POST['id_papeleta'];
    $id_motivo = $_POST['motivo'];
    $fecha_solicitud = $_POST['fecha'];
    $hora_salida = $_POST['hora_salida'];
    $hora_retorno = $_POST['hora_retorno'];
    $observaciones = trim($_POST['observaciones']) !== '' ? $_POST['observaciones'] : 'No hay observaciones';

    $sql = "BEGIN actualizar_papeleta(
            :id_papeleta,
            :id_motivo,
            TO_DATE(:fecha_solicitud, 'YYYY-MM-DD'),
            :hora_salida,
            :hora_retorno,
            :observaciones,
            :modificado_por
        ); END;";
    $modificado_por = $_SESSION['usuario'];
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_papeleta', $id_papeleta);
    oci_bind_by_name($stmt, ':id_motivo', $id_motivo);
    oci_bind_by_name($stmt, ':fecha_solicitud', $fecha_solicitud);
    oci_bind_by_name($stmt, ':hora_salida', $hora_salida);
    oci_bind_by_name($stmt, ':hora_retorno', $hora_retorno);
    oci_bind_by_name($stmt, ':observaciones', $observaciones);
    oci_bind_by_name($stmt, ':modificado_por', $modificado_por);

    if (oci_execute($stmt)) {
        $mensaje = "✅ Papeleta actualizada.";
    } else {
        $e = oci_error($stmt);
        $mensaje = "❌ Error al actualizar: " . $e['message'];
    }
    oci_free_statement($stmt);
}

// Eliminar
if (isset($_POST['eliminar'])) {
    $id_papeleta = $_POST['id_papeleta'];

    $sql = "BEGIN cancelar_papeleta(:id_papeleta); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_papeleta', $id_papeleta);

    if (oci_execute($stmt)) {
        $mensaje = "✅ Papeleta cancelada.";
    } else {
        $e = oci_error($stmt);
        $mensaje = "❌ Error al cancelar: " . $e['message'];
    }

    oci_free_statement($stmt);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Papeleta</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f5;
            padding: 20px;
        }
        .form-container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 25px;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .mensaje {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: green;
        }
        .error {
            color: red;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        td form {
            display: inline;
        }
        td form button {
            padding: 5px 8px;
            font-size: 14px;
            margin: 2px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Solicitar Papeleta de Salida</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo strpos($mensaje, '❌') !== false ? 'error' : ''; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="fecha">Fecha de Solicitud:</label>
        <input type="date" name="fecha" required>

        <label for="hora_salida">Hora de Salida:</label>
        <input type="time" name="hora_salida" required>

        <label for="hora_retorno">Hora de Retorno:</label>
        <input type="time" name="hora_retorno" required>

        <label for="motivo">Motivo:</label>
        <select name="motivo" required>
            <option value="">-- Selecciona un motivo --</option>
            <?php foreach ($motivos as $motivo): ?>
                <option value="<?= $motivo['ID_MOTIVO'] ?>">
                    <?= htmlspecialchars($motivo['DESCRIPCION']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="observaciones">Observaciones:</label>
        <textarea name="observaciones" rows="4"></textarea>

        <button type="submit" name="enviar">Enviar Solicitud</button>
    </form>
    </table>

    <div style="text-align:center; margin-top: 30px;">
        <a href="dashboard.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
            ← Volver al inicio
        </a>
    </div>
</div>
</body>
</html>
