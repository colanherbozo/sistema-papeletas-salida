<?php
session_start();
include("includes/db.php"); 

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

$usuarioId = $_SESSION['id_usuario']; // Asegúrate que esto esté seteado al iniciar sesión

$stmt = oci_parse($conn, "BEGIN DBMS_SESSION.SET_IDENTIFIER(:id); END;");
oci_bind_by_name($stmt, ":id", $usuarioId);
oci_execute($stmt);
oci_free_statement($stmt);

// Eliminar usuario si se envió la solicitud
if (isset($_POST['eliminar_id'])) {
    $id_usuario = $_POST['eliminar_id'];

    $verifica = oci_parse($conn, "SELECT COUNT(*) AS TOTAL FROM papeletas_salida WHERE id_usuario = :id_usuario");
    oci_bind_by_name($verifica, ":id_usuario", $id_usuario);
    oci_execute($verifica);
    $row = oci_fetch_assoc($verifica);
    oci_free_statement($verifica);

    if ($row['TOTAL'] > 0) {
        echo "<script>alert('❌ No se puede eliminar: el usuario tiene papeletas asociadas.');</script>";
    } else {
        $sql_delete = "BEGIN eliminar_usuario(:id_usuario); END;";
        $stmt_delete = oci_parse($conn, $sql_delete);
        oci_bind_by_name($stmt_delete, ":id_usuario", $id_usuario);
        oci_execute($stmt_delete);
        oci_free_statement($stmt_delete);
    }
}

// Insertar nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    $dni = strtoupper(trim($_POST['dni']));
    $nombre = strtoupper(trim($_POST['nombre_completo']));
    $correo = strtolower(trim($_POST['correo']));
    $tipo = strtolower(trim($_POST['tipo_usuario']));
    $cargo = strtoupper(trim($_POST['cargo']));
    $estado = strtolower(trim($_POST['estado']));

    $sql_insert = "BEGIN insertar_usuario(:dni, :nombre, :correo, :tipo, :cargo, :estado); END;";
    $stmt = oci_parse($conn, $sql_insert);

    oci_bind_by_name($stmt, ":dni", $dni);
    oci_bind_by_name($stmt, ":nombre", $nombre);
    oci_bind_by_name($stmt, ":correo", $correo);
    oci_bind_by_name($stmt, ":tipo", $tipo);
    oci_bind_by_name($stmt, ":cargo", $cargo);
    oci_bind_by_name($stmt, ":estado", $estado);

    oci_execute($stmt);
    oci_free_statement($stmt);
}

// Consultar usuarios
$sql_select = "SELECT * FROM usuarios ORDER BY id_usuario DESC";
$stmt_select = oci_parse($conn, $sql_select);
oci_execute($stmt_select);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
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
        h2 { text-align: center; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        table th { background-color: #3498db; color: white; }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        input, select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 6px 12px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #27ae60; }
        .btn-funciones {
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        .btn-editar { background-color: #3498db; }
        .btn-editar:hover { background-color: #2980b9; }
        .btn-eliminar { background-color: #e74c3c; }
        .btn-eliminar:hover { background-color: #c0392b; }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Gestión de Usuarios</h2>

    <form method="POST">
        <input type="text" name="dni" placeholder="DNI" required>
        <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>
        <input type="email" name="correo" placeholder="Correo Electrónico" required>
        <input type="text" name="cargo" placeholder="Cargo (Ej: Estudiante, Profesor, Autorizador)" required>

        <select name="tipo_usuario" required>
            <option value="">Seleccione Rol</option>
            <option value="usuario">Usuario</option>
            <option value="autorizador">Autorizador</option>
            <option value="administrador">Administrador</option>
        </select>

        <select name="estado" required>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>

        <button type="submit" name="agregar">Agregar Usuario</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>DNI</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Cargo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Funciones</th>
        </tr>
        <?php while ($row = oci_fetch_assoc($stmt_select)): ?>
            <tr>
                <td><?= $row['ID_USUARIO'] ?></td>
                <td><?= htmlspecialchars($row['DNI']) ?></td>
                <td><?= htmlspecialchars($row['NOMBRE_COMPLETO']) ?></td>
                <td><?= htmlspecialchars($row['CORREO']) ?></td>
                <td><?= htmlspecialchars($row['CARGO']) ?></td>
                <td><?= ucfirst($row['TIPO_USUARIO']) ?></td>
                <td><?= ucfirst($row['ESTADO']) ?></td>
                <td class="btn-funciones">
                    <a href="editar_usuario.php?id=<?= $row['ID_USUARIO'] ?>">
                        <button type="button" class="btn-editar">✏️</button>
                    </a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="eliminar_id" value="<?= $row['ID_USUARIO'] ?>">
                        <button type="submit" class="btn-eliminar" onclick="return confirm('¿Eliminar usuario?')">🗑️</button>
                    </form>
                </td>
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
