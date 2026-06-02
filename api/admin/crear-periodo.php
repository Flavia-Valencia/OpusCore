<?php
require_once __DIR__ . "/../../includes/conexion.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// evita que el navegaador interprete como HTML la respuesta
header('Content-Type: application/json');

try {

    $nombre = $_POST['nombre'];
    $fechaInicio  = $_POST['fechaInicio'];
    $fechaFin     = $_POST['fechaFin'];
    $fechaInicioCiclo = isset($_POST['fechaInicioCiclo']) ? $_POST['fechaInicioCiclo'] : null;
    $fechaFinCiclo    = isset($_POST['fechaFinCiclo']) ? $_POST['fechaFinCiclo'] : null;
    $estado       = 1;

    // Validación fechas incorrectas
    if ($fechaFin <= $fechaInicio || $fechaFinCiclo <= $fechaInicioCiclo) {
        echo json_encode(['success' => false, 'error' => 'fechas']);
        exit();
    }

    // Validar nombre repetido
    $sql_verificar = "SELECT id FROM PeriodoInscripcion WHERE LOWER(nombre) = LOWER('$nombre')";
    $resultado_verificar = mysqli_query($conexion, $sql_verificar);

    if (mysqli_num_rows($resultado_verificar) > 0) {
        echo json_encode(['success' => false, 'error' => 'existe']);
        exit();
    }

  
    $sql_periodo = "INSERT INTO PeriodoInscripcion (nombre, fechaInicio, fechaFin, fechaInicioCiclo, fechaFinCiclo, estado)
                    VALUES ('$nombre', '$fechaInicio', '$fechaFin', '$fechaInicioCiclo', '$fechaFinCiclo', '$estado')";

    mysqli_query($conexion, $sql_periodo);

    echo json_encode(['success' => true]);
// manejo de errores SQL, especialmente para detectar traslapes de fechas
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
