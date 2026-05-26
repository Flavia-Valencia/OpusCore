<?php
// maneja la logica de calificar una entrega de tarea por parte del docente
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin sesión activa']);
    exit();
}

require_once 'includes/conexion.php';

$idEntrega = filter_input(INPUT_POST, 'idEntrega', FILTER_VALIDATE_INT);
$nota      = filter_input(INPUT_POST, 'nota',      FILTER_VALIDATE_FLOAT);

if (!$idEntrega || $nota === false || $nota === null) {
    echo json_encode(['error' => true, 'mensaje' => 'Datos incompletos para calificar']);
    exit();
}

// Obtener la entrega con su tarea y verificar permiso del docente
$stmt = $conexion->prepare("
    SELECT 
        et.id,
        et.estado,
        et.nota AS notaActual,
        t.puntajeMaximo,
        t.fechaLimite,
        t.idCurso
    FROM entregablesTarea et
    INNER JOIN tareas t ON et.idTarea = t.id
    INNER JOIN cursos c ON t.idCurso = c.id
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE et.id = ? AND u.correo = ?
    LIMIT 1
");
$stmt->bind_param('is', $idEntrega, $_SESSION['usuario']);
$stmt->execute();
$entrega = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$entrega) {
    echo json_encode(['error' => true, 'mensaje' => 'Entrega no encontrada o sin permiso']);
    exit();
}

// Validar que el estado sea Entregado (solo se califica lo que fue entregado)
if ($entrega['estado'] !== 'Entregado' && $entrega['estado'] !== 'Revisado') {
    echo json_encode(['error' => true, 'mensaje' => 'Solo se pueden calificar entregas con estado Entregado']);
    exit();
}

// Validar rango de nota
if ($nota < 0 || $nota > $entrega['puntajeMaximo']) {
    echo json_encode([
        'error'   => true,
        'mensaje' => "La nota debe estar entre 0 y {$entrega['puntajeMaximo']}"
    ]);
    exit();
}


$stmtUpd = $conexion->prepare("
    UPDATE entregablesTarea
    SET nota = ?
    WHERE id = ?
");
$stmtUpd->bind_param('di', $nota, $idEntrega);

if (!$stmtUpd->execute()) {

    echo json_encode(['error' => true, 'mensaje' => 'Error al guardar: ' . $conexion->error]);
    exit();
}
$stmtUpd->close();

echo json_encode([
    'error'   => false,
    'mensaje' => 'Calificación registrada correctamente',
    'nota'    => $nota,
    'estado'  => 'Revisado'
]);
?>