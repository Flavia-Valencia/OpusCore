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
$estadoPayPal = $capture['status'] ?? '';

// Verificar que la respuesta de PayPal fue exitosa (HTTP 201)
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
$total        = $pending['total'];

// Datos que devuelve PayPal tras capturar
$captureId   = $capture['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
$payerEmail  = $capture['payer']['email_address'] ?? '';
$payerNombre = trim(
    ($capture['payer']['name']['given_name'] ?? '') . ' ' .
    ($capture['payer']['name']['surname']    ?? '')
);

// Calcular total real: cursos + matrícula ($25.00)
$costoMatricula = 25.00;
$totalConMatricula = (float)$total + $costoMatricula;

// Registrar en BD con TRANSACCIÓN
$conexion->begin_transaction();

try {
    // 🔥 CORREGIDO: El if estaba mal escrito
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
    
    // Insertar registro en tabla pagos (SIEMPRE se guarda)
    $stmtPago = $conexion->prepare("
        INSERT INTO pagos (
            idEstudiante, 
            idMetodoPago, 
            monto, 
            idTransaccionPasarela, 
            estado
        ) VALUES (?, 1, ?, ?, ?)
    ");
    
    // 🔥 CORREGIDO: Agregar $estadoBD como 4to parámetro
    $stmtPago->bind_param('idss', $idEstudiante, $totalConMatricula, $captureId, $estadoBD);
    $stmtPago->execute();
    $idPago = $conexion->insert_id;
    
    // Solo si el pago fue COMPLETADO, se registran las inscripciones
    if ($pagoExitoso) {
        $stmtIns = $conexion->prepare("
            INSERT INTO inscripciones (idEstudiante, idCurso, idPeriodo, estado_academico)
            VALUES (?, ?, ?, 'Activo')
        ");
        
        foreach ($cursoIds as $idCurso) {
            $stmtIns->bind_param('iii', $idEstudiante, $idCurso, $idPeriodo);
            $stmtIns->execute();
            
            // Descontar cupo
            $conexion->query("UPDATE cursos SET cupos = cupos - 1 WHERE id = $idCurso AND cupos > 0");
        }
        
        // Registrar matrícula
        $stmtMatricula = $conexion->prepare("
            INSERT INTO matricula (idEstudiante, idPeriodo, monto, estado)
            VALUES (?, ?, ?, 'Pagado')
            ON DUPLICATE KEY UPDATE estado = 'Pagado'
        ");
        $stmtMatricula->bind_param('iid', $idEstudiante, $idPeriodo, $costoMatricula);
        $stmtMatricula->execute();
        
        // 🔥 ENVIAR COMPROBANTE POR CORREO (pendiente plantilla frontend)
        /*
        require_once 'includes/enviar-comprobante.php';
        
        $datosCorreo = [
            'total' => $totalConMatricula,
            'captureId' => $captureId,
            'cantidadCursos' => count($cursoIds),
            'fecha' => date('Y-m-d H:i:s')
        ];
        
        enviarComprobante($payerEmail, $payerNombre, $datosCorreo);
        */
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
    'cursos' => count($cursoIds),
    'idPago' => $idPago,
    'matricula' => $pagoExitoso ? 'Pagado' : 'Pendiente'
]);
?>