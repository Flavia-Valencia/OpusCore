<?php
// Procesa la confirmación del pago aprobado desde PayPal.
// Soporta pago de múltiples mensualidades en una sola transacción.
// Verifica que la orden se haya completado correctamente, registra el pago,
// actualiza cada mensualidad como pagada, genera la factura con detalles
// individuales y envía el comprobante consolidado por correo.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

if (!isset($_SESSION['paypal_mensualidad'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay pago pendiente en sesión']);
    exit;
}

require_once 'includes/conexion.php';
require_once 'includes/paypal-config.php';

$body = json_decode(file_get_contents('php://input'), true);
$orderId = trim($body['orderID'] ?? '');
$metodoPago = strtolower(trim($body['metodoPago'] ?? 'paypal'));
$idMetodoPago = match ($metodoPago) {
    'tarjeta', 'card', 'credit' => 2,
    default => 1,
};

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID no recibido']);
    exit;
}

try {
    $token = paypalGetAccessToken();
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error autenticando con PayPal']);
    exit;
}

$ch = curl_init(PAYPAL_BASE_URL . "/v2/checkout/orders/{$orderId}/capture");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '{}',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode !== 201 || ($result['status'] ?? '') !== 'COMPLETED') {
    http_response_code(400);
    echo json_encode([
        'error' => 'Pago no completado',
        'paypal' => $result
    ]);
    exit;
}

$pending = $_SESSION['paypal_mensualidad'];
$mensualidadIds = $pending['mensualidadIds'] ?? [];
$idEstudiante = (int)($pending['idEstudiante'] ?? 0);
$monto = (float)($pending['monto'] ?? 0);

// Compatibilidad: si todavía viene un ID único, conviértelo a array
if (empty($mensualidadIds) && isset($pending['mensualidadId'])) {
    $mensualidadIds = [(int)$pending['mensualidadId']];
}

if (empty($mensualidadIds) || !$idEstudiante) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos de sesión inválidos']);
    exit;
}

$captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

// Detecta la fuente real del pago para registrar PayPal o tarjeta correctamente.
$paymentSource = $result['payment_source'] ?? [];
if (isset($paymentSource['card'])) {
    $idMetodoPago = 2;
} elseif (isset($paymentSource['paypal']) && $idMetodoPago !== 2) {
    $idMetodoPago = 1;
}
$nombreMetodoPago = match ($idMetodoPago) {
    2       => 'Tarjeta de Crédito/Débito',
    default => 'PayPal',
};

// ── Obtiene datos de cada mensualidad para el comprobante ───────────────────
// Se hace antes de la transacción para evitar bloqueos prolongados en la BD
$placeholders = implode(',', array_fill(0, count($mensualidadIds), '?'));
$types = str_repeat('i', count($mensualidadIds));

$stmtMens = $conexion->prepare("
    SELECT m.id, c.nombre AS curso_nombre, c.costoMensual,
           pi.nombre AS periodo_nombre,
           m.mesPagado,
           GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' - ', h.etiqueta) SEPARATOR ', ') AS horario,
           GROUP_CONCAT(DISTINCT a.aula SEPARATOR ', ') AS aula
    FROM mensualidades m
    INNER JOIN cursos c ON m.idCurso = c.id
    INNER JOIN PeriodoInscripcion pi ON m.idPeriodo = pi.id
    LEFT JOIN CursoHorario ch ON c.id = ch.idCurso
    LEFT JOIN horarios h ON ch.idHorario = h.id
    LEFT JOIN aulas a ON ch.idAula = a.id
    WHERE m.id IN ($placeholders)
    GROUP BY m.id
");
$stmtMens->bind_param($types, ...$mensualidadIds);
$stmtMens->execute();
$datosMensualidades = $stmtMens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMens->close();

$periodoNombre = $datosMensualidades[0]['periodo_nombre'] ?? 'Periodo actual';

$conexion->begin_transaction();

