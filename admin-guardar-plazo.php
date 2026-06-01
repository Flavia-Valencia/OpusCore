<?php
// Este archivo gestiona la creación de plazos de notas para un período académico.
// Valida la sesión activa, campos obligatorios y que el fin no sea menor al inicio.
// Verifica que el período seleccionado exista y que las fechas estén dentro de su ciclo.
// Comprueba que no exista otro plazo con el mismo nombre o para el mismo período.
// Inserta el nuevo plazo en PlazoNotas con estado inactivo por defecto y responde en formato JSON.
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

if (!$idPeriodo || !$nombre || !$inicio || !$fin) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

$inicioDT = new DateTime($inicio);
$finDT    = new DateTime($fin);

if ($finDT < $inicioDT) {
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

$inicioCicloDT = new DateTime($inicioCiclo);
$finCicloDT    = new DateTime($finCiclo);

if ($inicioDT < $inicioCicloDT || $finDT > $finCicloDT) {
    echo json_encode([
        'success' => false,
        'message' => 'El plazo no puede salir del ciclo del período.'
    ]);
    exit();
}

$stmt = mysqli_prepare($conexion, "
    SELECT id FROM PlazoNotas WHERE nombre = ?
");

mysqli_stmt_bind_param($stmt, "s", $nombre);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0 && $id == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Ya existe un plazo con ese nombre.'
    ]);
    exit();
}

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "
    SELECT id FROM PlazoNotas WHERE idPeriodo = ?
");

mysqli_stmt_bind_param($stmt, "i", $idPeriodo);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0 && $id == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Este período ya tiene un plazo registrado.'
    ]);
    exit();
}

mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "
    INSERT INTO PlazoNotas (idPeriodo, nombre, plazoInicio, plazoFin, estado)
    VALUES (?, ?, ?, ?, 0)
");

mysqli_stmt_bind_param($stmt, "isss", $idPeriodo, $nombre, $inicio, $fin);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Plazo creado correctamente.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . mysqli_error($conexion)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>