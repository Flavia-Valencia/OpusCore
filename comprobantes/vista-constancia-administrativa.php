<?php
date_default_timezone_set('America/El_Salvador');

// Plantilla frontend compatible con Dompdf
// Backend podra definir estas variables antes de incluir esta vista
$codigoConstancia = $codigoConstancia ?? ($_GET['codigo'] ?? 'CONST-0001');
$tipoConstancia   = $tipoConstancia ?? ($_GET['tipo'] ?? 'Constancia de aprobación de curso');
$solicitante      = $solicitante ?? ($_GET['solicitante'] ?? 'Yahir Romero');
$correo           = $correo ?? ($_GET['correo'] ?? 'yahir.romero@academia.test');
$destino          = $destino ?? ($_GET['destino'] ?? 'Estudiante');
$curso            = $curso ?? ($_GET['curso'] ?? 'Diseño Web');
$codigoCurso      = $codigoCurso ?? ($_GET['codigoCurso'] ?? 'DIS-101');
$periodo          = $periodo ?? ($_GET['periodo'] ?? '2026-I');
$notaFinal        = $notaFinal ?? ($_GET['notaFinal'] ?? '8.8');
$resultado        = $resultado ?? ($_GET['resultado'] ?? 'Aprobado');
$motivo           = $motivo ?? ($_GET['motivo'] ?? 'Trámite administrativo');
$fechaSolicitud   = $fechaSolicitud ?? ($_GET['fechaSolicitud'] ?? date('d/m/Y'));
$fechaActividad   = $fechaActividad ?? ($_GET['fechaActividad'] ?? $fechaSolicitud);
$horaSolicitud    = $horaSolicitud ?? ($_GET['horaSolicitud'] ?? date('h:i A'));
$fechaEmision     = $fechaEmision ?? ($_GET['fechaEmision'] ?? date('d/m/Y'));
$horaEmision      = $horaEmision ?? ($_GET['horaEmision'] ?? date('h:i A'));
$estado           = $estado ?? 'Generada';

$tipoNormalizado = mb_strtolower($tipoConstancia, 'UTF-8');

if ($textoConstancia ?? false) {
    $textoConstancia = $textoConstancia;
} elseif (str_contains($tipoNormalizado, 'aprob')) {
    $textoConstancia = sprintf(
        'Por medio de la presente se hace constar que %s, registrado(a) como %s en Academia Futuro Digital, aprobó el curso %s (%s) durante el ciclo académico %s, con nota final %s. Este documento se extiende a solicitud de la persona interesada para %s.',
        $solicitante,
        strtolower($destino),
        $curso,
        $codigoCurso,
        $periodo,
        $notaFinal,
        strtolower($motivo)
    );
} elseif (str_contains($tipoNormalizado, 'particip') || str_contains($tipoNormalizado, 'asistencia')) {
    $textoConstancia = sprintf(
        'Por medio de la presente se hace constar que %s, registrado(a) como %s en Academia Futuro Digital, participó en una actividad académica vinculada al curso %s (%s) el día %s, correspondiente al ciclo académico %s. Este documento se extiende para %s.',
        $solicitante,
        strtolower($destino),
        $curso,
        $codigoCurso,
        $fechaActividad,
        $periodo,
        strtolower($motivo)
    );
} elseif (str_contains($tipoNormalizado, 'docencia')) {
    $textoConstancia = sprintf(
        'Por medio de la presente se hace constar que %s, registrado(a) como %s en Academia Futuro Digital, impartió o tuvo asignación académica en el curso %s (%s) durante el ciclo académico %s. Este documento se extiende para %s.',
        $solicitante,
        strtolower($destino),
        $curso,
        $codigoCurso,
        $periodo,
        strtolower($motivo)
    );
} else {
    $textoConstancia = sprintf(
        'Por medio de la presente se hace constar que %s, registrado(a) como %s en Academia Futuro Digital, se encuentra vinculado(a) al curso %s (%s) durante el ciclo académico %s, con estado %s. Este documento se extiende para %s.',
        $solicitante,
        strtolower($destino),
        $curso,
        $codigoCurso,
        $periodo,
        strtolower($resultado),
        strtolower($motivo)
    );
}

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
<title><?= htmlspecialchars($tipoConstancia) ?> <?= htmlspecialchars($codigoConstancia) ?> — Academia Futuro Digital</title>
<style>
<?php
$cssPdfPath = __DIR__ . '/../css/stylePlantillasPdf.css';
if (is_readable($cssPdfPath)) {
    echo file_get_contents($cssPdfPath);
}
?>
</style>
</head>
<body>

