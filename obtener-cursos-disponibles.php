<?php
// Verifica que el usuario haya iniciado sesión y evita el almacenamiento en caché
// para proteger el acceso a la información del sistema.
// Obtiene el estudiante asociado al correo guardado en sesión y valida
// si existe un período de inscripción activo.
// Finalmente consulta y muestra los cursos disponibles según el período,
// filtrando únicamente aquellos activos y cuyos prerrequisitos
// hayan sido completados por el estudiante.
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id, u.nombre, u.apellido FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$estudiante = $resultado->fetch_assoc();

if (!$estudiante) {
    header("Location: login.php");
    exit();
}

$idEstudiante = $estudiante['id'];
$estudianteNombreCompleto = trim($estudiante['nombre'] . ' ' . $estudiante['apellido']);

$periodoStmt = $conexion->query("SELECT * FROM PeriodoInscripcion WHERE estado = 1 
    AND CURDATE() BETWEEN fechaInicio AND fechaFin LIMIT 1");
$periodo = $periodoStmt->fetch_assoc();

$cursos = [];
if ($periodo) {
    // Muestra cursos del periodo activo que el estudiante aun no tiene inscritos
    // y cuyos prerrequisitos ya fueron completados.
    // El limite de 5 cursos se valida al confirmar en validar-inscripcion.php.
    // La consulta incluye docente, horario, aula y dias para evitar peticiones extra en el modal.
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.cupos, c.fechaInicio, c.fechaFin,
               CONCAT(u.nombre, ' ', u.apellido) AS docente_nombre,
               GROUP_CONCAT(DISTINCT h.etiqueta ORDER BY h.horaInicio SEPARATOR ', ') AS horarios_etiqueta,
               GROUP_CONCAT(DISTINCT a.aula ORDER BY a.id SEPARATOR ', ') AS aulas_nombre,
               GROUP_CONCAT(DISTINCT ch.dia ORDER BY ch.dia SEPARATOR ', ') AS dias_semana
        FROM cursos c
        LEFT JOIN docentes d ON c.idDocente = d.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN cursohorario ch ON ch.idCurso = c.id
        LEFT JOIN horarios h ON h.id = ch.idHorario
        LEFT JOIN aulas a ON a.id = ch.idAula
        WHERE c.estado = 1
        AND c.idPeriodo = ?
        AND NOT EXISTS (
            SELECT 1 FROM prerrequisitos pr
            WHERE pr.idCursoActual = c.id
            AND pr.idCursoPrevio NOT IN (
                SELECT i.idCurso FROM inscripciones i
                WHERE i.idEstudiante = ?
                AND i.estado_academico = 'Finalizado'
            )
        )
        AND c.id NOT IN(
        SELECT i.idCurso FROM inscripciones i
        WHERE i.idEstudiante = ?
        AND i.idPeriodo = ?
        AND i.estado_academico != 'Retirado'
        )
        GROUP BY c.id, c.nombre, c.descripcion, c.costoMensual, c.cupos, c.fechaInicio, c.fechaFin, u.nombre, u.apellido
        ORDER BY c.nombre ASC
    ");
    $stmt->bind_param("iiii", $periodo['id'], $idEstudiante, $idEstudiante, $periodo['id']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cursos = $resultado->fetch_all(MYSQLI_ASSOC);
}
?>
