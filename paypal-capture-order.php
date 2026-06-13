<?php
// Captura el pago aprobado por el comprador en el popup de PayPal.
// Verifica que el estado sea COMPLETED y registra el pago + inscripciones + matrícula en la BD.
// Incluye envío de comprobante por correo.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['paypal_pending'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida o expirada']);
    exit;
}

require_once 'includes/conexion.php';
require_once 'includes/paypal-config.php';
 use Dompdf\Dompdf;
use Dompdf\Options;

// Lee el Order ID enviado por el SDK de PayPal tras la aprobacion.
$body    = json_decode(file_get_contents('php://input'), true);
$orderId = trim($body['orderID'] ?? '');

// Registra el metodo seleccionado para reflejarlo en pagos y comprobantes.
$metodoPago = strtolower(trim($body['metodoPago'] ?? 'paypal'));
$idMetodoPago = match ($metodoPago) {
    'tarjeta', 'card', 'credit' => 2,
    default   => 1,
};

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID no recibido']);
    exit;
}

// Obtiene token de PayPal y captura la orden aprobada.
try {
    $token = paypalGetAccessToken();
} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo autenticar con PayPal']);
    exit;
}

$ch = curl_init(PAYPAL_BASE_URL . "/v2/checkout/orders/{$orderId}/capture");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '{}',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$capture = json_decode($response, true);
$estadoPayPal = $capture['status'] ?? '';

// Verifica que PayPal haya respondido correctamente.
if ($httpCode !== 201) {
    http_response_code(402);
    echo json_encode(['error' => 'Error al comunicarse con PayPal. Código: ' . $httpCode]);
    exit;
}

// Recupera los datos guardados en sesion al crear la orden.
$pending      = $_SESSION['paypal_pending'];
$cursoIds     = $pending['cursoIds'];
$idPeriodo    = $pending['idPeriodo'];
$idEstudiante = $pending['idEstudiante'];
$totalCursos     = $pending['total'];
$yaPayoMatricula = $pending['yaPayoMatricula'] ?? false; // Indica si la matricula ya fue pagada en el periodo.

// Datos devueltos por PayPal tras capturar el pago.
$captureId   = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
// Prioriza la fuente real devuelta por PayPal para mantener consistentes pagos y comprobantes.
$paymentSource = $capture['payment_source'] ?? [];
if (isset($paymentSource['card'])) {
    $idMetodoPago = 2; // Tarjeta de Crédito/Débito
} elseif (isset($paymentSource['paypal']) && $idMetodoPago !== 2) {
    $idMetodoPago = 1; // PayPal
}
$nombreMetodoPago = match ($idMetodoPago) {
    2       => 'Tarjeta de Crédito/Débito',
    default => 'PayPal',
};
$payerEmail  = $capture['payer']['email_address'] ?? '';
$payerNombre = trim(
    ($capture['payer']['name']['given_name'] ?? '') . ' ' .
    ($capture['payer']['name']['surname']    ?? '')
);

// Cobra matricula solo si el estudiante aun no la ha pagado en este periodo.
$totalConMatricula = (float)$pending['total'];
// Obtiene datos del estudiante para comprobante y correo.
$stmtEstudiante = $conexion->prepare("
    SELECT u.correo, u.nombre, u.apellido 
    FROM usuarios u
    INNER JOIN estudiantes e ON e.usuario_id = u.id
    WHERE e.id = ?
");
$stmtEstudiante->bind_param('i', $idEstudiante);
$stmtEstudiante->execute();
$datosEstudiante = $stmtEstudiante->get_result()->fetch_assoc();
$stmtEstudiante->close();

$correoEstudiante = $datosEstudiante['correo'] ?? $payerEmail;
$nombreEstudiante = trim(($datosEstudiante['nombre'] ?? '') . ' ' . ($datosEstudiante['apellido'] ?? ''));
$costoMatricula    = $yaPayoMatricula ? 0 : 25.00;

$periodoNombre = "";
$stmtPeriodo = $conexion->prepare("SELECT nombre FROM PeriodoInscripcion WHERE id = ?");
$stmtPeriodo->bind_param('i', $idPeriodo);
$stmtPeriodo->execute();
$periodoNombre = $stmtPeriodo->get_result()->fetch_assoc()['nombre'] ?? 'Periodo actual';


$cursosDetalle = [];
$stmtCurso = $conexion->prepare("
    SELECT c.nombre, c.costoMensual,
           GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' - ', h.etiqueta) SEPARATOR ', ') AS horario,
           GROUP_CONCAT(DISTINCT a.aula SEPARATOR ', ') AS aula
    FROM cursos c
    LEFT JOIN CursoHorario ch ON c.id = ch.idCurso
    LEFT JOIN horarios h ON ch.idHorario = h.id
    LEFT JOIN aulas a ON ch.idAula = a.id
    WHERE c.id = ?
    GROUP BY c.id
