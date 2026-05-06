<?php
// Gestiona la consulta de cursos inscritos por el estudiante.
// Verifica sesión activa, obtiene datos del usuario
// y muestra cursos con estado académico activo.
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
    SELECT e.id 
    FROM estudiantes e
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

$stmt = $conexion->prepare("
    SELECT c.id, c.nombre, c.descripcion, c.costoMensual, 
           c.fechaInicio, c.fechaFin,
           i.estado_academico, i.fecha_registro
    FROM inscripciones i
    INNER JOIN cursos c ON i.idCurso = c.id
    WHERE i.idEstudiante = ?
    AND i.estado_academico = 'Activo'
    ORDER BY c.nombre ASC
");
$stmt->bind_param("i", $idEstudiante);
$stmt->execute();
$resultado = $stmt->get_result();
$cursos = $resultado->fetch_all(MYSQLI_ASSOC);

?>