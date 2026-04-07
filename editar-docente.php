<?php
include('includes/conexion.php');  

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$especialidad = $_POST['especialidad'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$salario = $_POST['salario'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estadoTexto = $_POST['estado'];
$estado = ($estadoTexto === 'Activo') ? 1 : 0;    #envía el estado correctamente a la bd, cuando se modifique la bd, lo cambio

$sql_usuario = "UPDATE usuarios SET
nombre='$nombre',
apellido='$apellido',
correo='$correo',
estado='$estado',
password_hash='$password'
WHERE id='$id'";
mysqli_query($conexion, $sql_usuario);


$sql_docente = "UPDATE docentes SET
especialidad='$especialidad',
fecha_nacimiento='$fecha_nacimiento',
genero='$genero',
salario='$salario',
telefono='$telefono',
direccion='$direccion'
WHERE usuario_id='$id'";
mysqli_query($conexion, $sql_docente);

header("Location: admin-docentes.php");
exit();
?>