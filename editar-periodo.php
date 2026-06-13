<?php
include("includes/conexion.php");
// Responde siempre en JSON para que la interfaz procese el resultado.
header('Content-Type: application/json');

try {

    $id          = intval($_POST['id']);
    $nombre      = $_POST['nombre'];
    $fechaInicio = $_POST['fechaInicio'];
    $fechaFin    = $_POST['fechaFin'];
    $fechaInicioCiclo = isset($_POST['fechaInicioCiclo']) ? $_POST['fechaInicioCiclo'] : null;
    $fechaFinCiclo    = isset($_POST['fechaFinCiclo']) ? $_POST['fechaFinCiclo'] : null;

    // Valida que los rangos de inscripcion y ciclo sean coherentes.
    if ($fechaFin <= $fechaInicio || $fechaFinCiclo <= $fechaInicioCiclo) {
        echo json_encode(['success' => false, 'error' => 'fechas']);
        exit();
    }

    // Valida que la fecha de inicio del ciclo no sea de un año anterior al actual.
    $anioActual = intval(date('Y'));
    $anioInicioCiclo = intval(date('Y', strtotime($fechaInicioCiclo)));
    if ($anioInicioCiclo < $anioActual) {
        echo json_encode(['success' => false, 'error' => 'anio_anterior']);
        exit();
    }

    $dtInicio = new DateTime($fechaInicio);
    $dtFin = new DateTime($fechaFin);
    $dtInicioCiclo = new DateTime($fechaInicioCiclo);

    if ($dtInicio < $dtInicioCiclo) {
        echo json_encode(['success' => false, 'error' => 'inicio_inscripcion_antes_ciclo']);
        exit();
    }

    $diffFin = $dtInicioCiclo->diff($dtFin);
    if ($diffFin->invert == 0 && $diffFin->days > 30) {
        echo json_encode(['success' => false, 'error' => 'fin_inscripcion_excede_30_dias']);
        exit();
    }

     // Validar que la nueva fecha fin de ciclo no sea menor que la de sus cursos activos
    if ($fechaFinCiclo !== null) {
        $sql_cursos_activos = "SELECT COUNT(*) AS total FROM cursos WHERE idPeriodo = '$id' AND estado = 1 AND fechaFin > '$fechaFinCiclo'";
        $res_cursos_activos = mysqli_query($conexion, $sql_cursos_activos);
        $row_cursos_activos = mysqli_fetch_assoc($res_cursos_activos);
        if ($row_cursos_activos['total'] > 0) {
            echo json_encode(['success' => false, 'error' => 'fecha_fin_ciclo_curso']);
            exit();
        }
    }
    // Evita duplicar nombres de periodos, excluyendo el registro actual.
    $sql_verificar = "SELECT id FROM PeriodoInscripcion 
                      WHERE LOWER(nombre) = LOWER('$nombre') 
                      AND id != '$id'";

    $resultado_verificar = mysqli_query($conexion, $sql_verificar);

    if (mysqli_num_rows($resultado_verificar) > 0) {
        echo json_encode(['success' => false, 'error' => 'existe']);
        exit();
    }

    $sql_periodo = "UPDATE PeriodoInscripcion SET
        nombre      = '$nombre',
        fechaInicio = '$fechaInicio',
        fechaFin    = '$fechaFin',
        fechaInicioCiclo = '$fechaInicioCiclo',
        fechaFinCiclo = '$fechaFinCiclo'
        WHERE id = '$id'";

    mysqli_query($conexion, $sql_periodo);

    echo json_encode(['success' => true]);

} catch (mysqli_sql_exception $e) {

    $error = $e->getMessage();

    if (strpos($error, 'choca') !== false || strpos($error, 'traslapar') !== false) {
        echo json_encode(['success' => false, 'error' => 'traslape']);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'sql',
            'detalle' => $error
        ]);
    }
}
