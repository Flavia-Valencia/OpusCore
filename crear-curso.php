<?php
include("includes/conexion.php");

$nombre       = $_POST['nombre'];
$descripcion  = $_POST['descripcion'];
$costoMensual = $_POST['costoMensual'];
$cupos        = $_POST['cupos'];
$fechaInicio  = $_POST['fechaInicio'];
$fechaFin     = $_POST['fechaFin'];
$idDocente    = intval($_POST['idDocente']);
$idPeriodo = intval($_POST['idPeriodo']);
$estado       = 1;

// Verifica si ya existe un curso con ese nombre
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre')";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);
if (mysqli_num_rows($resultado_verificar) > 0) {
    header("Location: admin-cursos.php?error=existe");
    exit();
}

// Verifica que el docente no tenga ya 4 cursos activos
$sql_limite = "SELECT COUNT(*) AS total FROM cursos WHERE idDocente = '$idDocente' AND estado = 1";
$res_limite = mysqli_query($conexion, $sql_limite);
$row_limite = mysqli_fetch_assoc($res_limite);
if ($row_limite['total'] >= 4) {
    header("Location: admin-cursos.php?error=limite_docente");
    exit();
}


// Insertar curso
$sql_curso = "INSERT INTO cursos (nombre, descripcion, costoMensual, cupos, fechaInicio, fechaFin, estado, idDocente, idPeriodo)
              VALUES ('$nombre', '$descripcion', '$costoMensual', '$cupos', '$fechaInicio', '$fechaFin', '$estado', '$idDocente', '$idPeriodo')";
mysqli_query($conexion, $sql_curso);

// Obtener el ID del curso recién creado
$idCursoNuevo = mysqli_insert_id($conexion);

// guarda prerrequisito si se seleccionó alguno
$idPrerrequisito = isset($_POST['idPrerrequisitos']) ? intval($_POST['idPrerrequisitos']) : 0;
if ($idPrerrequisito > 0 && $idPrerrequisito != $idCursoNuevo) {
    $sql_pre = "INSERT INTO prerrequisitos (idCursoActual, idCursoPrevio) 
                VALUES ('$idCursoNuevo', '$idPrerrequisito')";
    mysqli_query($conexion, $sql_pre);
}

header("Location: admin-cursos.php");
exit();
?>