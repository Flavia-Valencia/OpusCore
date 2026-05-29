<?php
// Actualiza el estado de un contenido verificando sesión activa,
// recibe el ID y estado por POST, realiza la actualización
// en la base de datos y devuelve una respuesta JSON.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '../includes/conexion.php';

$id     = (int)($_POST['id'] ?? 0);
$estado = (int)($_POST['estado'] ?? 1);

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit();
}

$stmt = $conexion->prepare("UPDATE sesionContenido SET estado = ? WHERE id = ?");
$stmt->bind_param('ii', $estado, $id);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true]);