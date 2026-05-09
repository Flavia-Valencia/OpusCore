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
$estadoPayPal = $capture['status'] ?? '';

// Verificar que la respuesta de PayPal fue exitosa
if ($httpCode !== 201) {
    http_response_code(402);
    echo json_encode(['error' => 'Error al comunicarse con PayPal. Código: ' . $httpCode]);
    exit;
}

// Recuperar datos guardados en sesión al crear la orden
$pending      = $_SESSION['paypal_pending'];
$cursoIds     = $pending['cursoIds'];
$idPeriodo    = $pending['idPeriodo'];
$idEstudiante = $pending['idEstudiante'];
$totalCursos     = $pending['total'];
$yaPayoMatricula = $pending['yaPayoMatricula'] ?? false; // ← nuevo

// Datos que devuelve PayPal tras capturar
$captureId   = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
// Detectar método de pago desde la respuesta de PayPal
$paymentSource = $capture['payment_source'] ?? [];
if (isset($paymentSource['card'])) {
    $idMetodoPago = 2; // Tarjeta de Crédito/Débito
} else {
    $idMetodoPago = 1; // PayPal
}
$payerEmail  = $capture['payer']['email_address'] ?? '';
$payerNombre = trim(
    ($capture['payer']['name']['given_name'] ?? '') . ' ' .
    ($capture['payer']['name']['surname']    ?? '')
);

// Solo cobrar matrícula si no la ha pagado
$totalConMatricula = (float)$pending['total'];
// Obtener correo y nombre del estudiante desde la BD
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

// Registrar en BD con TRANSACCIÓN
$conexion->begin_transaction();

try {
    // Determinar estado según respuesta de PayPal
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
    
    // Insertar registro en tabla pagos
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
    
    // Solo si el pago fue COMPLETADO, se registran inscripciones, matrícula y se descuentan cupos
    if ($pagoExitoso) {
        $stmtIns = $conexion->prepare("
            INSERT INTO inscripciones (idEstudiante, idCurso, idPeriodo, idFactura, estado_academico)
            VALUES (?, ?, ?, ?, 'Activo')
        ");
        
        foreach ($cursoIds as $idCurso) {
            $stmtIns->bind_param('iiii', $idEstudiante, $idCurso, $idPeriodo, $idPago);
            $stmtIns->execute();
            
            // Descontar cupo
            $conexion->query("UPDATE cursos SET cupos = cupos - 1 WHERE id = $idCurso AND cupos > 0");
        }
        
        // Solo registrar matrícula si no la había pagado
        if (!$yaPayoMatricula) {
            $stmtMatricula = $conexion->prepare("
                INSERT INTO matricula (idEstudiante, idPeriodo, monto, estado)
                VALUES (?, ?, 25.00, 'Pagado')
                ON DUPLICATE KEY UPDATE estado = 'Pagado'
            ");
            $stmtMatricula->bind_param('ii', $idEstudiante, $idPeriodo);
            $stmtMatricula->execute();
        }

        require_once 'includes/enviar-comprobante.php';
        
        $datosCorreo = [
            'total' => $totalConMatricula,
            'captureId' => $captureId,
            'cantidadCursos' => count($cursoIds),
            'fecha' => date('Y-m-d H:i:s'),
            'estado' => 'Completado',
            'periodo_nombre' => $periodoNombre, 
            'cursos' => $cursosDetalle        
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

// Limpiar sesión
unset($_SESSION['paypal_pending']);

// Respuesta al frontend
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