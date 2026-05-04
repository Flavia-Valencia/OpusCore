<?php

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

// Obtener id del estudiante desde el correo de sesión
$correo = $_SESSION["usuario"];
$stmt = $conexion->prepare("
    SELECT e.id FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$resultado = $stmt->get_result();
$estudiante = $resultado->fetch_assoc();
$idEstudiante = $estudiante['id'];

// Verificar si hay periodo activo
$periodoStmt = $conexion->query("SELECT * FROM PeriodoInscripcion WHERE estado = 1 LIMIT 1");
$periodo = $periodoStmt->fetch_assoc();

// Obtener cursos sin prerrequisito (alumno nuevo)
$cursos = [];
if ($periodo) {
    $stmt = $conexion->prepare("
        SELECT c.id, c.nombre, c.descripcion, c.costoMensual, c.cupos, c.fechaInicio, c.fechaFin
        FROM cursos c
        WHERE c.estado = 1
        AND c.cupos > 0
        AND NOT EXISTS (
            SELECT 1 FROM prerrequisitos pr
            WHERE pr.idCursoActual = c.id
        )
        ORDER BY c.nombre ASC
    ");
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cursos = $resultado->fetch_all(MYSQLI_ASSOC);
}
?>