<?php
# Recibe el ID de un docente, invierte su estado en la tabla usuarios (activo/inactivo)
# y devuelve el nuevo estado en JSON para actualizar el botón sin recargar la página.

include("includes/conexion.php");

$id = $_POST['id'];

$sql = "UPDATE usuarios SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'";
mysqli_query($conexion, $sql);

$res = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>