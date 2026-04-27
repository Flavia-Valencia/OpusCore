<?php
include("includes/conexion.php");

$data = json_decode(file_get_contents('php://input'), true); // recibe el JSON que manda JS
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
//Valida que el aula no esté ocupada

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
// Obtener registros actuales de la BD
$stmt = $conexion->prepare("SELECT id, dia, idHorario, idAula FROM cursohorario WHERE idCurso = ?");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$res = $stmt->get_result();
$actuales = [];
while ($row = $res->fetch_assoc()) {
    $actuales[] = $row;
}
$stmt->close();

// Convertir nuevos registros a formato comparable
$nuevosSet = [];
foreach ($registros as $r) {
    $clave = $r['dia'] . '-' . $r['idHorario'] . '-' . $r['idAula'];
    $nuevosSet[$clave] = $r;
}

// Convertir actuales a formato comparable
$actualesSet = [];
foreach ($actuales as $a) {
    $clave = $a['dia'] . '-' . $a['idHorario'] . '-' . $a['idAula'];
    $actualesSet[$clave] = $a;
}

// Eliminar solo los que ya no están en los nuevos
$stmtDel = $conexion->prepare("DELETE FROM cursohorario WHERE id = ?");
foreach ($actuales as $a) {
    $clave = $a['dia'] . '-' . $a['idHorario'] . '-' . $a['idAula'];
    if (!isset($nuevosSet[$clave])) {
        $stmtDel->bind_param("i", $a['id']);
        $stmtDel->execute();
    }
}
$stmtDel->close();

// Insertar solo los que no existen aún
$stmtIns = $conexion->prepare("INSERT INTO cursohorario (idCurso, dia, idHorario, idAula) VALUES (?, ?, ?, ?)");
foreach ($nuevosSet as $clave => $r) {
    if (!isset($actualesSet[$clave])) {
        $stmtIns->bind_param("isii", $idCurso, $r['dia'], $r['idHorario'], $r['idAula']);
        $stmtIns->execute();
    }
}
$stmtIns->close();

echo json_encode(["success" => true, "message" => "Horarios guardados correctamente"]);