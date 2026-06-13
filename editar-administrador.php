<?php
include('includes/conexion.php');  

$usuario_id = $_POST['usuario_id'];
$administrador_id = $_POST['administrador_id'];
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = trim($_POST['password_hash']);
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$salario = $_POST['salario'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estadoTexto = $_POST['estado'];
$estado = ($estadoTexto === 'Activo') ? 1 : 0;    #envía el estado correctamente a la bd, cuando se modifique la bd, lo cambio

header('Content-Type: application/json');

if (!preg_match('/^[267][0-9]{3}-?[0-9]{4}$/', $telefono)) {
    echo json_encode(['error' => true, 'mensaje' => 'Número validado con un tipo de insertacion con número salvadoreño']);
    exit();
}
# validar correos duplicados excluyendo el del usuario seleccionado.
$check = "SELECT id FROM usuarios WHERE correo = '$correo' AND id != '$usuario_id'";
$resultado = mysqli_query($conexion, $check);
if (mysqli_num_rows($resultado) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Ya existe un usuario con ese correo electrónico']);
    exit();
}

# validar fecha nacimiento.
$fechaNac = new DateTime($fecha_nacimiento);
$hoy      = new DateTime();
$minima   = new DateTime('1950-01-01');
$edad     = $hoy->diff($fechaNac)->y;
if ($fechaNac < $minima || $edad < 18) {
    echo json_encode(['error' => true, 'mensaje' => 'Fecha de nacimiento inválida']);
    exit();
}

if ($password === '') {
    $sql_usuario = "UPDATE usuarios SET
    nombre='$nombre',
    apellido='$apellido',
    correo='$correo',
    estado='$estado'
    WHERE id='$usuario_id'";
} else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql_usuario = "UPDATE usuarios SET
    nombre='$nombre',
    apellido='$apellido',
    correo='$correo',
    estado='$estado',
    password_hash='$hashed_password'
    WHERE id='$usuario_id'";
}
mysqli_query($conexion, $sql_usuario);


$sql_administrador = "UPDATE administradores SET
fecha_nacimiento='$fecha_nacimiento',
genero='$genero',
salario='$salario',
telefono='$telefono',
direccion='$direccion'
WHERE usuario_id='$usuario_id'";
mysqli_query($conexion, $sql_administrador);
echo json_encode(['success' => true]);
exit();
?>