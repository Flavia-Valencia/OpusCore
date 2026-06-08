<?php
// Descarga facturas PDF para estudiantes, docentes o administradores autorizados.
// Selecciona la plantilla Dompdf segun el tipo de receptor.
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$idFactura = filter_input(INPUT_GET, 'factura_id', FILTER_VALIDATE_INT);

if (!$idFactura) {
    http_response_code(400);
    exit('Factura inválida.');
}

$rolId = $_SESSION['rol_id'] ?? 0;

// Datos principales de la factura y del receptor.
$stmtFact = $conexion->prepare("
    SELECT 
        f.id,
        f.numeroFactura,
        f.tipoReceptor,
        f.idReceptor,
        f.idPago,
        f.metodoPago,
        f.noReferencia,
        f.observaciones,
        f.total,
        f.estado,
        f.fechaEmision,
        CASE f.tipoReceptor
            WHEN 'Estudiante' THEN CONCAT(u_est.nombre, ' ', u_est.apellido)
            WHEN 'Docente'    THEN CONCAT(u_doc.nombre, ' ', u_doc.apellido)
        END AS receptor_nombre,
        CASE f.tipoReceptor
            WHEN 'Estudiante' THEN u_est.correo
            WHEN 'Docente'    THEN u_doc.correo
        END AS receptor_correo,
        CASE f.tipoReceptor
            WHEN 'Estudiante' THEN est.telefono
            WHEN 'Docente'    THEN doc.telefono
        END AS receptor_telefono,
        CASE f.tipoReceptor
            WHEN 'Estudiante' THEN est.direccion
            WHEN 'Docente'    THEN doc.direccion
        END AS receptor_direccion,
        doc.especialidad
    FROM facturas f
    LEFT JOIN estudiantes est ON f.tipoReceptor = 'Estudiante' AND f.idReceptor = est.id
    LEFT JOIN usuarios u_est  ON est.usuario_id = u_est.id
    LEFT JOIN docentes doc    ON f.tipoReceptor = 'Docente'    AND f.idReceptor = doc.id
    LEFT JOIN usuarios u_doc  ON doc.usuario_id = u_doc.id
    WHERE f.id = ?
");
$stmtFact->bind_param('i', $idFactura);
$stmtFact->execute();
$factura = $stmtFact->get_result()->fetch_assoc();
$stmtFact->close();

if (!$factura) {
    http_response_code(404);
    exit('Factura no encontrada.');
}


if ($rolId == 2) {
    $stmtEst = $conexion->prepare("
        SELECT e.id FROM estudiantes e
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE u.correo = ?
    ");
    $stmtEst->bind_param('s', $_SESSION['usuario']);
    $stmtEst->execute();
    $estData = $stmtEst->get_result()->fetch_assoc();
    $stmtEst->close();

    if (!$estData || $factura['tipoReceptor'] !== 'Estudiante' || $factura['idReceptor'] != $estData['id']) {
        http_response_code(403);
        exit('No autorizado.');
    }
} elseif ($rolId == 3) {
    $stmtDoc = $conexion->prepare("
        SELECT d.id FROM docentes d
        INNER JOIN usuarios u ON d.usuario_id = u.id
        WHERE u.correo = ?
    ");
    $stmtDoc->bind_param('s', $_SESSION['usuario']);
    $stmtDoc->execute();
    $docData = $stmtDoc->get_result()->fetch_assoc();
    $stmtDoc->close();

    if (!$docData || $factura['tipoReceptor'] !== 'Docente' || $factura['idReceptor'] != $docData['id']) {
        http_response_code(403);
        exit('No autorizado.');
    }
}

// Lineas de detalle que alimentan la plantilla PDF.
$stmtDet = $conexion->prepare("
    SELECT tipoOrigen, descripcion, cantidad, precioUnitario, subtotal
    FROM detalle_facturas
    WHERE idFactura = ?
    ORDER BY id ASC
");
$stmtDet->bind_param('i', $idFactura);
$stmtDet->execute();
$detalles = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtDet->close();

date_default_timezone_set('America/El_Salvador');

$nombreArchivo = 'factura-' . $factura['numeroFactura'] . '.pdf';
$numeroFacturaVista = $factura['numeroFactura'];

// Renderiza la plantilla correspondiente al tipo de receptor.
if ($factura['tipoReceptor'] === 'Docente') {

    $facturaId     = $factura['numeroFactura'];
    $docente       = $factura['receptor_nombre'];
    $correo        = $factura['receptor_correo'];
    $telefono      = $factura['receptor_telefono'] ?? '';
    $direccion     = $factura['receptor_direccion'] ?? '';
    $especialidad  = $factura['especialidad'] ?? '';
    $metodoPago    = $factura['metodoPago'] ?? '';
    $condicion     = 'CONTADO';
    $referencia    = $factura['noReferencia'] ?? '';
    $observaciones = $factura['observaciones'] ?? '';
    $estado        = $factura['estado'];
    $fecha         = date('d/m/Y', strtotime($factura['fechaEmision']));
    $hora          = date('h:i A', strtotime($factura['fechaEmision']));
    $total         = $factura['total'];

    $items = array_map(fn($d) => [
        'tipo'        => 'Pago Docente',
        'descripcion' => $d['descripcion'],
        'periodo'     => '',
        'monto'       => $d['subtotal'],
    ], $detalles);

    ob_start();
    include __DIR__ . '/vista-factura-docente.php';
    $html = ob_get_clean();

} else {

    $pagoId      = 0;
    $estudiante  = $factura['receptor_nombre'];
    $correo      = $factura['receptor_correo'];
    $telefono    = $factura['receptor_telefono'] ?? '';
    $direccion   = $factura['receptor_direccion'] ?? '';
    $dui         = '';
    $metodoPago  = $factura['metodoPago'] ?? '';
    $estado      = $factura['estado'];
    $transaccion = $factura['noReferencia'] ?? '';
    $total       = $factura['total'];
    $fecha       = date('d/m/Y', strtotime($factura['fechaEmision']));
    $hora        = date('h:i A', strtotime($factura['fechaEmision']));
    $periodo     = !empty($detalles) ? ($detalles[0]['descripcion'] ?? '') : '';

    $items = array_map(fn($d) => [
        'tipo'        => $d['tipoOrigen'],
        'descripcion' => $d['descripcion'],
        'periodo'     => '',
        'monto'       => $d['subtotal'],
    ], $detalles);

    ob_start();
    include __DIR__ . '/vista-facturacion-electronica.php';
    $html = ob_get_clean();
}

// Genera el PDF y fuerza la descarga en el navegador.
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream($nombreArchivo, ['Attachment' => true]);
exit();
?>
