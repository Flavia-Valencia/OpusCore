<?php
// Edita un plazo de notas existente y responde el resultado en JSON.
// Mantiene las fechas dentro del ciclo del periodo y evita duplicados.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sin sesión activa.']);
    exit();
}

include('includes/conexion.php');

$id        = intval($_POST['id'] ?? 0);
$idPeriodo = intval($_POST['idPeriodo'] ?? 0);
$nombre    = trim($_POST['nombre'] ?? '');
$inicio    = trim($_POST['plazoInicio'] ?? '');
$fin       = trim($_POST['plazoFin'] ?? '');

if (!$id || !$idPeriodo || !$nombre || !$inicio || !$fin) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

if (strtotime($fin) < strtotime($inicio)) {
    echo json_encode(['success' => false, 'message' => 'Fechas inválidas.']);
    exit();
}

$stmt = mysqli_prepare($conexion, "
    SELECT fechaInicioCiclo, fechaFinCiclo
    FROM PeriodoInscripcion
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $idPeriodo);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $inicioCiclo, $finCiclo);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$inicioCiclo) {
    echo json_encode(['success' => false, 'message' => 'Periodo no válido.']);
    exit();
}

if ($inicio < $inicioCiclo || $fin > $finCiclo) {
    echo json_encode([
        'success' => false,
        'message' => 'El plazo debe estar dentro del ciclo del período.'
    ]);
    exit();
}

$inicioDT = new DateTime($inicio);
$finCicloDT = new DateTime($finCiclo);
$interval = $inicioDT->diff($finCicloDT);
if ($interval->invert == 0 && $interval->days > 30) {
    echo json_encode([
        'success' => false,
        'message' => 'El inicio del plazo no puede ser mayor a 30 días antes del fin del ciclo.'
    ]);
    exit();
}

$stmt = mysqli_prepare($conexion, "
    SELECT id FROM PlazoNotas
    WHERE nombre = ? AND id != ?
");
mysqli_stmt_bind_param($stmt, "si", $nombre, $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya existe otro plazo con ese nombre.']);
    exit();
}

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "
    SELECT id FROM PlazoNotas
    WHERE idPeriodo = ? AND id != ?
");
mysqli_stmt_bind_param($stmt, "ii", $idPeriodo, $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    echo json_encode(['success' => false, 'message' => 'Este período ya tiene otro plazo.']);
    exit();
}

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "
    UPDATE PlazoNotas
    SET idPeriodo = ?, nombre = ?, plazoInicio = ?, plazoFin = ?
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt, "isssi", $idPeriodo, $nombre, $inicio, $fin, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Plazo actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
