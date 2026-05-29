<?php
# Recibe el ID de un estudiante y cambia su estado entre activo e inactivo.
# Verifica que no tenga inscripciones activas antes de deshabilitarlo.
# Devuelve el nuevo estado en formato JSON para actualizar la interfaz.
include("../includes/conexion.php");

$id = $_POST['id'];

$res_actual = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = $id");
$usuario = mysqli_fetch_assoc($res_actual);

if ($usuario['estado'] == 1) {
    $res_est = mysqli_query($conexion, "
        SELECT COUNT(*) as total FROM inscripciones i
        INNER JOIN estudiantes e ON i.idEstudiante = e.id
        WHERE e.usuario_id = $id
        AND i.estado_academico = 'Activo'
    ");
    $fila_est = mysqli_fetch_assoc($res_est);

    if ($fila_est['total'] > 0) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'El estudiante tiene cursos inscritos.'
        ]);
        exit();
    }
}

$sql = "UPDATE usuarios SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'";
mysqli_query($conexion, $sql);

$res = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>