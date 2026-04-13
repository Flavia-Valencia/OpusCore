<?php
include("includes/conexion.php");

$id          = intval($_POST['id']);
$nombre      = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$costoMensual = $_POST['costoMensual'];
$fechaInicio = $_POST['fechaInicio'];
$fechaFin    = $_POST['fechaFin'];
$estado      = $_POST['estado'] == 'Activo' ? 1 : 0;

# Verificar que el nombre no lo use OTRO curso (distinto al que estamos editando)
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre') AND id != '$id'";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);

if(mysqli_num_rows($resultado_verificar) > 0){
    header("Location: admin-cursos.php?error=existe");
    exit();
}

# Actualizar curso
$sql = "UPDATE cursos SET
    nombre        = '$nombre',
    descripcion   = '$descripcion',
    costoMensual  = '$costoMensual',
    fechaInicio   = '$fechaInicio',
    fechaFin      = '$fechaFin',
    estado        = '$estado'
WHERE id = '$id'";

mysqli_query($conexion, $sql);

header("Location: admin-cursos.php");
exit();
?>