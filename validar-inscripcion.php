<?php
// Gestiona la inscripcion de estudiantes a cursos.
// Valida sesion, disponibilidad, duplicados, limite por periodo y prerrequisitos.
// Si todo es valido, registra la inscripcion y actualiza cupos dentro de una transaccion.
// Responde en JSON para que la interfaz muestre el resultado.
session_start();

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate");

// Valida que el estudiante tenga una sesion activa.
if (!isset($_SESSION["usuario"])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión expirada. Inicie sesión nuevamente.']);
    exit();
}

require_once 'includes/conexion.php';

$correo = $_SESSION["usuario"];
$idCurso = isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0;

if (!$idCurso) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos.']);
    exit();
}

// Obtiene el ID interno del estudiante autenticado.
$stmt = $conexion->prepare("
    SELECT e.id FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ? AND u.estado = 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$res = $stmt->get_result();
$estudiante = $res->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    echo json_encode(['success' => false, 'mensaje' => 'No se encontró tu cuenta de estudiante.']);
    exit();
}

$idEstudiante = $estudiante['id'];

// Verifica que el curso exista, este activo y tenga cupos disponibles.
$stmt = $conexion->prepare("
    SELECT id, cupos, idPeriodo FROM cursos
    WHERE id = ? AND estado = 1 AND cupos > 0
");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$res = $stmt->get_result();
$curso = $res->fetch_assoc();
$stmt->close();

if (!$curso) {
    echo json_encode(['success' => false, 'mensaje' => 'El curso no está disponible o ya no tiene cupos.']);
    exit();
}

$idPeriodo = $curso['idPeriodo'];

// Evita inscripciones duplicadas en el mismo periodo.
$stmt = $conexion->prepare("
    SELECT id FROM inscripciones
    WHERE idEstudiante = ? AND idCurso = ? AND idPeriodo = ?
");
$stmt->bind_param("iii", $idEstudiante, $idCurso, $idPeriodo);
$stmt->execute();
$res = $stmt->get_result();
$yaInscrito = $res->fetch_assoc();
$stmt->close();

if ($yaInscrito) {
    echo json_encode(['success' => false, 'mensaje' => 'Ya estás inscrito en este curso.']);
    exit();
}

// Verifica el limite de 5 cursos por periodo.
// Esta validación bloquea la inscripción de un sexto curso en el mismo período,
// pero no oculta cursos disponibles en la lista de inscripción.
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total FROM inscripciones
    WHERE idEstudiante = ? AND idPeriodo = ? AND estado_academico != 'Retirado'
");
$stmt->bind_param("ii", $idEstudiante, $idPeriodo);
$stmt->execute();
$res = $stmt->get_result();
$conteo = $res->fetch_assoc();
$stmt->close();

if ($conteo['total'] >= 5) {
    echo json_encode(['success' => false, 'mensaje' => 'Has alcanzado el límite de 5 cursos por período.']);
    exit();
}

// Comprueba que el estudiante haya finalizado los prerrequisitos.
$stmt = $conexion->prepare("
    SELECT idCursoPrevio FROM prerrequisitos
    WHERE idCursoActual = ?
");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$res = $stmt->get_result();
$prerrequisitos = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($prerrequisitos as $pre) {
    $idCursoPrevio = $pre['idCursoPrevio'];

    $stmt = $conexion->prepare("
        SELECT id FROM RegistroNotas
        WHERE idEstudiante = ? AND idCurso = ? AND estadoEstudiante = 'Aprobado'
    ");
    $stmt->bind_param("ii", $idEstudiante, $idCursoPrevio);
    $stmt->execute();
    $res = $stmt->get_result();
    $cumple = $res->fetch_assoc();
    $stmt->close();

    if (!$cumple) {
        $stmtNombre = $conexion->prepare("SELECT nombre FROM cursos WHERE id = ?");
        $stmtNombre->bind_param("i", $idCursoPrevio);
        $stmtNombre->execute();
        $resNombre = $stmtNombre->get_result();
        $cursoNombre = $resNombre->fetch_assoc();
        $stmtNombre->close();

        $nombrePre = $cursoNombre ? $cursoNombre['nombre'] : 'un curso previo';
        echo json_encode([
            'success' => false,
            'mensaje' => "Debes completar primero el curso: \"$nombrePre\"."
        ]);
        exit();
    }
}

// Todas las validaciones pasaron; se confirma la inscripcion.
$conexion->begin_transaction();

try {
    $stmt = $conexion->prepare("
        INSERT INTO inscripciones (idEstudiante, idCurso, idPeriodo, estado_academico)
        VALUES (?, ?, ?, 'Activo')
    ");
    $stmt->bind_param("iii", $idEstudiante, $idCurso, $idPeriodo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE cursos SET cupos = cupos - 1 WHERE id = ? AND cupos > 0");
    $stmt->bind_param("i", $idCurso);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('El cupo se agotó justo antes de confirmar.');
    }
    $stmt->close();

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'idCurso' => $idCurso,
        'mensaje' => '¡Inscripción exitosa!'
    ]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