<div class="doc constancia-doc">
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
                        Correo: administracion@academiafuturodigital.edu.sv &nbsp;|&nbsp; Tel: (503) 0000-0000<br>
                        Dirección: San Salvador, El Salvador
                    </div>
                </td>
                <td class="doc-badge">
                    <div class="doc-type">Constancia<br>Administrativa</div>
                    <div class="doc-num">N° <?= htmlspecialchars($codigoConstancia) ?></div>
                    <div class="doc-fecha">
                        <strong>Fecha:</strong> <?= htmlspecialchars($fechaEmision) ?><br>
                        <strong>Hora:</strong> <?= htmlspecialchars($horaEmision) ?>
                    </div>
                    <span class="estado-badge"><?= htmlspecialchars($estado) ?></span>
                </td>
            </tr>
        </table>
    </header>

    <section class="constancia-hero">
        <div class="constancia-label">Documento institucional</div>
        <h1><?= htmlspecialchars($tipoConstancia) ?></h1>
        <p>Emitida por la Administración Académica de Academia Futuro Digital.</p>
    </section>

    <section class="partes constancia-partes">
        <div class="parte">
            <div class="parte-titulo">Solicitante</div>
            <div class="parte-fila"><span class="lbl">Nombre:</span><span class="val"><?= htmlspecialchars($solicitante) ?></span></div>
            <div class="parte-fila"><span class="lbl">Destino:</span><span class="val"><?= htmlspecialchars($destino) ?></span></div>
            <div class="parte-fila"><span class="lbl">Correo:</span><span class="val"><?= htmlspecialchars($correo) ?></span></div>
        </div>
        <div class="parte">
            <div class="parte-titulo">Datos del curso</div>
            <div class="parte-fila"><span class="lbl">Curso:</span><span class="val"><?= htmlspecialchars($curso) ?></span></div>
            <div class="parte-fila"><span class="lbl">Código:</span><span class="val"><?= htmlspecialchars($codigoCurso) ?></span></div>
            <div class="parte-fila"><span class="lbl">Periodo:</span><span class="val"><?= htmlspecialchars($periodo) ?></span></div>
            <div class="parte-fila"><span class="lbl">Resultado:</span><span class="val"><?= htmlspecialchars($resultado) ?></span></div>
            <div class="parte-fila"><span class="lbl">Nota final:</span><span class="val"><?= htmlspecialchars($notaFinal) ?></span></div>
            <div class="parte-fila"><span class="lbl">Referencia:</span><span class="val"><?= htmlspecialchars($fechaActividad) ?></span></div>
        </div>
        <div class="parte">
            <div class="parte-titulo">Datos de emisión</div>
            <div class="parte-fila"><span class="lbl">Solicitud:</span><span class="val"><?= htmlspecialchars($fechaSolicitud) ?> · <?= htmlspecialchars($horaSolicitud) ?></span></div>
            <div class="parte-fila"><span class="lbl">Motivo:</span><span class="val"><?= htmlspecialchars($motivo) ?></span></div>
        </div>
    </section>

    <main class="constancia-cuerpo">
        <p><?= htmlspecialchars($textoConstancia) ?></p>
        <p>
            La presente constancia se emite en la ciudad de San Salvador, El Salvador,
            el día <?= htmlspecialchars($fechaEmision) ?>, para ser presentada ante la institución que corresponda.
        </p>
    </main>

    <section class="constancia-validacion">
        <table>
            <tr>
                <td>
                    <span>Código de documento</span>
                    <strong><?= htmlspecialchars($codigoConstancia) ?></strong>
                </td>
                <td>
                    <span>Curso</span>
                    <strong><?= htmlspecialchars($codigoCurso) ?></strong>
                </td>
                <td>
                    <span>Estado</span>
                    <strong><?= htmlspecialchars($estado) ?></strong>
                </td>
            </tr>
        </table>
    </section>

    <footer class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Este documento fue generado por el sistema administrativo de Academia Futuro Digital.<br>
                    Para validar su emisión, contacte a administracion@academiafuturodigital.edu.sv.
                </td>
                <td class="footer-right">
                    N° de Documento: <?= htmlspecialchars($codigoConstancia) ?><br>
                    Generado: <?= date('d/m/Y H:i:s') ?>
                </td>
            </tr>
        </table>
    </footer>
</div>

<button class="print-btn" onclick="window.print()">Imprimir / Guardar como PDF</button>

</body>
</html>
