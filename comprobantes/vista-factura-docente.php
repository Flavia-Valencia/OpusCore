<?php
date_default_timezone_set('America/El_Salvador');

// Plantilla de factura docente compatible con Dompdf.
// El backend puede definir estas variables antes de incluir la vista.
$facturaId     = $facturaId ?? '';
$docente       = $docente ?? '';
$correo        = $correo ?? '';
$telefono      = $telefono ?? '';
$direccion     = $direccion ?? '';
$especialidad  = $especialidad ?? '';
$concepto      = $concepto ?? '';
$periodoPago   = $periodoPago ?? '';
$descripcion   = $descripcion ?? '';
$metodoPago    = $metodoPago ?? '';
$condicion     = $condicion ?? 'CONTADO';
$referencia    = $referencia ?? '';
$observaciones = $observaciones ?? '';
$estado        = $estado ?? 'Emitida';
$fecha         = $fecha ?? '';
$hora          = $hora ?? '';
$total         = $total ?? 0;

$items = $items ?? [];
if (empty($items) && ($concepto || $descripcion || $periodoPago || $total > 0)) {
    $items[] = [
        'tipo' => $concepto,
        'descripcion' => $descripcion,
        'periodo' => $periodoPago,
        'monto' => $total,
    ];
}

function numeroALetrasDocente(float $n): string {
    $entero = (int) $n;
    $centavos = round(($n - $entero) * 100);
    $unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE',
                 'DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE',
                 'DIECIOCHO','DIECINUEVE'];
    $decenas  = ['','','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $centenas = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
                 'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    $toStr = function(int $n) use ($unidades, $decenas, $centenas): string {
        if ($n === 0) return 'CERO';
        if ($n === 100) return 'CIEN';
        $c = intdiv($n, 100);
        $d = intdiv($n % 100, 10);
        $u = $n % 10;
        $res = '';
        if ($c) $res .= $centenas[$c] . ' ';
        if ($n % 100 < 20 && $n % 100 > 0) {
            $res .= $unidades[$n % 100];
        } else {
            if ($d) $res .= $decenas[$d] . ($u ? ' Y ' : '');
            if ($u) $res .= $unidades[$u];
        }
        return trim($res);
    };

    $miles = intdiv($entero, 1000);
    $resto = $entero % 1000;
    $letras = '';
    if ($miles === 1) $letras .= 'MIL ';
    elseif ($miles > 1) $letras .= $toStr($miles) . ' MIL ';
    if ($resto || !$letras) $letras .= $toStr($resto);
    return trim($letras) . ' ' . sprintf('%02d', $centavos) . '/100 DÓLARES';
}

$totalLetras = numeroALetrasDocente((float)$total);

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
<title>Factura Docente #<?= htmlspecialchars($facturaId ?: '—') ?> — Academia Futuro Digital</title>
<style>
<?php
// Inserta CSS compatible con Dompdf dentro de la plantilla.
$cssPdfPath = __DIR__ . '/../css/stylePlantillasPdf.css';
if (is_readable($cssPdfPath)) {
    echo file_get_contents($cssPdfPath);
}
?>
</style>
</head>
<body>

