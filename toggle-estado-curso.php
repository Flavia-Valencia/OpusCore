<?php
# Recibe el ID de un curso, invierte su estado en la tabla cursos (activo/inactivo)
# y devuelve el nuevo estado en JSON para actualizar el botón sin recargar la página.
# Si al activar el curso el docente ya tiene 4 cursos activos, devuelve error.
include("includes/conexion.php");

$id = intval($_POST['id']);

// Obtener estado actual y docente del curso
$res_actual = mysqli_query($conexion, "SELECT estado, idDocente FROM cursos WHERE id = '$id'");
$curso = mysqli_fetch_assoc($res_actual);

// Solo validar límite si el curso está inactivo y se va a activar
if ($curso['estado'] == 0) {
    $idDocente = $curso['idDocente'];

    // Contar cuántos cursos activos tiene ese docente (excluyendo el actual)
    $res_limite = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM cursos 
                                           WHERE idDocente = '$idDocente' 
                                           AND estado = 1 
                                           AND id != '$id'");
    $limite = mysqli_fetch_assoc($res_limite);

    if ($limite['total'] >= 4) {
        echo json_encode(['error' => 'limite_docente']);
        exit();
    }
}

// Invertir estado
mysqli_query($conexion, "UPDATE cursos SET estado = IF(estado = 1, 0, 1) WHERE id = '$id'");

$res = mysqli_query($conexion, "SELECT estado FROM cursos WHERE id = '$id'");
$fila = mysqli_fetch_assoc($res);

echo json_encode(['estado' => $fila['estado']]);
?>