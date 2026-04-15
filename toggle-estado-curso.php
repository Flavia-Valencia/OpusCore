<?php
# Recibe el ID de un curso, invierte su estado en la tabla cursos (activo/inactivo)
# y devuelve el nuevo estado en JSON para actualizar el botón sin recargar la página.
include("includes/conexion.php");

$id = $_POST['id'];

$sql = "UPDATE cursos SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'";
mysqli_query($conexion, $sql);

$res = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>