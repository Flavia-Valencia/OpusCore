<?php
// Captura el pago aprobado por el comprador en el popup de PayPal.
// Verifica que el estado sea COMPLETED y registra el pago + inscripciones en la BD.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['paypal_pending'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión inválida o expirada']);
    exit;
}

require_once 'includes/conexion.php';
require_once 'includes/paypal-config.php';

// Leer el Order ID que manda el SDK de PayPal tras la aprobación
$body    = json_decode(file_get_contents('php://input'), true);
$orderId = trim($body['orderID'] ?? '');

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID no recibido']);
    exit;
}

// Obtener token y capturar la orden
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

// Verificar que PayPal confirmó el pago como COMPLETED
if ($httpCode !== 201 || ($capture['status'] ?? '') !== 'COMPLETED') {
    http_response_code(402);
    echo json_encode(['error' => 'El pago no fue completado. Intentá de nuevo.']);
    exit;
}

// Recuperar datos guardados en sesión al crear la orden
$pending      = $_SESSION['paypal_pending'];
$cursoIds     = $pending['cursoIds'];
$idPeriodo    = $pending['idPeriodo'];
$idEstudiante = $pending['idEstudiante'];
$total        = $pending['total'];

// Datos que devuelve PayPal tras capturar
$captureId   = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
$payerEmail  = $capture['payer']['email_address'] ?? '';
$payerNombre = trim(
    ($capture['payer']['name']['given_name'] ?? '') . ' ' .
    ($capture['payer']['name']['surname']    ?? '')
);

// Registrar en BD con TRANSACCIÓN
$conexion->begin_transaction();
  // Insertar registro en tabla pagos
try {
  
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (
            idEstudiante, 
            idMetodoPago, 
            monto, 
            idTransaccionPasarela, 
            estado
        ) VALUES (?, 1, ?, ?, 'Completado')
    ");
    
    $stmtPago->bind_param('ids', $idEstudiante, $total, $captureId);
    $stmtPago->execute();
    $idPago = $conexion->insert_id; // Guarda por si después se necesita vincular
    
    // Insertar inscripciones
    $stmtIns = $conexion->prepare("
        INSERT INTO inscripciones (idEstudiante, idCurso, idPeriodo, estado_academico)
        VALUES (?, ?, ?, 'Activo')
    ");
    
    foreach ($cursoIds as $idCurso) {
        $stmtIns->bind_param('iii', $idEstudiante, $idCurso, $idPeriodo);
        $stmtIns->execute();
        
        // Descontar cupo del curso
        $conexion->query("UPDATE cursos SET cupos = cupos - 1 WHERE id = $idCurso AND cupos > 0");
    }
    
    $conexion->commit();
    
} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Pago recibido pero error al guardar: ' . $e->getMessage()]);
    exit;
}

// Limpiar datos temporales de la sesión
unset($_SESSION['paypal_pending']);

// Devolver éxito al frontend
echo json_encode([
    'success'      => true,
    'captureId'    => $captureId,
    'total'        => $total,
    'payerEmail'   => $payerEmail,
    'payerNombre'  => $payerNombre,
    'cursos'       => count($cursoIds),
    'idPago'       => $idPago
]);
?>