<?php
// Cambia el estado de un curso entre activo e inactivo.
// Antes de deshabilitarlo verifica que no tenga inscripciones activas.
// Al desactivar, libera docente y horarios, y responde el nuevo estado en JSON.
include("includes/conexion.php");

$id = intval($_POST['id']);

$res_actual = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = '$id'");
$curso = mysqli_fetch_assoc($res_actual);

if ($curso['estado'] == 1) {
    $res_insc = mysqli_query($conexion, "
        SELECT COUNT(*) as total FROM inscripciones
        WHERE idCurso = $id
        AND estado_academico = 'Activo'
    ");
    $fila_insc = mysqli_fetch_assoc($res_insc);

    if ($fila_insc['total'] > 0) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'El curso tiene estudiantes inscritos.'
        ]);
        exit();
    }

    mysqli_query($conexion, "UPDATE cursos SET idDocente = NULL WHERE id = $id");
    mysqli_query($conexion, "DELETE FROM CursoHorario WHERE idCurso = $id");
} else {
    // Al intentar activar (estado actual = 0)
    $sql_curso_periodo = "SELECT c.fechaFin, pi.estado AS estado_periodo, pi.fechaFinCiclo, pi.nombre AS nombre_periodo
                          FROM cursos c
                          LEFT JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
                          WHERE c.id = $id";
    $res_curso_periodo = mysqli_query($conexion, $sql_curso_periodo);
    if ($res_curso_periodo && $row = mysqli_fetch_assoc($res_curso_periodo)) {
        if ($row['estado_periodo'] !== null && $row['estado_periodo'] == 0) {
            echo json_encode([
                'error' => true,
                'mensaje' => 'No se puede activar el curso porque el período "' . $row['nombre_periodo'] . '" al que pertenece está inactivo.'
            ]);
            exit();
        }
        if ($row['fechaFinCiclo'] !== null && $row['fechaFin'] > $row['fechaFinCiclo']) {
            echo json_encode([
                'error' => true,
                'mensaje' => 'No se puede activar el curso porque su fecha de fin supera la fecha de fin del ciclo.'
            ]);
            exit();
        }
    }
}

mysqli_query($conexion, "UPDATE cursos SET estado = IF(estado = 1, 0, 1) WHERE id = $id");

$res = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = $id");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>
