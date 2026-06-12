<?php
include("includes/conexion.php");
// Responde siempre en JSON para que la interfaz procese el resultado.
header('Content-Type: application/json');

try {

    $id = intval($_POST['id']);
    $nombre = $_POST['nombre'];
    $fechaInicio  = $_POST['fechaInicio'];
    $fechaFin     = $_POST['fechaFin'];
    $fechaInicioCiclo = isset($_POST['fechaInicioCiclo']) ? $_POST['fechaInicioCiclo'] : null;
    $fechaFinCiclo    = isset($_POST['fechaFinCiclo']) ? $_POST['fechaFinCiclo'] : null;
    $estado       = 1;

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

    // Evita registrar periodos con nombre duplicado.
    $sql_verificar = "SELECT id FROM PeriodoInscripcion WHERE LOWER(nombre) = LOWER('$nombre')";
    $resultado_verificar = mysqli_query($conexion, $sql_verificar);

    if (mysqli_num_rows($resultado_verificar) > 0) {
        echo json_encode(['success' => false, 'error' => 'existe']);
        exit();
    }

  
    $sql_periodo = "INSERT INTO PeriodoInscripcion (id, nombre, fechaInicio, fechaFin, fechaInicioCiclo, fechaFinCiclo, estado)
                    VALUES ('$id', '$nombre', '$fechaInicio', '$fechaFin', '$fechaInicioCiclo', '$fechaFinCiclo', '$estado')";

    mysqli_query($conexion, $sql_periodo);

    echo json_encode(['success' => true]);
// Maneja errores SQL, especialmente traslapes de fechas detectados por la BD.
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
