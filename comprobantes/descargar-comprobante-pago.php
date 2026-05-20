<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit();
}

$pagoId = filter_input(INPUT_GET, 'pago_id', FILTER_VALIDATE_INT);

if (!$pagoId) {
    http_response_code(400);
    exit('Pago inválido.');
}

require_once __DIR__ . '/../includes/conexion.php';

// Se mantiene la validación: el pago debe pertenecer al usuario en sesión.
$stmtVerificar = $conexion->prepare("
    SELECT p.id
    FROM pagos p
    INNER JOIN estudiantes e ON p.idEstudiante = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE p.id = ?
      AND u.correo = ?
    LIMIT 1
");
$stmtVerificar->bind_param('is', $pagoId, $_SESSION["usuario"]);
$stmtVerificar->execute();
$pagoPermitido = $stmtVerificar->get_result()->fetch_assoc();
$stmtVerificar->close();

if (!$pagoPermitido) {
    http_response_code(404);
    exit('Comprobante no encontrado.');
}

// Cambio: ahora el PDF usa Dompdf y la plantilla HTML del comprobante de pago.
require_once __DIR__ . '/../includes/dompdf/autoload.inc.php';

// Cambio: se renderiza la vista con sus datos reales del pago.
ob_start();
include __DIR__ . '/vista-comprobante-pago.php';
$html = ob_get_clean();

// Cambio: se inserta un CSS simple compatible con Dompdf para el comprobante.
$cssPath = __DIR__ . '/../css/styleComprobantePdf.css';
if (is_readable($cssPath)) {
    $css = file_get_contents($cssPath);
    $html = preg_replace(
        '/<link\s+rel="stylesheet"\s+href="\.\.\/css\/styleComprobante\.css"\s*>/i',
        '<style>' . $css . '</style>',
        $html
    );
}

// Eliminado: generación manual del PDF con texto plano y objetos PDF.
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->setChroot(realpath(__DIR__ . '/..'));

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nombreArchivo = 'comprobante-pago-' . str_pad((string)$pagoId, 6, '0', STR_PAD_LEFT) . '.pdf';

$dompdf->stream($nombreArchivo, ['Attachment' => true]);
exit();
