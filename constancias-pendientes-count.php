<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['total' => 0]);
    exit();
}

require_once 'includes/conexion.php';

$resEst = $conexion->query("SELECT COUNT(*) AS total FROM solicitudConstanciaEstudiante WHERE estado = 'Pendiente'");
$totalEst = $resEst ? (int)$resEst->fetch_assoc()['total'] : 0;

$resDoc = $conexion->query("SELECT COUNT(*) AS total FROM solicitudConstanciaDocente WHERE estado = 'Pendiente'");
$totalDoc = $resDoc ? (int)$resDoc->fetch_assoc()['total'] : 0;

echo json_encode(['total' => $totalEst + $totalDoc]);