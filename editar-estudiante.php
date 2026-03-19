<?php
include("includes/conexion.php");

$id = $_POST['usuario_id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estado = $_POST['estado'];

$sql_usuario = "UPDATE usuarios SET
nombre='$nombre',
apellido='$apellido',
correo='$correo',
password_hash='$password'
WHERE id='$id'";
mysqli_query($conexion, $sql_usuario);

$sql_estudiante= "UPDATE estudiantes SET
fecha_nacimiento='$fecha_nacimiento',
genero='$genero',
telefono='$telefono',
direccion='$direccion'
WHERE usuario_id='$id'";
mysqli_query($conexion, $sql_estudiante);

header("Location: admin-estudiantes.php");
exit();
?>