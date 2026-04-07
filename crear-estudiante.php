<?php
include("includes/conexion.php");

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estado = 1;  #envía el estado correctamente a la bd, cuando se modifique la bd, lo cambio



#Inserta el usuario
$sql_usuario = "INSERT INTO usuarios 
(nombre, apellido, correo, password_hash, estado, rol_id)
VALUES('$nombre', '$apellido', '$correo', '$password', '$estado', 2)"; //rol_id 2 para estudiantes con cambio de "Activo" a estado

mysqli_query($conexion, $sql_usuario);
$usuario_id = mysqli_insert_id($conexion);

#Inserta el estudiante

$sql_estudiante = "INSERT INTO estudiantes 
(usuario_id, fecha_nacimiento, genero, telefono, direccion)
VALUES('$usuario_id', '$fecha_nacimiento', '$genero', '$telefono', '$direccion')";

mysqli_query($conexion, $sql_estudiante);

header("Location: admin-estudiantes.php");
exit();
?>
