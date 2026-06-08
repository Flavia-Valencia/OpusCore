<?php
// Invierte el estado de un periodo de inscripcion y responde el nuevo valor en JSON.

include("includes/conexion.php");
$id = intval($_POST['id']);
// Alterna el estado directamente en la base de datos.
mysqli_query($conexion, "UPDATE PeriodoInscripcion SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'");
$res = mysqli_query($conexion, "SELECT estado FROM PeriodoInscripcion WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>