");

foreach ($cursoIds as $idCurso) {
    $stmtCurso->bind_param('i', $idCurso);
    $stmtCurso->execute();
    $result = $stmtCurso->get_result()->fetch_assoc();
    
    $cursosDetalle[] = [
        'nombre' => $result['nombre'],
        'costo' => $result['costoMensual'],
        'horario' => $result['horario'] ?? 'No asignado',
        'aula' => $result['aula'] ?? 'N/A'
    ];
}

// Registra el pago y sus efectos academicos dentro de una transaccion.
$conexion->begin_transaction();

try {
    // Determina el estado interno segun la respuesta de PayPal.
    if ($estadoPayPal === 'COMPLETED') {
        $estadoBD = 'Completado';
        $pagoExitoso = true;
        
    } elseif ($estadoPayPal === 'PENDING') {
        $estadoBD = 'Procesando';
        $pagoExitoso = false;
        
    } else {
        $estadoBD = 'Fallido';
        $pagoExitoso = false;
    }
    
    // Inserta el registro principal del pago.
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (
            idEstudiante, 
            idMetodoPago, 
            monto, 
            idTransaccionPasarela, 
            estado
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmtPago->bind_param('iidss', $idEstudiante, $idMetodoPago, $totalConMatricula, $captureId, $estadoBD);
    $stmtPago->execute();
    $idPago = $conexion->insert_id;
    
    // Si el pago se completo, registra inscripciones, matricula y descuenta cupos.
    if ($pagoExitoso) {
        $stmtIns = $conexion->prepare("
            INSERT INTO inscripciones (idEstudiante, idCurso, idPeriodo, estado_academico)
            VALUES (?, ?, ?, 'Activo')
        ");
        
        foreach ($cursoIds as $idCurso) {
            $stmtIns->bind_param('iii', $idEstudiante, $idCurso, $idPeriodo);
            $stmtIns->execute();
            
            // Descuenta un cupo del curso inscrito.
            $conexion->query("UPDATE cursos SET cupos = cupos - 1 WHERE id = $idCurso AND cupos > 0");
        }
        
        // Registra matricula si aun no existia para este periodo.
        if (!$yaPayoMatricula) {
            $stmtMatricula = $conexion->prepare("
                INSERT INTO matricula (idEstudiante, idPeriodo, monto, estado)
                VALUES (?, ?, 25.00, 'Pagado')
                ON DUPLICATE KEY UPDATE estado = 'Pagado'
            ");
            $stmtMatricula->bind_param('ii', $idEstudiante, $idPeriodo);
            $stmtMatricula->execute();
        }

        // Genera la factura electronica asociada al pago.
         $anio = date('Y');
        $stmtUltima = $conexion->prepare("
            SELECT COUNT(*) AS total FROM facturas WHERE YEAR(fechaEmision) = ?
        ");
        $stmtUltima->bind_param('i', $anio);
        $stmtUltima->execute();
        $totalFacturas = $stmtUltima->get_result()->fetch_assoc()['total'] ?? 0;
        $stmtUltima->close();
        $numeroFactura = 'ADFE-' . $anio . '-' . str_pad($totalFacturas + 1, 6, '0', STR_PAD_LEFT);
         $stmtFactura = $conexion->prepare("
            INSERT INTO facturas 
                (numeroFactura, tipoFactura, idReceptor, tipoReceptor, idPago, 
                 metodoPago, noReferencia, observaciones, total, estado, generadoPor)
            VALUES (?, 'Estudiante', ?, 'Estudiante', ?, ?, ?, 'Pago registrado correctamente.', ?, 'Emitida', ?)
        ");

        $obsFactura  = 'Pago registrado correctamente.';
        $stmtFactura->bind_param(
            'siissdi',
            $numeroFactura,
            $idEstudiante,
            $idPago,
            $nombreMetodoPago,
            $captureId,
            $totalConMatricula,
            $idEstudiante
        );
        $stmtFactura->execute();
        $idFactura = $conexion->insert_id;
        $stmtFactura->close();

             $stmtDetalle = $conexion->prepare("
            INSERT INTO detalle_facturas 
                (idFactura, tipoOrigen, idOrigen, descripcion, cantidad, precioUnitario, subtotal)
            VALUES (?, 'Inscripcion', ?, ?, 1, ?, ?)
        ");
        foreach ($cursoIds as $idx => $idCurso) {
            $nombreCurso  = $cursosDetalle[$idx]['nombre'] ?? 'Curso';
            $costoCurso   = (float)($cursosDetalle[$idx]['costo'] ?? 0);
            $descDetalle  = 'Inscripción — ' . $nombreCurso . ' / ' . $periodoNombre;
            $stmtDetalle->bind_param('iisdd', $idFactura, $idCurso, $descDetalle, $costoCurso, $costoCurso);
            $stmtDetalle->execute();
        }
        $stmtDetalle->close();

        // Agrega el detalle de matricula solo si se cobro en este pago.
        if (!$yaPayoMatricula) {
            $stmtDetalleMatricula = $conexion->prepare("
                INSERT INTO detalle_facturas 
                    (idFactura, tipoOrigen, idOrigen, descripcion, cantidad, precioUnitario, subtotal)
                VALUES (?, 'Matricula', NULL, ?, 1, 25.00, 25.00)
            ");
            $descMatricula = 'Matrícula — ' . $periodoNombre;
            $stmtDetalleMatricula->bind_param('is', $idFactura, $descMatricula);
            $stmtDetalleMatricula->execute();
            $stmtDetalleMatricula->close();
        }
    // Envia comprobante y adjunta la factura generada.
       require_once 'includes/enviar-comprobante.php';

// Genera el PDF de la factura electronica para adjuntarlo al correo.
$pdfFactura = null;
try {

    // Variables esperadas por la vista de factura electronica.
    $pagoId      = $idPago;
    $estudiante  = $nombreEstudiante;
    $correo      = $correoEstudiante;
    $telefono    = $datosEstudiante['telefono'] ?? '';
    $direccion   = $datosEstudiante['direccion'] ?? '';
    $dui         = '';
    $metodoPago  = $nombreMetodoPago;
    $estado      = 'Emitida';
    $transaccion = $captureId;
    $total       = $totalConMatricula;
    date_default_timezone_set('America/El_Salvador');
    $fecha = date('d/m/Y');
    $hora  = date('h:i A');
    $periodo     = $periodoNombre;

    // Construye los items que mostrara la vista de factura.
    $items = [];
    foreach ($cursosDetalle as $c) {
        $items[] = [
            'tipo'        => 'Inscripción',
            'descripcion' => $c['nombre'],
            'periodo'     => $periodoNombre,
            'monto'       => $c['costo'],
        ];
    }
    if (!$yaPayoMatricula) {
        $items[] = [
            'tipo'        => 'Matrícula',
            'descripcion' => 'Matrícula',
            'periodo'     => $periodoNombre,
            'monto'       => 25.00,
        ];
    }

    ob_start();
    include __DIR__ . '/comprobantes/vista-facturacion-electronica.php';
    $htmlFactura = ob_get_clean();

    $optPdf = new Options();
    $optPdf->set('isHtml5ParserEnabled', true);
    $optPdf->set('isRemoteEnabled', true);
    $optPdf->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($optPdf);
    $dompdf->loadHtml($htmlFactura);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfFactura = $dompdf->output();

} catch (Throwable $e) {
    error_log('Error generando PDF factura estudiante: ' . $e->getMessage());
}

$datosCorreo = [
    'total'          => $totalConMatricula,
    'captureId'      => $captureId,
    'cantidadCursos' => count($cursoIds),
    'fecha'          => date('Y-m-d H:i:s'),
    'estado'         => 'Completado',
    'metodoPago'     => $nombreMetodoPago,
    'periodo_nombre' => $periodoNombre,
    'cursos'         => $cursosDetalle,
    'pdfFactura'     => $pdfFactura,         // PDF adjunto al correo.
    'numeroFactura'  => $numeroFactura,      // Numero usado para nombrar el archivo.
];

$resultado = enviarComprobante($correoEstudiante, $nombreEstudiante, $datosCorreo);
    }
    
    $conexion->commit();
    
} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar: ' . $e->getMessage()]);
    exit;
}

// Limpia la sesion temporal del pago.
unset($_SESSION['paypal_pending']);

// Respuesta final para actualizar la interfaz.
$mensaje = match($estadoBD) {
    'Completado' => 'Pago exitoso. Ya estás inscrito.',
    'Procesando' => 'Pago pendiente de confirmación. Recibirás un correo cuando se complete.',
    'Fallido' => 'El pago no fue procesado. Intentá de nuevo.',
    default => 'Estado desconocido'
};

echo json_encode([
    'success' => $pagoExitoso,
    'estado' => $estadoBD,
    'mensaje' => $mensaje,
    'captureId' => $captureId,
    'total' => $totalConMatricula,
    'totalCursos' => $totalCursos,
    'costoMatricula' => $costoMatricula,
    'cursos' => count($cursoIds),
    'idPago' => $idPago,
    'matricula' => $pagoExitoso ? 'Pagado' : 'Pendiente'
]);
?>
