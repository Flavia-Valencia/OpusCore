<?php
include("includes/conexion.php");

$id           = intval($_POST['id']);
$nombre       = $_POST['nombre'];
$descripcion  = $_POST['descripcion'];
$costoMensual = $_POST['costoMensual'];
$fechaInicio  = $_POST['fechaInicio'];
$fechaFin     = $_POST['fechaFin'];
$cupos        = intval($_POST['cupos']);
$idDocente    = intval($_POST['idDocente']);
$estado       = $_POST['estado'] == 'Activo' ? 1 : 0;

# Verificar que el nombre no lo use OTRO curso (distinto al que estamos editando)
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre') AND id != '$id'";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);

if (mysqli_num_rows($resultado_verificar) > 0) {
    header("Location: admin-cursos.php?error=existe");
    exit();
}

// Verifica límite solo si el docente cambió
$sql_docente_actual = "SELECT idDocente FROM cursos WHERE id = '$id'";
$res_docente_actual = mysqli_query($conexion, $sql_docente_actual);
$row_docente_actual = mysqli_fetch_assoc($res_docente_actual);

if ($row_docente_actual['idDocente'] != $idDocente) {
    $sql_limite = "SELECT COUNT(*) AS total FROM cursos 
                   WHERE idDocente = '$idDocente' AND estado = 1";
    $res_limite = mysqli_query($conexion, $sql_limite);
    $row_limite = mysqli_fetch_assoc($res_limite);
    if ($row_limite['total'] >= 4) {
        header("Location: admin-cursos.php?error=limite_docente");
        exit();
    }
}
# Actualizar curso
$sql = "UPDATE cursos SET
    nombre        = '$nombre',
    descripcion   = '$descripcion',
    costoMensual  = '$costoMensual',
    fechaInicio   = '$fechaInicio',
    fechaFin      = '$fechaFin',
    cupos         = '$cupos',
    estado        = '$estado',
    idDocente     = '$idDocente'
WHERE id = '$id'";
mysqli_query($conexion, $sql);


// inserta nuevos
mysqli_query($conexion, "DELETE FROM prerrequisitos WHERE idCursoActual = '$id'");
$idPrerrequisito = isset($_POST['idPrerrequisitos']) ? intval($_POST['idPrerrequisitos']) : 0;
if ($idPrerrequisito > 0 && $idPrerrequisito != $id) {
    $sql_pre = "INSERT INTO prerrequisitos (idCursoActual, idCursoPrevio) 
                VALUES ('$id', '$idPrerrequisito')";
    mysqli_query($conexion, $sql_pre);
}

header("Location: admin-cursos.php");
exit();
?>