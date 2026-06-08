<?php
include("includes/conexion.php");

$data = json_decode(file_get_contents('php://input'), true); // JSON enviado por el modal de horarios.
$idCurso = intval($data['idCurso'] ?? 0);
$bloques = $data['bloques'] ?? [];


if(!$idCurso || empty($bloques)){
    echo json_encode([
        "success" => false,
        "message"=> "Datos importantes no completados"
    ]);
    exit;
}

$registros =[];
foreach($bloques as $bloque){
    $idHorario = intval($bloque['horario']);
    $idAula = intval($bloque['aula']);

    foreach($bloque['dias'] as $dia){
        $registros[] = ['dia' => $dia, 'idHorario' => $idHorario, 'idAula' => $idAula];
    }
}
// Evita solapar aulas en el mismo dia y horario.

foreach($registros as $r){
    $stmt = $conexion-> prepare("
    SELECT ch.id
    FROM cursohorario ch
    WHERE ch.dia = ?
      AND ch.idHorario = ?
      AND ch.idAula = ?
      AND ch.idCurso != ?
      ");

      $stmt->bind_param("siii", $r['dia'], $r['idHorario'], $r['idAula'], $idCurso);
      $stmt->execute();
      $stmt->store_result();

       if ($stmt->num_rows > 0) {
        $etiqueta = '';
        $stmtInfo = $conexion->prepare("SELECT etiqueta FROM horarios WHERE id = ?");
        $stmtInfo->bind_param("i", $r['idHorario']);
        $stmtInfo->execute();
        $stmtInfo->bind_result($etiqueta);
        $stmtInfo->fetch();
        $stmtInfo->close();

        echo json_encode([
            "success" => false,
            "message" => "El aula ya está ocupada el {$r['dia']} en el horario {$etiqueta}"
        ]);
        $stmt->close();
        exit;
       }
       $stmt->close();
}
// Registros actuales del curso en base de datos.
$stmt = $conexion->prepare("SELECT id, dia, idHorario, idAula FROM cursohorario WHERE idCurso = ?");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$res = $stmt->get_result();
$actuales = [];
while ($row = $res->fetch_assoc()) {
    $actuales[] = $row;
}
$stmt->close();

// Nuevos horarios indexados por dia, horario y aula.
$nuevosSet = [];
foreach ($registros as $r) {
    $clave = $r['dia'] . '-' . $r['idHorario'] . '-' . $r['idAula'];
    $nuevosSet[$clave] = $r;
}

// Horarios actuales indexados con la misma clave.
$actualesSet = [];
foreach ($actuales as $a) {
    $clave = $a['dia'] . '-' . $a['idHorario'] . '-' . $a['idAula'];
    $actualesSet[$clave] = $a;
}

// Elimina solo horarios retirados desde el modal.
$stmtDel = $conexion->prepare("DELETE FROM cursohorario WHERE id = ?");
foreach ($actuales as $a) {
    $clave = $a['dia'] . '-' . $a['idHorario'] . '-' . $a['idAula'];
    if (!isset($nuevosSet[$clave])) {
        $stmtDel->bind_param("i", $a['id']);
        $stmtDel->execute();
    }
}
$stmtDel->close();

// Inserta solo horarios que aun no existen.
$stmtIns = $conexion->prepare("INSERT INTO cursohorario (idCurso, dia, idHorario, idAula) VALUES (?, ?, ?, ?)");
foreach ($nuevosSet as $clave => $r) {
    if (!isset($actualesSet[$clave])) {
        $stmtIns->bind_param("isii", $idCurso, $r['dia'], $r['idHorario'], $r['idAula']);
        $stmtIns->execute();
    }
}
$stmtIns->close();

echo json_encode(["success" => true, "message" => "Horarios guardados correctamente"]);
