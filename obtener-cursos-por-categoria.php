<?php
include("includes/conexion.php");
header('Content-Type: application/json');

$idCategoria = intval($_GET['idCategoria']);
$idCursoActual = intval($_GET['idCursoActual'] ?? 0); // Excluye el curso actual al elegir prerrequisitos.

$query = "SELECT id, nombre FROM cursos 
          WHERE estado = 1 
          AND idCategoria = '$idCategoria'
          AND id != '$idCursoActual'";

$result = mysqli_query($conexion, $query);
$cursos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cursos[] = $row;
}
echo json_encode($cursos);
?>
