<?php
// Permite eliminar adjuntos de tareas pertenecientes al docente autenticado.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión activa']);
    exit();
}

require_once 'includes/conexion.php';

$idArchivo = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$idArchivo) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de archivo requerido']);
    exit();
}

// Confirma que el adjunto pertenece a una tarea del docente autenticado.
$stmt = $conexion->prepare("
    SELECT ta.rutaArchivo, ta.tipo
    FROM tareasArchivos ta
    INNER JOIN tareas t ON ta.idTarea = t.id
    INNER JOIN cursos c ON t.idCurso = c.id
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE ta.id = ? AND u.correo = ?
    LIMIT 1
");
$stmt->bind_param('is', $idArchivo, $_SESSION['usuario']);
$stmt->execute();
$archivo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$archivo) {
    echo json_encode(['ok' => false, 'mensaje' => 'Archivo no encontrado o sin permiso']);
    exit();
}

// Elimina el archivo fisico solo cuando el adjunto no es un enlace.
if ($archivo['tipo'] === 'Archivo' && !empty($archivo['rutaArchivo']) && file_exists($archivo['rutaArchivo'])) {
    unlink($archivo['rutaArchivo']);
}

// Elimina el registro del adjunto en base de datos.
$stmtDel = $conexion->prepare("DELETE FROM tareasArchivos WHERE id = ?");
$stmtDel->bind_param('i', $idArchivo);
$stmtDel->execute();
$stmtDel->close();

echo json_encode(['ok' => true, 'mensaje' => 'Archivo eliminado correctamente']);
?>
