<?php
// Los datos ahora vienen desde enviar-comprobante.php
// $estudiante, $correo, $cursos (array), $periodo, $metodoPago, $estado, $transaccion, $fecha, $hora, $total
date_default_timezone_set('America/El_Salvador');

// Si se abre directo sin pago_id, se muestra la plantilla vacia para revisar diseño.
if (!isset($estudiante)) {
    $pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;

    if ($pagoId > 0) {
        session_start();

        if (!isset($_SESSION['usuario'])) {
            header("Location: ../login.php");
            exit();
        }

        require_once __DIR__ . '/../includes/conexion.php';

        // Verificar que el pago pertenece al estudiante logueado
        $stmtPago = $conexion->prepare("
            SELECT p.id, p.monto, p.idTransaccionPasarela, p.estado, p.fechaPago,
                   mp.nombre AS metodo_pago,
                   CONCAT(u.nombre, ' ', u.apellido) AS nombre_estudiante,
                   u.correo,
                   e.id AS idEstudiante
            FROM pagos p
            INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
            INNER JOIN estudiantes e ON p.idEstudiante = e.id
            INNER JOIN usuarios u ON e.usuario_id = u.id
            WHERE p.id = ? AND u.correo = ?
        ");
        $stmtPago->bind_param('is', $pagoId, $_SESSION['usuario']);
        $stmtPago->execute();
        $datosPago = $stmtPago->get_result()->fetch_assoc();
        $stmtPago->close();

        if (!$datosPago) {
            die('Comprobante no encontrado o no tienes permiso para verlo.');
        }

        // Obtener cursos vinculados via idFactura
        $stmtCursos = $conexion->prepare("
            SELECT c.nombre, c.costoMensual AS costo,
                   pi.nombre AS periodo_nombre,
                   GROUP_CONCAT(DISTINCT CONCAT(ch.dia, ' - ', h.etiqueta) SEPARATOR ', ') AS horario,
                   GROUP_CONCAT(DISTINCT a.aula SEPARATOR ', ') AS aula
            FROM inscripciones i
            INNER JOIN cursos c ON i.idCurso = c.id
            INNER JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
            LEFT JOIN CursoHorario ch ON c.id = ch.idCurso
            LEFT JOIN horarios h ON ch.idHorario = h.id
            LEFT JOIN aulas a ON ch.idAula = a.id
            WHERE i.idFactura = ?
            GROUP BY c.id
        ");
        $stmtCursos->bind_param('i', $pagoId);
        $stmtCursos->execute();
        $cursos = $stmtCursos->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtCursos->close();

        $estudiante = $datosPago['nombre_estudiante'];
        $correo     = $datosPago['correo'];
        $metodoPago = $datosPago['metodo_pago'];
        $estado     = $datosPago['estado'];
        $transaccion = $datosPago['idTransaccionPasarela'];
        $total      = $datosPago['monto'];
        $periodo    = !empty($cursos) ? $cursos[0]['periodo_nombre'] : '—';
        $fecha      = date('d/m/Y', strtotime($datosPago['fechaPago']));
        $hora       = date('h:i A', strtotime($datosPago['fechaPago']));
    }
}

$estudiante  = $estudiante ?? '';
$correo      = $correo ?? '';
$cursos      = $cursos ?? [];
$periodo     = $periodo ?? '';
$metodoPago  = $metodoPago ?? '';
$estado      = $estado ?? '';
$transaccion = $transaccion ?? '';
$fecha       = $fecha ?? '';
$hora        = $hora ?? '';
$total       = isset($total) ? (float)$total : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <style>
    <?php
    // CSS simple compatible con Dompdf.
    $cssComprobantePath = __DIR__ . '/../css/styleComprobante.css';
    if (is_readable($cssComprobantePath)) {
        echo file_get_contents($cssComprobantePath);
    }
    ?>
    </style>
</head>
<body>

<div class="comprobante">

    <div class="header">
        <?php
        $logoPath = __DIR__ . '/../img/logo.svg';
        $logoSrc = '';

        if (is_readable($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/svg+xml;base64,' . $logoBase64;
        }
?>
        <table class="comprobante-header-table">
            <tr>
                <td class="comprobante-logo-cell">
                    <?php if ($logoSrc): ?>
                        <img src="<?= $logoSrc ?>" alt="Logo Academia Futuro Digital" class="comprobante-logo">
                    <?php endif; ?>
                </td>
                <td class="fecha">
                    <strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?><br>
                    <strong>Hora:</strong> <?= htmlspecialchars($hora) ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="titulo">
        <h2>COMPROBANTE DE PAGO E INSCRIPCIÓN</h2>
        <p>Academia Futuro Digital</p>
    </div>

    <div class="box">
        <div class="fila"><span class="label">Estudiante:</span> <?= htmlspecialchars($estudiante) ?></div>
        <div class="fila"><span class="label">Correo:</span> <?= htmlspecialchars($correo) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Curso</th>
                <th>Periodo</th>
                <th>Horario</th>
                <th>Aula</th>
                <th>Método</th>
                <th>Estado</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php $contador = 1; ?>
            <?php if (empty($cursos)): ?>
            <tr>
                <td colspan="8" class="tabla-vacia">Sin cursos registrados.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($cursos as $curso): ?>
            <tr>
                <td><?= $contador++ ?></td>
                <td><?= htmlspecialchars($curso['nombre']) ?></td>
                <td><?= htmlspecialchars($periodo) ?></td>
                <td><?= htmlspecialchars($curso['horario'] ?? 'No asignado') ?></td>
                <td><?= htmlspecialchars($curso['aula'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($metodoPago) ?></td>
                <td class="estado"><?= htmlspecialchars($estado) ?></td>
                <td>$<?= number_format($curso['costo'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="total-table">
        <tr>
            <td class="total-label">Total pagado:</td>
            <td class="total-monto">$<?= number_format($total, 2) ?></td>
        </tr>
    </table>

    <div class="box">
        <div class="fila"><span class="label">ID de transacción:</span> <?= htmlspecialchars($transaccion) ?></div>
        <div class="fila"><span class="label">Observación:</span> Pago registrado correctamente.</div>
    </div>

    <div class="footer">
        Este comprobante fue generado automáticamente por Academia Futuro Digital.
    </div>

</div>

</body>
</html>
