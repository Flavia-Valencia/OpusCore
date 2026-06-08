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
$estado = ($estadoTexto === 'Activo') ? 1 : 0;    // Convierte el estado del formulario al valor numerico de la BD.

header('Content-Type: application/json');
// Evita correos duplicados, excluyendo el usuario que se esta editando.
$check = "SELECT id FROM usuarios WHERE correo = '$correo' AND id != '$usuario_id'";
$resultado = mysqli_query($conexion, $check);
if (mysqli_num_rows($resultado) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Ya existe un usuario con ese correo electrónico']);
    exit();
}

// Valida edad minima y rango de fecha de nacimiento.
$fechaNac = new DateTime($fecha_nacimiento);
$hoy      = new DateTime();
$minima   = new DateTime('1940-01-01');
$edad     = $hoy->diff($fechaNac)->y;
if ($fechaNac < $minima || $edad < 12) {
    echo json_encode(['error' => true, 'mensaje' => 'Fecha de nacimiento inválida']);
    exit();
}

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

echo json_encode(['success' => true]);
exit();
?>
