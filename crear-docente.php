<?php
include("includes/conexion.php");

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

# Inserta usuario
$sql_usuario = "INSERT INTO usuarios
(nombre, apellido, correo, password_hash, estado, rol_id)
VALUES
('$nombre','$apellido','$correo','$password','Activo',3)";

mysqli_query($conexion, $sql_usuario);
$usuario_id = mysqli_insert_id($conexion);

# Inserta el docente
$sql_docente = "INSERT INTO docentes
(usuario_id, especialidad, fecha_nacimiento, genero, salario, telefono, direccion)
VALUES
('$usuario_id','$especialidad','$fecha_nacimiento','$genero','$salario','$telefono','$direccion')";

mysqli_query($conexion, $sql_docente);
header("Location: admin-docentes.php");
exit();
?>