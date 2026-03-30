<?php
session_start();
include("includes/db.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$motivos = [];
$id_usuario = $_SESSION['id_usuario'];

// Verificar que id_papeleta venga por GET
if (!isset($_GET['id_papeleta'])) {
    die("❌ ID de papeleta no especificado.");
}

$id_papeleta = $_GET['id_papeleta'];

// Obtener datos de la papeleta
$sql = "SELECT 
            id_papeleta,
            id_motivo, 
            TO_CHAR(fecha_solicitud, 'YYYY-MM-DD') AS fecha_solicitud, 
            hora_salida, 
            hora_retorno, 
            observaciones 
        FROM papeletas_salida 
        WHERE id_papeleta = :id_papeleta";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id_papeleta", $id_papeleta);
oci_execute($stmt);
$papeleta = oci_fetch_array($stmt, OCI_ASSOC);
oci_free_statement($stmt);

if (!$papeleta) {
    die("❌ No se encontró la papeleta o ya no se puede editar.");
}

// Obtener motivos
$consultaMotivos = oci_parse($conn, "SELECT id_motivo, descripcion FROM tipos_motivo");
oci_execute($consultaMotivos);
while ($row = oci_fetch_array($consultaMotivos, OCI_ASSOC)) {
    $motivos[] = $row;
}
oci_free_statement($consultaMotivos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Papeleta</title>
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
    </style>
</head>
<body>
<div class="form-container">
    <h2>Editar Papeleta</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo strpos($mensaje, '❌') !== false ? 'error' : ''; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="solicitar_papeleta.php">
        <input type="hidden" name="id_papeleta" value="<?= $papeleta['ID_PAPELETA'] ?>">

        <label for="fecha">Fecha de Solicitud:</label>
        <input type="date" name="fecha" value="<?= date('Y-m-d', strtotime($papeleta['FECHA_SOLICITUD'])) ?>" required>

        <label for="hora_salida">Hora de Salida:</label>
        <input type="time" name="hora_salida" value="<?= $papeleta['HORA_SALIDA'] ?>" required>

        <label for="hora_retorno">Hora de Retorno:</label>
        <input type="time" name="hora_retorno" value="<?= $papeleta['HORA_RETORNO'] ?>" required>

        <label for="motivo">Motivo:</label>
        <select name="motivo" required>
            <option value="">-- Selecciona un motivo --</option>
            <?php foreach ($motivos as $motivo): ?>
                <option value="<?= $motivo['ID_MOTIVO'] ?>" <?= $motivo['ID_MOTIVO'] == $papeleta['ID_MOTIVO'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($motivo['DESCRIPCION']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="observaciones">Observaciones:</label>
        <?php
$observaciones = $papeleta['OBSERVACIONES'] ?? '';
if ($observaciones instanceof OCILob) {
    $observaciones = $observaciones->load();
}
?>
        <textarea name="observaciones" rows="4"><?= htmlspecialchars($observaciones) ?></textarea>

        <button type="submit" name="actualizar">Actualizar Papeleta</button>
    </form>
    <div style="text-align: center; margin-top: 30px;">
        <a href="historial_usuario.php" style="color: #3498db; text-decoration: none; font-weight: bold;">← Volver al historial</a>
    </div>
</div>
</body>
</html>