<?php
include("includes/conexion.php");

$nombre      = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$costoMensual = $_POST['costoMensual'];
$cupos       = $_POST['cupos'];
$fechaInicio = $_POST['fechaInicio'];
$fechaFin    = $_POST['fechaFin'];
$idDocente   = $_POST['idDocente'];
$estado      = 1;

// Verifica si ya existe un curso con ese nombre
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre')";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);

if (mysqli_num_rows($resultado_verificar) > 0) {
    header("Location: admin-cursos.php?error=existe");
    exit();
}

// Insertar curso
$sql_curso = "INSERT INTO cursos (nombre, descripcion, costoMensual, cupos, fechaInicio, fechaFin, estado, idDocente)
              VALUES ('$nombre', '$descripcion', '$costoMensual', '$cupos', '$fechaInicio', '$fechaFin', '$estado', '$idDocente')";

mysqli_query($conexion, $sql_curso);

// Obtener el ID del curso recién creado
$idCursoNuevo = mysqli_insert_id($conexion);

// Guardar prerrequisitos si se seleccionaron
if (!empty($_POST['prerrequisitos'])) {
    foreach ($_POST['prerrequisitos'] as $idCursoPrevio) {
        // Esto evita que el curso sea su propio prerequisito
        if ($idCursoPrevio == $idCursoNuevo){
            continue; 
        }
        $sql_pre = "INSERT INTO prerrequisitos (idCursoActual, idCursoPrevio) 
                    VALUES ('$idCursoNuevo', '$idCursoPrevio')";
        mysqli_query($conexion, $sql_pre);
    }
}

header("Location: admin-cursos.php");
exit();
?>