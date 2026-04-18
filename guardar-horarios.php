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
//borra horarios anteriores
$stmt = $conexion->prepare("DELETE FROM cursohorario WHERE idCurso = ?");
$stmt->bind_param("i", $idCurso);
$stmt->execute();
$stmt->close();

//inserta nuevos
$stmt = $conexion->prepare("INSERT INTO cursohorario (idCurso, dia, idHorario, idAula) VALUES (?, ?, ?, ?)");
foreach ($registros as $r) {
    $stmt->bind_param("isii", $idCurso, $r['dia'], $r['idHorario'], $r['idAula']);
    $stmt->execute();
}
$stmt->close();

echo json_encode(["success" => true, "message" => "Horarios guardados correctamente"]);

?>