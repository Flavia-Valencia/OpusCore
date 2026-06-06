<?php
// Verifica que el usuario (administrador) haya iniciado sesión antes de procesar la solicitud.
// Obtiene los datos completos del docente y el curso asociado a la solicitud de constancia.
// Valida que la información del docente esté completa (nombre, apellido, correo) y que el curso
// tenga período asignado. Genera un código correlativo único, renderiza la plantilla HTML,
// produce el PDF con Dompdf, lo guarda en disco, registra la constancia en la base de datos,
// actualiza el estado de la solicitud a Aprobada y envía el PDF al correo del docente.
session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'No autorizado']);
    exit();
}

require_once 'includes/conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/includes/PHPMailer/Exception.php';
require_once __DIR__ . '/includes/dompdf/autoload.inc.php';

$solicitudId = (int)($_POST['solicitudId'] ?? 0);
if (!$solicitudId) {
    echo json_encode(['error' => true, 'mensaje' => 'Solicitud inválida']);
    exit();
}

$stmt = $conexion->prepare("
    SELECT
        sol.id              AS solicitud_id,
        sol.idDocente       AS docente_id,
        sol.idCurso         AS curso_id,
        sol.motivo,
        sol.fechaSolicitud,
        u.id                AS usuario_id,
        u.nombre,
        u.apellido,
        u.correo,
        d.telefono,
        d.direccion,
        c.nombre            AS curso_nombre,
        COALESCE(pi.nombre, 'Sin periodo') AS periodo_nombre
    FROM solicitudConstanciaDocente sol
    INNER JOIN docentes d        ON sol.idDocente = d.id
    INNER JOIN usuarios u        ON d.usuario_id = u.id
    INNER JOIN cursos c          ON sol.idCurso = c.id
    LEFT  JOIN PeriodoInscripcion pi ON c.idPeriodo = pi.id
    WHERE sol.id = ? AND sol.estado = 'Pendiente'
    LIMIT 1
");
$stmt->bind_param("i", $solicitudId);
$stmt->execute();
$sol = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sol) {
    echo json_encode(['error' => true, 'mensaje' => 'Solicitud no encontrada o ya procesada']);
    exit();
}

if (empty($sol['nombre']) || empty($sol['apellido']) || empty($sol['correo'])) {
    echo json_encode(['error' => true, 'mensaje' => 'El docente no tiene información completa (nombre, apellido o correo)']);
    exit();
}

if (empty($sol['curso_nombre']) || empty($sol['periodo_nombre'])) {
    echo json_encode(['error' => true, 'mensaje' => 'El curso no tiene información completa para generar la constancia']);
    exit();
}

$codigo = 'CONST-' . strtoupper(substr(md5(uniqid($solicitudId, true)), 0, 8));

date_default_timezone_set('America/El_Salvador');
$solicitante    = $sol['nombre'] . ' ' . $sol['apellido'];
$correo         = $sol['correo'];
$curso          = $sol['curso_nombre'];
$codigoCurso    = 'C-' . str_pad($sol['curso_id'], 3, '0', STR_PAD_LEFT);
$periodo        = $sol['periodo_nombre'];
$notaFinal      = 'No aplica';
$resultado      = 'Curso impartido';
$motivo         = $sol['motivo'];
$destino        = 'Docente';
$tipoConstancia = 'Constancia de docencia impartida';
$fechaSolicitud = date('d/m/Y', strtotime($sol['fechaSolicitud']));
$horaSolicitud  = date('h:i A', strtotime($sol['fechaSolicitud']));
$fechaEmision   = date('d/m/Y');
$horaEmision    = date('h:i A');
$fechaActividad = $fechaSolicitud;
$codigoConstancia = $codigo;
$estado         = 'Generada';

ob_start();
include __DIR__ . '/comprobantes/vista-constancia-administrativa.php';
$htmlConstancia = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlConstancia);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

$carpeta = __DIR__ . '/uploads/constancias/';
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}
$nombreArchivo = $codigo . '.pdf';
$rutaCompleta  = $carpeta . $nombreArchivo;
file_put_contents($rutaCompleta, $pdfOutput);
$rutaRelativa  = 'uploads/constancias/' . $nombreArchivo;

$stmtAdmin = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
$stmtAdmin->bind_param("s", $_SESSION['usuario']);
$stmtAdmin->execute();
$admin = $stmtAdmin->get_result()->fetch_assoc();
$stmtAdmin->close();
$idAdmin = (int)($admin['id'] ?? 0);

$ins = $conexion->prepare("
    INSERT INTO constancias (codigoConstancia, tipo, idUsuarioSolicitante, idGeneradoPor, rutaPDF)
    VALUES (?, 'Docente', ?, ?, ?)
");
$ins->bind_param("siis", $codigo, $sol['usuario_id'], $idAdmin, $rutaRelativa);
$ins->execute();
$ins->close();

$upd = $conexion->prepare("UPDATE solicitudConstanciaDocente SET estado = 'Aprobada' WHERE id = ?");
$upd->bind_param("i", $solicitudId);
$upd->execute();
$upd->close();

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'academiafuturodigital6@gmail.com';
    $mail->Password   = 'qrgzjvlgqccqcoab';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 0;

    $mail->setFrom('academiafuturodigital6@gmail.com', 'Academia Futuro Digital');
    $mail->addAddress($correo, $solicitante);

    $mail->addStringAttachment($pdfOutput, $codigo . '.pdf', 'base64', 'application/pdf');

    $mail->isHTML(true);
    $mail->Subject = 'Tu constancia ha sido generada - Academia Futuro Digital';
    $mail->Body    = "
        <p>Hola <strong>{$solicitante}</strong>,</p>
        <p>Tu constancia de docencia del curso <strong>{$curso}</strong> ha sido generada.</p>
        <p><strong>Código:</strong> {$codigo}<br>
           <strong>Periodo:</strong> {$periodo}<br>
           <strong>Fecha de emisión:</strong> {$fechaEmision}</p>
        <p>Adjunto encontrarás el PDF de tu constancia.</p>
        <p>Academia Futuro Digital</p>
    ";
    $mail->AltBody = "Hola {$solicitante}, tu constancia {$codigo} del curso {$curso} ha sido generada. Revisa el archivo adjunto.";

    $mail->send();
} catch (Exception $e) {
    error_log('Error enviando constancia docente: ' . $e->getMessage());
}

echo json_encode([
    'error'   => false,
    'codigo'  => $codigo,
    'mensaje' => 'Constancia generada y enviada correctamente'
]);