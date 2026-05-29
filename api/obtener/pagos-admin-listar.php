<?php
// Devuelve todos los pagos para el panel del administrador

session_start();
header('Content-Type: application/json');

// Verificar que sea administrador
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../includes/conexion.php';

// Consultar pagos con datos del estudiante
$sql = "
    SELECT 
        p.id,
        CONCAT('PAY-', LPAD(p.id, 4, '0')) AS codigo,
        CONCAT(u.nombre, ' ', u.apellido) AS estudiante,
        u.correo,
        p.monto,
        mp.nombre AS metodo,
        DATE_FORMAT(p.fechaPago, '%Y-%m-%d %H:%i') AS fecha,
        p.estado,
        CASE p.estado
            WHEN 'Completado' THEN 'Pagado'
            WHEN 'Procesando' THEN 'Pendiente'
            WHEN 'Fallido' THEN 'Fallido'
            ELSE p.estado
        END AS estado_mostrar
    FROM pagos p
    INNER JOIN estudiantes e ON p.idEstudiante = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
    ORDER BY p.fechaPago DESC
";

$result = $conexion->query($sql);
$pagos = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($pagos);
?>