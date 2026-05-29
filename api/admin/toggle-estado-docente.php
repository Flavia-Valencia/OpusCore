<?php
# Recibe el ID de un docente y cambia su estado entre activo e inactivo.
# Verifica que no tenga cursos activos asignados antes de deshabilitarlo.
# Devuelve el nuevo estado en formato JSON para actualizar la interfaz.

include("../includes/conexion.php");

$id = $_POST['id'];

$res_actual = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = $id");
$usuario = mysqli_fetch_assoc($res_actual);

if ($usuario['estado'] == 1) {
    $res_cursos = mysqli_query($conexion, "
        SELECT COUNT(*) as total FROM cursos c
        INNER JOIN docentes d ON c.idDocente = d.id
        WHERE d.usuario_id = $id
        AND c.estado = 1
    ");
    $fila_cursos = mysqli_fetch_assoc($res_cursos);

    if ($fila_cursos['total'] > 0) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'El docente tiene cursos asignados.'
        ]);
        exit();
    }
}

mysqli_query($conexion, "UPDATE usuarios SET estado = IF(estado = 1, 0, 1) WHERE id = $id");

$res = mysqli_query($conexion, "SELECT estado FROM usuarios WHERE id = $id");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>