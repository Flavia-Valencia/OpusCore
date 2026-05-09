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

$conexion->begin_transaction();

try {

    // Registrar pago
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (
            idEstudiante,
            idMetodoPago,
            monto,
            idTransaccionPasarela,
            estado
        ) VALUES (?, 1, ?, ?, 'Completado')
    ");

    $stmtPago->bind_param(
        "ids",
        $idEstudiante,
        $monto,
        $captureId
    );

    $stmtPago->execute();

    // Actualizar mensualidad
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

unset($_SESSION['paypal_mensualidad']);

echo json_encode([
    'success' => true,
    'mensaje' => 'Pago realizado correctamente'
]);
?>