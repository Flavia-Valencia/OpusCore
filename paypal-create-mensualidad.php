<?php
// Genera una orden de pago en PayPal para cancelar una mensualidad pendiente.
// Consulta el monto correspondiente en la base de datos y valida que aún no esté pagada.
// Guarda temporalmente la información necesaria en sesión para completar la captura.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

require_once 'includes/conexion.php';
require_once 'includes/paypal-config.php';


$body = json_decode(file_get_contents('php://input'), true);
$mensualidadId = (int)($body['mensualidadId'] ?? 0);

if (!$mensualidadId) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensualidad inválida']);
    exit;
}


$stmt = $conexion->prepare("
    SELECT id, idEstudiante, monto, estado
    FROM mensualidades
    WHERE id = ?
");

$stmt->bind_param("i", $mensualidadId);
$stmt->execute();
$resultado = $stmt->get_result();
$mensualidad = $resultado->fetch_assoc();

if (!$mensualidad) {
    http_response_code(404);
    echo json_encode(['error' => 'Mensualidad no encontrada']);
    exit;
}

if ($mensualidad['estado'] === 'Pagado') {
    http_response_code(400);
    echo json_encode(['error' => 'Esta mensualidad ya fue pagada']);
    exit;
}

$idEstudiante = (int)$mensualidad['idEstudiante'];
$monto = number_format((float)$mensualidad['monto'], 2, '.', '');


$_SESSION['paypal_mensualidad'] = [
    'mensualidadId' => $mensualidadId,
    'idEstudiante' => $idEstudiante,
    'monto' => $monto
];


try {
    $token = paypalGetAccessToken();
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error autenticando PayPal']);
    exit;
}


$orderData = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'amount' => [
            'currency_code' => 'USD',
            'value' => $monto
        ],
        'description' => 'Pago de mensualidad'
    ]]
];

$ch = curl_init(PAYPAL_BASE_URL . '/v2/checkout/orders');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($orderData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode !== 201) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo crear orden PayPal',
        'paypal' => $result
    ]);
    exit;
}

echo json_encode([
    'id' => $result['id']
]);
?>