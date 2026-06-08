<?php
// Genera una factura docente desde el panel administrativo.
// Guarda el registro, crea el PDF y responde el estado del envio por correo.

error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || ($_SESSION['rol_id'] ?? 0) != 1) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Datos enviados desde el formulario administrativo.
$idDocente     = (int)($_POST['idDocente'] ?? 0);
$metodoPago    = trim($_POST['metodoPago']    ?? '');
$noReferencia  = trim($_POST['noReferencia']  ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$fechaEmision  = trim($_POST['fechaEmision']  ?? date('Y-m-d'));
$items         = $_POST['items'] ?? [];   // Items: descripcion, cantidad y precio.

if (!$idDocente || empty($metodoPago) || empty($items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$total = 0;
$itemsLimpios = [];
foreach ($items as $item) {
    $desc     = trim($item['descripcion'] ?? '');
    $cantidad = max(1, (int)($item['cantidad'] ?? 1));
    $precio   = (float)($item['precio'] ?? 0);
    if ($desc === '' || $precio <= 0) continue;
    $subtotal       = $cantidad * $precio;
    $total         += $subtotal;
    $itemsLimpios[] = [
        'descripcion'   => $desc,
        'cantidad'      => $cantidad,
        'precioUnitario'=> $precio,
        'subtotal'      => $subtotal,
    ];
}

if (empty($itemsLimpios)) {
    http_response_code(400);
    echo json_encode(['error' => 'Debes agregar al menos un ítem válido']);
    exit;
}

// Datos del docente receptor de la factura.
$stmtDoc = $conexion->prepare("
    SELECT u.nombre, u.apellido, u.correo, d.especialidad, d.telefono, d.direccion
    FROM docentes d
    INNER JOIN usuarios u ON d.usuario_id = u.id
    WHERE d.id = ?
");
$stmtDoc->bind_param('i', $idDocente);
$stmtDoc->execute();
$docente = $stmtDoc->get_result()->fetch_assoc();
$stmtDoc->close();

if (!$docente) {
    http_response_code(404);
    echo json_encode(['error' => 'Docente no encontrado']);
    exit;
}

$nombreDocente = trim($docente['nombre'] . ' ' . $docente['apellido']);

// Numero correlativo de factura para el anio de emision.
$anio = date('Y', strtotime($fechaEmision));
$stmtCount = $conexion->prepare("SELECT COUNT(*) AS total FROM facturas WHERE YEAR(fechaEmision) = ?");
$stmtCount->bind_param('i', $anio);
$stmtCount->execute();
$totalFacturas = $stmtCount->get_result()->fetch_assoc()['total'] ?? 0;
$stmtCount->close();
$numeroFactura = 'ADFE-' . $anio . '-' . str_pad($totalFacturas + 1, 6, '0', STR_PAD_LEFT);

$conexion->begin_transaction();
try {
    $stmtFact = $conexion->prepare("
        INSERT INTO facturas
            (numeroFactura, tipoFactura, idReceptor, tipoReceptor, idPago,
             metodoPago, noReferencia, observaciones, total, estado, generadoPor)
        VALUES (?, 'Docente', ?, 'Docente', NULL, ?, ?, ?, ?, 'Emitida', ?)
    ");
    $stmtFact->bind_param(
        'sisssdi',
        $numeroFactura,
        $idDocente,
        $metodoPago,
        $noReferencia,
        $observaciones,
        $total,
        $idDocente
    );
    $stmtFact->execute();
    $idFactura = $conexion->insert_id;
    $stmtFact->close();

    $stmtDet = $conexion->prepare("
        INSERT INTO detalle_facturas
            (idFactura, tipoOrigen, idOrigen, descripcion, cantidad, precioUnitario, subtotal)
        VALUES (?, 'PagoDocente', NULL, ?, ?, ?, ?)
    ");
    foreach ($itemsLimpios as $it) {
        $stmtDet->bind_param(
            'isidd',
            $idFactura,
            $it['descripcion'],
            $it['cantidad'],
            $it['precioUnitario'],
            $it['subtotal']
        );
        $stmtDet->execute();
    }
    $stmtDet->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error guardando factura: ' . $e->getMessage()]);
    exit;
}

// Genera el PDF con la plantilla de factura docente.
date_default_timezone_set('America/El_Salvador');

// Variables esperadas por vista-factura-docente.php.
$facturaId    = $numeroFactura;
$docente_var  = $nombreDocente;
$correo       = $docente['correo'];
$telefono     = $docente['telefono'] ?? '';
$direccion    = $docente['direccion'] ?? '';
$especialidad = $docente['especialidad'] ?? '';
$concepto     = $itemsLimpios[0]['descripcion'];  
$periodoPago  = '';
$descripcion  = $observaciones;
$metodoPago   = $metodoPago;
$condicion    = 'CONTADO';
$referencia   = $noReferencia;
$estado       = 'Emitida';
$fecha        = date('d/m/Y', strtotime($fechaEmision));
$hora         = date('h:i A');
$items        = array_map(fn($it) => [
    'tipo'        => 'Pago Docente',
    'descripcion' => $it['descripcion'],
    'periodo'     => $periodoPago,
    'monto'       => $it['subtotal'],
], $itemsLimpios);

// Renombra la variable para evitar conflicto con el arreglo del docente.
$docente = $docente_var;

ob_start();
include __DIR__ . '/../comprobantes/vista-factura-docente.php';
$htmlFactura = ob_get_clean();

$pdfOutput = null;
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($htmlFactura);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();
} catch (Throwable $e) {
    error_log('Dompdf error factura docente: ' . $e->getMessage());
}

// Envia la factura al correo del docente.
$correoEnviado = false;
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'academiafuturodigital6@gmail.com';
    $mail->Password   = 'qrgzjvlgqccqcoab';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 0;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('academiafuturodigital6@gmail.com', 'Academia Futuro Digital');
    $mail->addAddress($correo, $docente);

    $mail->isHTML(true);
    $mail->Subject = 'Factura de pago N° ' . $numeroFactura . ' — Academia Futuro Digital';
    $mail->Body    = $htmlFactura;
    $mail->AltBody = "Estimado/a $docente,\n\nAdjunto encontrarás la factura de pago N° $numeroFactura.\nMonto total: \$$total\nFecha: $fecha\n\nAcademia Futuro Digital";

    if (!empty($pdfOutput)) {
        $mail->addStringAttachment(
            $pdfOutput,
            'factura-' . $numeroFactura . '.pdf',
            'base64',
            'application/pdf'
        );
    }

    $mail->send();
    $correoEnviado = true;
} catch (Exception $e) {
    error_log('Error enviando factura docente: ' . $e->getMessage());
}

echo json_encode([
    'success'       => true,
    'numeroFactura' => $numeroFactura,
    'idFactura'     => $idFactura,
    'total'         => number_format($total, 2),
    'correoEnviado' => $correoEnviado,
    'mensaje'       => $correoEnviado
        ? "Factura $numeroFactura generada y enviada a $correo"
        : "Factura $numeroFactura generada. No se pudo enviar el correo.",
]);
?>
