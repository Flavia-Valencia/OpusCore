<?php
session_start();
include("includes/conexion.php");
$id = intval($_POST['id']);
$correo_sesion = mysqli_real_escape_string($conexion, $_SESSION['usuario']);
$res_sesion = mysqli_query($conexion, "SELECT id FROM usuarios WHERE correo = '$correo_sesion'");
$fila_sesion = mysqli_fetch_assoc($res_sesion);
$id_sesion = (int)$fila_sesion['id'];

if ($id === $id_sesion) {
    echo json_encode(['error' => true, 'mensaje' => 'No puedes desactivarte a ti mismo.']);
    exit;
}

mysqli_query($conexion, "UPDATE usuarios SET estado = IF(estado = 1, 0, 1) WHERE id = $id");
$res = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = $id");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['error' => false, 'estado' => $fila['estado']]);
?>