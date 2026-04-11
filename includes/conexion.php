<!-- Aqui se establece la conexión con la base de datos -->
<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "db_academiadigital";

$conexion = new mysqli($server, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

?>