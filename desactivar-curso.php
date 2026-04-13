<?php
include('includes/conexion.php');

$id = intval($_GET['id']);

$sql = "UPDATE cursos SET estado = 0 WHERE id = $id";
mysqli_query($conexion, $sql);

header("Location: admin-cursos.php");
exit();
?>