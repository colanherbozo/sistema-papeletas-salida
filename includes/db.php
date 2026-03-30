<?php
$host = 'localhost:1521/SIS';
$user = 'proyecto';  
$password = 'hr1';

// 🔧 Conexión con codificación UTF-8
$conn = oci_connect($user, $password, $host, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Conexión fallida: " . $e['message']);
}
?>
 