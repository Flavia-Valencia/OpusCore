<?php
include("includes/conexion.php");

$sql = "SELECT id, nombre FROM PeriodoInscripcion WHERE estado = 1 ORDER BY id DESC";
$resultado = mysqli_query($conexion, $sql);

$periodos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $periodos[] = $fila;
}

echo json_encode($periodos);
?>