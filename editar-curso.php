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
$idPeriodo = !empty($_POST['idPeriodo']) ? intval($_POST['idPeriodo']) : NULL;
$idPeriodo_sql = ($idPeriodo !== NULL) ? "$idPeriodo" : "NULL"; // Guarda NULL cuando no se selecciona periodo para evitar error de FK.
$idCategoria  = intval($_POST['idCategoria']);
$estado       = $_POST['estado'] == 'Activo' ? 1 : 0;

// Valida que la fecha de fin sea posterior a la fecha de inicio.
    if ($fechaFin <= $fechaInicio) {
    echo json_encode(['error' => true, 'mensaje' => 'La fecha de fin no debe ser menor a la fecha de inicio.']);
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
      echo json_encode(['error' => true, 'mensaje' => 'La fecha de fin del curso no puede ser mayor a la fecha de fin del ciclo.']);
    exit();
}

// Evita que otro curso use el mismo nombre.
$sql_verificar = "SELECT id FROM cursos WHERE LOWER(nombre) = LOWER('$nombre') AND id != '$id'";
$resultado_verificar = mysqli_query($conexion, $sql_verificar);

if (mysqli_num_rows($resultado_verificar) > 0) {
    echo json_encode(['error' => true, 'mensaje' => 'El curso ya existe. Intenta con otro nombre.']);
    exit();
}

// Verifica el limite de cursos solo si se cambio el docente asignado.
$sql_docente_actual = "SELECT idDocente FROM cursos WHERE id = '$id'";
$res_docente_actual = mysqli_query($conexion, $sql_docente_actual);
$row_docente_actual = mysqli_fetch_assoc($res_docente_actual);

if ($row_docente_actual['idDocente'] != $idDocente) {
    $sql_limite = "SELECT COUNT(*) AS total FROM cursos 
                   WHERE idDocente = '$idDocente' AND estado = 1";
    $res_limite = mysqli_query($conexion, $sql_limite);
    $row_limite = mysqli_fetch_assoc($res_limite);
    if ($row_limite['total'] >= 4) {
        echo json_encode(['error' => true, 'mensaje' => 'El docente ya tiene el límite de cursos activos asignados.']);
        exit();
    }
}
try {
    // Actualiza los datos principales del curso.
    $sql = "UPDATE cursos SET
        nombre        = '$nombre',
        descripcion   = '$descripcion',
        costoMensual  = '$costoMensual',
        fechaInicio   = '$fechaInicio',
        fechaFin      = '$fechaFin',
        cupos         = '$cupos',
        estado        = '$estado',
        idDocente     = '$idDocente',
        idPeriodo = $idPeriodo_sql,
        idCategoria   = '$idCategoria'
    WHERE id = '$id'";
    mysqli_query($conexion, $sql);

    // Reemplaza el prerrequisito anterior por el seleccionado actualmente.
    mysqli_query($conexion, "DELETE FROM prerrequisitos WHERE idCursoActual = '$id'");
    $idPrerrequisito = isset($_POST['idPrerrequisitos']) ? intval($_POST['idPrerrequisitos']) : 0;
    if ($idPrerrequisito > 0 && $idPrerrequisito != $id) {
        $sql_pre = "INSERT INTO prerrequisitos (idCursoActual, idCursoPrevio)
                    VALUES ('$id', '$idPrerrequisito')";
        mysqli_query($conexion, $sql_pre);
    }

    echo json_encode(['error' => false, 'mensaje' => 'Curso actualizado exitosamente']);
    exit();

} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'fecha') !== false || strpos($msg, 'ciclo') !== false) {
        echo json_encode(['error' => true, 'mensaje' => 'La fecha de fin del curso no puede ser mayor a la fecha de fin del ciclo.']);
    } else {
        echo json_encode(['error' => true, 'mensaje' => 'Ocurrió un error al guardar el curso. Verifica los datos e intenta de nuevo.']);
    }
    exit();
}
?>