<div class="doc">
    <header class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <?php if ($logoSrc): ?>
                        <img src="<?= $logoSrc ?>" alt="Academia Futuro Digital">
                    <?php else: ?>
                        <div class="logo-placeholder">AF</div>
                    <?php endif; ?>    
                </td>
                <td class="emisor-info">
                    <div class="org-name">Academia Futuro Digital</div>
                    <div class="org-sub">Institución Educativa Tecnológica</div>
                    <div class="org-meta">
                        Correo: facturacion@academiafuturodigital.edu.sv &nbsp;|&nbsp; Tel: (503) 0000-0000<br>
                        Dirección: San Salvador, El Salvador &nbsp;
                    </div>
                </td>
                <td class="doc-badge">
                    <div class="doc-type">Factura<br>Docente</div>
                    <div class="doc-num">N° <?= htmlspecialchars($facturaId ?: '000000') ?></div>
                    <div class="doc-fecha">
                        <strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?><br>
                        <strong>Hora:</strong> <?= htmlspecialchars($hora) ?>
                    </div>
                    <span class="estado-badge estado-<?= htmlspecialchars($estado) ?>">
                        <?= htmlspecialchars($estado) ?>
                    </span>
                </td>
            </tr>
        </table>
    </header>

    <section class="partes">
        <div class="parte">
            <div class="parte-titulo">Pagador / Academia</div>
            <div class="parte-fila"><span class="lbl">Nombre:</span>   <span class="val">Academia Futuro Digital</span></div>
            <div class="parte-fila"><span class="lbl">Correo:</span>   <span class="val">facturacion@academiafuturodigital.edu.sv</span></div>
            <div class="parte-fila"><span class="lbl">Dirección:</span><span class="val">San Salvador, El Salvador</span></div>
            <div class="parte-fila"><span class="lbl">Teléfono:</span> <span class="val">(503) 0000-0000</span></div>
            <div class="parte-fila"><span class="lbl">Actividad:</span><span class="val">EDUCACIÓN</span></div>
        </div>

        <div class="parte">
            <div class="parte-titulo">Beneficiario / Docente</div>
            <div class="parte-fila"><span class="lbl">Nombre:</span>      <span class="val"><?= htmlspecialchars($docente) ?></span></div>
            <div class="parte-fila"><span class="lbl">Correo:</span>      <span class="val"><?= htmlspecialchars($correo) ?></span></div>
            <div class="parte-fila"><span class="lbl">Dirección:</span>   <span class="val"><?= htmlspecialchars($direccion) ?></span></div>
            <div class="parte-fila"><span class="lbl">Teléfono:</span>    <span class="val"><?= htmlspecialchars($telefono) ?></span></div>
            <div class="parte-fila"><span class="lbl">Área:</span>        <span class="val"><?= htmlspecialchars($especialidad) ?></span></div>
        </div>
    </section>

    <div class="tabla-wrap" style="margin-top:14px;">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:30px">N°</th>
                    <th style="width:28px" class="center">Cant.</th>
                    <th style="width:95px" class="center">Concepto</th>
                    <th>Descripción</th>
                    <th>Período de pago</th>
                    <th>Método</th>
                    <th style="width:80px">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#4b5563;padding:18px;">
                        Sin detalle registrado.
                    </td>
                </tr>
                <?php else:
                    $i = 1;
                    foreach ($items as $item): ?>
                <tr>
                    <td class="num center"><?= $i++ ?></td>
                    <td class="num center">1.00</td>
                    <td class="center">
                        <span class="tipo-tag tipo-Docente">
                            <?= htmlspecialchars($item['tipo'] ?? '') ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($item['descripcion'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['periodo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($metodoPago) ?></td>
                    <td class="num">$<?= number_format((float)($item['monto'] ?? 0), 2) ?></td>
                </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
    </div>

    <div class="totales-wrap" style="margin-top: 8px;">
        <table class="totales">
            <tr>
                <td class="tl">Subtotal:</td>
                <td class="tv">$<?= number_format((float)$total, 2) ?></td>
            </tr>
            <tr>
                <td class="tl">Retención:</td>
                <td class="tv">$0.00</td>
            </tr>
            <tr class="total-row">
                <td class="tl" style="color:#fff;">Total a pagar:</td>
                <td class="tv">$<?= number_format((float)$total, 2) ?></td>
            </tr>
        </table>
    </div>

    <div class="observaciones">
        <div class="letras">
            Valor en Letras: <?= htmlspecialchars($totalLetras) ?>
        </div>
        <table class="obs-table">
            <tr>
                <td><span class="obs-lbl">Condición:</span> <span class="obs-val"><?= htmlspecialchars($condicion) ?></span></td>
                <td><span class="obs-lbl">Método de pago:</span> <span class="obs-val"><?= htmlspecialchars($metodoPago) ?></span></td>
            </tr>
            <tr>
                <td><span class="obs-lbl">No. Referencia:</span> <span class="obs-val"><?= htmlspecialchars($referencia) ?></span></td>
                <td><span class="obs-lbl">Observación:</span> <span class="obs-val"><?= htmlspecialchars($observaciones) ?></span></td>
            </tr>
        </table>
    </div>

    <footer class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    <strong>Pagador del documento:</strong> Academia Futuro Digital<br>
                    Este documento registra un pago emitido al docente indicado.<br>
                    Cualquier consulta: <span style="color:#1f2937">soporte@academiafuturodigital.edu.sv</span>
                </td>
                <td class="footer-right">
                    N° de Documento: <?= htmlspecialchars($facturaId ?: '000000') ?><br>
                    Generado: <?= date('d/m/Y H:i:s') ?>
                </td>
            </tr>
        </table>
    </footer>
</div>

<button class="print-btn" onclick="window.print()">Imprimir / Guardar como PDF</button>

</body>
</html>
