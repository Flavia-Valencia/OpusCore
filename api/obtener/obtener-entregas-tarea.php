<?php
// Obtiene las entregas de una tarea específica para mostrar en el panel del docente
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin sesión activa']);
    exit();
}

require_once 'includes/conexion.php';

$idTarea = filter_input(INPUT_GET, 'idTarea', FILTER_VALIDATE_INT);

if (!$idTarea) {
    echo json_encode(['error' => true, 'mensaje' => 'ID de tarea requerido']);
    exit();
}

// Verificar que la tarea pertenece a un curso del docente autenticado
$stmtVerif = $conexion->prepare("
    SELECT t.id, t.puntajeMaximo, t.titulo, t.fechaLimite
    FROM tareas t
    INNER JOIN cursos c ON t.idCurso = c.id
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE t.id = ? AND u.correo = ?
    LIMIT 1
");
$stmtVerif->bind_param('is', $idTarea, $_SESSION['usuario']);
$stmtVerif->execute();
$tarea = $stmtVerif->get_result()->fetch_assoc();
$stmtVerif->close();

if (!$tarea) {
    echo json_encode(['error' => true, 'mensaje' => 'Acceso no autorizado a esta tarea']);
    exit();
}

// Obtener todos los estudiantes inscritos en el curso con su estado de entrega
$stmt = $conexion->prepare("
    SELECT
        e.id AS idEstudiante,
        u.nombre,
        u.apellido,
        u.correo,
        et.id AS idEntrega,
        et.estado AS estadoEntrega,
        et.nota,
        et.fechaEntrega,
        et.fechaRevision,
        GROUP_CONCAT(
            CASE WHEN ea.tipo = 'Archivo' THEN ea.nombreArchivo END
            ORDER BY ea.id SEPARATOR ', '
        ) AS archivosEntrega,
        GROUP_CONCAT(
            CASE WHEN ea.tipo = 'Archivo' THEN ea.rutaArchivo END
            ORDER BY ea.id SEPARATOR ','
        ) AS rutasEntrega,
        GROUP_CONCAT(
            CASE WHEN ea.tipo = 'Enlace' THEN ea.nombreArchivo END
            ORDER BY ea.id SEPARATOR ', '
        ) AS enlacesNombre,
        GROUP_CONCAT(
            CASE WHEN ea.tipo = 'Enlace' THEN ea.rutaArchivo END
            ORDER BY ea.id SEPARATOR ','
        ) AS enlacesUrl
    FROM inscripciones i
    INNER JOIN estudiantes e ON i.idEstudiante = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN tareas t ON t.id = ?
    LEFT JOIN entregablesTarea et ON et.idTarea = ? AND et.idEstudiante = e.id
    LEFT JOIN entregaArchivos ea ON ea.idEntrega = et.id
    WHERE i.idCurso = (SELECT idCurso FROM tareas WHERE id = ?)
      AND i.estado_academico = 'Activo'
    GROUP BY e.id
    ORDER BY u.apellido, u.nombre
");
$stmt->bind_param('iii', $idTarea, $idTarea, $idTarea);
$stmt->execute();
$result = $stmt->get_result();

$entregas = [];
while ($fila = $result->fetch_assoc()) {
    if (!$fila['idEntrega']) {
        $fila['estadoEntrega'] = 'Pendiente';
    }
    $entregas[] = $fila;
}
$stmt->close();

echo json_encode([
    'tarea'    => $tarea,
    'entregas' => $entregas,
    'total'    => count($entregas)
]);
?>