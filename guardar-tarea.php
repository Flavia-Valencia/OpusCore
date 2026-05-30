<?php
session_start();
date_default_timezone_set('America/El_Salvador');
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin sesión activa']);
    exit();
}

require_once 'includes/conexion.php';

$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$idCurso     = filter_input(INPUT_POST, 'idCurso', FILTER_VALIDATE_INT);
$idSesion    = filter_input(INPUT_POST, 'idSesion', FILTER_VALIDATE_INT) ?: null;
$titulo      = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fechaLimite = trim($_POST['fechaLimite'] ?? '');
$estado      = isset($_POST['estado']) && $_POST['estado'] === '1' ? 1 : 0;

$puntaje = isset($_POST['puntajeMaximo']) && $_POST['puntajeMaximo'] !== ''
    ? floatval($_POST['puntajeMaximo'])
    : false;

$intentos = filter_input(INPUT_POST, 'intentos', FILTER_VALIDATE_INT) ?: 1;
if ($intentos < 1) $intentos = 1;
if ($intentos > 10) $intentos = 10;

if (!$idCurso || !$titulo || !$descripcion || !$fechaLimite || $puntaje === false || $puntaje <= 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Complete todos los campos requeridos']);
    exit();
}

// VALIDAR FECHA
$fechaObj = DateTime::createFromFormat('Y-m-d\TH:i', $fechaLimite);

if (!$fechaObj) {
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fechaLimite);
    if ($fechaObj) {
        $fechaObj->setTime(23, 59, 59);
    }
}

if (!$fechaObj) {
    echo json_encode(['error' => true, 'mensaje' => 'Formato de fecha no válido']);
    exit();
}

// Comparar solo la parte de la fecha (sin hora) para permitir hoy a cualquier hora
$soloFechaSel = new DateTime($fechaObj->format('Y-m-d'));
$soloHoy      = new DateTime('today');

if ($soloFechaSel < $soloHoy) {
    echo json_encode(['error' => true, 'mensaje' => 'La fecha límite no puede ser anterior a la fecha actual']);
    exit();
}

$fechaLimiteFmt = $fechaObj->format('Y-m-d H:i:s');

$stmtVerif = $conexion->prepare("
    SELECT c.id FROM cursos c
    INNER JOIN docentes d ON c.idDocente = d.id
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE c.id = ? AND u.correo = ? AND c.estado = 1
    LIMIT 1
");
$stmtVerif->bind_param('is', $idCurso, $_SESSION['usuario']);
$stmtVerif->execute();
if (!$stmtVerif->get_result()->fetch_assoc()) {
    echo json_encode(['error' => true, 'mensaje' => 'No tienes permiso sobre este curso']);
    exit();
}
$stmtVerif->close();

// MANEJO DE ARCHIVO ADJUNTO 
$rutaArchivo   = null;
$nombreArchivo = null;

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $extensionesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'png', 'jpg', 'jpeg'];
    $nombreOriginal        = basename($_FILES['archivo']['name']);
    $extension             = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        echo json_encode(['error' => true, 'mensaje' => 'Tipo de archivo no permitido']);
        exit();
    }

    if ($_FILES['archivo']['size'] > 10 * 1024 * 1024) {
        echo json_encode(['error' => true, 'mensaje' => 'El archivo supera el tamaño máximo de 10 MB']);
        exit();
    }

    $dirUpload = 'uploads/tareas/';
    if (!is_dir($dirUpload)) {
        mkdir($dirUpload, 0755, true);
    }

    $nombreGuardado = uniqid('tarea_', true) . '.' . $extension;
    $rutaDestino    = $dirUpload . $nombreGuardado;

    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        echo json_encode(['error' => true, 'mensaje' => 'Error al subir el archivo']);
        exit();
    }

    $rutaArchivo   = $rutaDestino;
    $nombreArchivo = $nombreOriginal;
}

// CREAR O EDITAR 
if ($id > 0) {

    $stmtCheck = $conexion->prepare("
        SELECT id, fechaLimite, estado FROM tareas
        WHERE id = ? AND idCurso = ?
        LIMIT 1
    ");
    $stmtCheck->bind_param('ii', $id, $idCurso);
    $stmtCheck->execute();
    $tareaExistente = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$tareaExistente) {
        echo json_encode(['error' => true, 'mensaje' => 'Tarea no encontrada']);
        exit();
    }

    // Bloquear edición si la fecha límite original ya venció
    $fechaLimiteExistente = new DateTime($tareaExistente['fechaLimite']);
    $ahora                = new DateTime();

    if ($fechaLimiteExistente < $ahora) {
        echo json_encode(['error' => true, 'mensaje' => 'No se puede editar una tarea cuya fecha límite ya venció']);
        exit();
    }

    $stmt = $conexion->prepare("
        UPDATE tareas SET titulo=?, descripcion=?, idSesion=?, puntajeMaximo=?, fechaLimite=?, intentos=?
        WHERE id=? AND idCurso=?
    ");
    $stmt->bind_param('ssiisiii', $titulo, $descripcion, $idSesion, $puntaje, $fechaLimiteFmt, $intentos, $id, $idCurso);

    if (!$stmt->execute()) {
        echo json_encode(['error' => true, 'mensaje' => 'Error al actualizar: ' . $conexion->error]);
        exit();
    }
    $stmt->close();

    if ($rutaArchivo && $nombreArchivo) {
        $stmtArch = $conexion->prepare("
            INSERT INTO tareasArchivos (idTarea, nombreArchivo, tipo, rutaArchivo)
            VALUES (?, ?, 'Archivo', ?)
        ");
        $stmtArch->bind_param('iss', $id, $nombreArchivo, $rutaArchivo);
        $stmtArch->execute();
        $stmtArch->close();
    }

    echo json_encode(['error' => false, 'mensaje' => 'Tarea actualizada correctamente', 'id' => $id]);

} else {

    $stmt = $conexion->prepare("
        INSERT INTO tareas (idCurso, idSesion, titulo, descripcion, puntajeMaximo, intentos, fechaLimite, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->bind_param('iissisi', $idCurso, $idSesion, $titulo, $descripcion, $puntaje, $intentos, $fechaLimiteFmt);

    if (!$stmt->execute()) {
        echo json_encode(['error' => true, 'mensaje' => 'Error al crear la tarea: ' . $conexion->error]);
        exit();
    }

    $nuevaId = $stmt->insert_id;
    $stmt->close();

    if ($rutaArchivo && $nombreArchivo) {
        $stmtArch = $conexion->prepare("
            INSERT INTO tareasArchivos (idTarea, nombreArchivo, tipo, rutaArchivo)
            VALUES (?, ?, 'Archivo', ?)
        ");
        $stmtArch->bind_param('iss', $nuevaId, $nombreArchivo, $rutaArchivo);
        $stmtArch->execute();
        $stmtArch->close();
    }

    echo json_encode(['error' => false, 'mensaje' => 'Tarea creada correctamente', 'id' => $nuevaId]);
}
?>