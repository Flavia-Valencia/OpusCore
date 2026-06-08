<?php
// Este archivo implementa el endpoint para obtener las notas del estudiante autenticado.
// Valida la sesión activa y que el rol corresponda a un estudiante (rol_id = 2).
// Resuelve el id del estudiante desde la sesión para evitar acceso a notas ajenas.
// Consulta RegistroNotas con el último plazo registrado por curso desde inscripciones.
// Calcula métricas de evaluación y responde en formato JSON.
session_start();
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate");

if (!isset($_SESSION["usuario"]) || !isset($_SESSION["rol_id"]) || $_SESSION["rol_id"] != 2) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Acceso no autorizado."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

require_once 'includes/conexion.php';

$correo = $_SESSION["usuario"];

$stmt = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
    LIMIT 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Estudiante no encontrado."]);
    exit();
}

$idEstudiante = (int) $estudiante['id'];

$stmt = $conexion->prepare("
    SELECT
        c.id                                                        AS curso_id,
        c.nombre                                                    AS curso_nombre,
        COALESCE(c.descripcion, '')                                 AS descripcion,
        COALESCE(pi.nombre, 'Sin periodo')                          AS periodo_nombre,
        pi.id                                                       AS periodo_id,
        pi.fechaInicioCiclo,
        pi.fechaFinCiclo,
        COALESCE(CONCAT(ud.nombre, ' ', ud.apellido),
                 'Docente por asignar')                             AS docente_nombre,
        i.estado_academico,
        rn.actividades,
        rn.examenFinal,
        rn.notaFinal,
        rn.estadoEstudiante,
        rn.fechaRegistro,
        pn.nombre                                                   AS plazo_nombre,
        pn.plazoInicio,
        pn.plazoFin
    FROM inscripciones i
    INNER JOIN cursos c ON i.idCurso = c.id
    LEFT JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
    LEFT JOIN docentes d            ON c.idDocente = d.id
    LEFT JOIN usuarios ud           ON d.usuario_id = ud.id
    LEFT JOIN RegistroNotas rn
        ON rn.idCurso = c.id
        AND rn.idEstudiante = i.idEstudiante
        AND rn.id = (
            SELECT rn2.id
            FROM RegistroNotas rn2
            INNER JOIN PlazoNotas pn2 ON rn2.idPlazo = pn2.id
            WHERE rn2.idCurso      = c.id
              AND rn2.idEstudiante = i.idEstudiante
            ORDER BY pn2.plazoFin DESC, rn2.id DESC
            LIMIT 1
        )
    LEFT JOIN PlazoNotas pn ON rn.idPlazo = pn.id
    WHERE i.idEstudiante = ?
      AND i.estado_academico <> 'Retirado'
    ORDER BY pi.fechaInicio DESC, c.nombre ASC
");
$stmt->bind_param("i", $idEstudiante);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalCursos    = count($filas);
$totalEvaluadas = 0;
$aprobadas      = 0;
$reprobadas     = 0;
$sumaNotas      = 0.0;

$calificaciones = [];

foreach ($filas as $fila) {
    $tieneNota = $fila['notaFinal'] !== null;

    if ($tieneNota) {
        $totalEvaluadas++;
        $nota = (float) $fila['notaFinal'];
        $sumaNotas += $nota;

        if ($nota >= 6.0) {
            $aprobadas++;
            $estadoClase = $nota >= 7.5 ? 'aprobado' : 'intermedio';
        } else {
            $reprobadas++;
            $estadoClase = 'reprobado';
        }
    } else {
        $estadoClase = 'pendiente';
    }

    $calificaciones[] = [
        "curso_id"         => (int)   $fila['curso_id'],
        "curso_nombre"     =>         $fila['curso_nombre'],
        "descripcion"      =>         $fila['descripcion'],
        "periodo_nombre"   =>         $fila['periodo_nombre'],
        "periodo_id"       => $fila['periodo_id'] ? (int) $fila['periodo_id'] : null,
        "fechaInicioCiclo" =>         $fila['fechaInicioCiclo'],
        "fechaFinCiclo"    =>         $fila['fechaFinCiclo'],
        "docente_nombre"   =>         $fila['docente_nombre'],
        "estado_academico" =>         $fila['estado_academico'],
        "actividades"      => $tieneNota ? (float) $fila['actividades']  : null,
        "examenFinal"      => $tieneNota ? (float) $fila['examenFinal']   : null,
        "notaFinal"        => $tieneNota ? (float) $fila['notaFinal']     : null,
        "estadoEstudiante" =>         $fila['estadoEstudiante'],
        "estadoClase"      =>         $estadoClase,
        "fechaRegistro"    =>         $fila['fechaRegistro'],
        "plazo_nombre"     =>         $fila['plazo_nombre'],
        "plazoInicio"      =>         $fila['plazoInicio'],
        "plazoFin"         =>         $fila['plazoFin'],
    ];
}

$promedioGeneral = $totalEvaluadas > 0
    ? round($sumaNotas / $totalEvaluadas, 2)
    : null;

echo json_encode([
    "success"          => true,
    "estudiante"       => [
        "id"     => $idEstudiante,
        "nombre" => $estudiante['estudiante_nombre'],
    ],
    "metricas"         => [
        "totalCursos"      => $totalCursos,
        "totalEvaluadas"   => $totalEvaluadas,
        "aprobadas"        => $aprobadas,
        "reprobadas"       => $reprobadas,
        "promedioGeneral"  => $promedioGeneral,
    ],
    "calificaciones"   => $calificaciones,
]);

mysqli_close($conexion);
?>