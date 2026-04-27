<?php
# Recibe el ID de un curso, invierte su estado en la tabla cursos (activo/inactivo)
# y devuelve el nuevo estado en JSON para actualizar el botón sin recargar la página.
# Al desactivar, limpia el docente y los horarios para liberar el cupo.
include("includes/conexion.php");

$id = intval($_POST['id']);

// Obtener estado actual del curso
$res_actual = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = '$id'");
$curso = mysqli_fetch_assoc($res_actual);

// Invertir estado
mysqli_query($conexion, "UPDATE cursos SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'");

// Si se estaba desactivando, limpiar docente y horarios para liberar el cupo
if ($curso['estado'] == 1) {
    mysqli_query($conexion, "UPDATE cursos SET idDocente = NULL WHERE id = '$id'");
    mysqli_query($conexion, "DELETE FROM CursoHorario WHERE idCurso = '$id'");
}

$res = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>