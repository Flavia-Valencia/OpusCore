<?php
include("includes/conexion.php");

$idCurso = intval($_GET['idCurso'] ?? 0);

if (!$idCurso) {
    echo json_encode([]);
    exit;
}

$stmt = $conexion->prepare("
    SELECT ch.dia, ch.idHorario, ch.idAula
    FROM cursohorario ch
    WHERE ch.idCurso = ?
    ORDER BY ch.idHorario, ch.idAula
");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$res = $stmt->get_result();

// Agrupar días por horario y aula
$grupos = [];
while ($row = $res->fetch_assoc()) {
    $clave = $row['idHorario'] . '-' . $row['idAula'];
    if (!isset($grupos[$clave])) {
        $grupos[$clave] = [
            'idHorario' => $row['idHorario'],
            'idAula'    => $row['idAula'],
            'dias'      => []
        ];
    }
    $grupos[$clave]['dias'][] = $row['dia'];
}

$stmt->close();
echo json_encode(array_values($grupos));
?>