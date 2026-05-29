<?php
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-store");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../includes/conexion.php';

$body        = json_decode(file_get_contents('php://input'), true);
$cursoId      = isset($body['curso_id'])      ? (int)$body['curso_id']      : 0;
$estudianteId = isset($body['estudiante_id']) ? (int)$body['estudiante_id'] : 0;
$actividades  = isset($body['actividades'])   ? (float)$body['actividades'] : null;
$examenFinal  = isset($body['examen_final'])  ? (float)$body['examen_final']: null;
$plazoId      = isset($body['plazo_id'])      ? (int)$body['plazo_id']      : 0;

if (!$cursoId || !$estudianteId || $actividades === null || $examenFinal === null || !$plazoId) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}


$stmt = $conexion->prepare("
    SELECT c.id FROM cursos c
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE c.id = ? AND u.correo = ? AND c.estado = 1
    LIMIT 1
");
$stmt->bind_param('is', $cursoId, $_SESSION['usuario']);
$stmt->execute();
$cursoValido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cursoValido) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para registrar notas en este curso']);
    exit();
}

if ($actividades < 0 || $actividades > 10 || $examenFinal < 0 || $examenFinal > 10) {
    echo json_encode(['success' => false, 'message' => 'Las notas deben estar entre 0.00 y 10.00']);
    exit();
}

$stmt = $conexion->prepare("
    INSERT INTO RegistroNotas (idPlazo, idCurso, idEstudiante, actividades, examenFinal)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        actividades  = VALUES(actividades),
        examenFinal  = VALUES(examenFinal)
");
$stmt->bind_param('iiidd', $plazoId, $cursoId, $estudianteId, $actividades, $examenFinal);

try {
    $stmt->execute();
    $stmtGet = $conexion->prepare("
        SELECT notaFinal, estadoEstudiante 
        FROM RegistroNotas 
        WHERE idPlazo = ? AND idCurso = ? AND idEstudiante = ?
    ");
    $stmtGet->bind_param('iii', $plazoId, $cursoId, $estudianteId);
    $stmtGet->execute();
    $result = $stmtGet->get_result()->fetch_assoc();
    $stmtGet->close();

    echo json_encode([
        'success'    => true,
        'message'    => 'Nota registrada correctamente',
        'nota_final' => $result['notaFinal'],
        'estado'     => $result['estadoEstudiante']
    ]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conexion->close();
?>