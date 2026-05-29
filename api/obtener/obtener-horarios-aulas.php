<?php
include("includes/conexion.php");
$horarios = [];
$aulas = [];

$res = $conexion->query("SELECT id, etiqueta FROM horarios
 ORDER BY horaInicio");
while($row = $res->fetch_assoc()) $horarios[] = $row;

$res = $conexion->query("SELECT id, aula FROM aulas 
ORDER BY id");
while($row = $res->fetch_assoc()) $aulas[] = $row;

echo json_encode(["horarios" => $horarios, "aulas" => $aulas]);
?>