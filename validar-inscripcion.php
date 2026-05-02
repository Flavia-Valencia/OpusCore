<?php
# Recibe el ID de un curso y verifica si se puede inscribir:
# El curso debe estar activo
# El período asignado al curso debe estar activo
include("includes/conexion.php");

$idCurso = intval($_POST['idCurso']);

$sql = "SELECT c.estado AS curso_estado, pi.estado AS periodo_estado
        FROM cursos c
        LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
        WHERE c.id = '$idCurso'";

$res  = mysqli_query($conexion, $sql);
$fila = mysqli_fetch_assoc($res);

if (!$fila) {
    echo json_encode(['puede' => false, 'mensaje' => 'Curso no encontrado']);
    exit();
}

if ($fila['curso_estado'] == 0) {
    echo json_encode(['puede' => false, 'mensaje' => 'El curso está inactivo']);
    exit();
}

if ($fila['periodo_estado'] == 0 || $fila['periodo_estado'] === null) {
    echo json_encode(['puede' => false, 'mensaje' => 'El período del curso no está activo']);
    exit();
}

echo json_encode(['puede' => true, 'mensaje' => 'Inscripción disponible']);
?>