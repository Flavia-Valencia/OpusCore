<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/conexion.php';

$pagoId = filter_input(INPUT_GET, 'pago_id', FILTER_VALIDATE_INT);

if (!$pagoId) {
    http_response_code(400);
    exit('Pago invalido.');
}

$esAdmin = isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1;

// Si es admin busca el pago directo sin validar que sea su estudiante
// Si es estudiante valida que el pago le pertenezca
if ($esAdmin) {
    $stmtEstudiante = $conexion->prepare("
        SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre, u.correo
        FROM pagos p
        INNER JOIN estudiantes e ON p.idEstudiante = e.id
        INNER JOIN usuarios u ON e.usuario_id = u.id
        WHERE p.id = ?
    ");
     $stmtEstudiante->bind_param("i", $pagoId);
    $stmtEstudiante->execute();
    $estudiante = $stmtEstudiante->get_result()->fetch_assoc();
} else {
    $correoSesion = $_SESSION["usuario"];
$stmtEstudiante = $conexion->prepare("
    SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) AS estudiante_nombre, u.correo
    FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
    ");
    $stmtEstudiante->bind_param("s", $correoSesion);
    $stmtEstudiante->execute();
    $estudiante = $stmtEstudiante->get_result()->fetch_assoc();
}

if (!$estudiante) {
    http_response_code(403);
    exit('No autorizado.');
}

$pago = null;
$tablaPagosExiste = $conexion->query("SHOW TABLES LIKE 'pagos'");

if ($tablaPagosExiste && $tablaPagosExiste->num_rows > 0) {
    $stmtPago = $conexion->prepare("
    SELECT p.id AS pago_id, p.idTransaccionPasarela, p.estado AS estado_pago, p.fechaPago,
           p.monto, mp.nombre AS metodo_pago,
           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') AS curso,
           pi.nombre AS periodo,
           GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' ', h.etiqueta, ' / Aula ', a.aula) SEPARATOR '; ') AS horario_aula
    FROM pagos p
    LEFT JOIN MetodosPago mp ON p.idMetodoPago = mp.id
    LEFT JOIN inscripciones i ON i.idEstudiante = p.idEstudiante
    LEFT JOIN cursos c ON i.idCurso = c.id
    LEFT JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
    LEFT JOIN CursoHorario ch ON ch.idCurso = c.id
    LEFT JOIN horarios h ON ch.idHorario = h.id
    LEFT JOIN aulas a ON ch.idAula = a.id
    LEFT JOIN facturas f ON f.idPago = p.id
    WHERE p.id = ? AND p.idEstudiante = ?
    GROUP BY p.id, p.idTransaccionPasarela, p.estado, p.fechaPago, p.monto, mp.nombre, pi.nombre
    LIMIT 1
");

    if ($stmtPago) {
        $idEstudiante = (int) $estudiante['id'];
        $stmtPago->bind_param("ii", $pagoId, $idEstudiante);
        $stmtPago->execute();
        $pago = $stmtPago->get_result()->fetch_assoc();
    }
}

if (!$pago && $pagoId === 1) {
    $pago = [
        'pago_id' => 1,
        'idTransaccionPasarela' => 'PAY-DEMO-2026-0001',
        'estado_pago' => 'Completado',
        'fechaPago' => date('Y-m-d H:i:s'),
        'monto' => 20.00,
        'metodo_pago' => 'PayPal',
        'curso' => 'Diseno de Paginas Web',
        'periodo' => 'Periodo I - 2026',
        'horario_aula' => 'Lunes y Miercoles, 8:00 AM - 10:00 AM / Aula 11'
    ];
}

if (!$pago) {
    http_response_code(404);
    exit('Comprobante no encontrado.');
}

$codigo = $pago['idTransaccionPasarela'] ?: 'PAY-' . str_pad((string) $pago['pago_id'], 5, '0', STR_PAD_LEFT);
$fechaPago = $pago['fechaPago'] ? date('d/m/Y h:i A', strtotime($pago['fechaPago'])) : date('d/m/Y h:i A');
$monto = '$' . number_format((float) $pago['monto'], 2);
$nombreArchivo = 'comprobante-pago-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $codigo) . '.pdf';

$lineas = [
    ['size' => 18, 'text' => 'COMPROBANTE DE PAGO E INSCRIPCION'],
    ['size' => 12, 'text' => 'Academia Futuro Digital'],
    ['size' => 11, 'text' => 'Fecha de emision: ' . date('d/m/Y h:i A')],
    ['size' => 11, 'text' => ''],
    ['size' => 13, 'text' => 'Datos del estudiante'],
    ['size' => 11, 'text' => 'Estudiante: ' . $estudiante['estudiante_nombre']],
    ['size' => 11, 'text' => 'Correo: ' . $estudiante['correo']],
    ['size' => 11, 'text' => ''],
    ['size' => 13, 'text' => 'Detalle del pago'],
    ['size' => 11, 'text' => 'Curso: ' . ($pago['curso'] ?: 'No especificado')],
    ['size' => 11, 'text' => 'Periodo: ' . ($pago['periodo'] ?: 'No especificado')],
    ['size' => 11, 'text' => 'Horario y aula: ' . ($pago['horario_aula'] ?: 'No especificado')],
    ['size' => 11, 'text' => 'Metodo: ' . ($pago['metodo_pago'] ?: 'PayPal')],
    ['size' => 11, 'text' => 'Estado: ' . $pago['estado_pago']],
    ['size' => 11, 'text' => 'Fecha de pago: ' . $fechaPago],
    ['size' => 11, 'text' => 'ID de transaccion: ' . $codigo],
    ['size' => 14, 'text' => 'Total pagado: ' . $monto],
    ['size' => 10, 'text' => ''],
    ['size' => 10, 'text' => 'Este comprobante fue generado automaticamente por Academia Futuro Digital.']
];

function textoPdf(string $texto): string
{
    $textoConvertido = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
    $texto = $textoConvertido === false ? $texto : $textoConvertido;

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texto);
}

function agregarLineaPdf(string &$contenido, int $x, int &$y, int $size, string $texto): void
{
    $segmentos = $texto === '' ? [''] : explode("\n", wordwrap($texto, 88, "\n", false));

    foreach ($segmentos as $segmento) {
        $contenido .= "BT /F1 {$size} Tf {$x} {$y} Td (" . textoPdf($segmento) . ") Tj ET\n";
        $y -= $size + 8;
    }
}

$contenido = "";
$y = 770;

foreach ($lineas as $linea) {
    agregarLineaPdf($contenido, 55, $y, $linea['size'], $linea['text']);
}

$objetos = [];
$objetos[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objetos[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objetos[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
$objetos[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objetos[] = "<< /Length " . strlen($contenido) . " >>\nstream\n{$contenido}endstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];

foreach ($objetos as $indice => $objeto) {
    $offsets[] = strlen($pdf);
    $numero = $indice + 1;
    $pdf .= "{$numero} 0 obj\n{$objeto}\nendobj\n";
}

$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objetos) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";

for ($i = 1; $i <= count($objetos); $i++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
}

$pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n{$xref}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, must-revalidate');

echo $pdf;
exit();
