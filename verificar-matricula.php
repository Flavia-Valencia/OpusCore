<?php
// Indica si el estudiante ya pago matricula en el periodo activo.
// La interfaz usa esta respuesta para evitar cobrar matricula duplicada.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['yaPayoMatricula' => false]);
    exit;
}

require_once 'includes/conexion.php';

$correo = $_SESSION['usuario'];
$stmtEst = $conexion->prepare("
    SELECT e.id FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmtEst->bind_param('s', $correo);
$stmtEst->execute();
$estudiante = $stmtEst->get_result()->fetch_assoc();
$idEstudiante = $estudiante['id'];

$periodoRes = $conexion->query("
    SELECT id FROM PeriodoInscripcion
    WHERE estado = 1 AND CURDATE() BETWEEN fechaInicio AND fechaFin
    LIMIT 1
");
$periodo = $periodoRes->fetch_assoc();
$idPeriodo = $periodo['id'] ?? 0;

$stmtMat = $conexion->prepare("
    SELECT id FROM matricula 
    WHERE idEstudiante = ? AND idPeriodo = ? AND estado = 'Pagado'
");
$stmtMat->bind_param('ii', $idEstudiante, $idPeriodo);
$stmtMat->execute();
$yaPayoMatricula = $stmtMat->get_result()->num_rows > 0;

echo json_encode(['yaPayoMatricula' => $yaPayoMatricula]);
?>
