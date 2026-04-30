<?php
# Recibe el ID de un periodo, invierte su estado en la tabla PeriodoInscripcion (activo/inactivo)
# y devuelve el nuevo estado en JSON para actualizar el botón sin recargar la página.

include("includes/conexion.php");
$id = intval($_POST['id']);
// Invertir estado
mysqli_query($conexion, "UPDATE PeriodoInscripcion SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'");
$res = mysqli_query($conexion, "SELECT estado FROM PeriodoInscripcion WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>