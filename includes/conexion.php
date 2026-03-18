<?php
$server = "127.0.0.1";
$user = "root";
$pass = "";
$db = "db_academiadigital";

$conexion = new mysqli($server, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

?>