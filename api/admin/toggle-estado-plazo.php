<?php
// Este archivo gestiona el toggle de estado de un plazo de notas.
// Valida que el ID recibido sea válido y que el plazo exista en la base de datos.
// Si se intenta activar, verifica que no haya otro plazo ya activo.
// Cambia el estado entre activo e inactivo y responde en formato JSON.
session_start();
header('Content-Type: application/json');

include("../../includes/conexion.php");

$id = intval($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => true, 'mensaje' => 'ID inválido']);
    exit();
}

$stmt = mysqli_prepare($conexion, "SELECT estado FROM PlazoNotas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $estadoActual);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($estadoActual === null) {
    echo json_encode(['error' => true, 'mensaje' => 'No existe']);
    exit();
}

if ($estadoActual == 0) {

    $check = mysqli_query($conexion, "SELECT id FROM PlazoNotas WHERE estado = 1 LIMIT 1");

    if (mysqli_num_rows($check) > 0) {

        echo json_encode([
            'error' => true,
            'mensaje' => 'Ya existe un plazo activo. Debes desactivarlo primero.'
        ]);
        exit();
    }

    mysqli_query($conexion, "UPDATE PlazoNotas SET estado = 1 WHERE id = $id");

    echo json_encode([
        'success' => true,
        'estado' => 1
    ]);

} else {

    mysqli_query($conexion, "UPDATE PlazoNotas SET estado = 0 WHERE id = $id");

    echo json_encode([
        'success' => true,
        'estado' => 0
    ]);
}

mysqli_close($conexion);
?>