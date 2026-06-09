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
$idCategoria = intval($_POST['idCategoria']);
$estado       = 1;

// Valida que la fecha de fin sea posterior a la fecha de inicio.
    if ($fechaFin <= $fechaInicio) {
    header("Location: admin-cursos.php?error=fechas");
    exit();
}

// Obtener la fecha fin del ciclo/periodo
$fechaFinCiclo = null;
if ($idPeriodo > 0) {
    $sql_periodo_info = "SELECT fechaFinCiclo FROM PeriodoInscripcion WHERE id = '$idPeriodo'";
    $res_periodo_info = mysqli_query($conexion, $sql_periodo_info);
    if ($res_periodo_info && $row_periodo = mysqli_fetch_assoc($res_periodo_info)) {
        $fechaFinCiclo = $row_periodo['fechaFinCiclo'];
    }
}

// Validar que la fecha de fin del curso no sea mayor a la del ciclo
if ($fechaFinCiclo !== null && $fechaFin > $fechaFinCiclo) {
    echo json_encode(['success' => false, 'error' => 'fecha_fin_ciclo']);
    exit();
}
// Evita crear cursos con nombres duplicados.
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre')";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);
if (mysqli_num_rows($resultado_verificar) > 0) {
    header("Location: admin-cursos.php?error=existe");
    exit();
}

// Limita la asignacion a cuatro cursos activos por docente.
$sql_limite = "SELECT COUNT(*) AS total FROM cursos WHERE idDocente = '$idDocente' AND estado = 1";
$res_limite = mysqli_query($conexion, $sql_limite);
$row_limite = mysqli_fetch_assoc($res_limite);
if ($row_limite['total'] >= 4) {
    header("Location: admin-cursos.php?error=limite_docente");
    exit();
}


// Crea el curso con sus datos academicos y comerciales.
$sql_curso = "INSERT INTO cursos (nombre, descripcion, costoMensual, cupos, fechaInicio, fechaFin, estado, idDocente, idCategoria, idPeriodo)
              VALUES ('$nombre', '$descripcion', '$costoMensual', '$cupos', '$fechaInicio', '$fechaFin', '$estado', '$idDocente','$idCategoria', '$idPeriodo')";
mysqli_query($conexion, $sql_curso);

// Obtiene el ID generado para registrar dependencias relacionadas.
$idCursoNuevo = mysqli_insert_id($conexion);

// Registra el prerrequisito si se selecciono un curso previo valido.
$idPrerrequisito = isset($_POST['idPrerrequisitos']) ? intval($_POST['idPrerrequisitos']) : 0;
if ($idPrerrequisito > 0 && $idPrerrequisito != $idCursoNuevo) {
    $sql_pre = "INSERT INTO prerrequisitos (idCursoActual, idCursoPrevio) 
                VALUES ('$idCursoNuevo', '$idPrerrequisito')";
    mysqli_query($conexion, $sql_pre);
}

header("Location: admin-cursos.php");
exit();
?>
