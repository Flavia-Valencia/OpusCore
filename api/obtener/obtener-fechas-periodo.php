<?php
// Este archivo obtiene las fechas sugeridas de un período académico para autocompletar el formulario de plazo.
// Recibe el ID del período por GET, consulta su fecha de inicio y fin de ciclo en PeriodoInscripcion.
// Calcula la fecha de inicio sugerida como 15 días antes del fin del ciclo.
// Responde en formato JSON con las fechas de inicio sugerido y fin del ciclo.
include("../../includes/conexion.php");
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$stmt = mysqli_prepare($conexion, "
    SELECT fechaInicioCiclo, fechaFinCiclo
    FROM PeriodoInscripcion
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en prepare']);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result($stmt, $inicioCiclo, $finCiclo);

$ok = mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);

if (!$ok || !$finCiclo) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontraron datos del periodo'
    ]);
    exit();
}

$fin = new DateTime($finCiclo);
$inicio = clone $fin;
$inicio->modify('-15 days');

echo json_encode([
    'success' => true,
    'inicio' => $inicio->format('Y-m-d'),
    'fin' => $finCiclo
]);

mysqli_close($conexion);
?>