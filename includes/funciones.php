<?php
function registrar_log($conn, $id_usuario, $accion) {
    $stmt = $conn->prepare("INSERT INTO logs (id_usuario, accion, fecha) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $id_usuario, $accion);
    $stmt->execute();
}
?>

