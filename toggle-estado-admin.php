<?php
include("includes/conexion.php");
$id = $_POST['id'];
mysqli_query($conexion, "UPDATE usuarios SET estado = IF(estado = 1, 0, 1) WHERE id = $id");
$res = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = $id");
$fila = mysqli_fetch_assoc($res);
echo json_encode(['estado' => $fila['estado']]);
?>