try {
    // 1) Registra el pago global
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (
            idEstudiante,
            idMetodoPago,
            monto,
            idTransaccionPasarela,
            estado
        ) VALUES (?, ?, ?, ?, 'Completado')
    ");
    $stmtPago->bind_param(
        "iids",
        $idEstudiante,
        $idMetodoPago,
        $monto,
        $captureId
    );
    $stmtPago->execute();
    $idPago = $conexion->insert_id;
    $stmtPago->close();

    // 2) Marca cada mensualidad seleccionada como 'Pagado'
    $stmtMensualidad = $conexion->prepare("
        UPDATE mensualidades
        SET estado = 'Pagado'
        WHERE id = ?
    ");
    foreach ($mensualidadIds as $mId) {
        $stmtMensualidad->bind_param("i", $mId);
        $stmtMensualidad->execute();
    }
    $stmtMensualidad->close();

    // 3) Genera factura única
    $anio = date('Y');
    $stmtUltima = $conexion->prepare("
        SELECT COUNT(*) AS total FROM facturas WHERE YEAR(fechaEmision) = ?
    ");
    $stmtUltima->bind_param('i', $anio);
    $stmtUltima->execute();
    $totalFacturas = $stmtUltima->get_result()->fetch_assoc()['total'] ?? 0;
    $stmtUltima->close();
    $numeroFactura = 'ADFE-' . $anio . '-' . str_pad($totalFacturas + 1, 6, '0', STR_PAD_LEFT);

    $nombreMetodoPagoFact = match ($idMetodoPago) {
        2       => 'Tarjeta de Crédito/Débito',
        default => 'PayPal',
    };

    $observaciones = count($mensualidadIds) > 1 
        ? 'Pago de ' . count($mensualidadIds) . ' mensualidades.' 
        : 'Pago de mensualidad.';

    $stmtFact = $conexion->prepare("
        INSERT INTO facturas 
            (numeroFactura, tipoFactura, idReceptor, tipoReceptor, idPago,
             metodoPago, noReferencia, observaciones, total, estado, generadoPor)
        VALUES (?, 'Estudiante', ?, 'Estudiante', ?, ?, ?, ?, ?, 'Emitida', ?)
    ");
    $stmtFact->bind_param(
        'siisssdi',
        $numeroFactura,
        $idEstudiante,
        $idPago,          
        $nombreMetodoPagoFact,
        $captureId,
        $observaciones,
        $monto,
        $idEstudiante
    );
    $stmtFact->execute();
    $idFactura = $conexion->insert_id;
    $stmtFact->close();

    // 4) Inserta los detalles individuales para cada mensualidad en la factura
    $stmtDetFact = $conexion->prepare("
        INSERT INTO detalle_facturas
            (idFactura, tipoOrigen, idOrigen, descripcion, cantidad, precioUnitario, subtotal)
        VALUES (?, 'Mensualidad', ?, ?, 1, ?, ?)
    ");
    foreach ($datosMensualidades as $dm) {
        $mId = (int)$dm['id'];
        $descMens = ($dm['curso_nombre'] ?? 'Curso') . ' — ' . ($dm['mesPagado'] ?? '') . ' / ' . ($dm['periodo_nombre'] ?? '');
        $precioUnit = (float)($dm['costoMensual'] ?? 0);
        $stmtDetFact->bind_param('iisdd', $idFactura, $mId, $descMens, $precioUnit, $precioUnit);
        $stmtDetFact->execute();
    }
    $stmtDetFact->close();

    $conexion->commit();

} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode([
        'error' => 'Error guardando pago: ' . $e->getMessage()
    ]);
    exit;
}

// Obtiene datos del estudiante para enviar el comprobante.
$stmtDatos = $conexion->prepare("
    SELECT u.correo, u.nombre, u.apellido
    FROM usuarios u
    INNER JOIN estudiantes e ON e.usuario_id = u.id
    WHERE e.id = ?
");
$stmtDatos->bind_param('i', $idEstudiante);
$stmtDatos->execute();
$datosEst = $stmtDatos->get_result()->fetch_assoc();
$stmtDatos->close();

// Envia el comprobante de pago por correo.
require_once 'includes/enviar-comprobante.php';

$cursosCorreo = [];
foreach ($datosMensualidades as $dm) {
    $cursosCorreo[] = [
        'nombre'  => ($dm['curso_nombre'] ?? 'Curso') . ' — ' . ($dm['mesPagado'] ?? ''),
        'costo'   => (float)($dm['costoMensual'] ?? 0),
        'horario' => $dm['horario'] ?? 'No asignado',
        'aula'    => $dm['aula'] ?? 'N/A',
    ];
}

$datosCorreo = [
    'total'          => $monto,
    'captureId'      => $captureId,
    'estado'         => 'Completado',
    'metodoPago'     => $nombreMetodoPago,
    'periodo_nombre' => $periodoNombre,
    'cursos'         => $cursosCorreo,
];

$nombreEst = trim(($datosEst['nombre'] ?? '') . ' ' . ($datosEst['apellido'] ?? ''));
enviarComprobante($datosEst['correo'] ?? '', $nombreEst, $datosCorreo);

unset($_SESSION['paypal_mensualidad']);

echo json_encode([
    'success' => true,
    'mensaje' => 'Pago realizado correctamente'
]);
?>
