<?php
include("includes/conexion.php");

$id          = intval($_POST['id']);
$nombre      = $_POST['nombre'];
$fechaInicio = $_POST['fechaInicio'];
$fechaFin    = $_POST['fechaFin'];


// Verifica si existe otro periodo con el mismo nombre
$sql_verificar = "SELECT id FROM PeriodoInscripcion WHERE LOWER(nombre) = LOWER('$nombre') AND id != '$id'";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);
if (mysqli_num_rows($resultado_verificar) > 0) {
    echo json_encode(['success' => false, 'error' => 'existe']);
    exit();
}

$sql_periodo = "UPDATE PeriodoInscripcion SET
    nombre      = '$nombre',
    fechaInicio = '$fechaInicio',
    fechaFin    = '$fechaFin'
WHERE id = '$id'";

mysqli_query($conexion, $sql_periodo);
// Se utiliza json_encode para enviar una respuesta en formato json, por el boton del HTML
echo json_encode(['success' => true]);
?>