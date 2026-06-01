<?php
// Permite al docente eliminar un archivo adjunto de una tarea

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión activa']);
    exit();
}

include("../../includes/conexion.php");

$idArchivo = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$idArchivo) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de archivo requerido']);
    exit();
}

// Verificar que el archivo pertenece a una tarea de un curso del docente autenticado
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

// Eliminar archivo físico solo si es tipo Archivo (no Enlace)
if ($archivo['tipo'] === 'Archivo' && !empty($archivo['rutaArchivo']) && file_exists($archivo['rutaArchivo'])) {
    unlink($archivo['rutaArchivo']);
}

// Eliminar registro de la BD
$stmtDel = $conexion->prepare("DELETE FROM tareasArchivos WHERE id = ?");
$stmtDel->bind_param('i', $idArchivo);
$stmtDel->execute();
$stmtDel->close();

echo json_encode(['ok' => true, 'mensaje' => 'Archivo eliminado correctamente']);
?>