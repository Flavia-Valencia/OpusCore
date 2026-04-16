<?php
include("includes/conexion.php");

$usuario_id = $_POST['usuario_id'];
$estudiante_id = $_POST['estudiante_id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estadoTexto = $_POST['estado'];
$estado = ($estadoTexto === 'Activo') ? 1 : 0;    #envía el estado correctamente a la bd, cuando se modifique la bd, lo cambio

$sql_usuario = "UPDATE usuarios SET
nombre='$nombre',
apellido='$apellido',
estado= '$estado',
correo='$correo',
password_hash='$password'
WHERE id='$usuario_id'";
mysqli_query($conexion, $sql_usuario);

$sql_estudiante= "UPDATE estudiantes SET
fecha_nacimiento='$fecha_nacimiento',
genero='$genero',
telefono='$telefono',
direccion='$direccion'
WHERE usuario_id='$usuario_id'";
mysqli_query($conexion, $sql_estudiante);

header("Location: admin-estudiantes.php");
exit();
?>