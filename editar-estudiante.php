<?php
include("includes/conexion.php");

$id = $_POST['usuario_id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$telefono = $_POST['telefono'];
$correo = $_POST['correo'];
$contasena = $_POST['contrasena'];
$estado = $_POST['estado'];

mysqli_query($conexion, "UPDATE usuarios
SET nombre='$nombre',
apellido='$apellido',
correo= '$correo',
estado='$estado'
WHERE id='$id'");

mysqli_query($conexion, "UPDATE estudiantes
SET telefono='$telefono'
WHERE usuario_id='$id'");

header("Location: admin-estudiantes.php");
exit();