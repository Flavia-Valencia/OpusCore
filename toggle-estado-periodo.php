<?php
// Invierte el estado de un periodo de inscripcion y responde el nuevo valor en JSON.

include("includes/conexion.php");
$id = intval($_POST['id']);

$res_actual = mysqli_query($conexion, "SELECT estado FROM PeriodoInscripcion WHERE id = '$id'");
$periodo = mysqli_fetch_assoc($res_actual);

if ($periodo['estado'] == 1) {
    $res_cursos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM cursos WHERE idPeriodo = '$id' AND estado = 1");
    $row_cursos = mysqli_fetch_assoc($res_cursos);
    if ($row_cursos['total'] > 0) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'No se puede desactivar el período porque tiene cursos activos.'
        ]);
        exit();
    }
}

// Alterna el estado directamente en la base de datos.
mysqli_query($conexion, "UPDATE PeriodoInscripcion SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'");
$res = mysqli_query($conexion, "SELECT estado FROM PeriodoInscripcion WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>
