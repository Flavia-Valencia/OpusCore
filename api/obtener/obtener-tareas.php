<?php
// Obtiene la lista de tareas de un curso en particular, 
// con sus archivos adjuntos agrupados, 
// para mostrar en el panel del docente

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin sesión activa']);
    exit();
}

include("../../includes/conexion.php");

$idCurso = filter_input(INPUT_GET, 'idCurso', FILTER_VALIDATE_INT);

if (!$idCurso) {
    echo json_encode(['error' => true, 'mensaje' => 'ID de curso requerido']);
    exit();
}

$stmtVerif = $conexion->prepare("
    SELECT c.id FROM cursos c
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE c.id = ? AND u.correo = ? AND c.estado = 1
    LIMIT 1
");
$stmtVerif->bind_param('is', $idCurso, $_SESSION['usuario']);
$stmtVerif->execute();
if (!$stmtVerif->get_result()->fetch_assoc()) {
    echo json_encode(['error' => true, 'mensaje' => 'Acceso no autorizado']);
    exit();
}
$stmtVerif->close();

// Obtener tareas con archivos adjuntos agrupados
$stmt = $conexion->prepare("
    SELECT 
        t.id,
        t.idCurso,
        t.idSesion,
        sc.titulo AS sesionTitulo,
        t.titulo,
        t.descripcion,
        t.puntajeMaximo,
        t.fechaLimite,
        t.fechaCreacion,
        t.estado,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Archivo' THEN ta.nombreArchivo END
            ORDER BY ta.id SEPARATOR ', '
        ) AS archivos,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Archivo' THEN ta.rutaArchivo END
            ORDER BY ta.id SEPARATOR ','
        ) AS rutasArchivos,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Archivo' THEN ta.id END
            ORDER BY ta.id SEPARATOR ','
        ) AS idsArchivos,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Enlace' THEN ta.nombreArchivo END
            ORDER BY ta.id SEPARATOR ', '
        ) AS enlaces,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Enlace' THEN ta.rutaArchivo END
            ORDER BY ta.id SEPARATOR ','
        ) AS urlsEnlaces,
        GROUP_CONCAT(
            CASE WHEN ta.tipo = 'Enlace' THEN ta.id END
            ORDER BY ta.id SEPARATOR ','
        ) AS idsEnlaces
    FROM tareas t
    LEFT JOIN sesionContenido sc ON sc.id = t.idSesion
    LEFT JOIN tareasArchivos ta ON ta.idTarea = t.id
    WHERE t.idCurso = ?
    GROUP BY t.id
    ORDER BY t.fechaLimite ASC
");
$stmt->bind_param('i', $idCurso);
$stmt->execute();
$result = $stmt->get_result();

$tareas = [];
while ($fila = $result->fetch_assoc()) {
    $fila['estadoTexto'] = $fila['estado'] == 1 ? 'Activa' : 'Vencida';
    $tareas[] = $fila;
}
$stmt->close();

echo json_encode($tareas);
?>