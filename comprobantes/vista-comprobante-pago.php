<?php
// Vista reutilizable para comprobantes generados por correo, descarga PDF o vista directa.
// Variables esperadas: estudiante, correo, cursos, periodo, metodoPago, estado, transaccion, fecha, hora y total.
date_default_timezone_set('America/El_Salvador');

// Permite consultar un comprobante desde el navegador cuando se recibe pago_id.
if (!isset($estudiante)) {
    $pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;

    if ($pagoId > 0) {
        session_start();

        if (!isset($_SESSION['usuario'])) {
            header("Location: ../login.php");
            exit();
        }

        require_once __DIR__ . '/../includes/conexion.php';

        $esAdmin = isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1;

        if ($esAdmin) {
            // El admin puede ver cualquier comprobante
            $stmtPago = $conexion->prepare("
                SELECT p.id, p.monto, p.idTransaccionPasarela, p.estado, p.fechaPago,
                       mp.nombre AS metodo_pago,
                       CONCAT(u.nombre, ' ', u.apellido) AS nombre_estudiante,
                       u.correo,
                       e.id AS idEstudiante,
                       f.id AS factura_id
                FROM pagos p
                INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
                INNER JOIN estudiantes e ON p.idEstudiante = e.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                LEFT JOIN facturas f ON f.idPago = p.id
                WHERE p.id = ?
            ");
            $stmtPago->bind_param('i', $pagoId);
        } else {
            // El estudiante solo puede ver los propios
            $stmtPago = $conexion->prepare("
                SELECT p.id, p.monto, p.idTransaccionPasarela, p.estado, p.fechaPago,
                       mp.nombre AS metodo_pago,
                       CONCAT(u.nombre, ' ', u.apellido) AS nombre_estudiante,
                       u.correo,
                       e.id AS idEstudiante,
                       f.id AS factura_id
                FROM pagos p
                INNER JOIN MetodosPago mp ON p.idMetodoPago = mp.id
                INNER JOIN estudiantes e ON p.idEstudiante = e.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                LEFT JOIN facturas f ON f.idPago = p.id
                WHERE p.id = ? AND u.correo = ?
            ");
            $stmtPago->bind_param('is', $pagoId, $_SESSION['usuario']);
        }
        $stmtPago->execute();
        $datosPago = $stmtPago->get_result()->fetch_assoc();
        $stmtPago->close();

        if (!$datosPago) {
            die('Comprobante no encontrado o no tienes permiso para verlo.');
        }

        $cursos = [];
        $facturaId = $datosPago['factura_id'];
        if ($facturaId) {
            $stmtCursos = $conexion->prepare("
                SELECT 
                    df.descripcion AS descripcion,
                    df.precioUnitario AS costo,
                    CASE 
                        WHEN df.tipoOrigen = 'Inscripcion' THEN c_ins.nombre
                        WHEN df.tipoOrigen = 'Mensualidad' THEN CONCAT(c_men.nombre, ' (Mensualidad - ', m.mesPagado, ')')
                        WHEN df.tipoOrigen = 'Matricula' THEN 'Matrícula'
                        ELSE df.descripcion
                    END AS nombre,
                    CASE 
                        WHEN df.tipoOrigen = 'Inscripcion' THEN pi_ins.nombre
                        WHEN df.tipoOrigen = 'Mensualidad' THEN pi_men.nombre
                        ELSE ''
                    END AS periodo_nombre,
                    CASE 
                        WHEN df.tipoOrigen = 'Inscripcion' THEN COALESCE(GROUP_CONCAT(DISTINCT CONCAT(ch_ins.dia, ' - ', h_ins.etiqueta) SEPARATOR ', '), 'No asignado')
                        WHEN df.tipoOrigen = 'Mensualidad' THEN COALESCE(GROUP_CONCAT(DISTINCT CONCAT(ch_men.dia, ' - ', h_men.etiqueta) SEPARATOR ', '), 'No asignado')
                        ELSE 'No asignado'
                    END AS horario,
                    CASE 
                        WHEN df.tipoOrigen = 'Inscripcion' THEN COALESCE(GROUP_CONCAT(DISTINCT a_ins.aula SEPARATOR ', '), 'N/A')
                        WHEN df.tipoOrigen = 'Mensualidad' THEN COALESCE(GROUP_CONCAT(DISTINCT a_men.aula SEPARATOR ', '), 'N/A')
                        ELSE 'N/A'
                    END AS aula
                FROM detalle_facturas df
                LEFT JOIN cursos c_ins ON (df.tipoOrigen = 'Inscripcion' AND df.idOrigen = c_ins.id)
                LEFT JOIN PeriodoInscripcion pi_ins ON c_ins.idPeriodo = pi_ins.id
                LEFT JOIN CursoHorario ch_ins ON c_ins.id = ch_ins.idCurso
                LEFT JOIN horarios h_ins ON ch_ins.idHorario = h_ins.id
                LEFT JOIN aulas a_ins ON ch_ins.idAula = a_ins.id
                
                LEFT JOIN mensualidades m ON (df.tipoOrigen = 'Mensualidad' AND df.idOrigen = m.id)
                LEFT JOIN cursos c_men ON m.idCurso = c_men.id
                LEFT JOIN PeriodoInscripcion pi_men ON m.idPeriodo = pi_men.id
                LEFT JOIN CursoHorario ch_men ON c_men.id = ch_men.idCurso
                LEFT JOIN horarios h_men ON ch_men.idHorario = h_men.id
                LEFT JOIN aulas a_men ON ch_men.idAula = a_men.id
                WHERE df.idFactura = ?
                GROUP BY df.id
            ");
            if ($stmtCursos) {
                $stmtCursos->bind_param('i', $facturaId);
                $stmtCursos->execute();
                $cursos = $stmtCursos->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtCursos->close();
            }
        }

        // Fallback para retrocompatibilidad si no hay detalle_facturas
        if (empty($cursos)) {
            $stmtCursosFallback = $conexion->prepare("
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
                WHERE i.idEstudiante = ?
                GROUP BY c.id
            ");
            if ($stmtCursosFallback) {
                $idEstudiante = (int) $datosPago['idEstudiante'];
                $stmtCursosFallback->bind_param("i", $idEstudiante);
                $stmtCursosFallback->execute();
                $cursos = $stmtCursosFallback->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtCursosFallback->close();
            }
        }

        if (empty($cursos)) {
            $cursos = [[
                'nombre' => 'Curso no especificado',
                'periodo_nombre' => 'No especificado',
                'horario' => 'No asignado',
                'aula' => 'N/A',
                'costo' => (float)$datosPago['monto']
            ]];
        }

        $estudiante = $datosPago['nombre_estudiante'];
        $correo     = $datosPago['correo'];
        $metodoPago = $datosPago['metodo_pago'];
        $estado     = $datosPago['estado'];
        $transaccion = $datosPago['idTransaccionPasarela'] ?: 'PAY-' . str_pad((string)$datosPago['id'], 5, '0', STR_PAD_LEFT);
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
    // Inserta CSS compatible con Dompdf dentro de la plantilla.
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
                <td><?= htmlspecialchars($curso['periodo_nombre'] ?? $periodo) ?></td>
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
