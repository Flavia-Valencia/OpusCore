<?php
include("includes/conexion.php");

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$salario = $_POST['salario'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estado = 1;  #envía el estado correctamente a la bd, cuando se modifique la bd, lo cambio

header('Content-Type: application/json');

if (!preg_match('/^[267][0-9]{3}-?[0-9]{4}$/', $telefono)) {
    echo json_encode(['error' => true, 'mensaje' => 'Número validado con un tipo de insertacion con número salvadoreño']);
    exit();
}

# Verifica que el año que ingresa sea validado con el año actual (mayor de 18 y mayor de 1950)
$fechaNac = new DateTime($fecha_nacimiento);
$hoy      = new DateTime();
$minima   = new DateTime('1950-01-01');
$edad     = $hoy->diff($fechaNac)->y;
if ($fechaNac < $minima || $edad < 18) {
    echo json_encode(['error' => true, 'mensaje' => 'Fecha de nacimiento inválida']);
    exit();
}

# valida correos duplicados
$check = "SELECT correo FROM usuarios WHERE correo = '$correo'";
$resultado = mysqli_query($conexion, $check);
if (mysqli_num_rows($resultado) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Ya existe un usuario con ese correo electrónico']);
    exit();
}

# Inserta usuario
$sql_usuario = "INSERT INTO usuarios
(nombre, apellido, correo, password_hash, estado, rol_id)
VALUES
('$nombre','$apellido','$correo','$hashed_password','$estado',1)"; //rol_id 1 para administradores

mysqli_query($conexion, $sql_usuario);
$usuario_id = mysqli_insert_id($conexion);

# Inserta el administrador
$sql_administrador = "INSERT INTO administradores
(usuario_id, fecha_nacimiento, genero, salario, telefono, direccion)
VALUES
('$usuario_id','$fecha_nacimiento','$genero','$salario','$telefono','$direccion')";

mysqli_query($conexion, $sql_administrador);
echo json_encode(['success' => true]);
exit();
?>