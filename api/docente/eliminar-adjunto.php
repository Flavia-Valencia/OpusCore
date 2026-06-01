<?php
// Elimina un archivo o enlace adjunto de una sesión de clase.
// Primero verifica si es un archivo físico para borrarlo del servidor,
// luego elimina el registro correspondiente de la base de datos.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../../includes/conexion.php';

$idArchivo = (int)($_POST['id'] ?? 0);

if (!$idArchivo) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit();
}

$stmt = $conexion->prepare("SELECT rutaArchivo, tipo FROM sesionArchivos WHERE id = ?");
$stmt->bind_param('i', $idArchivo);
$stmt->execute();
$result = $stmt->get_result();
$archivo = $result->fetch_assoc();
$stmt->close();

if (!$archivo) {
    echo json_encode(['ok' => false, 'msg' => 'Archivo no encontrado']);
    exit();
}

if ($archivo['tipo'] === 'Archivo' && !empty($archivo['rutaArchivo'])) {
    $rutaFisica = __DIR__ . '/' . $archivo['rutaArchivo'];
    if (file_exists($rutaFisica)) {
        unlink($rutaFisica);
    }
}

$stmt = $conexion->prepare("DELETE FROM sesionArchivos WHERE id = ?");
$stmt->bind_param('i', $idArchivo);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true]);