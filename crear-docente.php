<?php
include("includes/conexion.php");

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$password = $_POST['password_hash'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$especialidad = $_POST['especialidad'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$genero = $_POST['genero'];
$salario = $_POST['salario'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$estado = 1;  // Los docentes nuevos se crean activos por defecto.

header('Content-Type: application/json');

if (!preg_match('/^[267][0-9]{3}-?[0-9]{4}$/', $telefono)) {
    echo json_encode(['error' => true, 'mensaje' => 'Número validado con un tipo de insertacion con número salvadoreño']);
    exit();
}

// Valida edad minima y evita fechas de nacimiento fuera del rango aceptado.
$fechaNac = new DateTime($fecha_nacimiento);
$hoy      = new DateTime();
$minima   = new DateTime('1950-01-01');
$edad     = $hoy->diff($fechaNac)->y;
if ($fechaNac < $minima || $edad < 18) {
    echo json_encode(['error' => true, 'mensaje' => 'Fecha de nacimiento inválida']);
    exit();
}

// Evita registrar correos duplicados en usuarios.
$check = "SELECT correo FROM usuarios WHERE correo = '$correo'";
$resultado = mysqli_query($conexion, $check);
if (mysqli_num_rows($resultado) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Ya existe un usuario con ese correo electrónico']);
    exit();
}

// Crea primero el usuario con rol docente.
$sql_usuario = "INSERT INTO usuarios
(nombre, apellido, correo, password_hash, estado, rol_id)
VALUES
('$nombre','$apellido','$correo','$hashed_password','$estado',3)"; // rol_id 3: docente

mysqli_query($conexion, $sql_usuario);
$usuario_id = mysqli_insert_id($conexion);

// Crea el perfil docente vinculado al usuario.
$sql_docente = "INSERT INTO docentes
(usuario_id, especialidad, fecha_nacimiento, genero, salario, telefono, direccion)
VALUES
('$usuario_id','$especialidad','$fecha_nacimiento','$genero','$salario','$telefono','$direccion')";

mysqli_query($conexion, $sql_docente);
echo json_encode(['success' => true]);
exit();
?>
