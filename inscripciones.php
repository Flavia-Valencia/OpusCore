<?php
require_once 'obtener-cursos-disponibles.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADF | Inscripciones</title>
</head>
<body>

<?php if (!$periodo): ?>
    <p>No hay un periodo de inscripción activo en este momento.</p>

<?php elseif (empty($cursos)): ?>
    <p>No hay cursos disponibles para inscribir en este periodo.</p>

<?php else: ?>
    <p>Periodo activo: <strong><?= htmlspecialchars($periodo['nombre']) ?></strong>
       (<?= $periodo['fechaInicio'] ?> - <?= $periodo['fechaFin'] ?>)</p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Costo mensual</th>
                <th>Cupos</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursos as $curso): ?>
            <tr>
                <td><?= htmlspecialchars($curso['nombre']) ?></td>
                <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                <td>$<?= number_format($curso['costoMensual'], 2) ?></td>
                <td><?= $curso['cupos'] ?></td>
                <td><?= $curso['fechaInicio'] ?></td>
                <td><?= $curso['fechaFin'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>