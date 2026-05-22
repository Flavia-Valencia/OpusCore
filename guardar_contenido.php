<?php
// Crea o edita un contenido de sesión según si se recibe un id.
// Si hay archivos físicos los mueve a uploads/sesiones/ y los registra en sesionArchivos.
// Si hay enlaces los valida y los guarda también en sesionArchivos.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/includes/conexion.php';

$id          = (int)($_POST['id'] ?? 0);
$titulo      = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha       = trim($_POST['fecha'] ?? '');
$idCurso     = (int)($_POST['idCurso'] ?? 0);
$estado      = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;

if (!$titulo || !$fecha || !$idCurso) {
    echo json_encode(['ok' => false, 'msg' => 'Título, fecha y curso son obligatorios']);
    exit();
}

$conexion->begin_transaction();

try {
    if ($id > 0) {
        $stmt = $conexion->prepare("
            UPDATE sesionContenido 
            SET titulo = ?, descripcion = ?, fecha = ?, estado = ?
            WHERE id = ?
        ");
        $descNull = $descripcion ?: null;
        $stmt->bind_param('sssii', $titulo, $descNull, $fecha, $estado, $id);
        $stmt->execute();
        $stmt->close();
        $idSesion = $id;
    } else {
        // CREAR
        $stmt = $conexion->prepare("
            INSERT INTO sesionContenido (idCurso, titulo, descripcion, fecha, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $descNull = $descripcion ?: null;
        $stmt->bind_param('isssi', $idCurso, $titulo, $descNull, $fecha, $estado);
        $stmt->execute();
        $idSesion = $conexion->insert_id;
        $stmt->close();
    }

    $uploadDir = __DIR__ . '/../uploads/sesiones/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $archivosSubidos = $_FILES['archivos'] ?? null;
    if ($archivosSubidos && is_array($archivosSubidos['name'])) {
        $permitidos = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'png', 'jpg', 'jpeg'];
        $stmtArch = $conexion->prepare("
            INSERT INTO sesionArchivos (idSesion, nombreArchivo, rutaArchivo, tipo)
            VALUES (?, ?, ?, 'Archivo')
        ");

        for ($i = 0; $i < count($archivosSubidos['name']); $i++) {
            if ($archivosSubidos['error'][$i] !== UPLOAD_ERR_OK) continue;

            $nombreOriginal = basename($archivosSubidos['name'][$i]);
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidos)) continue;

            $nombreFinal = uniqid('ses_', true) . '.' . $ext;
            $rutaFisica  = $uploadDir . $nombreFinal;
            $rutaGuardar = 'uploads/sesiones/' . $nombreFinal;

            if (move_uploaded_file($archivosSubidos['tmp_name'][$i], $rutaFisica)) {
                $stmtArch->bind_param('iss', $idSesion, $nombreOriginal, $rutaGuardar);
                $stmtArch->execute();
            }
        }
        $stmtArch->close();
    }

    $enlaces = $_POST['enlaces'] ?? [];
    if (!empty($enlaces)) {
        $stmtEnl = $conexion->prepare("
            INSERT INTO sesionArchivos (idSesion, nombreArchivo, rutaArchivo, tipo)
            VALUES (?, ?, ?, 'Enlace')
        ");
        foreach ($enlaces as $enlace) {
            $nombreEnl = trim($enlace['nombre'] ?? '');
            $urlEnl    = trim($enlace['url'] ?? '');
            if (!$nombreEnl || !$urlEnl) continue;
            if (!filter_var($urlEnl, FILTER_VALIDATE_URL)) continue;

            $stmtEnl->bind_param('iss', $idSesion, $nombreEnl, $urlEnl);
            $stmtEnl->execute();
        }
        $stmtEnl->close();
    }

    $conexion->commit();
    echo json_encode(['ok' => true, 'msg' => 'Contenido guardado correctamente', 'idSesion' => $idSesion]);

} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
}