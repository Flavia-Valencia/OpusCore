<?php
// Procesa la confirmación del pago aprobado desde PayPal.
// Verifica que la orden se haya completado correctamente y registra la transacción en la BD.
// Actualiza la mensualidad como pagada y finaliza el flujo limpiando la sesión temporal.
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

$mensualidadId = (int)$pending['mensualidadId'];
$idEstudiante = (int)$pending['idEstudiante'];
$monto = (float)$pending['monto'];

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

$conexion->begin_transaction();

try {

    // Registra el pago de la mensualidad.
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
    $idPago = $conexion->insert_id; // ID del pago recien registrado.

    // Marca la mensualidad como pagada.
    $stmtMensualidad = $conexion->prepare("
        UPDATE mensualidades
        SET estado = 'Pagado'
        WHERE id = ?
    ");

    $stmtMensualidad->bind_param("i", $mensualidadId);
    $stmtMensualidad->execute();

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

// Obtiene datos de la mensualidad para el comprobante.
$stmtMens = $conexion->prepare("
    SELECT c.nombre AS curso_nombre, c.costoMensual,
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
    WHERE m.id = ?
    GROUP BY m.id
");
$stmtMens->bind_param('i', $mensualidadId);
$stmtMens->execute();
$datosMens = $stmtMens->get_result()->fetch_assoc();
$stmtMens->close();

// Genera la factura electronica de la mensualidad.

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

        $stmtFact = $conexion->prepare("
            INSERT INTO facturas 
                (numeroFactura, tipoFactura, idReceptor, tipoReceptor, idPago,
                 metodoPago, noReferencia, observaciones, total, estado, generadoPor)
            VALUES (?, 'Estudiante', ?, 'Estudiante', ?, ?, ?, 'Pago de mensualidad.', ?, 'Emitida', ?)
        ");
        $stmtFact->bind_param(
            'siissdi',
            $numeroFactura,
            $idEstudiante,
            $idPago,          
            $nombreMetodoPagoFact,
            $captureId,
            $monto,
            $idEstudiante
        );
        $stmtFact->execute();
        $idFactura = $conexion->insert_id;
        $stmtFact->close();

        $stmtDetFact = $conexion->prepare("
            INSERT INTO detalle_facturas
                (idFactura, tipoOrigen, idOrigen, descripcion, cantidad, precioUnitario, subtotal)
            VALUES (?, 'Mensualidad', ?, ?, 1, ?, ?)
        ");
        $descMens = ($datosMens['curso_nombre'] ?? 'Curso') . ' — ' . ($datosMens['mesPagado'] ?? '') . ' / ' . ($datosMens['periodo_nombre'] ?? '');
        $stmtDetFact->bind_param('iisdd', $idFactura, $mensualidadId, $descMens, $monto, $monto);
        $stmtDetFact->execute();
        $stmtDetFact->close();
          // Finaliza el detalle de la factura electronica.

// Envia el comprobante de pago por correo.
require_once 'includes/enviar-comprobante.php';

$datosCorreo = [
    'total'          => $monto,
    'captureId'      => $captureId,
    'estado'         => 'Completado',
    'metodoPago'     => $nombreMetodoPago,
    'periodo_nombre' => $datosMens['periodo_nombre'] ?? 'Periodo actual',
    'cursos'         => [[
        'nombre'  => ($datosMens['curso_nombre'] ?? 'Curso') . ' — ' . ($datosMens['mesPagado'] ?? ''),
        'costo'   => $monto,
        'horario' => $datosMens['horario'] ?? 'No asignado',
        'aula'    => $datosMens['aula'] ?? 'N/A',
    ]],
];

$nombreEst = trim(($datosEst['nombre'] ?? '') . ' ' . ($datosEst['apellido'] ?? ''));
enviarComprobante($datosEst['correo'] ?? '', $nombreEst, $datosCorreo);

unset($_SESSION['paypal_mensualidad']);

echo json_encode([
    'success' => true,
    'mensaje' => 'Pago realizado correctamente'
]);
?>
