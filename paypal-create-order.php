<?php
// Crea una orden PayPal para los cursos seleccionados.
// Valida disponibilidad, cupos, duplicados y cobro de matricula.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require_once 'includes/conexion.php';
require_once 'includes/paypal-config.php';

// Valida los IDs de cursos enviados por la interfaz.
$body     = json_decode(file_get_contents('php://input'), true);
$cursoIds = array_map('intval', $body['cursos'] ?? []);

if (empty($cursoIds) || count($cursoIds) > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Seleccioná entre 1 y 5 cursos']);
    exit;
}

// Obtiene el periodo de inscripcion activo.
$periodoRes = $conexion->query("
    SELECT id FROM PeriodoInscripcion
    WHERE estado = 1 AND CURDATE() BETWEEN fechaInicio AND fechaFin
    LIMIT 1
");
$periodo = $periodoRes->fetch_assoc();

if (!$periodo) {
    http_response_code(400);
    echo json_encode(['error' => 'No hay periodo de inscripción activo']);
    exit;
}
$idPeriodo = $periodo['id'];

// Valida que los cursos seleccionados existan, pertenezcan al periodo y tengan cupos.
$placeholders = implode(',', array_fill(0, count($cursoIds), '?'));
$types        = str_repeat('i', count($cursoIds));

$stmt = $conexion->prepare("
    SELECT id, nombre, costoMensual
    FROM cursos
    WHERE id IN ($placeholders)
      AND estado = 1
      AND idPeriodo = ?
      AND cupos > 0
");
$params = array_merge($cursoIds, [$idPeriodo]);
$stmt->bind_param($types . 'i', ...$params);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (count($cursos) !== count($cursoIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Uno o más cursos no están disponibles']);
    exit;
}

// Evita que el estudiante pague cursos en los que ya esta inscrito.
$correo  = $_SESSION['usuario'];
$stmtEst = $conexion->prepare("
    SELECT e.id FROM estudiantes e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.correo = ?
");
$stmtEst->bind_param('s', $correo);
$stmtEst->execute();
$estudiante   = $stmtEst->get_result()->fetch_assoc();
$idEstudiante = $estudiante['id'];

foreach ($cursoIds as $idCurso) {
    $stmtDup = $conexion->prepare("
        SELECT id FROM inscripciones
        WHERE idEstudiante = ? AND idCurso = ? AND idPeriodo = ?
    ");
    $stmtDup->bind_param('iii', $idEstudiante, $idCurso, $idPeriodo);
    $stmtDup->execute();
    if ($stmtDup->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Ya estás inscrito en uno de los cursos seleccionados']);
        exit;
    }
}

// Verifica si el estudiante ya pago matricula en este periodo.
$stmtMat = $conexion->prepare("
    SELECT id FROM matricula 
    WHERE idEstudiante = ? AND idPeriodo = ? AND estado = 'Pagado'
");
$stmtMat->bind_param('ii', $idEstudiante, $idPeriodo);
$stmtMat->execute();
$yaPayoMatricula = $stmtMat->get_result()->num_rows > 0;

// Calcula el total e incluye matricula solo si corresponde.
$totalCursos = array_sum(array_column($cursos, 'costoMensual'));
$total = number_format($totalCursos + ($yaPayoMatricula ? 0 : 25.00), 2, '.', '');

// Guarda datos en sesion para capturar el pago sin recalcular.
$_SESSION['paypal_pending'] = [
    'cursoIds'        => $cursoIds,
    'idPeriodo'       => $idPeriodo,
    'idEstudiante'    => $idEstudiante,
    'total'           => $total,
    'yaPayoMatricula' => $yaPayoMatricula, // Evita cobrar matricula dos veces.
];

// Construye los items que PayPal mostrara al comprador.
$items = [];
foreach ($cursos as $c) {
    $items[] = [
        'name'        => $c['nombre'],
        'unit_amount' => ['currency_code' => 'USD', 'value' => number_format($c['costoMensual'], 2, '.', '')],
        'quantity'    => '1',
    ];
}

// Agrega matricula como item solo si aun no fue pagada.
if (!$yaPayoMatricula) {
    $items[] = [
        'name'        => 'Matrícula',
        'unit_amount' => ['currency_code' => 'USD', 'value' => '25.00'],
        'quantity'    => '1',
    ];
}

// Obtiene token y crea la orden en PayPal.
try {
    $token = paypalGetAccessToken();
} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudo conectar con PayPal']);
    exit;
}

$orderPayload = [
    'intent'         => 'CAPTURE',
    'purchase_units' => [[
        'description' => 'Inscripción — Academia Futuro Digital',
        'amount'      => [
            'currency_code' => 'USD',
            'value'         => $total,
            'breakdown'     => ['item_total' => ['currency_code' => 'USD', 'value' => $total]],
        ],
        'items' => $items,
    ]],
    'application_context' => [
        'brand_name'  => 'Academia Futuro Digital',
        'locale'      => 'es-SV',
        'user_action' => 'PAY_NOW',
    ],
];

$ch = curl_init(PAYPAL_BASE_URL . '/v2/checkout/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($orderPayload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 201) {
    http_response_code(502);
    echo json_encode(['error' => 'Error al crear orden en PayPal']);
    exit;
}

echo json_encode(['id' => json_decode($response, true)['id']]);
?>
