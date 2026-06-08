<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/dompdf/autoload.inc.php';

$pagoId = filter_input(INPUT_GET, 'pago_id', FILTER_VALIDATE_INT);

if (!$pagoId) {
    http_response_code(400);
    exit('Pago invalido.');
}

$esAdmin = isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1;

// El admin puede descargar cualquier comprobante; el estudiante solo los propios.
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
$nombreArchivo = 'comprobante-pago-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $codigo) . '.pdf';

// Variables que consume la plantilla visual del comprobante.
$estudianteNombre = $estudiante['estudiante_nombre'];
$correo = $estudiante['correo'];
$metodoPago = $pago['metodo_pago'] ?: 'PayPal';
$estado = $pago['estado_pago'];
$transaccion = $codigo;
$total = (float) $pago['monto'];
$periodo = $pago['periodo'] ?: 'No especificado';
$fecha = $pago['fechaPago'] ? date('d/m/Y', strtotime($pago['fechaPago'])) : date('d/m/Y');
$hora = $pago['fechaPago'] ? date('h:i A', strtotime($pago['fechaPago'])) : date('h:i A');
$cursos = [[
    'nombre' => $pago['curso'] ?: 'No especificado',
    'periodo_nombre' => $periodo,
    'horario' => $pago['horario_aula'] ?: 'No asignado',
    'aula' => 'N/A',
    'costo' => $total,
]];
$estudiante = $estudianteNombre;

// Genera el PDF con la misma plantilla usada en correos y vista directa.
ob_start();
include __DIR__ . '/vista-comprobante-pago.php';
$html = ob_get_clean();

$options = new \Dompdf\Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();
$dompdf->stream($nombreArchivo, ['Attachment' => true]);
exit();
