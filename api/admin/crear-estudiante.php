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

header('Content-Type: application/json');

# valida correos duplicados
$check = "SELECT correo FROM usuarios WHERE correo = '$correo'";
$resultado = mysqli_query($conexion, $check);
if (mysqli_num_rows($resultado) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Ya existe un usuario con ese correo electrónico']);
    exit();
}
# Verifica que el año que ingresa sea validado con el año actual (mayor de 12 y mayor de 1940)
$fechaNac = new DateTime($fecha_nacimiento);
$hoy      = new DateTime();
$minima   = new DateTime('1940-01-01');
$edad     = $hoy->diff($fechaNac)->y;
if ($fechaNac < $minima || $edad < 12) {
    echo json_encode(['error' => true, 'mensaje' => 'Fecha de nacimiento inválida']);
    exit();
}

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

echo json_encode(['success' => true]);
exit();
?>
