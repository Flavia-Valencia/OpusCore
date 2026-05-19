<?php
date_default_timezone_set('America/El_Salvador');

$pagoId = isset($_GET['pago_id']) ? (int)$_GET['pago_id'] : 0;

// ─── Carga desde BD 
if (!isset($estudiante) && $pagoId > 0) {
    session_start();
    if (!isset($_SESSION['usuario'])) {
        $pagoId = 0;
    }

    if ($pagoId > 0) {
        require_once __DIR__ . '/../includes/conexion.php';

        // Datos principales del pago
        $stmtPago = $conexion->prepare("
            SELECT p.id,
                   p.monto,
                   p.idTransaccionPasarela,
                   p.estado,
                   p.fechaPago,
                   mp.nombre        AS metodo_pago,
                   CONCAT(u.nombre, ' ', u.apellido) AS nombre_estudiante,
                   u.correo,
                   est.telefono,
                   est.direccion,
                   e.id             AS idEstudiante
            FROM   pagos p
            INNER JOIN MetodosPago  mp  ON p.idMetodoPago   = mp.id
            INNER JOIN estudiantes  est ON p.idEstudiante   = est.id
            INNER JOIN usuarios     u   ON est.usuario_id   = u.id
            INNER JOIN estudiantes  e   ON e.id             = est.id
            WHERE  p.id = ?
              AND  u.correo = ?
        ");
        $stmtPago->bind_param('is', $pagoId, $_SESSION['usuario']);
        $stmtPago->execute();
        $datosPago = $stmtPago->get_result()->fetch_assoc();
        $stmtPago->close();

        if ($datosPago) {
            $idEst = $datosPago['idEstudiante'];
            $items = [];

            // ── Inscripciones vinculadas al pago 
            $stmtInsc = $conexion->prepare("
                SELECT c.nombre             AS descripcion,
                       pi.nombre           AS periodo,
                       c.costoMensual      AS monto,
                       'Inscripción'       AS tipo
                FROM   inscripciones i
                INNER JOIN cursos            c  ON i.idCurso  = c.id
                INNER JOIN PeriodoInscripcion pi ON i.idPeriodo = pi.id
                WHERE  i.idFactura = ?
            ");
            $stmtInsc->bind_param('i', $pagoId);
            $stmtInsc->execute();
            $resInsc = $stmtInsc->get_result();
            while ($row = $resInsc->fetch_assoc()) $items[] = $row;
            $stmtInsc->close();

            // ── Mensualidades vinculadas al pago 
            $stmtMens = $conexion->prepare("
                SELECT CONCAT(c.nombre, ' — ', m.mesPagado) AS descripcion,
                       pi.nombre                             AS periodo,
                       m.monto,
                       'Mensualidad'                         AS tipo
                FROM   mensualidades m
                INNER JOIN cursos            c  ON m.idCurso  = c.id
                INNER JOIN PeriodoInscripcion pi ON m.idPeriodo = pi.id
                WHERE  m.idFactura = ?
            ");
            $stmtMens->bind_param('i', $pagoId);
            $stmtMens->execute();
            $resMens = $stmtMens->get_result();
            while ($row = $resMens->fetch_assoc()) $items[] = $row;
            $stmtMens->close();

            // ── Matrícula vinculada al pago 
            $stmtMat = $conexion->prepare("
                SELECT 'Matrícula'   AS descripcion,
                       pi.nombre     AS periodo,
                       mat.monto,
                       'Matrícula'   AS tipo
                FROM   matricula mat
                INNER JOIN PeriodoInscripcion pi ON mat.idPeriodo = pi.id
                WHERE  mat.idFactura = ?
                  AND  mat.idEstudiante = ?
            ");
            $stmtMat->bind_param('ii', $pagoId, $idEst);
            $stmtMat->execute();
            $resMat = $stmtMat->get_result();
            while ($row = $resMat->fetch_assoc()) $items[] = $row;
            $stmtMat->close();

            // ── Asignar variables globales 
            $estudiante  = $datosPago['nombre_estudiante'];
            $correo      = $datosPago['correo'];
            $telefono    = $datosPago['telefono'] ?? '';
            $direccion   = $datosPago['direccion'] ?? '';
            $dui         = '';
            $metodoPago  = $datosPago['metodo_pago'];
            $estado      = $datosPago['estado'];
            $transaccion = $datosPago['idTransaccionPasarela'] ?? '';
            $total       = $datosPago['monto'];
            $fecha       = date('d/m/Y', strtotime($datosPago['fechaPago']));
            $hora        = date('h:i A', strtotime($datosPago['fechaPago']));
            $periodo     = !empty($items) ? $items[0]['periodo'] : '';
        }
    }
}

$items       = $items ?? [];
$estudiante  = $estudiante ?? '';
$correo      = $correo ?? '';
$telefono    = $telefono ?? '';
$direccion   = $direccion ?? '';
$dui         = $dui ?? '';
$metodoPago  = $metodoPago ?? '';
$estado      = $estado ?? 'Emitida';
$transaccion = $transaccion ?? '';
$total       = $total ?? 0;
$fecha       = $fecha ?? '';
$hora        = $hora ?? '';
$periodo     = $periodo ?? '';

// Total en letras (utilidad simple)
function numeroALetras(float $n): string {
    $entero = (int) $n;
    $centavos = round(($n - $entero) * 100);
    $unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE',
                 'DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE',
                 'DIECIOCHO','DIECINUEVE'];
    $decenas  = ['','','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $centenas = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
                 'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    $toStr = function(int $n) use ($unidades, $decenas, $centenas): string {
        if ($n === 100) return 'CIEN';
        $c = intdiv($n, 100);
        $d = intdiv($n % 100, 10);
        $u = $n % 10;
        $res = '';
        if ($c) $res .= $centenas[$c] . ' ';
        if ($n % 100 < 20 && $n % 100 > 0) { $res .= $unidades[$n % 100]; }
        else {
            if ($d) $res .= $decenas[$d] . ($u ? ' Y ' : '');
            if ($u) $res .= $unidades[$u];
        }
        return trim($res);
    };
    $miles   = intdiv($entero, 1000);
    $resto   = $entero % 1000;
    $letras  = '';
    if ($miles === 1) $letras .= 'MIL ';
    elseif ($miles > 1) $letras .= $toStr($miles) . ' MIL ';
    if ($resto) $letras .= $toStr($resto);
    if (!$letras) $letras = 'CERO';
    return trim($letras) . ' ' . sprintf('%02d', $centavos) . '/100 DÓLARES';
}

$totalLetras = numeroALetras((float)$total);

// Logo en base64
$logoSrc = '';
$logoPath = __DIR__ . '/../img/logo.svg';
if (is_readable($logoPath)) {
    $logoSrc = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comprobante de Pago #<?= $pagoId ?? '—' ?> — Academia Futuro Digital</title>
<link rel="stylesheet" href="../css/styleFacturacionElectronica.css">
</head>
<body>

<div class="doc">
    <header class="header">
        <div class="logo-wrap">
            <?php if ($logoSrc): ?>
                <img src="../img/logo.svg" alt="Academia Futuro Digital">
            <?php else: ?>
                <div class="logo-placeholder">AF</div>
            <?php endif; ?>
        </div>

        <div class="emisor-info">
            <div class="org-name">Academia Futuro Digital</div>
            <div class="org-sub">Institución Educativa Tecnológica</div>
            <div class="org-meta">
                Correo: facturacion@academiafuturodigital.edu.sv &nbsp;|&nbsp; Tel: (503) 0000-0000<br>
                Dirección: San Salvador, El Salvador &nbsp;|&nbsp; NIT: 0000-000000-000-0
            </div>
        </div>

        <div class="doc-badge">
            <div class="doc-type">Comprobante<br>de Pago</div>
            <div class="doc-num">N° <?= str_pad($pagoId ?? 0, 6, '0', STR_PAD_LEFT) ?></div>
            <div class="doc-fecha">
                <strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?><br>
                <strong>Hora:</strong>  <?= htmlspecialchars($hora)  ?>
            </div>
            <span class="estado-badge estado-<?= htmlspecialchars($estado) ?>">
                <?= htmlspecialchars($estado) ?>
            </span>
        </div>
    </header>

    <section class="partes">
        <div class="parte">
            <div class="parte-titulo">Pagador / Alumno</div>
            <div class="parte-fila"><span class="lbl">Nombre:</span>   <span class="val"><?= htmlspecialchars($estudiante) ?></span></div>
            <div class="parte-fila"><span class="lbl">Correo:</span>   <span class="val"><?= htmlspecialchars($correo) ?></span></div>
            <div class="parte-fila"><span class="lbl">Dirección:</span><span class="val"><?= htmlspecialchars($direccion ?? '—') ?></span></div>
            <div class="parte-fila"><span class="lbl">Teléfono:</span> <span class="val"><?= htmlspecialchars($telefono ?? '—') ?></span></div>
            <?php if (!empty($dui) && $dui !== '—'): ?>
            <div class="parte-fila"><span class="lbl">DUI:</span>      <span class="val"><?= htmlspecialchars($dui) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="parte">
            <div class="parte-titulo">Beneficiario / Academia</div>
            <div class="parte-fila"><span class="lbl">Nombre:</span>   <span class="val">Academia Futuro Digital</span></div>
            <div class="parte-fila"><span class="lbl">Correo:</span>   <span class="val">facturacion@academiafuturodigital.edu.sv</span></div>
            <div class="parte-fila"><span class="lbl">Dirección:</span><span class="val">San Salvador, El Salvador</span></div>
            <div class="parte-fila"><span class="lbl">Teléfono:</span> <span class="val">(503) 0000-0000</span></div>
            <div class="parte-fila"><span class="lbl">NIT:</span>      <span class="val">0000-000000-000-0</span></div>
            <div class="parte-fila"><span class="lbl">Actividad:</span><span class="val">EDUCACIÓN</span></div>
        </div>
    </section>

    <div class="tabla-wrap" style="margin-top:14px;">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:30px">N°</th>
                    <th style="width:28px" class="center">Cant.</th>
                    <th style="width:60px" class="center">Tipo</th>
                    <th>Descripción</th>
                    <th>Período</th>
                    <th>Método</th>
                    <th style="width:75px">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items = $items ?? [];
                if (empty($items)):
                ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--ink-soft);padding:18px;">
                        Sin ítems registrados.
                    </td>
                </tr>
                <?php else:
                    $i = 1;
                    foreach ($items as $item): ?>
                <tr>
                    <td class="num center"><?= $i++ ?></td>
                    <td class="num center">1.00</td>
                    <td class="center">
                        <span class="tipo-tag tipo-<?= htmlspecialchars($item['tipo']) ?>">
                            <?= htmlspecialchars($item['tipo']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($item['descripcion']) ?></td>
                    <td><?= htmlspecialchars($item['periodo']) ?></td>
                    <td><?= htmlspecialchars($metodoPago) ?></td>
                    <td class="num">$<?= number_format($item['monto'], 2) ?></td>
                </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sello NO DEVOLUCIÓN -->
    <div style="padding: 6px 22px 0;">
        <span class="sello">No Devolución</span>
    </div>

    <!-- ── TOTALES -->
    <div class="totales-wrap" style="margin-top: 8px;">
        <table class="totales">
            <tr>
                <td class="tl">Subtotal:</td>
                <td class="tv">$<?= number_format($total, 2) ?></td>
            </tr>
            <tr>
                <td class="tl">IVA (0%):</td>
                <td class="tv">$0.00</td>
            </tr>
            <tr class="total-row">
                <td class="tl" style="color:#fff;">Total a Pagar:</td>
                <td class="tv">$<?= number_format($total, 2) ?></td>
            </tr>
        </table>
    </div>

    <!-- ── VALOR EN LETRAS + DATOS TRANSACCIÓN -->
    <div class="observaciones">
        <div class="letras">
            Valor en Letras: <?= htmlspecialchars($totalLetras) ?>
        </div>
        <div style="margin-top:8px; display:grid; grid-template-columns:1fr 1fr; gap:4px 12px;">
            <div class="obs-fila">
                <span class="obs-lbl">Condición:</span>
                <span class="obs-val">CONTADO</span>
            </div>
            <div class="obs-fila">
                <span class="obs-lbl">Método de pago:</span>
                <span class="obs-val"><?= htmlspecialchars($metodoPago) ?></span>
            </div>
            <div class="obs-fila">
                <span class="obs-lbl">No. Referencia:</span>
                <span class="obs-val" style="font-family:var(--mono)"><?= htmlspecialchars($transaccion) ?></span>
            </div>
            <div class="obs-fila">
                <span class="obs-lbl">Observación:</span>
                <span class="obs-val">Pago registrado correctamente.</span>
            </div>
        </div>
    </div>

    <!-- ── PIE -->
    <footer class="footer">
        <div class="footer-left">
            <strong>Beneficiario del pago:</strong> Academia Futuro Digital<br>
            Este comprobante fue generado automáticamente por el sistema de Academia Futuro Digital.<br>
            Cualquier consulta: <span style="color:var(--accent)">soporte@academiafuturodigital.edu.sv</span>
        </div>
        <div class="footer-right">
            N° de Documento: <?= str_pad($pagoId ?? 0, 6, '0', STR_PAD_LEFT) ?><br>
            Generado: <?= date('d/m/Y H:i:s') ?>
        </div>
    </footer>

</div><!-- /.doc -->

<button class="print-btn" onclick="window.print()">🖨 Imprimir / Guardar como PDF</button>

</body>
</html>
