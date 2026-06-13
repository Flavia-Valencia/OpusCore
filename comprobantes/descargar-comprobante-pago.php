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
$cursos = [];
$tablaPagosExiste = $conexion->query("SHOW TABLES LIKE 'pagos'");

if ($tablaPagosExiste && $tablaPagosExiste->num_rows > 0) {
    $stmtPago = $conexion->prepare("
        SELECT p.id AS pago_id, p.idTransaccionPasarela, p.estado AS estado_pago, p.fechaPago,
               p.monto, mp.nombre AS metodo_pago, f.id AS factura_id
        FROM pagos p
        LEFT JOIN MetodosPago mp ON p.idMetodoPago = mp.id
        LEFT JOIN facturas f ON f.idPago = p.id
        WHERE p.id = ? AND p.idEstudiante = ?
        LIMIT 1
    ");

    if ($stmtPago) {
        $idEstudiante = (int) $estudiante['id'];
        $stmtPago->bind_param("ii", $pagoId, $idEstudiante);
        $stmtPago->execute();
        $pago = $stmtPago->get_result()->fetch_assoc();
        $stmtPago->close();
    }
}

if ($pago) {
    $facturaId = $pago['factura_id'];
    if ($facturaId) {
        $stmtCursos = $conexion->prepare("
            SELECT 
                df.descripcion AS descripcion,
                df.precioUnitario AS costo,
                CASE 
                    WHEN df.tipoOrigen = 'Inscripcion' THEN c_ins.nombre
                    WHEN df.tipoOrigen = 'Mensualidad' THEN CONCAT(c_men.nombre, ' (Mensualidad - ', m.mesPagado, ')')
                    WHEN df.tipoOrigen = 'Matricula' THEN 'Matrícula'
                    ELSE df.descripcion
                END AS nombre,
                CASE 
                    WHEN df.tipoOrigen = 'Inscripcion' THEN pi_ins.nombre
                    WHEN df.tipoOrigen = 'Mensualidad' THEN pi_men.nombre
                    ELSE ''
                END AS periodo_nombre,
                CASE 
                    WHEN df.tipoOrigen = 'Inscripcion' THEN COALESCE(GROUP_CONCAT(DISTINCT CONCAT(ch_ins.dia, ' - ', h_ins.etiqueta) SEPARATOR ', '), 'No asignado')
                    WHEN df.tipoOrigen = 'Mensualidad' THEN COALESCE(GROUP_CONCAT(DISTINCT CONCAT(ch_men.dia, ' - ', h_men.etiqueta) SEPARATOR ', '), 'No asignado')
                    ELSE 'No asignado'
                END AS horario,
                CASE 
                    WHEN df.tipoOrigen = 'Inscripcion' THEN COALESCE(GROUP_CONCAT(DISTINCT a_ins.aula SEPARATOR ', '), 'N/A')
                    WHEN df.tipoOrigen = 'Mensualidad' THEN COALESCE(GROUP_CONCAT(DISTINCT a_men.aula SEPARATOR ', '), 'N/A')
                    ELSE 'N/A'
                END AS aula
            FROM detalle_facturas df
            LEFT JOIN cursos c_ins ON (df.tipoOrigen = 'Inscripcion' AND df.idOrigen = c_ins.id)
            LEFT JOIN PeriodoInscripcion pi_ins ON c_ins.idPeriodo = pi_ins.id
            LEFT JOIN CursoHorario ch_ins ON c_ins.id = ch_ins.idCurso
            LEFT JOIN horarios h_ins ON ch_ins.idHorario = h_ins.id
            LEFT JOIN aulas a_ins ON ch_ins.idAula = a_ins.id
            
            LEFT JOIN mensualidades m ON (df.tipoOrigen = 'Mensualidad' AND df.idOrigen = m.id)
            LEFT JOIN cursos c_men ON m.idCurso = c_men.id
            LEFT JOIN PeriodoInscripcion pi_men ON m.idPeriodo = pi_men.id
            LEFT JOIN CursoHorario ch_men ON c_men.id = ch_men.idCurso
            LEFT JOIN horarios h_men ON ch_men.idHorario = h_men.id
            LEFT JOIN aulas a_men ON ch_men.idAula = a_men.id
            WHERE df.idFactura = ?
            GROUP BY df.id
        ");
        if ($stmtCursos) {
            $stmtCursos->bind_param("i", $facturaId);
            $stmtCursos->execute();
            $cursos = $stmtCursos->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtCursos->close();
        }
    }

    // Fallback retrocompatibilidad si no hay detalle_facturas
    if (empty($cursos)) {
        $stmtCursosFallback = $conexion->prepare("
            SELECT c.nombre, c.costoMensual AS costo,
                   pi.nombre AS periodo_nombre,
                   GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' - ', h.etiqueta) SEPARATOR ', ') AS horario,
                   GROUP_CONCAT(DISTINCT a.aula SEPARATOR ', ') AS aula
            FROM inscripciones i
            INNER JOIN cursos c ON i.idCurso = c.id
            INNER JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
            LEFT JOIN CursoHorario ch ON c.id = ch.idCurso
            LEFT JOIN horarios h ON ch.idHorario = h.id
            LEFT JOIN aulas a ON ch.idAula = a.id
            WHERE i.idEstudiante = ?
            GROUP BY c.id
        ");
        if ($stmtCursosFallback) {
            $idEstudiante = (int) $estudiante['id'];
            $stmtCursosFallback->bind_param("i", $idEstudiante);
            $stmtCursosFallback->execute();
            $cursos = $stmtCursosFallback->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtCursosFallback->close();
        }
    }
}

if (!$pago && $pagoId === 1) {
    $pago = [
        'pago_id' => 1,
        'idTransaccionPasarela' => 'PAY-DEMO-2026-0001',
        'estado_pago' => 'Completado',
        'fechaPago' => date('Y-m-d H:i:s'),
        'monto' => 20.00,
        'metodo_pago' => 'PayPal'
    ];
    $cursos = [[
        'nombre' => 'Diseño de Páginas Web',
        'periodo_nombre' => 'Periodo I - 2026',
        'horario' => 'Lunes y Miércoles, 8:00 AM - 10:00 AM',
        'aula' => 'Aula 11',
        'costo' => 20.00
    ]];
}

if (!$pago) {
    http_response_code(404);
    exit('Comprobante no encontrado.');
}

if (empty($cursos)) {
    $cursos = [[
        'nombre' => 'Curso no especificado',
        'periodo_nombre' => 'No especificado',
        'horario' => 'No asignado',
        'aula' => 'N/A',
        'costo' => (float)$pago['monto']
    ]];
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
$periodo = !empty($cursos) ? $cursos[0]['periodo_nombre'] : 'No especificado';
$fecha = $pago['fechaPago'] ? date('d/m/Y', strtotime($pago['fechaPago'])) : date('d/m/Y');
$hora = $pago['fechaPago'] ? date('h:i A', strtotime($pago['fechaPago'])) : date('h:i A');
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
