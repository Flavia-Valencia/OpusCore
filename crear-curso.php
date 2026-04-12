<?php
include("includes/conexion.php");

$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$costoMensual = $_POST['costoMensual'];
$cupos = $_POST['cupos'];
$fechaInicio = $_POST['fechaInicio'];
$fechaFin = $_POST['fechaFin'];
$idDocente = $_POST['idDocente'];
$estado = 1;  # activo por defecto

# Valida que el curso creado no exista anteriormente
$sql_verificar = "Select id From cursos Where Lower(nombre)= Lower('$nombre')"; #Valida mayusuculas y minusculas
$resultado_verificar = mysqli_query($conexion, $sql_verificar);

if(mysqli_num_rows($resultado_verificar) > 0){
    header("Location: admin-cursos.php?error=existe");
    exit();
}

# Insertar curso
$sql_curso = "INSERT INTO cursos
(nombre, descripcion, costoMensual, cupos, fechaInicio, fechaFin, estado, idDocente)
VALUES
('$nombre','$descripcion','$costoMensual','$cupos','$fechaInicio','$fechaFin','$estado','$idDocente')";

mysqli_query($conexion, $sql_curso);

header("Location: admin-cursos.php");
exit();
?>