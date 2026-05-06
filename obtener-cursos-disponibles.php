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
   
$stmt = $conexion->prepare("
    SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.cupos, c.fechaInicio, c.fechaFin,
        GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' · ', h.etiqueta) ORDER BY h.horaInicio SEPARATOR ' / ') AS horarios,
        GROUP_CONCAT(DISTINCT a.aula SEPARATOR ', ') AS aulas
    FROM cursos c
    LEFT JOIN CursoHorario ch ON ch.idCurso = c.id
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
    AND c.id NOT IN (
        SELECT i.idCurso FROM inscripciones i
        WHERE i.idEstudiante = ?
        AND i.idPeriodo = ?
        AND i.estado_academico != 'Retirado'
    )
    GROUP BY c.id
    ORDER BY c.nombre ASC
");
    $stmt->bind_param("iiii", $periodo['id'], $idEstudiante, $idEstudiante, $periodo['id']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cursos = $resultado->fetch_all(MYSQLI_ASSOC);
}
?>