<?php
include("includes/conexion.php");
// evita que el navegaador interprete como HTML la respuesta
header('Content-Type: application/json');

try {

    $id          = intval($_POST['id']);
    $nombre      = $_POST['nombre'];
    $fechaInicio = $_POST['fechaInicio'];
    $fechaFin    = $_POST['fechaFin'];

 // Validación fechas incorrectas
    if ($fechaFin <= $fechaInicio) {
        echo json_encode(['success' => false, 'error' => 'fechas']);
        exit();
    }
// Validar nombre repetido
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
        fechaFin    = '$fechaFin'
        WHERE id = '$id'";

    mysqli_query($conexion, $sql_periodo);

    echo json_encode(['success' => true]);

} catch (mysqli_sql_exception $e) {

    $error = $e->getMessage();

    if (strpos($error, 'choca con fechas') !== false || strpos($error, 'traslapar') !== false) {
        echo json_encode(['success' => false, 'error' => 'traslape']);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'sql',
            'detalle' => $error
        ]);
    }
}