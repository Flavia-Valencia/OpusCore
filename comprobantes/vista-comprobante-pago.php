<?php
// Los datos ahora vienen desde enviar-comprobante.php
// $estudiante, $correo, $cursos (array), $periodo, $metodoPago, $estado, $transaccion, $fecha, $hora, $total
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <link rel="stylesheet" href="../css/styleComprobante.css">
</head>
<body>

<div class="comprobante">

    <div class="header">
        <img src="../img/logo.svg" alt="Logo Academia Futuro Digital" class="comprobante-logo">
        <div class="fecha">
            <strong>Fecha:</strong> <?= $fecha ?><br>
            <strong>Hora:</strong> <?= $hora ?>
        </div>
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
        Este comprobante fue generado automáticamente por OpusCore.
    </div>

</div>

</body>
</html>