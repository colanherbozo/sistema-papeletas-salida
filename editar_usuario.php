<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

include("includes/db.php");

// Establecer CONTEXTOS para el trigger
$usuario_id = $_SESSION['id_usuario'];
$usuario_nombre = $_SESSION['usuario']; // Asegúrate que esto contenga el nombre completo

$stmt_ctx = oci_parse($conn, "BEGIN
    DBMS_SESSION.SET_IDENTIFIER(:id);
END;");
oci_bind_by_name($stmt_ctx, ":id", $usuario_id);
//oci_bind_by_name($stmt_ctx, ":nombre", $usuario_nombre);
oci_execute($stmt_ctx);
oci_free_statement($stmt_ctx);

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido.";
    exit();
}

$id_usuario = $_GET['id'];
$mensaje = "";

// Obtener datos del usuario
$sql_select = "SELECT * FROM usuarios WHERE id_usuario = :id";
$stmt = oci_parse($conn, $sql_select);
oci_bind_by_name($stmt, ":id", $id_usuario);
oci_execute($stmt);
$usuario = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit();
}

// Actualizar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = strtoupper(trim($_POST['dni']));
    $nombre = strtoupper(trim($_POST['nombre_completo']));
    $correo = strtolower(trim($_POST['correo']));
    $tipo = strtolower(trim($_POST['tipo_usuario']));
    $cargo = strtoupper(trim($_POST['cargo']));
    $estado = strtolower(trim($_POST['estado']));

    $sql_update = "BEGIN actualizar_usuario(
                        :id_usuario, :dni, :nombre, :correo,
                        :tipo_usuario, :cargo, :estado
                   ); END;";

    $stmt_update = oci_parse($conn, $sql_update);
    oci_bind_by_name($stmt_update, ":id_usuario", $id_usuario);
    oci_bind_by_name($stmt_update, ":dni", $dni);
    oci_bind_by_name($stmt_update, ":nombre", $nombre);
    oci_bind_by_name($stmt_update, ":correo", $correo);
    oci_bind_by_name($stmt_update, ":tipo_usuario", $tipo);
    oci_bind_by_name($stmt_update, ":cargo", $cargo);
    oci_bind_by_name($stmt_update, ":estado", $estado);

    if (oci_execute($stmt_update)) {
        $mensaje = "✅ Usuario actualizado correctamente.";
        // Recargar datos actualizados
        $stmt_reload = oci_parse($conn, $sql_select);
        oci_bind_by_name($stmt_reload, ":id", $id_usuario);
        oci_execute($stmt_reload);
        $usuario = oci_fetch_assoc($stmt_reload);
        oci_free_statement($stmt_reload);
    } else {
        $e = oci_error($stmt_update);
        $mensaje = "❌ Error al actualizar: " . $e['message'];
    }

    oci_free_statement($stmt_update);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="assets/styles.php">
    <style>
        .contenedor {
            max-width: 600px;
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

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input, select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        .mensaje {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            color: green;
        }

        .mensaje.error {
            color: red;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Editar Usuario</h2>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= strpos($mensaje, '❌') !== false ? 'error' : '' ?>">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="dni" value="<?= htmlspecialchars($usuario['DNI']) ?>" placeholder="DNI" required>
        <input type="text" name="nombre_completo" value="<?= htmlspecialchars($usuario['NOMBRE_COMPLETO']) ?>" placeholder="Nombre Completo" required>
        <input type="email" name="correo" value="<?= htmlspecialchars($usuario['CORREO']) ?>" placeholder="Correo" required>
        <input type="text" name="cargo" value="<?= htmlspecialchars($usuario['CARGO']) ?>" placeholder="Cargo" required>

        <select name="tipo_usuario" required>
            <option value="usuario" <?= $usuario['TIPO_USUARIO'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
            <option value="autorizador" <?= $usuario['TIPO_USUARIO'] === 'autorizador' ? 'selected' : '' ?>>Autorizador</option>
            <option value="administrador" <?= $usuario['TIPO_USUARIO'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <select name="estado" required>
            <option value="activo" <?= $usuario['ESTADO'] === 'activo' ? 'selected' : '' ?>>Activo</option>
            <option value="inactivo" <?= $usuario['ESTADO'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
        </select>

        <button type="submit">Actualizar Usuario</button>
    </form>

    <div style="text-align:center; margin-top: 30px;">
        <a href="usuarios.php" style="color: #3498db; text-decoration: none; font-weight: bold;">
            ← Volver a la gestión
        </a>
    </div>
</div>
</body>
</html>
