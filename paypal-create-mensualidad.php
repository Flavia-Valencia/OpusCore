<?php
// Crea una orden PayPal para pagar una o múltiples mensualidades pendientes.
// Guarda en sesión los datos necesarios para completar la captura.
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

// Soporta tanto array "mensualidadIds" como id individual "mensualidadId"
$mensualidadIds = $body['mensualidadIds'] ?? [];
if (!is_array($mensualidadIds) && !empty($body['mensualidadId'])) {
    $mensualidadIds = [(int)$body['mensualidadId']];
}

if (empty($mensualidadIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensualidades inválidas']);
    exit;
}

$mensualidadIds = array_map('intval', $mensualidadIds);
$placeholders = implode(',', array_fill(0, count($mensualidadIds), '?'));
$types = str_repeat('i', count($mensualidadIds));

$stmt = $conexion->prepare("
    SELECT id, idEstudiante, monto, estado
    FROM mensualidades
    WHERE id IN ($placeholders)
");

$stmt->bind_param($types, ...$mensualidadIds);
$stmt->execute();
$resultado = $stmt->get_result();
$mensualidades = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (count($mensualidades) !== count($mensualidadIds)) {
    http_response_code(404);
    echo json_encode(['error' => 'Una o más mensualidades no fueron encontradas']);
    exit;
}

$montoTotal = 0.0;
$idEstudiante = null;

foreach ($mensualidades as $mensualidad) {
    if ($mensualidad['estado'] === 'Pagado') {
        http_response_code(400);
        echo json_encode(['error' => 'Una o más mensualidades ya fueron pagadas']);
        exit;
    }
    if ($idEstudiante === null) {
        $idEstudiante = (int)$mensualidad['idEstudiante'];
    } elseif ($idEstudiante !== (int)$mensualidad['idEstudiante']) {
        http_response_code(400);
        echo json_encode(['error' => 'Las mensualidades deben pertenecer al mismo estudiante']);
        exit;
    }
    $montoTotal += (float)$mensualidad['monto'];
}

$monto = number_format($montoTotal, 2, '.', '');

$_SESSION['paypal_mensualidad'] = [
    'mensualidadIds' => $mensualidadIds,
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
        'description' => 'Pago de mensualidad(es)'
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
