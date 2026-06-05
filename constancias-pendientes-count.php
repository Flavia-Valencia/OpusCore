<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['total' => 0]);
    exit();
}

require_once 'includes/conexion.php';

$result = $conexion->query("SELECT COUNT(*) AS total FROM solicitudConstanciaEstudiante WHERE estado = 'Pendiente'");
$row    = $result->fetch_assoc();

echo json_encode(['total' => (int)$row['total']]);